<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Student;
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

        $attendances = $query->orderBy('clock_in', 'desc')->get();

        $totalEmployees = Employee::where('is_active', true)->count();
        $presentCount = $attendances->where('clock_in', '!=', null)->count();

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

        return view('monitoring.employees', compact('attendances', 'totalEmployees', 'presentCount', 'employees', 'months', 'years'));
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

        $attendances = $query->orderBy('clock_in', 'desc')->get();

        $totalStudents = Student::where('is_active', true)->count();
        $presentCount = $attendances->where('clock_in', '!=', null)->count();

        $rombels = \App\Models\Rombel::with('academicYear')->orderBy('name')->get();

        return view('monitoring.students', compact('attendances', 'totalStudents', 'presentCount', 'rombels'));
    }
}
