<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Student;
use App\Models\Rombel;
use App\Models\Setting;
use App\Models\EmployeeShift;
use App\Models\Salary;
use App\Models\Permission;
use App\Models\AttendanceSetting;
use App\Models\Schedule;
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
        } else {
            // Default to current month and year if not provided
            $selectedMonth = Carbon::now()->month;
            $selectedYear = Carbon::now()->year;
            $query->whereMonth('date', $selectedMonth)
                  ->whereYear('date', $selectedYear);
        }

        // Filter by specific employee if provided
        if ($request->filled('employee_id')) {
            $query->where('attendable_id', $request->employee_id);
        }

        // Sorting
        $sortField = $request->get('sort', 'date');
        $sortDirection = $request->get('direction', 'asc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
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

                // If filtering by employee_id, sort by date ascending; otherwise, sort by name first then date
                if ($request->filled('employee_id')) {
                    $attendances = $attendances->sortBy(function($attendance) {
                        return $attendance->date->format('Y-m-d') . '_' . $attendance->date->format('H:i:s');
                    }, SORT_REGULAR, false); // Ascending order for date
                } else {
                    $attendances = $attendances->sortBy(function($attendance) use ($sortField, $sortDirection) {
                        $name = strtolower($attendance->attendable->$sortField ?? '');
                        $date = $attendance->date->format('Y-m-d');
                        // Group by position for employee grouping
                        $position = $attendance->attendable->position ?? '';
                        return [$position, $name, $date];
                    }, SORT_REGULAR, $sortDirection === 'desc');
                }

                // Manually paginate
                $perPage = $this->getPerPagePreference();
                if ($perPage === 'all') {
                    // Don't paginate, return collection
                    $paginated = false;
                } else {
                    $currentPage = request()->get('page', 1);
                    $perPage = in_array($perPage, [10, 20, 30, 50, 100]) ? $perPage : 20;
                    $attendances = new \Illuminate\Pagination\LengthAwarePaginator(
                        $attendances->forPage($currentPage, $perPage),
                        $attendances->count(),
                        $perPage,
                        $currentPage,
                        ['path' => request()->url(), 'query' => request()->query()]
                    );
                    $paginated = true;
                }
                break;

            case 'clock_in':
            case 'clock_out':
                if ($request->filled('employee_id')) {
                    // If filtering by employee, sort by date ascending
                    $attendances = $query->orderBy($sortField, $sortDirection)
                                         ->orderBy('date', 'asc')
                                         ->get();
                } else {
                    // Otherwise, sort by employee name first, then by the field, then by date
                    $attendances = $query->join('employees', 'attendances.attendable_id', '=', 'employees.id')
                                         ->orderBy('employees.full_name', $sortDirection)
                                         ->orderBy($sortField, $sortDirection)
                                         ->orderBy('attendances.date', 'asc')
                                         ->select('attendances.*')
                                         ->with('attendable')
                                         ->get();
                }
                $paginated = false; // Handle pagination after sorting
                $perPage = $this->getPerPagePreference(); // Define perPage for all code paths
                break;

            case 'date':
            default:
                if ($request->filled('employee_id')) {
                    // If filtering by employee, sort by date ascending
                    $attendances = $query->orderBy('date', 'asc')->get();
                } else {
                    // Default grouping by position/name and then by date
                    $attendances = $query->join('employees', 'attendances.attendable_id', '=', 'employees.id')
                                         ->orderBy('employees.position')
                                         ->orderBy('employees.full_name')
                                         ->orderBy('attendances.date', $sortDirection)
                                         ->select('attendances.*')
                                         ->with('attendable')
                                         ->get();
                }
                $paginated = false; // Handle pagination after sorting
                $perPage = $this->getPerPagePreference(); // Define perPage for all code paths
                break;
        }

        // Apply pagination if needed
        if (isset($paginated) && !$paginated && $perPage !== 'all') {
            $currentPage = request()->get('page', 1);
            $perPage = in_array($perPage, [10, 20, 30, 50, 100]) ? $perPage : 20;
            $attendances = new \Illuminate\Pagination\LengthAwarePaginator(
                $attendances->forPage($currentPage, $perPage),
                $attendances->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        // Get employees who were active during this month or before for the dropdown
        $employees = Employee::where(function($query) use ($selectedMonth, $selectedYear) {
            // Currently active employees (not soft deleted)
            $query->whereNull('deleted_at')
                  // OR employees who were soft deleted after this month (were active during this month)
                  ->orWhere(function($q) use ($selectedMonth, $selectedYear) {
                      $q->whereNotNull('deleted_at')
                        ->where('deleted_at', '>=', Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth());
                  });
        })
            ->orderBy('full_name')
            ->select('id', 'full_name', 'nip', 'position') // Include position for grouping
            ->get();

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

        // Calculate summary statistics (OPTIMIZED: single query instead of 5 separate queries)
        $summaryQuery = Attendance::where('attendable_type', Employee::class);
        if ($selectedMonth && $selectedYear) {
            $summaryQuery->whereMonth('date', $selectedMonth)
                         ->whereYear('date', $selectedYear);
        }

        if ($request->filled('employee_id')) {
            $summaryQuery->where('attendable_id', $request->employee_id);
        }

        // Use single aggregate query instead of multiple count queries
        $stats = $summaryQuery->selectRaw('
            COUNT(*) as total,
            COUNT(CASE WHEN clock_in IS NOT NULL OR clock_out IS NOT NULL THEN 1 END) as present,
            COUNT(CASE WHEN clock_in IS NULL OR clock_out IS NULL THEN 1 END) as absent,
            COUNT(CASE WHEN status_in = ? THEN 1 END) as late,
            COUNT(CASE WHEN status_out = ? THEN 1 END) as early_departure
        ', ['Terlambat', 'Pulang Awal'])->first();

        $totalAttendances = $stats->total ?? 0;
        $presentCount = $stats->present ?? 0;
        $absentCount = $stats->absent ?? 0;
        $lateCount = $stats->late ?? 0;
        $earlyDepartureCount = $stats->early_departure ?? 0;

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

        // Get time difference format setting (OPTIMIZED: with caching)
        $timeDiffFormat = \Illuminate\Support\Facades\Cache::remember(
            'attendance_setting_time_difference_format',
            3600, // Cache for 1 hour
            function () {
                $setting = \App\Models\AttendanceSetting::where('setting_key', 'time_difference_format')->first();
                return $setting ? $setting->setting_value : 'time';
            }
        );

        return view('admin.reports.employees', compact('attendances', 'employees', 'months', 'years', 'summary', 'timeDiffFormat', 'selectedMonth', 'selectedYear', 'paginated'));
    }
    
    public function students()
    {
        // OPTIMIZED: Only select needed columns
        $rombels = Rombel::with('academicYear:id,name,year_start,year_end')
            ->orderBy('name')
            ->select('id', 'name', 'academic_year_id')
            ->get();
        return view('admin.reports.index', compact('rombels'));
    }

    public function studentReports(Request $request)
    {
        $query = Attendance::with(['rombel', 'attendable.rombel'])
            ->where('attendable_type', Student::class);

        // Apply filters - first check for month/year
        if ($request->filled('month') && $request->filled('year')) {
            $selectedMonth = $request->month;
            $selectedYear = $request->year;
            $startDate = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
            $endDate = Carbon::create($selectedYear, $selectedMonth, 1)->endOfMonth();
            $query->whereBetween('date', [$startDate, $endDate]);
        } else {
            // Default to current month if no filters are provided
            $selectedMonth = Carbon::now()->month;
            $selectedYear = Carbon::now()->year;
            $startDate = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
            $endDate = Carbon::create($selectedYear, $selectedMonth, 1)->endOfMonth();
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        // OPTIMIZED: Use whereHas instead of whereIn with pluck (reduces queries)
        if ($request->filled('rombel_id')) {
            // Class at the time of attendance, not the student's current class
            $query->where('attendances.rombel_id', $request->rombel_id);
        }

        if ($request->filled('student_name')) {
            $query->whereHasMorph('attendable', [Student::class], function ($q) use ($request) {
                $q->where('full_name', 'ILIKE', '%' . $request->student_name . '%');
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'date');
        $sortDirection = $request->get('direction', 'asc'); // Default to ascending for better organization

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        // Apply sorting based on field
        switch ($sortField) {
            case 'full_name':
            case 'rombel':
                // For relationship fields, we need to sort in collection
                $attendances = $query->get()->sortBy(function($attendance) use ($sortField) {
                    if ($sortField === 'rombel') {
                        return strtolower($attendance->rombel_name ?? '');
                    }
                    return strtolower($attendance->attendable->$sortField ?? '');
                }, SORT_REGULAR, $sortDirection === 'desc');

                // If filtering by student, sort by date ascending; otherwise, sort by name first then date
                if ($request->filled('student_name')) {
                    $attendances = $attendances->sortBy(function($attendance) {
                        return $attendance->date->format('Y-m-d') . '_' . $attendance->date->format('H:i:s');
                    }, SORT_REGULAR, false); // Ascending order for date
                } else {
                    $attendances = $attendances->sortBy(function($attendance) use ($sortField, $sortDirection) {
                        if ($sortField === 'rombel') {
                            $group = strtolower($attendance->rombel_name ?? '');
                            $name = strtolower($attendance->attendable->full_name ?? '');
                        } else {
                            $group = strtolower($attendance->rombel_name ?? '');
                            $name = strtolower($attendance->attendable->$sortField ?? '');
                        }
                        $date = $attendance->date->format('Y-m-d');
                        return [$group, $name, $date];
                    }, SORT_REGULAR, $sortDirection === 'desc');
                }

                $perPage = $this->getPerPagePreference();
                if ($perPage === 'all') {
                    // Don't paginate, return collection
                    $paginated = false;
                } else {
                    $currentPage = request()->get('page', 1);
                    $perPage = in_array($perPage, [10, 20, 30, 50, 100]) ? $perPage : 20;
                    $attendances = new \Illuminate\Pagination\LengthAwarePaginator(
                        $attendances->forPage($currentPage, $perPage),
                        $attendances->count(),
                        $perPage,
                        $currentPage,
                        ['path' => request()->url(), 'query' => request()->query()]
                    );
                    $paginated = true;
                }
                break;

            case 'clock_in':
            case 'clock_out':
                if ($request->filled('student_name')) {
                    // If filtering by student, sort by date ascending
                    $attendances = $query->orderBy($sortField, $sortDirection)
                                         ->orderBy('date', 'asc')
                                         ->get();
                } else {
                    // Otherwise, sort by student name first, then by the field, then by date
                    $attendances = $query->join('students', 'attendances.attendable_id', '=', 'students.id')
                                         ->leftJoin('rombels', 'attendances.rombel_id', '=', 'rombels.id')
                                         ->orderBy('rombels.name', $sortDirection)
                                         ->orderBy('students.full_name', $sortDirection)
                                         ->orderBy($sortField, $sortDirection)
                                         ->orderBy('attendances.date', 'asc')
                                         ->select('attendances.*')
                                         ->with(['rombel', 'attendable.rombel'])
                                         ->get();
                }
                $paginated = false; // Handle pagination after sorting
                $perPage = $this->getPerPagePreference(); // Define perPage for all code paths
                break;

            case 'date':
            default:
                if ($request->filled('student_name')) {
                    // If filtering by student, sort by date ascending
                    $attendances = $query->orderBy('date', 'asc')->get();
                } else {
                    // Default grouping by rombel/name and then by date
                    $attendances = $query->join('students', 'attendances.attendable_id', '=', 'students.id')
                                         ->leftJoin('rombels', 'attendances.rombel_id', '=', 'rombels.id')
                                         ->orderBy('rombels.name')
                                         ->orderBy('students.full_name')
                                         ->orderBy('attendances.date', $sortDirection)
                                         ->select('attendances.*')
                                         ->with(['rombel', 'attendable.rombel'])
                                         ->get();
                }
                $paginated = false; // Handle pagination after sorting
                $perPage = $this->getPerPagePreference(); // Define perPage for all code paths
                break;
        }

        // Apply pagination if needed
        if (isset($paginated) && !$paginated && $perPage !== 'all') {
            $currentPage = request()->get('page', 1);
            $perPage = in_array($perPage, [10, 20, 30, 50, 100]) ? $perPage : 20;
            $attendances = new \Illuminate\Pagination\LengthAwarePaginator(
                $attendances->forPage($currentPage, $perPage),
                $attendances->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        // OPTIMIZED: Only select needed columns for rombels dropdown
        $rombels = Rombel::orderBy('name')
            ->select('id', 'name')
            ->get();

        // Get available months for the dropdown
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        // Get unique years from attendance records or default to current year and nearby years
        $years = Attendance::where('attendable_type', Student::class)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Get current year and include a few years before and after if no years are found
        $currentYear = now()->year;
        if ($years->isEmpty()) {
            $years = collect([$currentYear - 1, $currentYear, $currentYear + 1])->sortDesc();
        } else {
            // Add current year if not already in the list, plus a year ahead
            $years = $years->push($currentYear, $currentYear + 1)->unique()->sortDesc();
        }

        return view('admin.reports.student-reports', compact('attendances', 'rombels', 'months', 'years', 'paginated', 'selectedMonth', 'selectedYear'));
    }

    public function generate(Request $request)
    {
        // Validate either date range or month/year
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $request->validate([
                'type' => 'required|in:employee,student',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'format' => 'required|in:excel,pdf',
            ]);

            $query = Attendance::with('attendable')
                ->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('month') && $request->filled('year')) {
            $request->validate([
                'type' => 'required|in:employee,student',
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2000|max:'.(date('Y') + 5),
                'format' => 'required|in:excel,pdf',
            ]);

            $startDate = Carbon::create($request->year, $request->month, 1)->startOfMonth();
            $endDate = Carbon::create($request->year, $request->month, 1)->endOfMonth();

            $query = Attendance::with('attendable')
                ->whereBetween('date', [$startDate, $endDate]);
        } else {
            return back()->with('error', 'Silakan pilih periode laporan (rentang tanggal atau bulan dan tahun).');
        }

        if ($request->type === 'employee') {
            $query->where('attendable_type', Employee::class);
            if ($request->filled('employee_id')) {
                $query->where('attendable_id', $request->employee_id);
            }
        } else { // student
            $query->where('attendable_type', Student::class);
            if ($request->filled('rombel_id')) {
                $query->where('attendances.rombel_id', $request->rombel_id);
            }
        }

        $attendances = $query->orderBy('date', 'asc')->get();

        if ($request->format === 'excel') {
            return $this->exportExcel($attendances, $request->type, $startDate, $endDate);
        } else { // PDF
            if ($request->type === 'employee') {
                $attendances = $attendances->sortBy(function($a) {
                    return $a->attendable->full_name ?? '';
                });
                return $this->generateEmployeePdf($request, $attendances);
            } else {
                return $this->exportStudentPdf($attendances, $startDate, $endDate);
            }
        }
    }

    private function exportExcel($attendances, $type, $startDate, $endDate)
    {
        $filename = 'laporan_absensi_' . $type . '_' . date('YmdHis') . '.xlsx';
        return Excel::download(new AttendanceExport($attendances, $type, $startDate, $endDate), $filename);
    }

    public function generateEmployeePdf(Request $request, $attendances)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $month = $request->input('month');
        $year = $request->input('year');
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $period = $monthNames[$month] . ' ' . $year;
        $headerSettings = $this->getHeaderSettings();

        // SINGLE EMPLOYEE case
        if ($request->filled('employee_id')) {
            $employee = Employee::find($request->employee_id);
            if (!$employee) {
                return back()->with('error', 'Pegawai tidak ditemukan.');
            }

            $processedAttendances = $this->processAttendances($attendances);

            $pdf = Pdf::loadView('employee.report-export-pdf', [
                'attendances' => $processedAttendances,
                'employee' => $employee,
                'period' => $period,
                'headerSettings' => $headerSettings
            ])->setPaper('a4', 'portrait');

            $fileName = 'laporan_absensi_' . str_replace(' ', '_', $employee->full_name) . '_' . date('Y-m-d') . '.pdf';
            return $pdf->download($fileName);
        }
        // ALL EMPLOYEES case
        else {
            $employeeIds = $attendances->pluck('attendable_id')->unique();
            $employees = Employee::whereIn('id', $employeeIds)->orderBy('full_name')->get();

            $processedAttendances = $this->processAttendances($attendances);
            $groupedAttendances = $processedAttendances->groupBy('attendable_id');

            $pdf = Pdf::loadView('admin.reports.employee-report-collection-pdf', [
                'employees' => $employees,
                'groupedAttendances' => $groupedAttendances,
                'period' => $period,
                'headerSettings' => $headerSettings
            ])->setPaper('a4', 'portrait');

            $fileName = 'laporan_absensi_semua_pegawai_' . date('YmdHis') . '.pdf';
            return $pdf->download($fileName);
        }
    }

    private function exportStudentPdf($attendances, $startDate, $endDate)
    {
        $headerSettings = $this->getHeaderSettings();
        $schedules = \App\Models\Schedule::where('type', 'siswa')->get()->keyBy('day_of_week');

        $processedAttendances = $attendances->map(function($attendance) use ($schedules) {
            $dayOfWeek = $attendance->date->dayOfWeek;
            $schedule = $schedules->get($dayOfWeek);

            return [
                'attendance' => $attendance,
                'name' => $attendance->attendable->full_name ?? '-',
                'date' => $attendance->date->format('d/m/Y'),
                'clock_in_time' => $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-',
                'clock_out_time' => $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '-',
                'nisn' => $attendance->attendable->nisn ?? '-',
                'rombel' => $attendance->rombel_name ?? '-',
                'schedule_start' => $schedule ? Carbon::parse($schedule->start_time)->format('H:i:s') : '-',
                'schedule_end' => $schedule ? Carbon::parse($schedule->end_time)->format('H:i:s') : '-',
                'clock_in_status' => $attendance->status_in,
                'clock_out_status' => $attendance->status_out,
            ];
        });

        $data = [
            'attendances' => $processedAttendances,
            'type' => 'student',
            'startDate' => Carbon::parse($startDate)->format('d/m/Y'),
            'endDate' => Carbon::parse($endDate)->format('d/m/Y'),
            'headerSettings' => $headerSettings,
        ];

        $pdf = Pdf::loadView('admin.reports.pdf', $data)->setPaper('a4', 'landscape');
        $filename = 'laporan_absensi_siswa_' . date('YmdHis') . '.pdf';
        return $pdf->download($filename);
    }

    private function processAttendances($attendances)
    {
        return $attendances->map(function($attendance) {
            $schedule = self::getSchedule($attendance);
            $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $dayName = $attendance->date ? $dayNames[$attendance->date->dayOfWeek] : '-';

            // Determine status_in
            $statusIn = '-';
            if (in_array($attendance->status_in, ['C', 'S', 'DL'])) {
                $statusMap = ['C' => 'Cuti', 'S' => 'Sakit', 'DL' => 'Dinas Luar'];
                $statusIn = $statusMap[$attendance->status_in];
            } elseif ($attendance->clock_in && $schedule) {
                // If status is 'corrected', recalculate based on time; otherwise use stored status if available
                if ($attendance->status_in === 'corrected' || empty($attendance->status_in)) {
                    $clockIn = Carbon::parse($attendance->clock_in);
                    $startTime = Carbon::parse($schedule->start_time)->setDate($clockIn->year, $clockIn->month, $clockIn->day);
                    $diffInSeconds = $clockIn->diffInSeconds($startTime, false);
                    $statusIn = ($diffInSeconds > 0) ? 'Terlambat' : 'Tepat Waktu';
                } elseif (in_array($attendance->status_in, ['Tepat Waktu', 'Terlambat'])) {
                    $statusIn = $attendance->status_in;
                } else {
                    // Fallback: calculate from times
                    $clockIn = Carbon::parse($attendance->clock_in);
                    $startTime = Carbon::parse($schedule->start_time)->setDate($clockIn->year, $clockIn->month, $clockIn->day);
                    $diffInSeconds = $clockIn->diffInSeconds($startTime, false);
                    $statusIn = ($diffInSeconds > 0) ? 'Terlambat' : 'Tepat Waktu';
                }
            } elseif (!$attendance->clock_in) {
                $statusIn = 'Tidak Hadir';
            }

            // Determine status_out
            $statusOut = '-';
            if (in_array($attendance->status_out, ['C', 'S', 'DL'])) {
                $statusMap = ['C' => 'Cuti', 'S' => 'Sakit', 'DL' => 'Dinas Luar'];
                $statusOut = $statusMap[$attendance->status_out];
            } elseif ($attendance->clock_out && $schedule) {
                // If status is 'corrected', recalculate based on time; otherwise use stored status if available
                if ($attendance->status_out === 'corrected' || empty($attendance->status_out)) {
                    $clockOut = Carbon::parse($attendance->clock_out);
                    $endTime = Carbon::parse($schedule->end_time)->setDate($clockOut->year, $clockOut->month, $clockOut->day);
                    $diffInSeconds = $clockOut->diffInSeconds($endTime, false);
                    $statusOut = ($diffInSeconds < 0) ? 'Pulang Awal' : 'Tepat Waktu';
                } elseif (in_array($attendance->status_out, ['Tepat Waktu', 'Pulang Awal', 'Belum Pulang'])) {
                    $statusOut = $attendance->status_out;
                } else {
                    // Fallback: calculate from times
                    $clockOut = Carbon::parse($attendance->clock_out);
                    $endTime = Carbon::parse($schedule->end_time)->setDate($clockOut->year, $clockOut->month, $clockOut->day);
                    $diffInSeconds = $clockOut->diffInSeconds($endTime, false);
                    $statusOut = ($diffInSeconds < 0) ? 'Pulang Awal' : 'Tepat Waktu';
                }
            } elseif (!$attendance->clock_out) {
                $statusOut = 'Belum Pulang';
            }

            return [
                'attendable_id' => $attendance->attendable_id,
                'date' => $attendance->date ? $attendance->date->format('d/m/Y') : '-',
                'day' => $dayName,
                'schedule_start' => $schedule ? Carbon::parse($schedule->start_time)->format('H:i:s') : '-',
                'schedule_end' => $schedule ? Carbon::parse($schedule->end_time)->format('H:i:s') : '-',
                'clock_in' => $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-',
                'clock_out' => $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '-',
                'status_in' => $statusIn,
                'status_out' => $statusOut,
            ];
        });
    }

    private static function getSchedule($attendance)
    {
        // Try to get shift from employee_shifts table
        $employeeShift = \App\Models\EmployeeShift::where('employee_id', $attendance->attendable_id)
            ->where('date', $attendance->date)
            ->with('schedule')
            ->first();

        if ($employeeShift && $employeeShift->schedule) {
            return $employeeShift->schedule;
        }

        // Try to get schedule by day of week
        $dayOfWeek = $attendance->date->dayOfWeek;
        return Schedule::where('type', 'pegawai')
            ->where('day_of_week', $dayOfWeek)
            ->first();
    }

    /**
     * Calculate actual status based on time, even if status is 'corrected'
     */
    private function calculateActualStatus($attendance, $schedule, $isClockIn = true)
    {
        if (!$attendance) {
            return null;
        }

        if ($isClockIn) {
            // Check clock_in status
            if (!$attendance->clock_in) {
                return null;
            }

            // If status is 'corrected', recalculate the actual status based on time
            if ($attendance->status_in === 'corrected' || empty($attendance->status_in)) {
                $clockIn = Carbon::parse($attendance->clock_in);

                // Try to get schedule time from schedule object first, then fall back to attendance record
                if ($schedule) {
                    $startTime = Carbon::parse($schedule->start_time)->setDate($clockIn->year, $clockIn->month, $clockIn->day);
                } elseif ($attendance->scheduled_start_time) {
                    $startTime = Carbon::parse($attendance->scheduled_start_time);
                } else {
                    // No schedule information available, cannot determine status
                    return null;
                }

                $diffInSeconds = $clockIn->diffInSeconds($startTime, false);
                return ($diffInSeconds > 0) ? 'Terlambat' : 'Tepat Waktu';
            }

            // Return the existing status_in if it's already set
            return $attendance->status_in;
        } else {
            // Check clock_out status
            if (!$attendance->clock_out) {
                return null;
            }

            // If status is 'corrected', recalculate the actual status based on time
            if ($attendance->status_out === 'corrected' || empty($attendance->status_out)) {
                $clockOut = Carbon::parse($attendance->clock_out);

                // Try to get schedule time from schedule object first, then fall back to attendance record
                if ($schedule) {
                    $endTime = Carbon::parse($schedule->end_time)->setDate($clockOut->year, $clockOut->month, $clockOut->day);
                } elseif ($attendance->scheduled_end_time) {
                    $endTime = Carbon::parse($attendance->scheduled_end_time);
                } else {
                    // No schedule information available, cannot determine status
                    return null;
                }

                $diffInSeconds = $clockOut->diffInSeconds($endTime, false);
                return ($diffInSeconds < 0) ? 'Pulang Awal' : 'Tepat Waktu';
            }

            // Return the existing status_out if it's already set
            return $attendance->status_out;
        }
    }

    public function mealAllowance(Request $request)
    {
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);

        $employeeQuery = Employee::with(['salary' => function($query) use ($selectedMonth, $selectedYear) {
            $query->where('month', $selectedMonth)
                  ->where('year', $selectedYear)
                  ->latest('created_at');
        }])->where('is_active', true);

        if ($request->filled('employee_id')) {
            $employeeQuery->where('id', $request->employee_id);
        }

        // Sorting functionality - default to alphabetical order by name
        $sortField = $request->get('sort', 'full_name');
        $sortDirection = $request->get('direction', 'asc');

        if ($sortField === 'full_name') {
            $employeeQuery->orderBy('full_name', $sortDirection);
        } elseif ($sortField === 'position') {
            $employeeQuery->orderBy('position', $sortDirection);
        } else {
            $employeeQuery->orderBy('full_name', $sortDirection);
        }

        $employees = $employeeQuery->get();
        $employeeIds = $employees->pluck('id');

        $startDate = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        // Fetch all employee shifts for the period for all filtered employees
        $allEmployeeShifts = \App\Models\EmployeeShift::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('schedule')
            ->get()
            ->groupBy(function($shift) {
                return $shift->employee_id . '_' . $shift->date->format('Y-m-d');
            });

        // Fetch all attendances for the period for all filtered employees
        $allAttendances = Attendance::whereIn('attendable_id', $employeeIds)
            ->where('attendable_type', Employee::class)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function($attendance) {
                return $attendance->attendable_id . '_' . $attendance->date->format('Y-m-d');
            });

        // Fetch all approved permissions for the period for all filtered employees
        $allPermissions = \App\Models\Permission::whereIn('permissible_id', $employeeIds)
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
            ->get()
            ->groupBy('permissible_id');

        $permissionTypesFull = [
            'C' => 'Cuti',
            'S' => 'Sakit',
            'DL' => 'Dinas Luar',
        ];

        $mealAllowanceData = [];

        foreach ($employees as $employee) {
            $employeeId = $employee->id;
            $salary = $employee->salary;

            if (!$salary) {
                continue;
            }

            $employeePermissions = $allPermissions->get($employeeId, collect());

            $permissionMap = [];
            foreach ($employeePermissions as $permission) {
                $current = Carbon::parse($permission->start_date)->max($startDate);
                $end = Carbon::parse($permission->end_date)->min($endDate);
                while ($current->lte($end)) {
                    $permissionMap[$current->format('Y-m-d')] = $permissionTypesFull[$permission->type] ?? $permission->type;
                    $current->addDay();
                }
            }

            $mealAllowanceSummary = [
                'employee' => $employee,
                'active_days' => 0,
                'present_days' => 0,
                'absent_days' => 0,
                'sick_days' => 0,
                'leave_days' => 0,
                'official_duty_days' => 0,
                'total_meal_allowance' => 0, // Calculated below
            ];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = Carbon::create($selectedYear, $selectedMonth, $day);
                $dateKey = $currentDate->format('Y-m-d');
                $lookupKey = $employeeId . '_' . $dateKey;

                $shift = $allEmployeeShifts->get($lookupKey)?->first();
                $attendance = $allAttendances->get($lookupKey)?->first();
                $permissionType = $permissionMap[$dateKey] ?? null;

                if ($permissionType) {
                    if ($permissionType === 'Sakit') $mealAllowanceSummary['sick_days']++;
                    if ($permissionType === 'Cuti') $mealAllowanceSummary['leave_days']++;
                    if ($permissionType === 'Dinas Luar') $mealAllowanceSummary['official_duty_days']++;
                }

                // Hari Aktif (Shift) - menghitung dari hari yang diisikan nama shiftnya di /admin/employee-shifts
                // HANYA menghitung dari EmployeeShift yang di-assign, BUKAN dari default schedule
                $hasValidShift = $shift && $shift->schedule && !empty($shift->schedule->shift_name);

                if ($hasValidShift) {
                    $mealAllowanceSummary['active_days']++;
                }

                // Process attendance data only if there's no permission for this day
                if (!$permissionType) {
                    // Hanya menghitung untuk hari yang memiliki nama shift di /admin/employee-shifts
                    if ($hasValidShift) {
                        if ($attendance) {
                            // Hari Hadir - melihat dari hari yang HANYA memiliki nama shift di /admin/employee-shifts
                            // dan melihat terisinya kolom absensi masuk ATAU kolom absensi pulang sesuai data di /admin/reports
                            if ($attendance->clock_in || $attendance->clock_out) {
                                $mealAllowanceSummary['present_days']++;
                            } else {
                                // Tidak Hadir - melihat dari hari yang HANYA memiliki nama shift di /admin/employee-shifts
                                // dan melihat kolom kosong absensi masuk DAN kolom absensi pulang kosong sesuai data di /admin/reports
                                $mealAllowanceSummary['absent_days']++;
                            }
                        } else {
                            // Jika tidak ada record attendance sama sekali, berarti tidak hadir
                            // Tidak Hadir - hari memiliki shift di /admin/employee-shifts tapi tidak ada data absensi
                            $mealAllowanceSummary['absent_days']++;
                        }
                    }
                    // Jika tidak ada shift di /admin/employee-shifts untuk hari ini, tidak ikut terhitung
                } else {
                    // Untuk hari dengan perizinan, tidak menghitung attendance metrics
                    // Perizinan (Sakit, Cuti, Dinas Luar) sudah dihitung di atas
                }
            }

            $mealAllowanceData[$employeeId] = $mealAllowanceSummary;
        }

        // After collecting all data, calculate total meal allowance for each employee
        foreach ($mealAllowanceData as &$data) {
            $employee = $data['employee'];
            $salary = $employee->salary;

            if ($salary) {
                // Total meal allowance = daily meal allowance × number of present days
                $data['total_meal_allowance'] = $salary->meal_allowance * $data['present_days'];
            } else {
                $data['total_meal_allowance'] = 0;
            }
        }

        // Sort the final data array based on the sort parameters
        $sortField = $request->get('sort', 'full_name');
        $sortDirection = $request->get('direction', 'asc');

        $mealAllowanceData = collect($mealAllowanceData)->sortBy(function($data) use ($sortField) {
            switch ($sortField) {
                case 'position':
                    return strtolower($data['employee']->position ?? '');
                case 'total_meal_allowance':
                    return $data['total_meal_allowance'];
                case 'full_name':
                default:
                    return strtolower($data['employee']->full_name ?? '');
            }
        }, SORT_REGULAR, $sortDirection === 'desc');

        $mealAllowanceData = $mealAllowanceData->values()->all();

        // Get all employees for dropdown - only show active employees
        $allEmployees = Employee::where('is_active', true)->orderBy('full_name')->get();

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

        // *** NEW LOGIC for years ***
        // Get unique years from attendance records
        $yearsFromAttendance = Attendance::where('attendable_type', Employee::class)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->pluck('year')
            ->map(fn($year) => (int)$year);

        // Determine min and max year for the dropdown
        $currentYear = (int)Carbon::now()->year;
        $minYear = $yearsFromAttendance->min();

        if ($minYear === null || $minYear > $currentYear) {
            $minYear = $currentYear - 2;
        } else {
            $minYear = min($minYear, $currentYear - 2);
        }

        $years = collect(range($currentYear, $minYear))->sortDesc()->values();
        $years->prepend($currentYear + 1);
        $years = $years->unique()->sortDesc()->values();

        if ($years->isEmpty()) {
            $years = collect([$currentYear]);
        }
        // *** END NEW LOGIC for years ***

        // Debug: Check data being sent
        \Log::info('Meal Allowance Data Count: ' . count($mealAllowanceData));
        \Log::info('Selected Month/Year: ' . $selectedMonth . '/' . $selectedYear);
        if (count($mealAllowanceData) > 0) {
            \Log::info('First employee data: ', array_values($mealAllowanceData)[0]);
        }

        return view('admin.reports.meal-allowance', compact('mealAllowanceData', 'allEmployees', 'months', 'years', 'selectedMonth', 'selectedYear'));
    }

    public function downloadMealAllowancePdf(Request $request)
    {
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);

        $employeeQuery = Employee::with(['salary' => function($query) use ($selectedMonth, $selectedYear) {
            $query->where('month', $selectedMonth)
                  ->where('year', $selectedYear)
                  ->latest('created_at');
        }])->where('is_active', true);

        if ($request->filled('employee_id')) {
            $employeeQuery->where('id', $request->employee_id);
        }

        $employees = $employeeQuery->orderBy('full_name')->get();
        $employeeIds = $employees->pluck('id');

        $startDate = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        // Fetch all employee shifts for the period for all filtered employees
        $allEmployeeShifts = \App\Models\EmployeeShift::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('schedule')
            ->get()
            ->groupBy(function($shift) {
                return $shift->employee_id . '_' . $shift->date->format('Y-m-d');
            });

        // Fetch all attendances for the period for all filtered employees
        $allAttendances = Attendance::whereIn('attendable_id', $employeeIds)
            ->where('attendable_type', Employee::class)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function($attendance) {
                return $attendance->attendable_id . '_' . $attendance->date->format('Y-m-d');
            });

        // Fetch all approved permissions for the period for all filtered employees
        $allPermissions = \App\Models\Permission::whereIn('permissible_id', $employeeIds)
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
            ->get()
            ->groupBy('permissible_id');

        $permissionTypesFull = [
            'C' => 'Cuti',
            'S' => 'Sakit',
            'DL' => 'Dinas Luar',
        ];

        $mealAllowanceData = [];

        foreach ($employees as $employee) {
            $employeeId = $employee->id;
            $salary = $employee->salary;

            if (!$salary) {
                continue;
            }

            $employeePermissions = $allPermissions->get($employeeId, collect());

            $permissionMap = [];
            foreach ($employeePermissions as $permission) {
                $current = Carbon::parse($permission->start_date)->max($startDate);
                $end = Carbon::parse($permission->end_date)->min($endDate);
                while ($current->lte($end)) {
                    $permissionMap[$current->format('Y-m-d')] = $permissionTypesFull[$permission->type] ?? $permission->type;
                    $current->addDay();
                }
            }

            $mealAllowanceSummary = [
                'employee' => $employee,
                'active_days' => 0,
                'present_days' => 0,
                'absent_days' => 0,
                'sick_days' => 0,
                'leave_days' => 0,
                'official_duty_days' => 0,
                'total_meal_allowance' => 0,
            ];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = Carbon::create($selectedYear, $selectedMonth, $day);
                $dateKey = $currentDate->format('Y-m-d');
                $lookupKey = $employeeId . '_' . $dateKey;

                $shift = $allEmployeeShifts->get($lookupKey)?->first();
                $attendance = $allAttendances->get($lookupKey)?->first();
                $permissionType = $permissionMap[$dateKey] ?? null;

                if ($permissionType) {
                    if ($permissionType === 'Sakit') $mealAllowanceSummary['sick_days']++;
                    if ($permissionType === 'Cuti') $mealAllowanceSummary['leave_days']++;
                    if ($permissionType === 'Dinas Luar') $mealAllowanceSummary['official_duty_days']++;
                }

                $hasValidShift = $shift && $shift->schedule && !empty($shift->schedule->shift_name);

                if ($hasValidShift) {
                    $mealAllowanceSummary['active_days']++;
                }

                if (!$permissionType) {
                    if ($hasValidShift) {
                        if ($attendance) {
                            if ($attendance->clock_in || $attendance->clock_out) {
                                $mealAllowanceSummary['present_days']++;
                            } else {
                                $mealAllowanceSummary['absent_days']++;
                            }
                        } else {
                            $mealAllowanceSummary['absent_days']++;
                        }
                    }
                }
            }

            $mealAllowanceSummary['total_meal_allowance'] = $salary->meal_allowance * $mealAllowanceSummary['present_days'];
            $mealAllowanceData[] = $mealAllowanceSummary;
        }

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $data = [
            'mealAllowanceData' => $mealAllowanceData,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'monthName' => $months[$selectedMonth],
            'headerSettings' => $this->getHeaderSettings(),
        ];

        $pdf = Pdf::loadView('admin.reports.meal-allowance-pdf', $data)
            ->setPaper('a4', 'portrait');

        $filename = 'laporan_uang_makan_' . $months[$selectedMonth] . '_' . $selectedYear . '.pdf';
        return $pdf->download($filename);
    }

    public function performanceAllowance(Request $request)
    {
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);

        $employeeQuery = Employee::with(['salary' => function($query) use ($selectedMonth, $selectedYear) {
            $query->where('month', $selectedMonth)
                  ->where('year', $selectedYear)
                  ->latest('created_at');
        }])->where('is_active', true);
        if ($request->filled('employee_id')) {
            $employeeQuery->where('id', $request->employee_id);
        }

        // Sorting functionality - default to alphabetical order by name
        $sortField = $request->get('sort', 'full_name');
        $sortDirection = $request->get('direction', 'asc');

        if ($sortField === 'full_name') {
            $employeeQuery->orderBy('full_name', $sortDirection);
        } elseif ($sortField === 'position') {
            $employeeQuery->orderBy('position', $sortDirection);
        } else {
            $employeeQuery->orderBy('full_name', $sortDirection);
        }
        $employees = $employeeQuery->get();

        // Fetch all employee shifts for the period for all filtered employees
        $employeeIds = $employees->pluck('id');
        $startDate = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endDate = Carbon::create($selectedYear, $selectedMonth, 1)->endOfMonth();

        $allEmployeeShifts = \App\Models\EmployeeShift::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('schedule')
            ->get()
            ->groupBy(function($shift) {
                return $shift->employee_id . '_' . $shift->date->format('Y-m-d');
            });

        // Fetch all attendances for the period for all filtered employees
        $allAttendances = \App\Models\Attendance::whereIn('attendable_id', $employeeIds)
            ->where('attendable_type', Employee::class)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function($attendance) {
                return $attendance->attendable_id . '_' . $attendance->date->format('Y-m-d');
            });

        // Fetch all approved permissions for the period for all filtered employees
        $allPermissions = \App\Models\Permission::whereIn('permissible_id', $employeeIds)
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
            ->get()
            ->groupBy('permissible_id');

        $permissionTypesFull = [
            'C' => 'Cuti',
            'S' => 'Sakit',
            'DL' => 'Dinas Luar',
        ];

        // Initialize performance data
        $performanceData = [];

        foreach ($employees as $employee) {
            $employeeId = $employee->id;
            $salary = $employee->salary;

            if (!$salary) {
                continue;
            }

            $employeePermissions = $allPermissions->get($employeeId, collect());

            $permissionMap = [];
            foreach ($employeePermissions as $permission) {
                $current = Carbon::parse($permission->start_date)->max($startDate);
                $end = Carbon::parse($permission->end_date)->min($endDate);
                while ($current->lte($end)) {
                    $permissionMap[$current->format('Y-m-d')] = $permissionTypesFull[$permission->type] ?? $permission->type;
                    $current->addDay();
                }
            }

            $daysInMonth = Carbon::create($selectedYear, $selectedMonth, 1)->daysInMonth;
            $activeDays = 0;
            $presentDays = 0;
            $absentDays = 0;
            $onTimeArrivalDays = 0;
            $onTimeDepartureDays = 0;
            $lateArrivalDays = 0; // Days with late arrival (Terlambat)
            $earlyDepartureDays = 0; // Days with early departure (Pulang Awal)
            $sickDays = 0;
            $leaveDays = 0;
            $officialDutyDays = 0;
            $onTimeBothDays = 0; // Days where both arrival and departure are on time

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = Carbon::create($selectedYear, $selectedMonth, $day);
                $dateKey = $currentDate->format('Y-m-d');
                $lookupKey = $employeeId . '_' . $dateKey;

                $shift = $allEmployeeShifts->get($lookupKey)?->first();
                $attendance = $allAttendances->get($lookupKey)?->first();
                $permissionType = $permissionMap[$dateKey] ?? null;

                // Count permission days
                if ($permissionType) {
                    if ($permissionType === 'Sakit') $sickDays++;
                    if ($permissionType === 'Cuti') $leaveDays++;
                    if ($permissionType === 'Dinas Luar') $officialDutyDays++;
                }

                // Hari Aktif (Shift) - menghitung dari hari yang diisikan nama shiftnya di /admin/employee-shifts
                // HANYA menghitung dari EmployeeShift yang di-assign, BUKAN dari default schedule
                $hasValidShift = $shift && $shift->schedule && !empty($shift->schedule->shift_name);

                if ($hasValidShift) {
                    $activeDays++;
                }

                // Process attendance data only if there's no permission for this day
                if (!$permissionType) {
                    // Hanya menghitung untuk hari yang memiliki nama shift di /admin/employee-shifts
                    if ($hasValidShift) {
                        $isPresent = false;
                        $isOnTimeArrival = false;
                        $isOnTimeDeparture = false;

                        if ($attendance) {
                            // Hari Hadir - melihat dari hari yang HANYA memiliki nama shift di /admin/employee-shifts
                            // dan melihat terisinya kolom absensi masuk ATAU kolom absensi pulang sesuai data di /admin/reports
                            if ($attendance->clock_in || $attendance->clock_out) {
                                $isPresent = true;
                                $presentDays++;

                                // Calculate actual status (handles 'corrected' status by recalculating based on time)
                                $actualStatusIn = $this->calculateActualStatus($attendance, $shift->schedule, true);
                                $actualStatusOut = $this->calculateActualStatus($attendance, $shift->schedule, false);

                                // Tepat Waktu Masuk - melihat dari kolom status absensi masuk "Tepat Waktu" sesuai data di /admin/reports
                                if ($actualStatusIn === 'Tepat Waktu') {
                                    $isOnTimeArrival = true;
                                    $onTimeArrivalDays++;
                                } elseif ($actualStatusIn === 'Terlambat') {
                                    // Terlambat Datang - melihat dari kolom status absensi masuk "Terlambat" sesuai data di /admin/reports
                                    $lateArrivalDays++;
                                }

                                // Tepat Waktu Pulang - melihat dari kolom status absensi pulang "Tepat Waktu" sesuai data di /admin/reports
                                if ($actualStatusOut === 'Tepat Waktu') {
                                    $isOnTimeDeparture = true;
                                    $onTimeDepartureDays++;
                                } elseif ($actualStatusOut === 'Pulang Awal') {
                                    // Pulang Awal - melihat dari kolom status absensi pulang "Pulang Awal" sesuai data di /admin/reports
                                    $earlyDepartureDays++;
                                }
                            } else {
                                // Tidak Hadir - melihat dari hari yang HANYA memiliki nama shift di /admin/employee-shifts
                                // dan melihat kolom kosong absensi masuk DAN kolom absensi pulang kosong sesuai data di /admin/reports
                                $absentDays++;
                            }
                        } else {
                            // Jika tidak ada record attendance sama sekali, berarti tidak hadir
                            // Tidak Hadir - hari memiliki shift di /admin/employee-shifts tapi tidak ada data absensi
                            $absentDays++;
                        }

                        // Tepat Waktu Datang dan Pulang - jika sama-sama dalam satu hari itu tepat waktu
                        if ($isOnTimeArrival && $isOnTimeDeparture) {
                            $onTimeBothDays++; // Counted as a perfect day for performance allowance
                        }
                    }
                    // Jika tidak ada shift di /admin/employee-shifts untuk hari ini, tidak ikut terhitung
                } else {
                    // Untuk hari dengan perizinan, tidak menghitung attendance metrics
                    // Perizinan (Sakit, Cuti, Dinas Luar) sesuai data di /admin/reports sudah dihitung di atas
                }
            }

            // Calculate total performance allowance based on perfect attendance days
            // Nilai uang tunjangan dihitung dari uang tunjangan di /admin/employees/salary-settings dikalikan dengan kolom tepat Waktu dating dan pulang
            $totalPerformanceAllowance = $salary->performance_allowance * $onTimeBothDays;

            // Nilai uang makan dihitung dari uang makan di /admin/employees/salary-settings dikalikan dengan kolom hari hadir
            $totalMealAllowance = $salary->meal_allowance * $presentDays;

            $performanceData[] = [
                'employee' => $employee,
                'active_days' => $activeDays,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'on_time_arrival_days' => $onTimeArrivalDays,
                'on_time_departure_days' => $onTimeDepartureDays,
                'late_arrival_days' => $lateArrivalDays,
                'early_departure_days' => $earlyDepartureDays,
                'sick_days' => $sickDays,
                'leave_days' => $leaveDays,
                'official_duty_days' => $officialDutyDays,
                'on_time_both_days' => $onTimeBothDays,
                'total_performance_allowance' => $totalPerformanceAllowance,
                'total_meal_allowance' => $totalMealAllowance
            ];
        }

        // Sort the final data array based on the sort parameters
        $sortField = $request->get('sort', 'full_name');
        $sortDirection = $request->get('direction', 'asc');

        $performanceData = collect($performanceData)->sortBy(function($data) use ($sortField) {
            switch ($sortField) {
                case 'position':
                    return strtolower($data['employee']->position ?? '');
                case 'total_performance_allowance':
                    return $data['total_performance_allowance'];
                case 'total_meal_allowance':
                    return $data['total_meal_allowance'];
                case 'on_time_both_days':
                    return $data['on_time_both_days'];
                case 'full_name':
                default:
                    return strtolower($data['employee']->full_name ?? '');
            }
        }, SORT_REGULAR, $sortDirection === 'desc');

        $performanceData = $performanceData->values()->all();

        // Get all employees for dropdown - only show active employees
        $employees = Employee::where('is_active', true)->orderBy('full_name')->get();

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

        // *** NEW LOGIC for years ***
        // Get unique years from attendance records
        $yearsFromAttendance = Attendance::where('attendable_type', Employee::class)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->pluck('year')
            ->map(fn($year) => (int)$year);

        // Determine min and max year for the dropdown
        $currentYear = (int)Carbon::now()->year;
        $minYear = $yearsFromAttendance->min();

        if ($minYear === null || $minYear > $currentYear) {
            // If no attendance years or all attendance years are in the future
            $minYear = $currentYear - 2; // Show current year and 2 past years
        } else {
            $minYear = min($minYear, $currentYear - 2); // Show at least 2 past years
        }

        // Generate the final list of years for the dropdown
        $years = collect(range($currentYear, $minYear))->sortDesc()->values();
        // Add one future year to ensure the current period is always selectable.
        $years->prepend($currentYear + 1);
        $years = $years->unique()->sortDesc()->values();

        if ($years->isEmpty()) {
            $years = collect([$currentYear]);
        }
        // *** END NEW LOGIC for years ***


        return view('admin.reports.performance-allowance', compact('performanceData', 'employees', 'months', 'years', 'selectedMonth', 'selectedYear'));
    }

    public function downloadPerformanceAllowancePdf(Request $request)
    {
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);

        $employeeQuery = Employee::with(['salary' => function($query) use ($selectedMonth, $selectedYear) {
            $query->where('month', $selectedMonth)
                  ->where('year', $selectedYear)
                  ->latest('created_at');
        }])->where('is_active', true);
        if ($request->filled('employee_id')) {
            $employeeQuery->where('id', $request->employee_id);
        }

        $employees = $employeeQuery->orderBy('full_name')->get();

        // Fetch all employee shifts for the period for all filtered employees
        $employeeIds = $employees->pluck('id');
        $startDate = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endDate = Carbon::create($selectedYear, $selectedMonth, 1)->endOfMonth();

        $allEmployeeShifts = \App\Models\EmployeeShift::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('schedule')
            ->get()
            ->groupBy(function($shift) {
                return $shift->employee_id . '_' . $shift->date->format('Y-m-d');
            });

        // Fetch all attendances for the period for all filtered employees
        $allAttendances = \App\Models\Attendance::whereIn('attendable_id', $employeeIds)
            ->where('attendable_type', Employee::class)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function($attendance) {
                return $attendance->attendable_id . '_' . $attendance->date->format('Y-m-d');
            });

        // Fetch all approved permissions for the period for all filtered employees
        $allPermissions = \App\Models\Permission::whereIn('permissible_id', $employeeIds)
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
            ->get()
            ->groupBy('permissible_id');

        $permissionTypesFull = [
            'C' => 'Cuti',
            'S' => 'Sakit',
            'DL' => 'Dinas Luar',
        ];

        // Initialize performance data
        $performanceData = [];

        foreach ($employees as $employee) {
            $employeeId = $employee->id;
            $salary = $employee->salary;

            if (!$salary) {
                continue;
            }

            $employeePermissions = $allPermissions->get($employeeId, collect());

            $permissionMap = [];
            foreach ($employeePermissions as $permission) {
                $current = Carbon::parse($permission->start_date)->max($startDate);
                $end = Carbon::parse($permission->end_date)->min($endDate);
                while ($current->lte($end)) {
                    $permissionMap[$current->format('Y-m-d')] = $permissionTypesFull[$permission->type] ?? $permission->type;
                    $current->addDay();
                }
            }

            $daysInMonth = Carbon::create($selectedYear, $selectedMonth, 1)->daysInMonth;
            $activeDays = 0;
            $presentDays = 0;
            $absentDays = 0;
            $onTimeArrivalDays = 0;
            $onTimeDepartureDays = 0;
            $lateArrivalDays = 0;
            $earlyDepartureDays = 0;
            $sickDays = 0;
            $leaveDays = 0;
            $officialDutyDays = 0;
            $onTimeBothDays = 0;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = Carbon::create($selectedYear, $selectedMonth, $day);
                $dateKey = $currentDate->format('Y-m-d');
                $lookupKey = $employeeId . '_' . $dateKey;

                $shift = $allEmployeeShifts->get($lookupKey)?->first();
                $attendance = $allAttendances->get($lookupKey)?->first();
                $permissionType = $permissionMap[$dateKey] ?? null;

                // Count permission days
                if ($permissionType) {
                    if ($permissionType === 'Sakit') $sickDays++;
                    if ($permissionType === 'Cuti') $leaveDays++;
                    if ($permissionType === 'Dinas Luar') $officialDutyDays++;
                }

                $hasValidShift = $shift && $shift->schedule && !empty($shift->schedule->shift_name);

                if ($hasValidShift) {
                    $activeDays++;
                }

                // Process attendance data only if there's no permission for this day
                if (!$permissionType) {
                    if ($hasValidShift) {
                        $isPresent = false;
                        $isOnTimeArrival = false;
                        $isOnTimeDeparture = false;

                        if ($attendance) {
                            if ($attendance->clock_in || $attendance->clock_out) {
                                $isPresent = true;
                                $presentDays++;

                                // Calculate actual status (handles 'corrected' status by recalculating based on time)
                                $actualStatusIn = $this->calculateActualStatus($attendance, $shift->schedule, true);
                                $actualStatusOut = $this->calculateActualStatus($attendance, $shift->schedule, false);

                                if ($actualStatusIn === 'Tepat Waktu') {
                                    $isOnTimeArrival = true;
                                    $onTimeArrivalDays++;
                                } elseif ($actualStatusIn === 'Terlambat') {
                                    $lateArrivalDays++;
                                }

                                if ($actualStatusOut === 'Tepat Waktu') {
                                    $isOnTimeDeparture = true;
                                    $onTimeDepartureDays++;
                                } elseif ($actualStatusOut === 'Pulang Awal') {
                                    $earlyDepartureDays++;
                                }
                            } else {
                                $absentDays++;
                            }
                        } else {
                            $absentDays++;
                        }

                        if ($isOnTimeArrival && $isOnTimeDeparture) {
                            $onTimeBothDays++;
                        }
                    }
                }
            }

            $totalPerformanceAllowance = $salary->performance_allowance * $onTimeBothDays;

            $performanceData[] = [
                'employee' => $employee,
                'active_days' => $activeDays,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'on_time_arrival_days' => $onTimeArrivalDays,
                'on_time_departure_days' => $onTimeDepartureDays,
                'late_arrival_days' => $lateArrivalDays,
                'early_departure_days' => $earlyDepartureDays,
                'sick_days' => $sickDays,
                'leave_days' => $leaveDays,
                'official_duty_days' => $officialDutyDays,
                'on_time_both_days' => $onTimeBothDays,
                'total_performance_allowance' => $totalPerformanceAllowance,
            ];
        }

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $data = [
            'performanceData' => $performanceData,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'monthName' => $months[$selectedMonth],
            'headerSettings' => $this->getHeaderSettings(),
        ];

        $pdf = Pdf::loadView('admin.reports.performance-allowance-pdf', $data)
            ->setPaper('a4', 'portrait');

        $filename = 'laporan_tunjangan_kinerja_' . $months[$selectedMonth] . '_' . $selectedYear . '.pdf';
        return $pdf->download($filename);
    }

    public function salaryReport(Request $request)
    {
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);

        $employeeQuery = Employee::with(['salary' => function($query) use ($selectedMonth, $selectedYear) {
            $query->where('month', $selectedMonth)
                  ->where('year', $selectedYear)
                  ->latest('created_at');
        }]);

        // Filter by specific employee if provided
        if ($request->filled('employee_id')) {
            $employeeQuery->where('id', $request->employee_id);
        } else {
            // For general report, only include active employees
            $employeeQuery->where('is_active', true);
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

            // Get employee shifts for the month/year
            $employeeShifts = \App\Models\EmployeeShift::where('employee_id', $employeeId)
                ->whereMonth('date', $selectedMonth)
                ->whereYear('date', $selectedYear)
                ->with('schedule')
                ->get()
                ->keyBy('date');

            // Get default schedules for non-shift assignment days
            $defaultSchedules = Schedule::where('type', 'pegawai')
                ->get()
                ->keyBy('day_of_week');

            $present_days = 0;
            $perfect_days = 0;
            $totalMealAllowance = 0;
            $totalTransportAllowance = 0;
            $totalPerformanceAllowance = 0;

            // Iterate through each day of the selected month
            $daysInMonth = Carbon::create($selectedYear, $selectedMonth, 1)->daysInMonth;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = Carbon::create($selectedYear, $selectedMonth, $day);
                $dateKey = $currentDate->format('Y-m-d');

                $attendance = $employeeAttendances->firstWhere('date', $currentDate);

                // Get shift schedule for this specific date if available, otherwise use default schedule
                $shift = $employeeShifts->get($dateKey);
                $schedule = $shift ? $shift->schedule : null;

                if (!$schedule) {
                    // Use default schedule based on day of week if no specific shift is assigned
                    // Convert to 1-7 format (Monday=1, Sunday=7) as typically used in schedules
                    $dayOfWeek = $currentDate->dayOfWeek;
                    if ($dayOfWeek == 0) $dayOfWeek = 7; // Convert Sunday (0) to 7
                    $schedule = $defaultSchedules->get($dayOfWeek);
                }

                // Calculate daily allowances based on schedule, attendance, and permissions
                $dailyMealAllowance = 0;
                $dailyTransportAllowance = 0;
                $dailyPerformanceAllowance = 0;

                if ($attendance) {
                    // There is an attendance record
                    $isPresent = $attendance->clock_in !== null || $attendance->clock_out !== null;

                    if ($isPresent) {
                        // Grant meal and transport allowances if present and it's a scheduled day
                        if ($schedule) {
                            $dailyMealAllowance = $salary->meal_allowance;
                            $dailyTransportAllowance = $salary->transport_allowance;
                        }

                        // Calculate actual status (handles 'corrected' status by recalculating based on time)
                        $actualStatusIn = $this->calculateActualStatus($attendance, $schedule, true);
                        $actualStatusOut = $this->calculateActualStatus($attendance, $schedule, false);

                        // Check for perfect attendance to grant performance allowance
                        $isPerfect = $actualStatusIn === 'Tepat Waktu' && $actualStatusOut === 'Tepat Waktu';
                        if ($isPerfect) {
                            $dailyPerformanceAllowance = $salary->performance_allowance;
                        }
                    }
                }

                // Accumulate daily allowances
                $totalMealAllowance += $dailyMealAllowance;
                $totalTransportAllowance += $dailyTransportAllowance;
                $totalPerformanceAllowance += $dailyPerformanceAllowance;

                // Count present and perfect days for summary
                if ($dailyMealAllowance > 0) { // If allowances were granted, it means employee was present on a scheduled day
                    $present_days++;
                }
                if ($dailyPerformanceAllowance > 0) { // If performance allowance was granted, it was a perfect day
                    $perfect_days++;
                }
            }

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

        // Data for filters - only include active employees
        $allEmployees = Employee::where('is_active', true)->orderBy('full_name')->get();
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        // *** NEW LOGIC ***
        // Get unique years from attendance records
        $yearsFromAttendance = Attendance::where('attendable_type', Employee::class)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->pluck('year')
            ->map(fn($year) => (int)$year);

        // Determine min and max year for the dropdown
        $currentYear = (int)Carbon::now()->year;
        $minYear = $yearsFromAttendance->min();

        if ($minYear === null || $minYear > $currentYear) {
            // If no attendance years or all attendance years are in the future
            $minYear = $currentYear - 2; // Show current year and 2 past years
        } else {
            $minYear = min($minYear, $currentYear - 2); // Show at least 2 past years
        }

        // Generate the final list of years for the dropdown
        $years = collect(range($currentYear, $minYear))->sortDesc()->values();
        // Add one future year to ensure the current period is always selectable.
        $years->prepend($currentYear + 1);
        $years = $years->unique()->sortDesc()->values();

        if ($years->isEmpty()) {
            $years = collect([$currentYear]);
        }

        // Get show salary menu setting
        $showSalaryMenuSetting = AttendanceSetting::where('setting_key', 'show_salary_menu_in_employee')->first();
        $showSalaryMenuInEmployee = $showSalaryMenuSetting ? filter_var($showSalaryMenuSetting->setting_value, FILTER_VALIDATE_BOOLEAN) : true;

        return view('admin.reports.salary', [
            'salaryData' => $salaryData,
            'employees' => $allEmployees,
            'months' => $months,
            'years' => $years,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'showSalaryMenuInEmployee' => $showSalaryMenuInEmployee,
        ]);
    }

    public function downloadSalaryPdf(Request $request)
    {
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);

        $employeeQuery = Employee::with(['salary' => function($query) use ($selectedMonth, $selectedYear) {
            $query->where('month', $selectedMonth)
                  ->where('year', $selectedYear)
                  ->latest('created_at');
        }])->where('is_active', true);

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

            // Get employee shifts for the month/year
            $employeeShifts = \App\Models\EmployeeShift::where('employee_id', $employeeId)
                ->whereMonth('date', $selectedMonth)
                ->whereYear('date', $selectedYear)
                ->with('schedule')
                ->get()
                ->keyBy('date');

            // Get default schedules for non-shift assignment days
            $defaultSchedules = Schedule::where('type', 'pegawai')
                ->get()
                ->keyBy('day_of_week');

            $present_days = 0;
            $perfect_days = 0;
            $totalMealAllowance = 0;
            $totalTransportAllowance = 0;
            $totalPerformanceAllowance = 0;

            // Iterate through each day of the selected month
            $daysInMonth = Carbon::create($selectedYear, $selectedMonth, 1)->daysInMonth;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = Carbon::create($selectedYear, $selectedMonth, $day);
                $dateKey = $currentDate->format('Y-m-d');

                $attendance = $employeeAttendances->firstWhere('date', $currentDate);

                // Get shift schedule for this specific date if available, otherwise use default schedule
                $shift = $employeeShifts->get($dateKey);
                $schedule = $shift ? $shift->schedule : null;

                if (!$schedule) {
                    // Use default schedule based on day of week if no specific shift is assigned
                    // Convert to 1-7 format (Monday=1, Sunday=7) as typically used in schedules
                    $dayOfWeek = $currentDate->dayOfWeek;
                    if ($dayOfWeek == 0) $dayOfWeek = 7; // Convert Sunday (0) to 7
                    $schedule = $defaultSchedules->get($dayOfWeek);
                }

                // Calculate daily allowances based on schedule, attendance, and permissions
                $dailyMealAllowance = 0;
                $dailyTransportAllowance = 0;
                $dailyPerformanceAllowance = 0;

                if ($attendance) {
                    // There is an attendance record
                    $isPresent = $attendance->clock_in !== null || $attendance->clock_out !== null;

                    if ($isPresent) {
                        // Grant meal and transport allowances if present and it's a scheduled day
                        if ($schedule) {
                            $dailyMealAllowance = $salary->meal_allowance;
                            $dailyTransportAllowance = $salary->transport_allowance;
                        }

                        // Calculate actual status (handles 'corrected' status by recalculating based on time)
                        $actualStatusIn = $this->calculateActualStatus($attendance, $schedule, true);
                        $actualStatusOut = $this->calculateActualStatus($attendance, $schedule, false);

                        // Check for perfect attendance to grant performance allowance
                        $isPerfect = $actualStatusIn === 'Tepat Waktu' && $actualStatusOut === 'Tepat Waktu';
                        if ($isPerfect) {
                            $dailyPerformanceAllowance = $salary->performance_allowance;
                        }
                    }
                }

                // Accumulate daily allowances
                $totalMealAllowance += $dailyMealAllowance;
                $totalTransportAllowance += $dailyTransportAllowance;
                $totalPerformanceAllowance += $dailyPerformanceAllowance;

                // Count present and perfect days for summary
                if ($dailyMealAllowance > 0) { // If allowances were granted, it means employee was present on a scheduled day
                    $present_days++;
                }
                if ($dailyPerformanceAllowance > 0) { // If performance allowance was granted, it was a perfect day
                    $perfect_days++;
                }
            }

            // Calculate total salary
            $totalSalary = $salary->base_salary + $totalMealAllowance + $totalTransportAllowance + $totalPerformanceAllowance;

            $salaryData[] = [
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

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $data = [
            'salaryData' => $salaryData,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'monthName' => $months[$selectedMonth],
            'headerSettings' => $this->getHeaderSettings(),
        ];

        $pdf = Pdf::loadView('admin.reports.salary-pdf', $data)
            ->setPaper('a4', 'portrait');

        $filename = 'laporan_gaji_' . $months[$selectedMonth] . '_' . $selectedYear . '.pdf';
        return $pdf->download($filename);
    }

    public function salaryReportDetail(Request $request, Employee $employee, $year, $month)
    {
        $data = $this->getSalaryDetailData($employee, $year, $month);
        $data['headerSettings'] = $this->getHeaderSettings();
        return view('admin.reports.salary-detail', $data);
    }

    public function generateSalaryPdf(Request $request, Employee $employee, $year, $month)
    {
        $data = $this->getSalaryDetailData($employee, $year, $month);
        $data['headerSettings'] = $this->getHeaderSettings();

        $pdf = Pdf::loadView('admin.reports.salary-detail-pdf', $data)
            ->setPaper('a4', 'portrait');

        $filename = 'laporan_gaji_' . str_replace(' ', '_', $employee->full_name) . '_' . Carbon::create($year, $month)->isoFormat('MMMM_YYYY') . '.pdf';
        return $pdf->download($filename);
    }

    private function getSalaryDetailData(Employee $employee, $year, $month)
    {
        $employee->load(['salary' => function($query) {
            $query->latest('created_at');
        }]);
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
                        // Fixed: If actual > scheduled (positive seconds), then late
                        // If actual == scheduled (0 seconds), then on-time
                        $statusIn = ($diffIn['seconds'] > 0) ? 'Terlambat' : 'Tepat Waktu';
                    } else {
                        $statusIn = 'Tidak Hadir';
                    }

                    if ($attendance->clock_out) {
                        $diffOut = $this->calculateTimeDifference($attendance->clock_out, $schedule->end_time);
                        // If actual < scheduled (negative seconds), then early departure
                        $statusOut = ($diffOut['seconds'] < 0) ? 'Pulang Awal' : 'Tepat Waktu';
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

    public function mealAllowanceDetail(Request $request, Employee $employee, $year, $month)
    {
        $data = $this->getMealAllowanceDetailData($employee, $year, $month);
        return view('admin.reports.meal-allowance-detail', $data);
    }

    public function generateMealAllowancePdf(Request $request, Employee $employee, $year, $month)
    {
        $data = $this->getMealAllowanceDetailData($employee, $year, $month);
        $data['headerSettings'] = $this->getHeaderSettings();

        $pdf = Pdf::loadView('admin.reports.meal-allowance-detail-pdf', $data)
            ->setPaper('a4', 'portrait');

        $filename = 'laporan_uang_makan_' . str_replace(' ', '_', $employee->full_name) . '_' . Carbon::create($year, $month)->isoFormat('MMMM_YYYY') . '.pdf';
        return $pdf->download($filename);
    }

    private function getMealAllowanceDetailData(Employee $employee, $year, $month)
    {
        $employee->load(['salary' => function($query) {
            $query->latest('created_at');
        }]);
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
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::create($year, $month, $day);
            $dateKey = $currentDate->format('Y-m-d');
            
            $attendance = $attendances->get($dateKey);
            $employeeShift = $employeeShifts->get($dateKey);
            $schedule = $employeeShift ? $employeeShift->schedule : null;
            $permissionType = $permissionMap[$dateKey] ?? null;

            $dailyMealAllowance = 0;
            
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
                        // Fixed: If actual > scheduled (positive seconds), then late
                        // If actual == scheduled (0 seconds), then on-time
                        $statusIn = ($diffIn['seconds'] > 0) ? 'Terlambat' : 'Tepat Waktu';
                    } else {
                        $statusIn = 'Tidak Hadir';
                    }

                    if ($attendance->clock_out) {
                        $diffOut = $this->calculateTimeDifference($attendance->clock_out, $schedule->end_time);
                        // If actual < scheduled (negative seconds), then early departure
                        $statusOut = ($diffOut['seconds'] < 0) ? 'Pulang Awal' : 'Tepat Waktu';
                    } else {
                        $statusOut = 'Belum Pulang';
                    }

                    // Grant allowances only if employee is present
                    $isPresent = $attendance->clock_in !== null || $attendance->clock_out !== null;
                    if ($isPresent) {
                        $dailyMealAllowance = $salary->meal_allowance;
                        $totalMealAllowance += $dailyMealAllowance;
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
            ];
        }

        $summary = [
            'total_meal_allowance' => $totalMealAllowance,
        ];

        return compact('employee', 'year', 'month', 'detailData', 'summary', 'permissionMap');
    }

    public function transportAllowance(Request $request)
    {
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);

        $employeeQuery = Employee::with(['salary' => function($query) use ($selectedMonth, $selectedYear) {
            $query->where('month', $selectedMonth)
                  ->where('year', $selectedYear)
                  ->latest('created_at');
        }])->where('is_active', true);

        if ($request->filled('employee_id')) {
            $employeeQuery->where('id', $request->employee_id);
        }

        // Sorting functionality - default to alphabetical order by name
        $sortField = $request->get('sort', 'full_name');
        $sortDirection = $request->get('direction', 'asc');

        if ($sortField === 'full_name') {
            $employeeQuery->orderBy('full_name', $sortDirection);
        } elseif ($sortField === 'position') {
            $employeeQuery->orderBy('position', $sortDirection);
        } else {
            $employeeQuery->orderBy('full_name', $sortDirection);
        }

        $employees = $employeeQuery->get();
        $employeeIds = $employees->pluck('id');

        $startDate = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        // Fetch all employee shifts for the period for all filtered employees
        $allEmployeeShifts = \App\Models\EmployeeShift::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('schedule')
            ->get()
            ->groupBy(function($shift) {
                return $shift->employee_id . '_' . $shift->date->format('Y-m-d');
            });

        // Fetch all attendances for the period for all filtered employees
        $allAttendances = Attendance::whereIn('attendable_id', $employeeIds)
            ->where('attendable_type', Employee::class)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function($attendance) {
                return $attendance->attendable_id . '_' . $attendance->date->format('Y-m-d');
            });

        // Fetch all approved permissions for the period for all filtered employees
        $allPermissions = \App\Models\Permission::whereIn('permissible_id', $employeeIds)
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
            ->get()
            ->groupBy('permissible_id');

        $permissionTypesFull = [
            'C' => 'Cuti',
            'S' => 'Sakit',
            'DL' => 'Dinas Luar',
        ];

        $transportAllowanceData = [];

        foreach ($employees as $employee) {
            $employeeId = $employee->id;
            $salary = $employee->salary;

            if (!$salary) {
                continue;
            }

            $employeePermissions = $allPermissions->get($employeeId, collect());

            $permissionMap = [];
            foreach ($employeePermissions as $permission) {
                $current = Carbon::parse($permission->start_date)->max($startDate);
                $end = Carbon::parse($permission->end_date)->min($endDate);
                while ($current->lte($end)) {
                    $permissionMap[$current->format('Y-m-d')] = $permissionTypesFull[$permission->type] ?? $permission->type;
                    $current->addDay();
                }
            }

            $transportAllowanceSummary = [
                'employee' => $employee,
                'active_days' => 0,
                'present_days' => 0,
                'absent_days' => 0,
                'sick_days' => 0,
                'leave_days' => 0,
                'official_duty_days' => 0,
                'total_transport_allowance' => 0, // Calculated below
            ];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = Carbon::create($selectedYear, $selectedMonth, $day);
                $dateKey = $currentDate->format('Y-m-d');
                $lookupKey = $employeeId . '_' . $dateKey;

                $shift = $allEmployeeShifts->get($lookupKey)?->first();
                $attendance = $allAttendances->get($lookupKey)?->first();
                $permissionType = $permissionMap[$dateKey] ?? null;

                if ($permissionType) {
                    if ($permissionType === 'Sakit') $transportAllowanceSummary['sick_days']++;
                    if ($permissionType === 'Cuti') $transportAllowanceSummary['leave_days']++;
                    if ($permissionType === 'Dinas Luar') $transportAllowanceSummary['official_duty_days']++;
                }

                // Hari Aktif (Shift) - menghitung dari hari yang diisikan nama shiftnya di /admin/employee-shifts
                // HANYA menghitung dari EmployeeShift yang di-assign, BUKAN dari default schedule
                $hasValidShift = $shift && $shift->schedule && !empty($shift->schedule->shift_name);

                if ($hasValidShift) {
                    $transportAllowanceSummary['active_days']++;
                }

                // Process attendance data only if there's no permission for this day
                if (!$permissionType) {
                    // Hanya menghitung untuk hari yang memiliki nama shift di /admin/employee-shifts
                    if ($hasValidShift) {
                        if ($attendance) {
                            // Hari Hadir - melihat dari hari yang HANYA memiliki nama shift di /admin/employee-shifts
                            // dan melihat terisinya kolom absensi masuk ATAU kolom absensi pulang sesuai data di /admin/reports
                            if ($attendance->clock_in || $attendance->clock_out) {
                                $transportAllowanceSummary['present_days']++;
                            } else {
                                // Tidak Hadir - melihat dari hari yang HANYA memiliki nama shift di /admin/employee-shifts
                                // dan melihat kolom kosong absensi masuk DAN kolom absensi pulang kosong sesuai data di /admin/reports
                                $transportAllowanceSummary['absent_days']++;
                            }
                        } else {
                            // Jika tidak ada record attendance sama sekali, berarti tidak hadir
                            // Tidak Hadir - hari memiliki shift di /admin/employee-shifts tapi tidak ada data absensi
                            $transportAllowanceSummary['absent_days']++;
                        }
                    }
                    // Jika tidak ada shift di /admin/employee-shifts untuk hari ini, tidak ikut terhitung
                } else {
                    // Untuk hari dengan perizinan, tidak menghitung attendance metrics
                    // Perizinan (Sakit, Cuti, Dinas Luar) sudah dihitung di atas
                }
            }

            $transportAllowanceData[$employeeId] = $transportAllowanceSummary;
        }

        // After collecting all data, calculate total transport allowance for each employee
        foreach ($transportAllowanceData as &$data) {
            $employee = $data['employee'];
            $salary = $employee->salary;

            if ($salary) {
                // Total transport allowance = daily transport allowance × number of present days
                $data['total_transport_allowance'] = $salary->transport_allowance * $data['present_days'];
            } else {
                $data['total_transport_allowance'] = 0;
            }
        }

        // Sort the final data array based on the sort parameters
        $sortField = $request->get('sort', 'full_name');
        $sortDirection = $request->get('direction', 'asc');

        $transportAllowanceData = collect($transportAllowanceData)->sortBy(function($data) use ($sortField) {
            switch ($sortField) {
                case 'position':
                    return strtolower($data['employee']->position ?? '');
                case 'total_transport_allowance':
                    return $data['total_transport_allowance'];
                case 'full_name':
                default:
                    return strtolower($data['employee']->full_name ?? '');
            }
        }, SORT_REGULAR, $sortDirection === 'desc');

        $transportAllowanceData = $transportAllowanceData->values()->all();

        // Get all employees for dropdown - only show active employees
        $allEmployees = Employee::where('is_active', true)->orderBy('full_name')->get();

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

        // Get unique years from attendance records
        $yearsFromAttendance = Attendance::where('attendable_type', Employee::class)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->pluck('year')
            ->map(fn($year) => (int)$year);

        // Determine min and max year for the dropdown
        $currentYear = (int)Carbon::now()->year;
        $minYear = $yearsFromAttendance->min();

        if ($minYear === null || $minYear > $currentYear) {
            $minYear = $currentYear - 2;
        } else {
            $minYear = min($minYear, $currentYear - 2);
        }

        $years = collect(range($currentYear, $minYear))->sortDesc()->values();
        $years->prepend($currentYear + 1);
        $years = $years->unique()->sortDesc()->values();

        if ($years->isEmpty()) {
            $years = collect([$currentYear]);
        }

        return view('admin.reports.transport-allowance', compact('transportAllowanceData', 'allEmployees', 'months', 'years', 'selectedMonth', 'selectedYear'));
    }

    public function downloadTransportAllowancePdf(Request $request)
    {
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);

        $employeeQuery = Employee::with(['salary' => function($query) use ($selectedMonth, $selectedYear) {
            $query->where('month', $selectedMonth)
                  ->where('year', $selectedYear)
                  ->latest('created_at');
        }])->where('is_active', true);

        if ($request->filled('employee_id')) {
            $employeeQuery->where('id', $request->employee_id);
        }

        $employees = $employeeQuery->orderBy('full_name')->get();
        $employeeIds = $employees->pluck('id');

        $startDate = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        // Fetch all employee shifts for the period for all filtered employees
        $allEmployeeShifts = \App\Models\EmployeeShift::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('schedule')
            ->get()
            ->groupBy(function($shift) {
                return $shift->employee_id . '_' . $shift->date->format('Y-m-d');
            });

        // Fetch all attendances for the period for all filtered employees
        $allAttendances = Attendance::whereIn('attendable_id', $employeeIds)
            ->where('attendable_type', Employee::class)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function($attendance) {
                return $attendance->attendable_id . '_' . $attendance->date->format('Y-m-d');
            });

        // Fetch all approved permissions for the period for all filtered employees
        $allPermissions = \App\Models\Permission::whereIn('permissible_id', $employeeIds)
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
            ->get()
            ->groupBy('permissible_id');

        $permissionTypesFull = [
            'C' => 'Cuti',
            'S' => 'Sakit',
            'DL' => 'Dinas Luar',
        ];

        $transportAllowanceData = [];

        foreach ($employees as $employee) {
            $employeeId = $employee->id;
            $salary = $employee->salary;

            if (!$salary) {
                continue;
            }

            $employeePermissions = $allPermissions->get($employeeId, collect());

            $permissionMap = [];
            foreach ($employeePermissions as $permission) {
                $current = Carbon::parse($permission->start_date)->max($startDate);
                $end = Carbon::parse($permission->end_date)->min($endDate);
                while ($current->lte($end)) {
                    $permissionMap[$current->format('Y-m-d')] = $permissionTypesFull[$permission->type] ?? $permission->type;
                    $current->addDay();
                }
            }

            $transportAllowanceSummary = [
                'employee' => $employee,
                'active_days' => 0,
                'present_days' => 0,
                'absent_days' => 0,
                'sick_days' => 0,
                'leave_days' => 0,
                'official_duty_days' => 0,
                'total_transport_allowance' => 0,
            ];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = Carbon::create($selectedYear, $selectedMonth, $day);
                $dateKey = $currentDate->format('Y-m-d');
                $lookupKey = $employeeId . '_' . $dateKey;

                $shift = $allEmployeeShifts->get($lookupKey)?->first();
                $attendance = $allAttendances->get($lookupKey)?->first();
                $permissionType = $permissionMap[$dateKey] ?? null;

                if ($permissionType) {
                    if ($permissionType === 'Sakit') $transportAllowanceSummary['sick_days']++;
                    if ($permissionType === 'Cuti') $transportAllowanceSummary['leave_days']++;
                    if ($permissionType === 'Dinas Luar') $transportAllowanceSummary['official_duty_days']++;
                }

                $hasValidShift = $shift && $shift->schedule && !empty($shift->schedule->shift_name);

                if ($hasValidShift) {
                    $transportAllowanceSummary['active_days']++;
                }

                if (!$permissionType) {
                    if ($hasValidShift) {
                        if ($attendance) {
                            if ($attendance->clock_in || $attendance->clock_out) {
                                $transportAllowanceSummary['present_days']++;
                            } else {
                                $transportAllowanceSummary['absent_days']++;
                            }
                        } else {
                            $transportAllowanceSummary['absent_days']++;
                        }
                    }
                }
            }

            $transportAllowanceSummary['total_transport_allowance'] = $salary->transport_allowance * $transportAllowanceSummary['present_days'];
            $transportAllowanceData[] = $transportAllowanceSummary;
        }

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $data = [
            'transportAllowanceData' => $transportAllowanceData,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'monthName' => $months[$selectedMonth],
            'headerSettings' => $this->getHeaderSettings(),
        ];

        $pdf = Pdf::loadView('admin.reports.transport-allowance-pdf', $data)
            ->setPaper('a4', 'portrait');

        $filename = 'laporan_uang_transport_' . $months[$selectedMonth] . '_' . $selectedYear . '.pdf';
        return $pdf->download($filename);
    }

    public function transportAllowanceDetail(Request $request, Employee $employee, $year, $month)
    {
        $data = $this->getTransportAllowanceDetailData($employee, $year, $month);
        return view('admin.reports.transport-allowance-detail', $data);
    }

    public function generateTransportAllowancePdf(Request $request, Employee $employee, $year, $month)
    {
        $data = $this->getTransportAllowanceDetailData($employee, $year, $month);
        $data['headerSettings'] = $this->getHeaderSettings();

        $pdf = Pdf::loadView('admin.reports.transport-allowance-detail-pdf', $data)
            ->setPaper('a4', 'portrait');

        $filename = 'laporan_uang_transport_' . str_replace(' ', '_', $employee->full_name) . '_' . Carbon::create($year, $month)->isoFormat('MMMM_YYYY') . '.pdf';
        return $pdf->download($filename);
    }

    private function getTransportAllowanceDetailData(Employee $employee, $year, $month)
    {
        $employee->load(['salary' => function($query) {
            $query->latest('created_at');
        }]);
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
        $totalTransportAllowance = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::create($year, $month, $day);
            $dateKey = $currentDate->format('Y-m-d');

            $attendance = $attendances->get($dateKey);
            $employeeShift = $employeeShifts->get($dateKey);
            $schedule = $employeeShift ? $employeeShift->schedule : null;
            $permissionType = $permissionMap[$dateKey] ?? null;

            $dailyTransportAllowance = 0;
            $statusIn = '-';
            $statusOut = '-';

            if ($permissionType) {
                // Handle permission day first
                $statusIn = $permissionType;
                $statusOut = $permissionType;
            } else {
                // If there's no permission, grant allowance simply if there is a clock-in or clock-out record, regardless of schedule.
                if ($attendance && ($attendance->clock_in !== null || $attendance->clock_out !== null)) {
                    $dailyTransportAllowance = $salary->transport_allowance;
                    $totalTransportAllowance += $dailyTransportAllowance;
                }

                // Keep the original status display logic which depends on schedule, but the allowance calculation is now independent of it.
                if ($schedule) {
                    if ($attendance) {
                        if ($attendance->clock_in) {
                            $diffIn = $this->calculateTimeDifference($attendance->clock_in, $schedule->start_time);
                            $statusIn = ($diffIn['seconds'] > 0) ? 'Terlambat' : 'Tepat Waktu';
                        } else {
                            $statusIn = 'Tidak Hadir';
                        }

                        if ($attendance->clock_out) {
                            $diffOut = $this->calculateTimeDifference($attendance->clock_out, $schedule->end_time);
                            $statusOut = ($diffOut['seconds'] < 0) ? 'Pulang Awal' : 'Tepat Waktu';
                        } else {
                            $statusOut = 'Belum Pulang';
                        }
                    } else {
                        $statusIn = 'Tidak Hadir';
                        $statusOut = 'Tidak Hadir';
                    }
                } else {
                     if ($attendance && ($attendance->clock_in || $attendance->clock_out)) {
                         $statusIn = 'Hadir';
                         $statusOut = 'Pulang';
                    } else {
                         $statusIn = 'Hari Libur';
                         $statusOut = 'Hari Libur';
                    }
                }
            }

            $detailData[] = [
                'date' => $currentDate,
                'attendance' => $attendance,
                'schedule' => $schedule,
                'status_in' => $statusIn,
                'status_out' => $statusOut,
                'daily_transport_allowance' => $dailyTransportAllowance,
            ];
        }

        $summary = [
            'total_transport_allowance' => $totalTransportAllowance,
        ];

        return compact('employee', 'year', 'month', 'detailData', 'summary', 'permissionMap');
    }

    public function performanceAllowanceDetail(Request $request, Employee $employee, $year, $month)
    {
        $data = $this->getPerformanceAllowanceDetailData($employee, $year, $month);
        return view('admin.reports.performance-allowance-detail', $data);
    }

    private function getPerformanceAllowanceDetailData(Employee $employee, $year, $month)
    {
        $employee->load(['salary' => function($query) {
            $query->latest('created_at');
        }]);
        $salary = $employee->salary;

        if (!$salary) {
            return [
                'employee' => $employee,
                'year' => $year,
                'month' => $month,
                'detailData' => [],
                'summary' => null,
                'permissionMap' => [],
            ];
        }

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        $employeeShifts = \App\Models\EmployeeShift::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('schedule')
            ->get()
            ->keyBy(function ($shift) {
                return $shift->date->format('Y-m-d');
            });

        $attendances = \App\Models\Attendance::where('attendable_id', $employee->id)
            ->where('attendable_type', Employee::class)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

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

        $permissionMap = [];
        $permissionTypesFull = ['C' => 'Cuti', 'S' => 'Sakit', 'DL' => 'Dinas Luar'];
        foreach ($permissions as $permission) {
            $current = Carbon::parse($permission->start_date)->max($startDate);
            $end = Carbon::parse($permission->end_date)->min($endDate);
            while ($current->lte($end)) {
                $permissionMap[$current->format('Y-m-d')] = $permissionTypesFull[$permission->type] ?? $permission->type;
                $current->addDay();
            }
        }

        $detailData = [];
        $totalPerformanceAllowance = 0;
        $totalMealAllowance = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::create($year, $month, $day);
            $dateKey = $currentDate->format('Y-m-d');

            $shift = $employeeShifts->get($dateKey);
            $attendance = $attendances->get($dateKey);
            $permissionType = $permissionMap[$dateKey] ?? null;

            $dailyPerformanceAllowance = 0;
            $dailyMealAllowance = 0;

            $statusIn = '-';
            $statusOut = '-';

            if ($permissionType) {
                $statusIn = $permissionType;
                $statusOut = $permissionType;
            } elseif ($shift) {
                if ($attendance) {
                    if ($attendance->clock_in) {
                        $statusIn = $attendance->status_in ?? 'Tepat Waktu';
                    } else {
                        $statusIn = 'Tidak Hadir';
                    }

                    if ($attendance->clock_out) {
                        $statusOut = $attendance->status_out ?? 'Tepat Waktu';
                    } else {
                        $statusOut = 'Belum Pulang';
                    }

                    $isPresent = $attendance->clock_in !== null || $attendance->clock_out !== null;
                    if ($isPresent) {
                        $dailyMealAllowance = $salary->meal_allowance;
                        $totalMealAllowance += $dailyMealAllowance;

                        // Check if both arrival and departure are on time for performance allowance
                        $isOnTimeIn = $attendance->status_in === 'Tepat Waktu';
                        $isOnTimeOut = $attendance->status_out === 'Tepat Waktu';
                        if ($isOnTimeIn && $isOnTimeOut) {
                            $dailyPerformanceAllowance = $salary->performance_allowance;
                            $totalPerformanceAllowance += $dailyPerformanceAllowance;
                        }
                    }
                } else {
                    $statusIn = 'Tidak Hadir';
                    $statusOut = 'Tidak Hadir';
                }
            } else {
                $statusIn = 'Hari Libur';
                $statusOut = 'Hari Libur';
            }

            $detailData[] = [
                'date' => $currentDate,
                'attendance' => $attendance,
                'shift' => $shift,
                'schedule' => $shift ? $shift->schedule : null,
                'status_in' => $statusIn,
                'status_out' => $statusOut,
                'daily_performance_allowance' => $dailyPerformanceAllowance,
                'daily_meal_allowance' => $dailyMealAllowance,
            ];
        }

        $summary = [
            'total_performance_allowance' => $totalPerformanceAllowance,
            'total_meal_allowance' => $totalMealAllowance,
        ];

        return compact('employee', 'year', 'month', 'detailData', 'summary', 'permissionMap');
    }

    public function generatePerformanceAllowancePdf(Request $request, Employee $employee, $year, $month)
    {
        $data = $this->getPerformanceAllowanceDetailData($employee, $year, $month);
        $data['headerSettings'] = $this->getHeaderSettings();

        $pdf = Pdf::loadView('admin.reports.performance-allowance-detail-pdf', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->download("laporan-tunjangan-kinerja-{$employee->id}-{$year}-{$month}.pdf");
    }

    /**
     * Get header settings for PDF exports
     */
    private function getHeaderSettings()
    {
        $schoolName = AttendanceSetting::where('setting_key', 'school_name')->first();
        $schoolFullName = AttendanceSetting::where('setting_key', 'school_full_name')->first();
        $schoolAddress = AttendanceSetting::where('setting_key', 'school_address')->first();
        $schoolPhone = AttendanceSetting::where('setting_key', 'school_phone')->first();
        $schoolEmail = AttendanceSetting::where('setting_key', 'school_email')->first();
        $leftLogoPath = AttendanceSetting::where('setting_key', 'left_logo_path')->first();
        $rightLogoPath = AttendanceSetting::where('setting_key', 'right_logo_path')->first();
        $headmasterName = AttendanceSetting::where('setting_key', 'headmaster_name')->first();
        $headmasterNip = AttendanceSetting::where('setting_key', 'headmaster_nip')->first();
        $adminTataUsahaName = AttendanceSetting::where('setting_key', 'admin_tata_usaha_name')->first();
        $adminTataUsahaNip = AttendanceSetting::where('setting_key', 'admin_tata_usaha_nip')->first();
        $activeSigner = AttendanceSetting::where('setting_key', 'active_signer')->first();

        // Convert logos to base64 for PDF
        $leftLogoBase64 = '';
        $rightLogoBase64 = '';

        if ($leftLogoPath && $leftLogoPath->setting_value) {
            $fullPath = public_path($leftLogoPath->setting_value);
            if (file_exists($fullPath)) {
                $imageData = base64_encode(file_get_contents($fullPath));
                $imageType = pathinfo($fullPath, PATHINFO_EXTENSION);
                $leftLogoBase64 = 'data:image/' . $imageType . ';base64,' . $imageData;
            }
        }

        if ($rightLogoPath && $rightLogoPath->setting_value) {
            $fullPath = public_path($rightLogoPath->setting_value);
            if (file_exists($fullPath)) {
                $imageData = base64_encode(file_get_contents($fullPath));
                $imageType = pathinfo($fullPath, PATHINFO_EXTENSION);
                $rightLogoBase64 = 'data:image/' . $imageType . ';base64,' . $imageData;
            }
        }

        $activeSignerValue = $activeSigner ? $activeSigner->setting_value : 'headmaster';
        $headmasterNameVal = $headmasterName ? $headmasterName->setting_value : '';
        $headmasterNipVal = $headmasterNip ? $headmasterNip->setting_value : '';
        $tataUsahaNameVal = $adminTataUsahaName ? $adminTataUsahaName->setting_value : '';
        $tataUsahaNipVal = $adminTataUsahaNip ? $adminTataUsahaNip->setting_value : '';

        // Determine active signer display values
        if ($activeSignerValue === 'tata_usaha') {
            $activeSignerName = $tataUsahaNameVal ?: '................................';
            $activeSignerNip = $tataUsahaNipVal ?: '................................';
            $activeSignerTitle = 'Kepala Tata Usaha';
        } else {
            $activeSignerName = $headmasterNameVal ?: '................................';
            $activeSignerNip = $headmasterNipVal ?: '................................';
            $activeSignerTitle = 'Kepala Madrasah';
        }

        return [
            'school_name' => $schoolName ? $schoolName->setting_value : 'MTsN 2 KOTA MALANG',
            'school_full_name' => $schoolFullName ? $schoolFullName->setting_value : 'MADRASAH TSANAWIYAH NEGERI 2 KOTA MALANG',
            'school_address' => $schoolAddress ? $schoolAddress->setting_value : 'Jl. KH. Hasyim Asy\'ari No. 1, Kota Malang, Jawa Timur 65144',
            'school_phone' => $schoolPhone ? $schoolPhone->setting_value : '(0341) 123456',
            'school_email' => $schoolEmail ? $schoolEmail->setting_value : 'info@mtsn2kotamalang.sch.id',
            'left_logo' => $leftLogoBase64,
            'right_logo' => $rightLogoBase64,
            'headmaster_name' => $headmasterNameVal,
            'headmaster_nip' => $headmasterNipVal,
            'admin_tata_usaha_name' => $tataUsahaNameVal,
            'admin_tata_usaha_nip' => $tataUsahaNipVal,
            'active_signer' => $activeSignerValue,
            'active_signer_name' => $activeSignerName,
            'active_signer_nip' => $activeSignerNip,
            'active_signer_title' => $activeSignerTitle,
        ];
    }

    public function toggleSalaryMenuVisibility(Request $request)
    {
        $showSalaryMenu = $request->input('show_salary_menu', false);

        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'show_salary_menu_in_employee'],
            [
                'setting_value' => $showSalaryMenu ? 'true' : 'false',
                'description' => 'Tampilkan menu laporan gaji di sidebar pegawai'
            ]
        );

        return response()->json([
            'success' => true,
            'message' => $showSalaryMenu ? 'Menu laporan gaji ditampilkan di sidebar pegawai' : 'Menu laporan gaji disembunyikan dari sidebar pegawai'
        ]);
    }

    private function calculateTimeDifference($actualTime, $scheduleTime)
    {
        $actual = Carbon::parse($actualTime);
        $scheduled = Carbon::parse($scheduleTime)->setDate($actual->year, $actual->month, $actual->day);
        // Fixed: Calculate difference as scheduled - actual for correct sign
        // Positive = late (actual > scheduled), Negative = early (actual < scheduled)
        $diffSeconds = $scheduled->diffInSeconds($actual, false);

        return ['seconds' => $diffSeconds];
    }
}
