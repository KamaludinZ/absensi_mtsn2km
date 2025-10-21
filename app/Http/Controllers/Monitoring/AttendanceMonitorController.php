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
    public function employees()
    {
        $today = Carbon::today();

        $attendances = Attendance::with('attendable')
            ->where('attendable_type', Employee::class)
            ->whereDate('date', $today)
            ->orderBy('clock_in', 'desc')
            ->get();

        $totalEmployees = Employee::where('is_active', true)->count();
        $presentCount = $attendances->where('clock_in', '!=', null)->count();

        return view('monitoring.employees', compact('attendances', 'totalEmployees', 'presentCount'));
    }

    public function students(Request $request)
    {
        $today = Carbon::today();

        $query = Attendance::with(['attendable.rombel'])
            ->where('attendable_type', Student::class)
            ->whereDate('date', $today);

        if ($request->filled('rombel_id')) {
            $studentIds = Student::where('rombel_id', $request->rombel_id)->pluck('id');
            $query->whereIn('attendable_id', $studentIds);
        }

        $attendances = $query->orderBy('clock_in', 'desc')->get();

        $totalStudents = Student::where('is_active', true)->count();
        $presentCount = $attendances->where('clock_in', '!=', null)->count();

        $rombels = \App\Models\Rombel::with('academicYear')->orderBy('name')->get();

        return view('monitoring.students', compact('attendances', 'totalStudents', 'presentCount', 'rombels'));
    }
}
