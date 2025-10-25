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
        $today = Carbon::today();

        $query = Attendance::with('attendable')
            ->where('attendable_type', Employee::class);

        // Filter by date range if provided, otherwise default to today
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        } elseif ($request->filled('month') && $request->filled('year')) {
            // Filter by month and year if provided
            $query->whereMonth('date', $request->month)
                  ->whereYear('date', $request->year);
        } else {
            // Default to today if no date filters
            $query->whereDate('date', $today);
        }

        // Filter by specific employee if provided
        if ($request->filled('employee_id')) {
            $query->where('attendable_id', $request->employee_id);
        }

        // Sorting logic for employees
        $sortField = $request->get('sort', 'date');
        $sortDirection = $request->get('direction', 'desc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        // Get pagination parameters
        $perPage = $request->get('per_page', 10); // Default to 10 items per page
        
        // Handle "All" option
        if ($perPage === 'all') {
            // Apply sorting based on field
            switch ($sortField) {
                case 'full_name':
                case 'position':
                    // For relationship fields, we need to sort the collection
                    $attendances = $query->get()->sortBy(function($attendance) use ($sortField, $sortDirection) {
                        return strtolower($attendance->attendable->$sortField ?? '');
                    }, SORT_REGULAR, $sortDirection === 'desc');
                    break;

                case 'date':
                    $attendances = $query->orderBy('date', $sortDirection)->orderBy('clock_in', 'desc')->get();
                    break;

                case 'clock_in':
                case 'clock_out':
                    $attendances = $query->orderBy($sortField, $sortDirection)->orderBy('date', 'desc')->get();
                    break;

                default:
                    // Default sorting by date and then by clock_in
                    $attendances = $query->orderBy('date', $sortDirection)->orderBy('clock_in', 'desc')->get();
                    break;
            }
            $paginated = false;
        } else {
            // Validate perPage to ensure it's one of the allowed values
            $allowedPerPage = [10, 20, 30, 50, 100];
            $perPage = in_array($perPage, $allowedPerPage) ? $perPage : 10;
            
            // Apply sorting based on field for paginated results
            switch ($sortField) {
                case 'full_name':
                case 'position':
                    // For relationship fields, we need to sort the collection
                    $attendances = $query->orderBy('date', $sortDirection)->paginate($perPage);
                    break;

                case 'date':
                    $attendances = $query->orderBy('date', $sortDirection)->orderBy('clock_in', 'desc')->paginate($perPage);
                    break;

                case 'clock_in':
                case 'clock_out':
                    $attendances = $query->orderBy($sortField, $sortDirection)->orderBy('date', 'desc')->paginate($perPage);
                    break;

                default:
                    // Default sorting by date and then by clock_in
                    $attendances = $query->orderBy('date', $sortDirection)->orderBy('clock_in', 'desc')->paginate($perPage);
                    break;
            }
            $paginated = true;
        }

        $totalEmployees = Employee::where('is_active', true)->count();
        $presentCount = $paginated ? $attendances->where('clock_in', '!=', null)->count() : collect($attendances)->where('clock_in', '!=', null)->count();

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

        return view('monitoring.employees', compact('attendances', 'totalEmployees', 'presentCount', 'employees', 'months', 'years', 'sortField', 'sortDirection', 'paginated'));
    }

    public function students(Request $request)
    {
        $today = Carbon::today();

        $query = Attendance::with(['attendable.rombel'])
            ->where('attendable_type', Student::class);

        // Filter by date range if provided, otherwise default to today
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        } else {
            // Default to today if no date filters
            $query->whereDate('date', $today);
        }

        // Filter by rombel
        if ($request->filled('rombel_id')) {
            $studentIds = Student::where('rombel_id', $request->rombel_id)->pluck('id');
            $query->whereIn('attendable_id', $studentIds);
        }

        // Filter by student name
        if ($request->filled('student_name')) {
            $studentIds = Student::where('full_name', 'ILIKE', '%' . $request->student_name . '%')->pluck('id');
            $query->whereIn('attendable_id', $studentIds);
        }

        // Sorting logic for students
        $sortField = $request->get('sort', 'date');
        $sortDirection = $request->get('direction', 'desc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        // Get pagination parameters
        $perPage = $request->get('per_page', 10); // Default to 10 items per page
        
        // Handle "All" option
        if ($perPage === 'all') {
            // Apply sorting based on field
            switch ($sortField) {
                case 'full_name':
                case 'rombel':
                    // For relationship fields, we need to sort the collection
                    $attendances = $query->get()->sortBy(function($attendance) use ($sortField, $sortDirection) {
                        if ($sortField === 'rombel') {
                            return strtolower($attendance->attendable->rombel->name ?? '');
                        }
                        return strtolower($attendance->attendable->$sortField ?? '');
                    }, SORT_REGULAR, $sortDirection === 'desc');
                    break;

                case 'date':
                    $attendances = $query->orderBy('date', $sortDirection)->orderBy('clock_in', 'desc')->get();
                    break;

                case 'clock_in':
                case 'clock_out':
                    $attendances = $query->orderBy($sortField, $sortDirection)->orderBy('date', 'desc')->get();
                    break;

                default:
                    // Default sorting by date and then by clock_in
                    $attendances = $query->orderBy('date', $sortDirection)->orderBy('clock_in', 'desc')->get();
                    break;
            }
            $paginated = false;
        } else {
            // Validate perPage to ensure it's one of the allowed values
            $allowedPerPage = [10, 20, 30, 50, 100];
            $perPage = in_array($perPage, $allowedPerPage) ? $perPage : 10;
            
            // Apply sorting based on field for paginated results
            switch ($sortField) {
                case 'full_name':
                case 'rombel':
                    // For relationship fields, we need to sort the collection
                    $attendances = $query->orderBy('date', $sortDirection)->paginate($perPage);
                    break;

                case 'date':
                    $attendances = $query->orderBy('date', $sortDirection)->orderBy('clock_in', 'desc')->paginate($perPage);
                    break;

                case 'clock_in':
                case 'clock_out':
                    $attendances = $query->orderBy($sortField, $sortDirection)->orderBy('date', 'desc')->paginate($perPage);
                    break;

                default:
                    // Default sorting by date and then by clock_in
                    $attendances = $query->orderBy('date', $sortDirection)->orderBy('clock_in', 'desc')->paginate($perPage);
                    break;
            }
            $paginated = true;
        }

        $totalStudents = Student::where('is_active', true)->count();
        $presentCount = $paginated ? $attendances->where('clock_in', '!=', null)->count() : collect($attendances)->where('clock_in', '!=', null)->count();

        $rombels = \App\Models\Rombel::with('academicYear')->orderBy('name')->get();

        return view('monitoring.students', compact('attendances', 'totalStudents', 'presentCount', 'rombels', 'sortField', 'sortDirection', 'paginated'));
    }
}