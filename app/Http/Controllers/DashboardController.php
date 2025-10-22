<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Student;
use App\Models\Permission;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Services\SecurityService;

class DashboardController extends Controller
{
    public function adminDashboard(SecurityService $securityService)
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

        $pendingPermissions = Cache::remember('pending_permissions_count', 60, function () {
            return Permission::where('status', 'pending')->count();
        });

        // Additional data for the admin dashboard
        $currentAcademicYear = Cache::remember('current_academic_year', 300, function () {
            $year = \App\Models\AcademicYear::where('status', 'active')->first();
            if (!$year) {
                $year = \App\Models\AcademicYear::orderBy('year', 'desc')->first();
            }
            return $year;
        });

        $totalMonitoringUsers = Cache::remember('total_monitoring_users', 300, function () {
            try {
                $monitoringRole = \App\Models\Role::where('name', 'monitoring')->first();
                return $monitoringRole ? \App\Models\User::where('role_id', $monitoringRole->id)->count() : 0;
            } catch (\Exception $e) {
                return 0;
            }
        });

        $totalGrades = Cache::remember('total_grades_count', 300, function () {
            return \App\Models\Grade::count();
        });

        $totalRombels = Cache::remember('total_rombels_count', 300, function () {
            return \App\Models\Rombel::count();
        });

        $totalSchedules = Cache::remember('total_schedules_count', 300, function () {
            return \App\Models\Schedule::count();
        });

        // Count backup files from storage (cache for 10 minutes)
        $backupCount = Cache::remember('backup_files_count', 600, function () {
            if (Storage::disk('local')->exists('backups')) {
                return count(Storage::disk('local')->files('backups'));
            }
            return 0;
        });

        // Get security data
        $securityStats = $securityService->getDashboardStats();

        // Get holidays for current month (cache for 1 hour as holidays don't change often)
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $cacheKey = "holidays_month_{$currentMonth}_{$currentYear}";

        $holidaysThisMonth = Cache::remember($cacheKey, 3600, function () use ($currentMonth, $currentYear) {
            return Holiday::whereMonth('date', $currentMonth)
                ->whereYear('date', $currentYear)
                ->where('is_active', true)
                ->orderBy('date', 'asc')
                ->get();
        });

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
            'backupCount',
            'securityStats',
            'holidaysThisMonth'
        ));
    }

    public function employeeDashboard()
    {
        $user = Auth::user();
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

        // Get holidays for current month
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $holidaysThisMonth = Holiday::whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->where('is_active', true)
            ->orderBy('date', 'asc')
            ->get();

        return view('employee.dashboard', compact(
            'employee',
            'attendanceCount',
            'todayAttendance',
            'latestPermission',
            'employeeShifts',
            'holidaysThisMonth'
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

        $pendingPermissions = Cache::remember('pending_employee_permissions_count', 60, function () {
            return Permission::where('status', 'pending')
                ->where('permissible_type', Employee::class)
                ->count();
        });

        // Additional data for monitoring dashboard
        $pendingStudentPermissions = Cache::remember('pending_student_permissions_count', 60, function () {
            return Permission::where('status', 'pending')
                ->where('permissible_type', Student::class)
                ->count();
        });

        $totalPermissionsToday = Cache::remember("total_permissions_today_{$today}", 300, function () use ($today) {
            return Permission::whereDate('created_at', $today)->count();
        });

        // Reuse same cache as admin dashboard
        $currentAcademicYear = Cache::remember('current_academic_year', 300, function () {
            $year = \App\Models\AcademicYear::where('status', 'active')->first();
            if (!$year) {
                $year = \App\Models\AcademicYear::orderBy('year', 'desc')->first();
            }
            return $year;
        });

        $totalMonitoringUsers = Cache::remember('total_monitoring_users', 300, function () {
            try {
                $monitoringRole = \App\Models\Role::where('name', 'monitoring')->first();
                return $monitoringRole ? \App\Models\User::where('role_id', $monitoringRole->id)->count() : 0;
            } catch (\Exception $e) {
                return 0;
            }
        });

        $totalGrades = Cache::remember('total_grades_count', 300, function () {
            return \App\Models\Grade::count();
        });

        $totalRombels = Cache::remember('total_rombels_count', 300, function () {
            return \App\Models\Rombel::count();
        });

        $totalSchedules = Cache::remember('total_schedules_count', 300, function () {
            return \App\Models\Schedule::count();
        });

        // Count backup files from storage (reuse same cache)
        $backupCount = Cache::remember('backup_files_count', 600, function () {
            if (Storage::disk('local')->exists('backups')) {
                return count(Storage::disk('local')->files('backups'));
            }
            return 0;
        });

        $overallAttendanceRate = number_format((($todayEmployeePresent + $todayStudentPresent) / max(($totalEmployees + $totalStudents), 1)) * 100, 1);

        // Get holidays for current month (reuse same cache)
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $cacheKey = "holidays_month_{$currentMonth}_{$currentYear}";

        $holidaysThisMonth = Cache::remember($cacheKey, 3600, function () use ($currentMonth, $currentYear) {
            return Holiday::whereMonth('date', $currentMonth)
                ->whereYear('date', $currentYear)
                ->where('is_active', true)
                ->orderBy('date', 'asc')
                ->get();
        });

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
            'overallAttendanceRate',
            'holidaysThisMonth'
        ));
    }
}
