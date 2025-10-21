<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Student;
use App\Models\Permission;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function adminDashboard()
    {
        $today = Carbon::today();

        $totalEmployees = Cache::remember('active_employee_count', 300, function () {
            return Employee::where('is_active', true)->count();
        });
        
        $totalStudents = Cache::remember('active_student_count', 300, function () {
            return Student::where('is_active', true)->count();
        });

        $todayEmployeePresent = Cache::remember("today_employee_attendance_{$today}", 300, function () use ($today) {
            return Attendance::where('attendable_type', Employee::class)
                ->whereDate('date', $today)
                ->whereNotNull('clock_in')
                ->count();
        });

        $todayStudentPresent = Cache::remember("today_student_attendance_{$today}", 300, function () use ($today) {
            return Attendance::where('attendable_type', Student::class)
                ->whereDate('date', $today)
                ->whereNotNull('clock_in')
                ->count();
        });

        $pendingPermissions = Permission::where('status', 'pending')->count();
        
        // Additional data for the admin dashboard
        $currentAcademicYear = \App\Models\AcademicYear::where('status', 'active')->first();
        if (!$currentAcademicYear) {
            $currentAcademicYear = \App\Models\AcademicYear::orderBy('year', 'desc')->first();
        }
        $totalMonitoringUsers = \App\Models\User::whereHas('role', function($query) {
            $query->where('name', 'monitoring');
        })->count();
        
        $totalGrades = \App\Models\Grade::count();
        $totalRombels = \App\Models\Rombel::count();
        $totalSchedules = \App\Models\Schedule::count();
        
        // Count backup files from storage
        $backupCount = 0;
        if (Storage::disk('local')->exists('backups')) {
            $backupCount = count(Storage::disk('local')->files('backups'));
        }

        return view('admin.dashboard', compact(
            'totalEmployees',
            'totalStudents',
            'todayEmployeePresent',
            'todayStudentPresent',
            'pendingPermissions',
            'currentAcademicYear',
            'totalMonitoringUsers',
            'totalGrades',
            'totalRombels',
            'totalSchedules',
            'backupCount'
        ));
    }

    public function employeeDashboard()
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        $thisMonth = Carbon::now();
        $attendanceCount = Attendance::where('attendable_type', Employee::class)
            ->where('attendable_id', $employee->id)
            ->whereMonth('date', $thisMonth->month)
            ->whereYear('date', $thisMonth->year)
            ->count();

        $todayAttendance = Attendance::where('attendable_type', Employee::class)
            ->where('attendable_id', $employee->id)
            ->whereDate('date', Carbon::today())
            ->first();

        // Get latest permission request for the employee
        $latestPermission = Permission::where('permissible_type', Employee::class)
            ->where('permissible_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Get employee shifts for the current month
        $startDate = Carbon::create($thisMonth->year, $thisMonth->month, 1)->startOfMonth();
        $endDate = Carbon::create($thisMonth->year, $thisMonth->month, 1)->endOfMonth();
        
        $employeeShifts = \App\Models\EmployeeShift::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->where('is_active', true)
            ->with(['schedule:id,shift_name,shift_type,start_time,end_time']) // Eager load specific schedule fields
            ->get()
            ->keyBy(function ($shift) {
                return $shift->date->format('Y-m-d'); // Group by date in Y-m-d format
            });

        return view('employee.dashboard', compact(
            'employee', 
            'attendanceCount', 
            'todayAttendance', 
            'latestPermission',
            'employeeShifts'
        ));
    }

    public function monitoringDashboard()
    {
        $today = Carbon::today();

        $totalEmployees = Cache::remember('active_employee_count', 300, function () {
            return Employee::where('is_active', true)->count();
        });
        
        $totalStudents = Cache::remember('active_student_count', 300, function () {
            return Student::where('is_active', true)->count();
        });

        $todayEmployeePresent = Cache::remember("today_employee_attendance_{$today}", 300, function () use ($today) {
            return Attendance::where('attendable_type', Employee::class)
                ->whereDate('date', $today)
                ->whereNotNull('clock_in')
                ->count();
        });

        $todayStudentPresent = Cache::remember("today_student_attendance_{$today}", 300, function () use ($today) {
            return Attendance::where('attendable_type', Student::class)
                ->whereDate('date', $today)
                ->whereNotNull('clock_in')
                ->count();
        });

        $pendingPermissions = Permission::where('status', 'pending')
            ->where('permissible_type', Employee::class)
            ->count();
            
        // Additional data for monitoring dashboard
        $pendingStudentPermissions = Permission::where('status', 'pending')
            ->where('permissible_type', Student::class)
            ->count();
            
        $totalPermissionsToday = Permission::whereDate('created_at', $today)->count();
        
        // Additional data for monitoring dashboard (similar to admin)
        $currentAcademicYear = \App\Models\AcademicYear::where('status', 'active')->first();
        if (!$currentAcademicYear) {
            $currentAcademicYear = \App\Models\AcademicYear::orderBy('year', 'desc')->first();
        }
        
        $totalMonitoringUsers = \App\Models\User::whereHas('role', function($query) {
            $query->where('name', 'monitoring');
        })->count();
        
        $totalGrades = \App\Models\Grade::count();
        $totalRombels = \App\Models\Rombel::count();
        $totalSchedules = \App\Models\Schedule::count();
        
        // Count backup files from storage
        $backupCount = 0;
        if (Storage::disk('local')->exists('backups')) {
            $backupCount = count(Storage::disk('local')->files('backups'));
        }

        $overallAttendanceRate = number_format((($todayEmployeePresent + $todayStudentPresent) / max(($totalEmployees + $totalStudents), 1)) * 100, 1);

        return view('monitoring.dashboard', compact(
            'totalEmployees',
            'totalStudents',
            'todayEmployeePresent',
            'todayStudentPresent',
            'pendingPermissions',
            'pendingStudentPermissions',
            'totalPermissionsToday',
            'currentAcademicYear',
            'totalMonitoringUsers',
            'totalGrades',
            'totalRombels',
            'totalSchedules',
            'backupCount',
            'overallAttendanceRate'
        ));
    }
}
