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
use App\Services\EmployeeAllowanceCalculator;
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
            'generatedAt' => now()->format('d/m/Y H:i:s'),
        ];

        $pdf = Pdf::loadView('admin.reports.pdf', $data)->setPaper('a4', 'landscape');
        $filename = 'laporan_absensi_siswa_' . date('YmdHis') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Rows for the employee attendance PDF, with the same schedule and status
     * as the /admin/reports screen.
     */
    private function processAttendances($attendances)
    {
        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        return $attendances->map(function($attendance) use ($dayNames) {
            $schedule = EmployeeAllowanceCalculator::scheduleFor($attendance->attendable_id, $attendance->date);
            $permissions = EmployeeAllowanceCalculator::PERMISSION_LABELS;

            if (isset($permissions[$attendance->status_in ?? ''])) {
                $statusIn = $permissions[$attendance->status_in];
            } elseif (!$attendance->clock_in) {
                $statusIn = 'Tidak Hadir';
            } else {
                $statusIn = $schedule ? EmployeeAllowanceCalculator::arrivalStatus($attendance, $schedule) : '-';
            }

            if (isset($permissions[$attendance->status_out ?? ''])) {
                $statusOut = $permissions[$attendance->status_out];
            } elseif (!$attendance->clock_out) {
                $statusOut = 'Belum Pulang';
            } else {
                $statusOut = $schedule ? EmployeeAllowanceCalculator::departureStatus($attendance, $schedule) : '-';
            }

            return [
                'attendable_id' => $attendance->attendable_id,
                'date' => $attendance->date ? $attendance->date->format('d/m/Y') : '-',
                'day' => $attendance->date ? $dayNames[$attendance->date->dayOfWeek] : '-',
                'schedule_start' => $schedule ? Carbon::parse($schedule->start_time)->format('H:i:s') : '-',
                'schedule_end' => $schedule ? Carbon::parse($schedule->end_time)->format('H:i:s') : '-',
                'clock_in' => $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-',
                'clock_out' => $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '-',
                'status_in' => $statusIn,
                'status_out' => $statusOut,
            ];
        });
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

        $mealAllowanceData = EmployeeAllowanceCalculator::rows($employees, (int) $selectedYear, (int) $selectedMonth);

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

        $mealAllowanceData = EmployeeAllowanceCalculator::rows($employees, (int) $selectedYear, (int) $selectedMonth);

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

        $performanceData = EmployeeAllowanceCalculator::rows($employees, (int) $selectedYear, (int) $selectedMonth);

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

        $performanceData = EmployeeAllowanceCalculator::rows($employees, (int) $selectedYear, (int) $selectedMonth);

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

        $salaryData = EmployeeAllowanceCalculator::rows($employees, (int) $selectedYear, (int) $selectedMonth);

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

        $salaryData = EmployeeAllowanceCalculator::rows($employees, (int) $selectedYear, (int) $selectedMonth);

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
        $salary = EmployeeAllowanceCalculator::salaryFor($employee, (int) $year, (int) $month);

        if (!$salary) {
            abort(404, 'Pengaturan gaji untuk pegawai ini tidak ditemukan.');
        }

        $loaded = EmployeeAllowanceCalculator::loadMonth([$employee->id], (int) $year, (int) $month)[$employee->id];
        $result = EmployeeAllowanceCalculator::summarize($salary, (int) $year, (int) $month, $loaded);

        $detailData = $result['days'];
        $permissionMap = $loaded['permissions'];
        $summary = [
            'base_salary' => $result['base_salary'],
            'total_meal_allowance' => $result['total_meal_allowance'],
            'total_transport_allowance' => $result['total_transport_allowance'],
            'total_performance_allowance' => $result['total_performance_allowance'],
            'total_salary' => $result['total_salary'],
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
        $salary = EmployeeAllowanceCalculator::salaryFor($employee, (int) $year, (int) $month);

        if (!$salary) {
            abort(404, 'Pengaturan gaji untuk pegawai ini tidak ditemukan.');
        }

        $loaded = EmployeeAllowanceCalculator::loadMonth([$employee->id], (int) $year, (int) $month)[$employee->id];
        $result = EmployeeAllowanceCalculator::summarize($salary, (int) $year, (int) $month, $loaded);

        $detailData = $result['days'];
        $permissionMap = $loaded['permissions'];
        $summary = [
            'total_meal_allowance' => $result['total_meal_allowance'],
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

        $transportAllowanceData = EmployeeAllowanceCalculator::rows($employees, (int) $selectedYear, (int) $selectedMonth);

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

        $transportAllowanceData = EmployeeAllowanceCalculator::rows($employees, (int) $selectedYear, (int) $selectedMonth);

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
        $salary = EmployeeAllowanceCalculator::salaryFor($employee, (int) $year, (int) $month);

        if (!$salary) {
            abort(404, 'Pengaturan gaji untuk pegawai ini tidak ditemukan.');
        }

        $loaded = EmployeeAllowanceCalculator::loadMonth([$employee->id], (int) $year, (int) $month)[$employee->id];
        $result = EmployeeAllowanceCalculator::summarize($salary, (int) $year, (int) $month, $loaded);

        $detailData = $result['days'];
        $permissionMap = $loaded['permissions'];
        $summary = [
            'total_transport_allowance' => $result['total_transport_allowance'],
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
        $salary = EmployeeAllowanceCalculator::salaryFor($employee, (int) $year, (int) $month);

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

        $loaded = EmployeeAllowanceCalculator::loadMonth([$employee->id], (int) $year, (int) $month)[$employee->id];
        $result = EmployeeAllowanceCalculator::summarize($salary, (int) $year, (int) $month, $loaded);

        $detailData = $result['days'];
        $permissionMap = $loaded['permissions'];
        $summary = [
            'total_performance_allowance' => $result['total_performance_allowance'],
            'total_meal_allowance' => $result['total_meal_allowance'],
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
        return \App\Services\ReportHeader::settings();
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
}
