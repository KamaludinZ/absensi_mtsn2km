<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Student;
use App\Models\Rombel;
use App\Models\Setting;
use App\Traits\Paginable;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    use Paginable;
    public function index(Request $request)
    {
        $query = Attendance::with('attendable')
            ->where('attendable_type', Employee::class);

        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);

        // Filter by month and year. Default to current month if not provided.
        if ($selectedMonth && $selectedYear) {
            $query->whereMonth('date', $selectedMonth)
                  ->whereYear('date', $selectedYear);
        }

        // Filter by specific employee if provided
        if ($request->filled('employee_id')) {
            $query->where('attendable_id', $request->employee_id);
        }

        // Sorting
        $sortField = $request->get('sort', 'date');
        $sortDirection = $request->get('direction', 'desc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        // Apply sorting based on field
        switch ($sortField) {
            case 'full_name':
            case 'position':
                // For relationship fields, we need to join or sort in collection
                // Get all results first, then sort
                $attendances = $query->get()->sortBy(function($attendance) use ($sortField) {
                    return strtolower($attendance->attendable->$sortField ?? '');
                }, SORT_REGULAR, $sortDirection === 'desc');

                // Manually paginate
                $perPage = 20;
                $currentPage = request()->get('page', 1);
                $attendances = new \Illuminate\Pagination\LengthAwarePaginator(
                    $attendances->forPage($currentPage, $perPage),
                    $attendances->count(),
                    $perPage,
                    $currentPage,
                    ['path' => request()->url(), 'query' => request()->query()]
                );
                break;

            case 'clock_in':
            case 'clock_out':
                $query->orderBy($sortField, $sortDirection);
                $perPage = $this->getPerPagePreference();
                if ($perPage === 'all') {
                    $attendances = collect($query->orderBy('date', 'desc')->get());
                } else {
                    $attendances = $query->orderBy('date', 'desc')->paginate($perPage);
                }
                break;

            case 'date':
            default:
                $query->orderBy('date', $sortDirection);
                $perPage = $this->getPerPagePreference();
                if ($perPage === 'all') {
                    $attendances = collect($query->orderBy('created_at', 'desc')->get());
                } else {
                    $attendances = $query->orderBy('created_at', 'desc')->paginate($perPage);
                }
                break;
        }

        // Get all employees for the dropdown
        $employees = Employee::orderBy('full_name')->get();

        // Get available months/years for the dropdown
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $years = Attendance::where('attendable_type', Employee::class)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Calculate summary statistics
        $summaryQuery = Attendance::where('attendable_type', Employee::class);
        if ($selectedMonth && $selectedYear) {
            $summaryQuery->whereMonth('date', $selectedMonth)
                         ->whereYear('date', $selectedYear);
        }

        if ($request->filled('employee_id')) {
            $summaryQuery->where('attendable_id', $request->employee_id);
        }

        $totalAttendances = $summaryQuery->count();

        // Count present (has either clock_in or clock_out)
        $presentCount = (clone $summaryQuery)->where(function($q) {
            $q->whereNotNull('clock_in')
              ->orWhereNotNull('clock_out');
        })->count();

        // Count absent - records where clock_in or clock_out is null (didn't attend)
        $absentCount = (clone $summaryQuery)->where(function($q) {
            $q->whereNull('clock_in')
              ->orWhereNull('clock_out');
        })->count();

        // Count late arrivals by checking status_in (should be 'Terlambat' as set by the controller)
        $lateCount = (clone $summaryQuery)->where('status_in', 'Terlambat')->count();

        // Count early departures by checking status_out (should be 'Pulang Awal' as set by the controller)
        $earlyDepartureCount = (clone $summaryQuery)->where('status_out', 'Pulang Awal')->count();

        // Get permission statistics for the same date range
        $permissionQuery = \App\Models\Permission::where('permissible_type', Employee::class);
        if ($selectedMonth && $selectedYear) {
            // Filter by month and year
            $permissionQuery->where(function($q) use ($selectedMonth, $selectedYear) {
                $q->whereMonth('start_date', $selectedMonth)
                  ->whereYear('start_date', $selectedYear)
                  ->orWhere(function($subQ) use ($selectedMonth, $selectedYear) {
                      $subQ->whereMonth('end_date', $selectedMonth)
                           ->whereYear('end_date', $selectedYear);
                  })
                  ->orWhere(function($subQ) use ($selectedMonth, $selectedYear) {
                      $subQ->where('start_date', '<=', Carbon::create($selectedYear, $selectedMonth, 1)->endOfMonth())
                           ->where('end_date', '>=', Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth());
                  });
            });
        }

        if ($request->filled('employee_id')) {
            $permissionQuery->where('permissible_id', $request->employee_id);
        }

        $permissionCount = $permissionQuery->count();

        $summary = [
            'total_attendances' => $totalAttendances,
            'present' => $presentCount,
            'absent' => $absentCount,
            'late' => $lateCount,
            'early_departure' => $earlyDepartureCount,
            'permissions' => $permissionCount
        ];

        // Get time difference format setting
        $timeDiffFormatSetting = \App\Models\AttendanceSetting::where('setting_key', 'time_difference_format')->first();
        $timeDiffFormat = $timeDiffFormatSetting ? $timeDiffFormatSetting->setting_value : 'time';

        return view('admin.reports.employees', compact('attendances', 'employees', 'months', 'years', 'summary', 'timeDiffFormat', 'selectedMonth', 'selectedYear'));
    }
    
    public function students()
    {
        $rombels = Rombel::with('academicYear')->orderBy('name')->get();
        return view('admin.reports.index', compact('rombels'));
    }

    public function studentReports(Request $request)
    {
        $query = Attendance::with(['attendable.rombel', 'attendable'])
            ->where('attendable_type', Student::class)
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'asc');

        // Apply filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('rombel_id')) {
            $studentIds = Student::where('rombel_id', $request->rombel_id)->pluck('id');
            $query->whereIn('attendable_id', $studentIds);
        }

        if ($request->filled('student_name')) {
            $studentIds = Student::where('full_name', 'ILIKE', '%' . $request->student_name . '%')->pluck('id');
            $query->whereIn('attendable_id', $studentIds);
        }

        $perPage = $this->getPerPagePreference();
        if ($perPage === 'all') {
            $attendances = collect($query->get());
        } else {
            $attendances = $query->paginate($perPage);
        }
        $rombels = Rombel::orderBy('name')->get();

        return view('admin.reports.student-reports', compact('attendances', 'rombels'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'type' => 'required|in:employee,student',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:excel,pdf',
        ]);

        $query = Attendance::with('attendable')
            ->whereBetween('date', [$request->start_date, $request->end_date]);

        if ($request->type === 'employee') {
            $query->where('attendable_type', Employee::class);

            // Filter by specific employee if provided
            if ($request->filled('employee_id')) {
                $query->where('attendable_id', $request->employee_id);
            }
        } else {
            $query->where('attendable_type', Student::class);

            // Filter by rombel if provided
            if ($request->filled('rombel_id')) {
                $studentIds = Student::where('rombel_id', $request->rombel_id)->pluck('id');
                $query->whereIn('attendable_id', $studentIds);
            }
        }

        // Get all attendances first
        $attendances = $query->get();

        // Sort by employee/student name first, then by date
        $attendances = $attendances->sort(function($a, $b) {
            // First sort by name (ascending)
            $nameComparison = strcmp(
                strtolower($a->attendable->full_name ?? ''),
                strtolower($b->attendable->full_name ?? '')
            );

            // If names are the same, sort by date (ascending for chronological order)
            if ($nameComparison === 0) {
                return $a->date <=> $b->date;
            }

            return $nameComparison;
        })->values(); // Reset array keys

        if ($request->format === 'excel') {
            return $this->exportExcel($attendances, $request->type, $request->start_date, $request->end_date);
        } else {
            return $this->exportPdf($attendances, $request->type, $request->start_date, $request->end_date);
        }
    }

    private function exportExcel($attendances, $type, $startDate, $endDate)
    {
        $filename = 'laporan_absensi_' . $type . '_' . date('YmdHis') . '.xlsx';

        return Excel::download(
            new AttendanceExport($attendances, $type, $startDate, $endDate),
            $filename
        );
    }

    private function exportPdf($attendances, $type, $startDate, $endDate)
    {
        // Increase execution time for large PDF generation
        set_time_limit(300); // 5 minutes for PDF rendering
        ini_set('memory_limit', '512M'); // Increase memory for large documents

        $schoolName = Setting::get('school_name', 'MTsN 2 Kota Malang');
        $schoolAddress = Setting::get('school_address', 'Kota Malang');
        $reportHeaderPath = Setting::get('report_header_path');

        // Pre-load all employee shifts and schedules to avoid N+1 queries in PDF view
        $employeeShifts = collect();
        $schedules = collect();

        if ($type === 'employee') {
            // Get all unique employee IDs and dates from attendances
            $employeeIds = $attendances->pluck('attendable_id')->unique();

            // Batch load all employee shifts for the date range
            $employeeShifts = \App\Models\EmployeeShift::whereIn('employee_id', $employeeIds)
                ->whereBetween('date', [$startDate, $endDate])
                ->with('schedule')
                ->get()
                ->groupBy(function($shift) {
                    return $shift->employee_id . '_' . $shift->date->format('Y-m-d');
                });

            // Load all pegawai schedules by day of week (0-6)
            $schedules = \App\Models\Schedule::where('type', 'pegawai')
                ->get()
                ->keyBy('day_of_week');
        } else {
            // Load all siswa schedules by day of week (0-6)
            $schedules = \App\Models\Schedule::where('type', 'siswa')
                ->get()
                ->keyBy('day_of_week');
        }

        // Get time difference format setting
        $timeDiffFormatSetting = \App\Models\AttendanceSetting::where('setting_key', 'time_difference_format')->first();
        $timeDiffFormat = $timeDiffFormatSetting ? $timeDiffFormatSetting->setting_value : 'time';

        // Pre-compute all calculations to reduce DOMPDF rendering time
        $processedAttendances = $this->preComputeAttendanceData($attendances, $employeeShifts, $schedules, $type, $timeDiffFormat);

        $data = [
            'attendances' => $processedAttendances,
            'type' => $type,
            'startDate' => Carbon::parse($startDate)->format('d/m/Y'),
            'endDate' => Carbon::parse($endDate)->format('d/m/Y'),
            'schoolName' => $schoolName,
            'schoolAddress' => $schoolAddress,
            'reportHeaderPath' => $reportHeaderPath,
            'generatedAt' => Carbon::now()->format('d/m/Y H:i'),
        ];

        // Configure DOMPDF for better performance with large documents
        $pdf = Pdf::loadView('admin.reports.pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isFontSubsettingEnabled', false)
            ->setOption('debugKeepTemp', false)
            ->setOption('debugCss', false)
            ->setOption('debugLayout', false)
            ->setOption('debugLayoutLines', false)
            ->setOption('debugLayoutBlocks', false)
            ->setOption('debugLayoutInline', false)
            ->setOption('debugLayoutPaddingBox', false);

        $filename = 'laporan_absensi_' . $type . '_' . date('YmdHis') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Pre-compute all attendance data with schedule info and time calculations
     * This significantly reduces PDF rendering time
     */
    private function preComputeAttendanceData($attendances, $employeeShifts, $schedules, $type, $timeDiffFormat = 'time')
    {
        return $attendances->map(function($attendance) use ($employeeShifts, $schedules, $type, $timeDiffFormat) {
            $data = [
                'attendance' => $attendance,
                'name' => $attendance->attendable->full_name ?? '-',
                'date' => $attendance->date->format('d/m/Y'),
                'clock_in_time' => $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-',
                'clock_out_time' => $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '-',
            ];

            // Add type-specific fields
            if ($type === 'employee') {
                $data['position'] = $attendance->attendable->position ?? '-';

                // Get schedule
                $shiftKey = $attendance->attendable_id . '_' . $attendance->date->format('Y-m-d');
                $employeeShift = $employeeShifts->get($shiftKey)?->first();

                if ($employeeShift && $employeeShift->schedule) {
                    $schedule = $employeeShift->schedule;
                    $data['shift_name'] = $schedule->shift_name;
                } else {
                    $dayOfWeek = $attendance->date->dayOfWeek;
                    $schedule = $schedules->get($dayOfWeek);
                    $data['shift_name'] = $schedule ? $schedule->shift_name : '-';
                }
            } else {
                $data['nisn'] = $attendance->attendable->nisn ?? '-';
                $data['rombel'] = $attendance->attendable->rombel->name ?? '-';

                // Get schedule for students
                $dayOfWeek = $attendance->date->dayOfWeek;
                $schedule = $schedules->get($dayOfWeek);
            }

            // Pre-compute time calculations if schedule exists
            if (isset($schedule) && $schedule) {
                $data['schedule_start'] = Carbon::parse($schedule->start_time)->format('H:i:s');
                $data['schedule_end'] = Carbon::parse($schedule->end_time)->format('H:i:s');

                // Calculate clock in diff and status
                if ($attendance->clock_in) {
                    $diff = $this->calculateTimeDifference(
                        $attendance->clock_in,
                        $schedule->start_time
                    );

                    $formattedValue = ($timeDiffFormat === 'minutes') ? $diff['formatted_minutes'] : $diff['formatted_time'];

                    // Based on user's observation, a late clock-in produces a negative diff.
                    if ($diff['seconds'] < 0) { // LATE
                        $data['clock_in_diff'] = '+' . $formattedValue;
                        $data['clock_in_diff_color'] = 'text-red';
                        $data['clock_in_status'] = 'Terlambat';
                        $data['clock_in_status_color'] = 'text-red';
                    } else { // ON-TIME or EARLY
                        $data['clock_in_diff'] = '-' . $formattedValue;
                        $data['clock_in_diff_color'] = 'text-green';
                        $data['clock_in_status'] = 'Tepat Waktu';
                        $data['clock_in_status_color'] = 'text-green';
                    }
                } else {
                    $data['clock_in_diff'] = '-';
                    $data['clock_in_status'] = 'Tidak Hadir';
                    $data['clock_in_status_color'] = '';
                }

                // Calculate clock out diff and status
                if ($attendance->clock_out) {
                    $diff = $this->calculateTimeDifference(
                        $attendance->clock_out,
                        $schedule->end_time
                    );

                    $formattedValue = ($timeDiffFormat === 'minutes') ? $diff['formatted_minutes'] : $diff['formatted_time'];

                    // Based on debug output, a positive diff means EARLY departure
                    if ($diff['seconds'] > 0) { // Early Departure
                        $data['clock_out_diff'] = '-' . $formattedValue;
                        $data['clock_out_diff_color'] = 'text-red';
                        $data['clock_out_status'] = 'Pulang Awal';
                        $data['clock_out_status_color'] = 'text-red';
                    } else { // On time or Late Departure
                        $data['clock_out_diff'] = '+' . $formattedValue;
                        $data['clock_out_diff_color'] = 'text-green';
                        $data['clock_out_status'] = 'Tepat Waktu';
                        $data['clock_out_status_color'] = 'text-green';
                    }
                } else {
                    $data['clock_out_diff'] = '-';
                    $data['clock_out_status'] = 'Belum Pulang';
                    $data['clock_out_status_color'] = '';
                }
            } else {
                $data['schedule_start'] = '-';
                $data['schedule_end'] = '-';
                $data['clock_in_diff'] = '-';
                $data['clock_in_status'] = $attendance->clock_in ? 'N/A' : 'Tidak Hadir';
                $data['clock_in_status_color'] = '';
                $data['clock_out_diff'] = '-';
                $data['clock_out_status'] = $attendance->clock_out ? 'N/A' : 'Belum Pulang';
                $data['clock_out_status_color'] = '';
            }

            return $data;
        });
    }

    /**
     * Calculate time difference between two times and return formatted result
     */
    private function calculateTimeDifference($actualTime, $scheduleTime)
    {
        $actual = Carbon::parse($actualTime);
        $scheduled = Carbon::parse($scheduleTime)->setDate($actual->year, $actual->month, $actual->day);
        // Calculation: Actual Time - Scheduled Time
        $diffSeconds = $actual->diffInSeconds($scheduled, false);

        $absSeconds = abs($diffSeconds);
        $absDiffMinutes = ceil($absSeconds / 60);

        $formattedTime = sprintf(
            '%02d:%02d:%02d',
            floor($absSeconds / 3600),
            floor(($absSeconds % 3600) / 60),
            $absSeconds % 60
        );

        return [
            'seconds' => $diffSeconds,
            'formatted_minutes' => $absDiffMinutes . ' menit',
            'formatted_time' => $formattedTime,
        ];
    }

    public function mealAllowance(Request $request)
    {
        $query = Attendance::with('attendable')
            ->where('attendable_type', Employee::class);

        // Filter by date range if provided
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        // Filter by month and year if provided
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('date', $request->month)
                  ->whereYear('date', $request->year);
        }

        // Filter by specific employee if provided
        if ($request->filled('employee_id')) {
            $query->where('attendable_id', $request->employee_id);
        }

        // Apply ordering
        $attendances = $query->orderBy('date', 'desc')
            ->orderBy('attendable_id', 'asc')
            ->get();

        // Group by employee and calculate meal allowance
        $mealAllowanceData = [];
        foreach ($attendances as $attendance) {
            $employeeId = $attendance->attendable_id;

            if (!isset($mealAllowanceData[$employeeId])) {
                $mealAllowanceData[$employeeId] = [
                    'employee' => $attendance->attendable,
                    'present_days' => 0,
                    'late_days' => 0,
                    'days' => []
                ];
            }

            $dateKey = $attendance->date->format('Y-m-d');
            if (!isset($mealAllowanceData[$employeeId]['days'][$dateKey])) {
                $isPresent = $attendance->clock_in !== null || $attendance->clock_out !== null;
                $isLate = $attendance->status_in === 'Terlambat';

                if ($isPresent) {
                    $mealAllowanceData[$employeeId]['present_days']++;
                }

                if ($isLate) {
                    $mealAllowanceData[$employeeId]['late_days']++;
                }

                $mealAllowanceData[$employeeId]['days'][$dateKey] = [
                    'date' => $attendance->date->format('d/m/Y'),
                    'present' => $isPresent,
                    'late' => $isLate,
                    'clock_in' => $attendance->clock_in ? $attendance->clock_in->format('H:i') : null,
                    'clock_out' => $attendance->clock_out ? $attendance->clock_out->format('H:i') : null
                ];
            }
        }

        // Get all employees for dropdown
        $employees = Employee::orderBy('full_name')->get();

        // Get available months/years for dropdown
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $years = Attendance::where('attendable_type', Employee::class)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('admin.reports.meal-allowance', compact('mealAllowanceData', 'employees', 'months', 'years'));
    }

    public function performanceAllowance(Request $request)
    {
        $query = Attendance::with('attendable')
            ->where('attendable_type', Employee::class);

        // Filter by date range if provided
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        // Filter by month and year if provided
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('date', $request->month)
                  ->whereYear('date', $request->year);
        }

        // Filter by specific employee if provided
        if ($request->filled('employee_id')) {
            $query->where('attendable_id', $request->employee_id);
        }

        // Apply ordering
        $attendances = $query->orderBy('date', 'desc')
            ->orderBy('attendable_id', 'asc')
            ->get();

        // Group by employee and calculate performance metrics
        $performanceData = [];
        foreach ($attendances as $attendance) {
            $employeeId = $attendance->attendable_id;

            if (!isset($performanceData[$employeeId])) {
                $performanceData[$employeeId] = [
                    'employee' => $attendance->attendable,
                    'total_days' => 0,
                    'present_days' => 0,
                    'late_days' => 0,
                    'early_departure_days' => 0,
                    'perfect_attendance_days' => 0,
                    'days' => []
                ];
            }

            $isPresent = $attendance->clock_in !== null || $attendance->clock_out !== null;
            $isLate = $attendance->status_in === 'Terlambat';
            $isEarlyDeparture = $attendance->status_out === 'Pulang Awal';
            $isPerfect = !$isLate && !$isEarlyDeparture && $isPresent;

            $performanceData[$employeeId]['total_days']++;

            if ($isPresent) {
                $performanceData[$employeeId]['present_days']++;
            }

            if ($isLate) {
                $performanceData[$employeeId]['late_days']++;
            }

            if ($isEarlyDeparture) {
                $performanceData[$employeeId]['early_departure_days']++;
            }

            if ($isPerfect) {
                $performanceData[$employeeId]['perfect_attendance_days']++;
            }

            $dateKey = $attendance->date->format('Y-m-d');
            $performanceData[$employeeId]['days'][$dateKey] = [
                'date' => $attendance->date->format('d/m/Y'),
                'present' => $isPresent,
                'late' => $isLate,
                'early_departure' => $isEarlyDeparture,
                'perfect' => $isPerfect,
                'clock_in' => $attendance->clock_in ? $attendance->clock_in->format('H:i') : null,
                'clock_out' => $attendance->clock_out ? $attendance->clock_out->format('H:i') : null
            ];
        }

        // Calculate scores
        foreach ($performanceData as &$data) {
            $presentRate = $data['total_days'] > 0 ? ($data['present_days'] / $data['total_days']) * 100 : 0;
            $lateRate = $data['present_days'] > 0 ? ($data['late_days'] / $data['present_days']) * 100 : 0;
            $earlyDepartureRate = $data['present_days'] > 0 ? ($data['early_departure_days'] / $data['present_days']) * 100 : 0;
            $perfectRate = $data['total_days'] > 0 ? ($data['perfect_attendance_days'] / $data['total_days']) * 100 : 0;

            $data['present_rate'] = round($presentRate, 2);
            $data['late_rate'] = round($lateRate, 2);
            $data['early_departure_rate'] = round($earlyDepartureRate, 2);
            $data['perfect_rate'] = round($perfectRate, 2);

            // Calculate performance score (custom formula)
            $score = 100;
            $score -= ($data['late_rate'] * 0.5); // Deduct 0.5 points per 1% late
            $score -= ($data['early_departure_rate'] * 0.3); // Deduct 0.3 points per 1% early departure
            $score += ($data['perfect_rate'] * 0.1); // Add 0.1 points per 1% perfect attendance

            // Ensure score is between 0-100
            $data['performance_score'] = max(0, min(100, round($score, 2)));
        }

        // Get all employees for dropdown
        $employees = Employee::orderBy('full_name')->get();

        // Get available months/years for dropdown
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $years = Attendance::where('attendable_type', Employee::class)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('admin.reports.performance-allowance', compact('performanceData', 'employees', 'months', 'years'));
    }

    public function salaryReport(Request $request)
    {
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);

        $employeeQuery = Employee::with('salary')->where('is_active', true);

        // Filter by specific employee if provided
        if ($request->filled('employee_id')) {
            $employeeQuery->where('id', $request->employee_id);
        }

        $employees = $employeeQuery->orderBy('full_name')->get();
        $employeeIds = $employees->pluck('id');

        // Prepare attendance query
        $attendanceQuery = Attendance::where('attendable_type', Employee::class)
            ->whereIn('attendable_id', $employeeIds);

        // Filter by month/year
        $attendanceQuery->whereMonth('date', $selectedMonth)
                      ->whereYear('date', $selectedYear);

        $attendances = $attendanceQuery->orderBy('date', 'asc')->get()->groupBy('attendable_id');

        $salaryData = [];

        foreach ($employees as $employee) {
            $employeeId = $employee->id;
            $salary = $employee->salary;

            // Skip employees without salary settings
            if (!$salary) {
                continue;
            }

            $employeeAttendances = $attendances->get($employeeId, collect());

            $present_days = 0;
            $perfect_days = 0;

            foreach ($employeeAttendances as $attendance) {
                $isPresent = $attendance->clock_in !== null || $attendance->clock_out !== null;
                if ($isPresent) {
                    $present_days++;
                }

                // A day is perfect if the employee was present, not late, and did not leave early
                $isPerfect = $isPresent && $attendance->status_in !== 'Terlambat' && $attendance->status_out !== 'Pulang Awal';
                if ($isPerfect) {
                    $perfect_days++;
                }
            }

            // Calculate salary components
            $totalMealAllowance = $salary->meal_allowance * $present_days;
            $totalTransportAllowance = $salary->transport_allowance * $present_days;
            $totalPerformanceAllowance = $salary->performance_allowance * $perfect_days;

            // Calculate total salary
            $totalSalary = $salary->base_salary + $totalMealAllowance + $totalTransportAllowance + $totalPerformanceAllowance;

            $salaryData[$employeeId] = [
                'employee' => $employee,
                'base_salary' => $salary->base_salary,
                'present_days' => $present_days,
                'total_meal_allowance' => $totalMealAllowance,
                'total_transport_allowance' => $totalTransportAllowance,
                'perfect_days' => $perfect_days,
                'total_performance_allowance' => $totalPerformanceAllowance,
                'total_salary' => $totalSalary,
            ];
        }

        // Data for filters
        $allEmployees = Employee::orderBy('full_name')->get();
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $years = Attendance::where('attendable_type', Employee::class)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('admin.reports.salary', [
            'salaryData' => $salaryData,
            'employees' => $allEmployees,
            'months' => $months,
            'years' => $years,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
        ]);
    }

    public function salaryReportDetail(Request $request, Employee $employee, $year, $month)
    {
        $data = $this->getSalaryDetailData($employee, $year, $month);
        return view('admin.reports.salary-detail', $data);
    }

    public function generateSalaryPdf(Request $request, Employee $employee, $year, $month)
    {
        $data = $this->getSalaryDetailData($employee, $year, $month);
        $pdf = Pdf::loadView('admin.reports.salary-detail-pdf', $data)
            ->setPaper('a4', 'portrait');

        $filename = 'laporan_gaji_' . str_replace(' ', '_', $employee->full_name) . '_' . Carbon::create($year, $month)->isoFormat('MMMM_YYYY') . '.pdf';
        return $pdf->download($filename);
    }

    private function getSalaryDetailData(Employee $employee, $year, $month)
    {
        $employee->load('salary');
        $salary = $employee->salary;

        if (!$salary) {
            abort(404, 'Pengaturan gaji untuk pegawai ini tidak ditemukan.');
        }

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        // Get all assigned shifts for the employee for the entire month
        $employeeShifts = \App\Models\EmployeeShift::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('schedule')
            ->get()
            ->keyBy(function ($shift) {
                return $shift->date->format('Y-m-d');
            });

        // Get all attendances for the employee for the month
        $attendances = Attendance::where('attendable_type', Employee::class)
            ->where('attendable_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            });
            
        // Get all approved permissions for the employee that overlap with the month
        $permissions = \App\Models\Permission::where('permissible_id', $employee->id)
            ->where('permissible_type', Employee::class)
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->get();

        // Create a date-keyed map of permissions for quick lookup
        $permissionMap = [];
        $permissionTypesFull = [
            'C' => 'Cuti',
            'S' => 'Sakit',
            'DL' => 'Dinas Luar',
        ];
        foreach ($permissions as $permission) {
            $current = Carbon::parse($permission->start_date)->max($startDate);
            $end = Carbon::parse($permission->end_date)->min($endDate);
            while ($current->lte($end)) {
                $permissionMap[$current->format('Y-m-d')] = $permissionTypesFull[$permission->type] ?? $permission->type;
                $current->addDay();
            }
        }

        $detailData = [];
        $totalMealAllowance = 0;
        $totalTransportAllowance = 0;
        $totalPerformanceAllowance = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::create($year, $month, $day);
            $dateKey = $currentDate->format('Y-m-d');
            
            $attendance = $attendances->get($dateKey);
            $employeeShift = $employeeShifts->get($dateKey);
            $schedule = $employeeShift ? $employeeShift->schedule : null;
            $permissionType = $permissionMap[$dateKey] ?? null;

            $dailyMealAllowance = 0;
            $dailyTransportAllowance = 0;
            $dailyPerformanceAllowance = 0;
            
            $statusIn = '-';
            $statusOut = '-';

            if ($permissionType) {
                // Handle permission day first
                $statusIn = $permissionType;
                $statusOut = $permissionType;
            } elseif ($schedule) {
                // Handle scheduled workday
                if ($attendance) {
                    // There is an attendance record on a scheduled day
                    if ($attendance->clock_in) {
                        $diffIn = $this->calculateTimeDifference($attendance->clock_in, $schedule->start_time);
                        $statusIn = ($diffIn['seconds'] < 0) ? 'Terlambat' : 'Tepat Waktu';
                    } else {
                        $statusIn = 'Tidak Hadir';
                    }

                    if ($attendance->clock_out) {
                        $diffOut = $this->calculateTimeDifference($attendance->clock_out, $schedule->end_time);
                        $statusOut = ($diffOut['seconds'] > 0) ? 'Pulang Awal' : 'Tepat Waktu';
                    } else {
                        $statusOut = 'Belum Pulang';
                    }

                    // Grant allowances only if employee is present
                    $isPresent = $attendance->clock_in !== null;
                    if ($isPresent) {
                        $dailyMealAllowance = $salary->meal_allowance;
                        $dailyTransportAllowance = $salary->transport_allowance;
                        $totalMealAllowance += $dailyMealAllowance;
                        $totalTransportAllowance += $dailyTransportAllowance;
                        
                        // Performance allowance only if perfect
                        $isPerfect = $statusIn === 'Tepat Waktu' && $statusOut === 'Tepat Waktu';
                        if ($isPerfect) {
                            $dailyPerformanceAllowance = $salary->performance_allowance;
                            $totalPerformanceAllowance += $dailyPerformanceAllowance;
                        }
                    }

                } else {
                    // Scheduled day, but no attendance record
                    $statusIn = 'Tidak Hadir';
                    $statusOut = 'Tidak Hadir';
                }
            } else {
                // No schedule and no permission -> off day
                $statusIn = 'Hari Libur';
                $statusOut = 'Hari Libur';
            }

            $detailData[] = [
                'date' => $currentDate,
                'attendance' => $attendance,
                'schedule' => $schedule,
                'status_in' => $statusIn,
                'status_out' => $statusOut,
                'daily_meal_allowance' => $dailyMealAllowance,
                'daily_transport_allowance' => $dailyTransportAllowance,
                'daily_performance_allowance' => $dailyPerformanceAllowance,
            ];
        }

        $totalSalary = $salary->base_salary + $totalMealAllowance + $totalTransportAllowance + $totalPerformanceAllowance;

        $summary = [
            'base_salary' => $salary->base_salary,
            'total_meal_allowance' => $totalMealAllowance,
            'total_transport_allowance' => $totalTransportAllowance,
            'total_performance_allowance' => $totalPerformanceAllowance,
            'total_salary' => $totalSalary,
        ];

        return compact('employee', 'year', 'month', 'detailData', 'summary', 'permissionMap');
    }
}
