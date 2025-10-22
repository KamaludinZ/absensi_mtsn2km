<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Student;
use App\Models\Rombel;
use App\Models\Setting;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
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
                $attendances = $query->orderBy('date', 'desc')->paginate(20);
                break;

            case 'date':
            default:
                $query->orderBy('date', $sortDirection);
                $attendances = $query->orderBy('created_at', 'desc')->paginate(20);
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
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $summaryQuery->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $summaryQuery->where('date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $summaryQuery->where('date', '<=', $request->end_date);
        }
        
        if ($request->filled('month') && $request->filled('year')) {
            $summaryQuery->whereMonth('date', $request->month)
                         ->whereYear('date', $request->year);
        }

        if ($request->filled('employee_id')) {
            $summaryQuery->where('attendable_id', $request->employee_id);
        }

        $totalAttendances = $summaryQuery->count();

        // Count present (on time) - both clock in and out on time
        $presentCount = (clone $summaryQuery)->where(function($q) {
            $q->where('status_in', 'Tepat Waktu')
              ->orWhere('status_out', 'Tepat Waktu');
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
        if ($request->filled('start_date') && $request->filled('end_date')) {
            // Filter by date range considering the permission date range
            $permissionQuery->where(function($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function($subQ) use ($request) {
                      $subQ->where('start_date', '<=', $request->start_date)
                           ->where('end_date', '>=', $request->end_date);
                  });
            });
        } elseif ($request->filled('start_date')) {
            $permissionQuery->where('start_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $permissionQuery->where('end_date', '<=', $request->end_date);
        }
        
        if ($request->filled('month') && $request->filled('year')) {
            // Filter by month and year
            $permissionQuery->where(function($q) use ($request) {
                $q->whereMonth('start_date', $request->month)
                  ->whereYear('start_date', $request->year)
                  ->orWhere(function($subQ) use ($request) {
                      $subQ->whereMonth('end_date', $request->month)
                           ->whereYear('end_date', $request->year);
                  })
                  ->orWhere(function($subQ) use ($request) {
                      $subQ->where('start_date', '<=', Carbon::create($request->year, $request->month, 1)->endOfMonth())
                           ->where('end_date', '>=', Carbon::create($request->year, $request->month, 1)->startOfMonth());
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

        return view('admin.reports.employees', compact('attendances', 'employees', 'months', 'years', 'summary'));
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

        $attendances = $query->paginate(20);
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

        // Pre-compute all calculations to reduce DOMPDF rendering time
        $processedAttendances = $this->preComputeAttendanceData($attendances, $employeeShifts, $schedules, $type);

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
    private function preComputeAttendanceData($attendances, $employeeShifts, $schedules, $type)
    {
        return $attendances->map(function($attendance) use ($employeeShifts, $schedules, $type) {
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
                        $attendance->clock_in->format('H:i:s'),
                        $schedule->start_time
                    );
                    $data['clock_in_diff'] = $diff['label'];
                    $data['clock_in_diff_color'] = $diff['color'];
                    $data['clock_in_status'] = $diff['seconds'] > 0 ? 'Terlambat' : 'Tepat Waktu';
                    $data['clock_in_status_color'] = $diff['seconds'] > 0 ? 'text-red' : 'text-green';
                } else {
                    $data['clock_in_diff'] = '-';
                    $data['clock_in_status'] = 'Tidak Hadir';
                    $data['clock_in_status_color'] = '';
                }

                // Calculate clock out diff and status
                if ($attendance->clock_out) {
                    $diff = $this->calculateTimeDifference(
                        $attendance->clock_out->format('H:i:s'),
                        $schedule->end_time
                    );
                    $data['clock_out_diff'] = $diff['label'];
                    $data['clock_out_diff_color'] = $diff['color'];
                    $data['clock_out_status'] = $diff['seconds'] < 0 ? 'Pulang Awal' : 'Tepat Waktu';
                    $data['clock_out_status_color'] = $diff['seconds'] < 0 ? 'text-yellow' : 'text-green';
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
        list($h1, $i1, $s1) = explode(':', $actualTime);
        $actualSeconds = ($h1 * 3600) + ($i1 * 60) + $s1;

        $scheduleTimeFormatted = Carbon::parse($scheduleTime)->format('H:i:s');
        list($h2, $i2, $s2) = explode(':', $scheduleTimeFormatted);
        $scheduleSeconds = ($h2 * 3600) + ($i2 * 60) + $s2;

        $diffSeconds = $actualSeconds - $scheduleSeconds;
        $absSeconds = abs($diffSeconds);

        $hours = floor($absSeconds / 3600);
        $minutes = floor(($absSeconds - ($hours * 3600)) / 60);
        $seconds = $absSeconds - ($hours * 3600) - ($minutes * 60);

        $formatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        if ($diffSeconds > 0) {
            $label = '+' . $formatted;
            $color = 'text-red';
        } elseif ($diffSeconds < 0) {
            $label = '-' . $formatted;
            $color = 'text-green';
        } else {
            $label = '00:00:00';
            $color = 'text-green';
        }

        return [
            'label' => $label,
            'color' => $color,
            'seconds' => $diffSeconds,
        ];
    }
}
