<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Student;
use App\Models\Schedule;
use Carbon\Carbon;

class AttendanceMonitorController extends Controller
{
    public function employees(Request $request)
    {
        $query = Attendance::with('attendable')
            ->where('attendable_type', Employee::class);

        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);

        // Filter by month and year.
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

        // Sorting logic for employees
        $sortField = $request->get('sort', 'date');
        $sortDirection = $request->get('direction', 'asc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        // Get pagination parameters
        $perPage = $request->get('per_page', 20); // Default to 20 items per page

        // Apply sorting based on field
        switch ($sortField) {
            case 'full_name':
            case 'position':
                // For relationship fields, we need to sort the collection
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

                // Handle pagination
                if ($perPage === 'all') {
                    // Don't paginate, return collection
                    $paginated = false;
                } else {
                    $currentPage = request()->get('page', 1);
                    $allowedPerPage = [10, 20, 30, 50, 100];
                    $perPage = in_array($perPage, $allowedPerPage) ? $perPage : 20;
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

                // Handle pagination
                if ($perPage === 'all') {
                    $paginated = false;
                } else {
                    $currentPage = request()->get('page', 1);
                    $allowedPerPage = [10, 20, 30, 50, 100];
                    $perPage = in_array($perPage, $allowedPerPage) ? $perPage : 20;
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

                // Handle pagination
                if ($perPage === 'all') {
                    $paginated = false;
                } else {
                    $currentPage = request()->get('page', 1);
                    $allowedPerPage = [10, 20, 30, 50, 100];
                    $perPage = in_array($perPage, $allowedPerPage) ? $perPage : 20;
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
        }

        // Calculate present count (has either clock_in or clock_out)
        $presentCount = (clone $query)->where(function($q) {
            $q->whereNotNull('clock_in')
              ->orWhereNotNull('clock_out');
        })->count();

        $totalEmployees = Employee::where(function($query) use ($selectedMonth, $selectedYear) {
            // Currently active employees (not soft deleted)
            $query->whereNull('deleted_at')
                  // OR employees who were soft deleted after this month (were active during this month)
                  ->orWhere(function($q) use ($selectedMonth, $selectedYear) {
                      $q->whereNotNull('deleted_at')
                        ->where('deleted_at', '>=', Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth());
                  });
        })->count();

        // Get all employees for the dropdown - only show employees who were active during this month or before
        $employees = Employee::where(function($query) use ($selectedMonth, $selectedYear) {
            // Currently active employees (not soft deleted)
            $query->whereNull('deleted_at')
                  // OR employees who were soft deleted after this month (were active during this month)
                  ->orWhere(function($q) use ($selectedMonth, $selectedYear) {
                      $q->whereNotNull('deleted_at')
                        ->where('deleted_at', '>=', Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth());
                  });
        })->orderBy('full_name')->get();

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

        // Get time difference format setting
        $timeDiffFormatSetting = \App\Models\AttendanceSetting::where('setting_key', 'time_difference_format')->first();
        $timeDiffFormat = $timeDiffFormatSetting ? $timeDiffFormatSetting->setting_value : 'time';

        return view('monitoring.employees', compact('attendances', 'totalEmployees', 'presentCount', 'employees', 'months', 'years', 'sortField', 'sortDirection', 'paginated', 'timeDiffFormat', 'selectedMonth', 'selectedYear'));
    }

    public function students(Request $request)
    {
        $query = Attendance::with(['rombel', 'attendable.rombel'])
            ->where('attendable_type', Student::class);

        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);

        // Filter by month and year.
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

        // Filter by rombel
        if ($request->filled('rombel_id')) {
            // Class at the time of attendance, not the student's current class
            $query->where('attendances.rombel_id', $request->rombel_id);
        }

        // Filter by student name
        if ($request->filled('student_name')) {
            $studentIds = Student::where('full_name', 'ILIKE', '%' . $request->student_name . '%')->pluck('id');
            $query->whereIn('attendable_id', $studentIds);
        }

        // Sorting logic for students
        $sortField = $request->get('sort', 'date');
        $sortDirection = $request->get('direction', 'asc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        // Get pagination parameters
        $perPage = $request->get('per_page', 20); // Default to 20 items per page

        // Apply sorting based on field
        switch ($sortField) {
            case 'full_name':
            case 'rombel':
                // For relationship fields, we need to sort the collection
                $attendances = $query->get()->sortBy(function($attendance) use ($sortField) {
                    if ($sortField === 'rombel') {
                        return strtolower($attendance->rombel_name ?? '');
                    }
                    return strtolower($attendance->attendable->$sortField ?? '');
                }, SORT_REGULAR, $sortDirection === 'desc');

                // If filtering by student name, sort by date ascending; otherwise, sort by name first then date
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

                // Handle pagination
                if ($perPage === 'all') {
                    // Don't paginate, return collection
                    $paginated = false;
                } else {
                    $currentPage = request()->get('page', 1);
                    $allowedPerPage = [10, 20, 30, 50, 100];
                    $perPage = in_array($perPage, $allowedPerPage) ? $perPage : 20;
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

                // Handle pagination
                if ($perPage === 'all') {
                    $paginated = false;
                } else {
                    $currentPage = request()->get('page', 1);
                    $allowedPerPage = [10, 20, 30, 50, 100];
                    $perPage = in_array($perPage, $allowedPerPage) ? $perPage : 20;
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

                // Handle pagination
                if ($perPage === 'all') {
                    $paginated = false;
                } else {
                    $currentPage = request()->get('page', 1);
                    $allowedPerPage = [10, 20, 30, 50, 100];
                    $perPage = in_array($perPage, $allowedPerPage) ? $perPage : 20;
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
        }

        $totalStudents = Student::where('is_active', true)->count();
        $presentCount = $paginated ? $attendances->where('clock_in', '!=', null)->count() : collect($attendances)->where('clock_in', '!=', null)->count();

        $rombels = \App\Models\Rombel::with('academicYear')->orderBy('name')->get();

        // Get available months/years for the dropdown
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $years = Attendance::where('attendable_type', Student::class)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('monitoring.students', compact('attendances', 'totalStudents', 'presentCount', 'rombels', 'sortField', 'sortDirection', 'paginated', 'months', 'years', 'selectedMonth', 'selectedYear'));
    }
}