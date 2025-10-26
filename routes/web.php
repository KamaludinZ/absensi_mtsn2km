<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Student\AttendanceController as StudentAttendanceController;
use App\Http\Controllers\Employee\AttendanceController as EmployeeAttendanceController;
use App\Http\Controllers\Employee\PermissionController as EmployeePermissionController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\RombelController;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\API\ScheduleController as ApiScheduleController;
use App\Http\Controllers\Monitoring\AttendanceMonitorController;
use App\Http\Controllers\Monitoring\PermissionMonitorController;
use App\Http\Controllers\SelfieController;
use App\Http\Middleware\CheckRole;

// Public routes
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

// Public API endpoints
Route::get('/api/schedules/current-status', [ApiScheduleController::class, 'getCurrentStatus'])->middleware('throttle:30,1');
Route::get('/api/rombels/by-grade/{grade}', [RombelController::class, 'getByGrade'])->middleware('throttle:30,1');
Route::get('/api/students/by-rombel/{rombelId}', [StudentController::class, 'getByRombel'])->middleware('throttle:30,1');

// Public location settings API
Route::get('/api/location-settings/geofence-center-lat', [\App\Http\Controllers\API\LocationSettingController::class, 'getGeofenceCenterLat']);
Route::get('/api/location-settings/geofence-center-lng', [\App\Http\Controllers\API\LocationSettingController::class, 'getGeofenceCenterLng']);
Route::get('/api/location-settings/geofence-radius', [\App\Http\Controllers\API\LocationSettingController::class, 'getGeofenceRadius']);
Route::get('/api/location-settings/location-required', [\App\Http\Controllers\API\LocationSettingController::class, 'getLocationRequired']);

// Student attendance (public with rate limiting)
Route::prefix('student/attendance')->name('student.attendance.')->middleware('throttle:60,1')->group(function () {
    Route::get('/', [StudentAttendanceController::class, 'index'])->name('index');
    Route::get('/get-grades', [StudentAttendanceController::class, 'getGrades'])->name('get-grades');
    Route::get('/get-rombels/{grade}', [StudentAttendanceController::class, 'getRombels'])->name('get-rombels');
    Route::get('/get-students/{rombelId}', [StudentAttendanceController::class, 'getStudents'])->name('get-students');
    Route::post('/get-student-info', [StudentAttendanceController::class, 'getStudentInfo'])->name('get-student-info');
    Route::post('/clock-in', [StudentAttendanceController::class, 'clockIn'])->name('clock-in');
    Route::post('/clock-out', [StudentAttendanceController::class, 'clockOut'])->name('clock-out');
});

// Authentication routes (with rate limiting)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1'); // 5 attempts per minute
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Secure selfie access route - requires authentication
Route::get('/selfies/{type}/{filename}', [SelfieController::class, 'show'])
    ->middleware('auth')
    ->where('type', 'employee|student')
    ->where('filename', '.*')
    ->name('selfies.show');

// Employee routes
Route::middleware(['auth', CheckRole::class . ':pegawai'])->prefix('employee')->name('employee.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'employeeDashboard'])->name('dashboard');

    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [EmployeeAttendanceController::class, 'index'])->name('index');
        Route::post('/clock-in', [EmployeeAttendanceController::class, 'clockIn'])->name('clock-in');
        Route::post('/clock-out', [EmployeeAttendanceController::class, 'clockOut'])->name('clock-out');
        Route::get('/report', [EmployeeAttendanceController::class, 'report'])->name('report');
        Route::get('/report/export', [EmployeeAttendanceController::class, 'export'])->name('report.export');
        // Test route for geofence validation (temporary)
        Route::get('/test-geofence', [EmployeeAttendanceController::class, 'testGeofence'])->name('test-geofence');
    });

    Route::prefix('permission')->name('permission.')->group(function () {
        Route::get('/', [EmployeePermissionController::class, 'index'])->name('index');
        Route::get('/create', [EmployeePermissionController::class, 'create'])->name('create');
        Route::post('/', [EmployeePermissionController::class, 'store'])->name('store');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/change-password', [App\Http\Controllers\Employee\ProfileController::class, 'showChangePasswordForm'])->name('change-password');
        Route::put('/change-password', [App\Http\Controllers\Employee\ProfileController::class, 'updatePassword'])->name('update-password');
    });
});

// Monitoring routes
Route::middleware(['auth', CheckRole::class . ':monitoring'])->prefix('monitoring')->name('monitoring.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'monitoringDashboard'])->name('dashboard');
    Route::get('/employees', [AttendanceMonitorController::class, 'employees'])->name('employees');
    Route::get('/students', [AttendanceMonitorController::class, 'students'])->name('students');

    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionMonitorController::class, 'index'])->name('index');
        Route::get('/employees', [PermissionMonitorController::class, 'employees'])->name('employees');
        Route::get('/students', [PermissionMonitorController::class, 'students'])->name('students');
        Route::post('/{permission}/approve', [PermissionMonitorController::class, 'approve'])->name('approve');
        Route::post('/{permission}/reject', [PermissionMonitorController::class, 'reject'])->name('reject');
        Route::post('/manual', [PermissionMonitorController::class, 'createManual'])->name('manual');
    });
    
    // Profile routes for monitoring users
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/change-password', [App\Http\Controllers\Monitoring\ProfileController::class, 'showChangePasswordForm'])->name('change-password');
        Route::put('/change-password', [App\Http\Controllers\Monitoring\ProfileController::class, 'updatePassword'])->name('update-password');
    });
});

// Admin routes
Route::middleware(['auth', CheckRole::class . ':admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'adminDashboard'])->name('dashboard');

    // Employees
    Route::get('employees/import', [EmployeeController::class, 'importForm'])->name('employees.import-form');
    Route::post('employees/import', [EmployeeController::class, 'import'])->name('employees.import');
    Route::get('employees/download-template', [EmployeeController::class, 'downloadTemplate'])->name('employees.download-template');
    Route::resource('employees', EmployeeController::class);

    // Students
    Route::get('students/import', [StudentController::class, 'importForm'])->name('students.import-form');
    Route::post('students/import', [StudentController::class, 'import'])->name('students.import');
    Route::resource('students', StudentController::class);

    // Rombels
    Route::resource('rombels', RombelController::class)->except(['show', 'create']);
    Route::get('rombels/{rombel}/edit', [RombelController::class, 'edit'])->name('rombels.edit');
    Route::get('rombels/import', [RombelController::class, 'importForm'])->name('rombels.import-form');
    Route::post('rombels/import', [RombelController::class, 'import'])->name('rombels.import');
    Route::get('rombels/download-template', [RombelController::class, 'downloadTemplate'])->name('rombels.download-template');
    Route::get('rombels/grade/{grade}', [RombelController::class, 'getByGrade'])->name('rombels.getByGrade');

    // Grades
    Route::resource('grades', GradeController::class)->except(['show']);

    // Academic Years
    Route::resource('academic-years', AcademicYearController::class)->except(['show']);

    // Schedules
    Route::resource('schedules', App\Http\Controllers\Admin\ScheduleController::class)->except(['show']);

    // Payrolls
    Route::resource('payrolls', PayrollController::class)->except(['show', 'create', 'edit']);

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/students', [ReportController::class, 'studentReports'])->name('students');
        Route::post('/generate', [ReportController::class, 'generate'])->name('generate');
    });

    // Attendances
    Route::prefix('attendances')->name('attendances.')->group(function () {
        Route::get('/{id}/edit', [App\Http\Controllers\Admin\AttendanceController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\Admin\AttendanceController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\Admin\AttendanceController::class, 'destroy'])->name('destroy');
    });

    // Permissions
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\PermissionController::class, 'index'])->name('index');
        Route::get('/employees', [App\Http\Controllers\Admin\PermissionController::class, 'employees'])->name('employees');
        Route::get('/students', [App\Http\Controllers\Admin\PermissionController::class, 'students'])->name('students');
        Route::post('/{permission}/approve', [App\Http\Controllers\Admin\PermissionController::class, 'approve'])->name('approve');
        Route::post('/{permission}/reject', [App\Http\Controllers\Admin\PermissionController::class, 'reject'])->name('reject');
        
        // Student Permissions CRUD
        Route::get('/students/create', [App\Http\Controllers\Admin\PermissionController::class, 'createStudent'])->name('students.create');
        Route::post('/students', [App\Http\Controllers\Admin\PermissionController::class, 'storeStudent'])->name('students.store');
        Route::get('/students/{permission}/edit', [App\Http\Controllers\Admin\PermissionController::class, 'editStudent'])->name('students.edit');
        Route::put('/students/{permission}', [App\Http\Controllers\Admin\PermissionController::class, 'updateStudent'])->name('students.update');
        Route::delete('/students/{permission}', [App\Http\Controllers\Admin\PermissionController::class, 'destroyStudent'])->name('students.destroy');

        // Employee Permissions CRUD
        Route::get('/employees/create', [App\Http\Controllers\Admin\PermissionController::class, 'createEmployee'])->name('employees.create');
        Route::post('/employees', [App\Http\Controllers\Admin\PermissionController::class, 'storeEmployee'])->name('employees.store');
        Route::get('/employees/{permission}/edit', [App\Http\Controllers\Admin\PermissionController::class, 'editEmployee'])->name('employees.edit');
        Route::put('/employees/{permission}', [App\Http\Controllers\Admin\PermissionController::class, 'updateEmployee'])->name('employees.update');
        Route::delete('/employees/{permission}', [App\Http\Controllers\Admin\PermissionController::class, 'destroyEmployee'])->name('employees.destroy');
    });
    
    // Permissions routes for admin
    Route::get('/admin/permissions/students', [App\Http\Controllers\Admin\PermissionController::class, 'students'])->name('admin.permissions.students');
    Route::get('/admin/permissions/employees', [App\Http\Controllers\Admin\PermissionController::class, 'employees'])->name('admin.permissions.employees');
    
    // Exports
    Route::prefix('exports')->name('exports.')->group(function () {
        Route::get('/employees', [App\Http\Controllers\Admin\ExportController::class, 'employees'])->name('employees');
        Route::get('/students', [App\Http\Controllers\Admin\ExportController::class, 'students'])->name('students');
    });
    
    // Schedules for both employees and students
    Route::prefix('schedules')->name('schedules.custom.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ScheduleController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\Admin\ScheduleController::class, 'store'])->name('store');
        Route::get('/create', [App\Http\Controllers\Admin\ScheduleController::class, 'create'])->name('create');
        Route::get('/{schedule}/edit', [App\Http\Controllers\Admin\ScheduleController::class, 'edit'])->name('edit');
        Route::put('/{schedule}', [App\Http\Controllers\Admin\ScheduleController::class, 'update'])->name('update');
        Route::delete('/{schedule}', [App\Http\Controllers\Admin\ScheduleController::class, 'destroy'])->name('destroy');
        
        // Specific routes for different types
        Route::get('/pegawai', [App\Http\Controllers\Admin\ScheduleController::class, 'pegawai'])->name('pegawai');
        Route::get('/siswa', [App\Http\Controllers\Admin\ScheduleController::class, 'siswa'])->name('siswa');
    });
    
    // Employee Shifts
    Route::prefix('employee-shifts')->name('employee-shifts.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\EmployeeShiftController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\Admin\EmployeeShiftController::class, 'store'])->name('store');
        Route::get('/export', [App\Http\Controllers\Admin\EmployeeShiftController::class, 'export'])->name('export');
        Route::post('/import', [App\Http\Controllers\Admin\EmployeeShiftController::class, 'import'])->name('import');
        Route::get('/by-date', [App\Http\Controllers\Admin\EmployeeShiftController::class, 'getShiftsByDate'])->name('by-date');
    });
    
    // Users - accessible by admin and monitoring
    Route::middleware(['auth', 'admin.or.monitoring'])->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Backup
    Route::prefix('backup')->name('backup.')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('index');
        Route::post('/create', [BackupController::class, 'create'])->name('create');
        Route::get('/download/{filename}', [BackupController::class, 'download'])->name('download');
        Route::delete('/delete/{filename}', [BackupController::class, 'delete'])->name('delete');
        Route::get('/restore', [BackupController::class, 'showRestore'])->name('restore');
        Route::post('/restore', [BackupController::class, 'restore'])->name('restore.execute');
    });

    // Update
    Route::prefix('update')->name('update.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\UpdateController::class, 'index'])->name('index');
        Route::get('/check', [App\Http\Controllers\Admin\UpdateController::class, 'check'])->name('check');
        Route::post('/update', [App\Http\Controllers\Admin\UpdateController::class, 'update'])->name('update');
    });

    // Profile routes for admin users
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/change-password', [App\Http\Controllers\Admin\ProfileController::class, 'showChangePasswordForm'])->name('change-password');
        Route::put('/change-password', [App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('update-password');
    });

    // Holidays
    Route::prefix('holidays')->name('holidays.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\HolidayController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\HolidayController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\HolidayController::class, 'store'])->name('store');
        Route::get('/{holiday}/edit', [App\Http\Controllers\Admin\HolidayController::class, 'edit'])->name('edit');
        Route::put('/{holiday}', [App\Http\Controllers\Admin\HolidayController::class, 'update'])->name('update');
        Route::delete('/{holiday}', [App\Http\Controllers\Admin\HolidayController::class, 'destroy'])->name('destroy');
        Route::get('/active-days', [App\Http\Controllers\Admin\HolidayController::class, 'activeDays'])->name('active-days');
        Route::post('/active-days', [App\Http\Controllers\Admin\HolidayController::class, 'updateActiveDays'])->name('update-active-days');
        Route::get('/working-days-report', [App\Http\Controllers\Admin\HolidayController::class, 'workingDaysReport'])->name('working-days-report');
        Route::get('/import', [App\Http\Controllers\Admin\HolidayController::class, 'showImportForm'])->name('import-form');
        Route::post('/import', [App\Http\Controllers\Admin\HolidayController::class, 'import'])->name('import');
        Route::get('/template', [App\Http\Controllers\Admin\HolidayController::class, 'downloadTemplate'])->name('template');
        Route::post('/bulk-update', [App\Http\Controllers\Admin\HolidayController::class, 'bulkUpdate'])->name('bulk-update');
    });

    // Security
    Route::prefix('security')->name('security.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SecurityController::class, 'index'])->name('index');
        Route::get('/logs', [App\Http\Controllers\Admin\SecurityController::class, 'logs'])->name('logs');
        Route::get('/login-attempts', [App\Http\Controllers\Admin\SecurityController::class, 'loginAttempts'])->name('login-attempts');
        Route::post('/clear-old-logs', [App\Http\Controllers\Admin\SecurityController::class, 'clearOldLogs'])->name('clear-old-logs');
    });

    // Location Settings
    Route::prefix('location-settings')->name('location-settings.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\LocationSettingsController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\Admin\LocationSettingsController::class, 'store'])->name('store');
        
        // API endpoints for geofence settings
        Route::get('/geofence-center-lat', [App\Http\Controllers\Admin\LocationSettingsController::class, 'getGeofenceCenterLat'])->name('geofence-center-lat');
        Route::get('/geofence-center-lng', [App\Http\Controllers\Admin\LocationSettingsController::class, 'getGeofenceCenterLng'])->name('geofence-center-lng');
        Route::get('/geofence-radius', [App\Http\Controllers\Admin\LocationSettingsController::class, 'getGeofenceRadius'])->name('geofence-radius');
        Route::get('/location-required', [App\Http\Controllers\Admin\LocationSettingsController::class, 'getLocationRequired'])->name('location-required');
    });
});

// Additional student permissions CRUD routes 
Route::get('/admin/permissions/students/create', [App\Http\Controllers\Admin\PermissionController::class, 'createStudent'])->name('admin.permissions.students.create');
Route::post('/admin/permissions/students', [App\Http\Controllers\Admin\PermissionController::class, 'storeStudent'])->name('admin.permissions.students.store');
Route::get('/admin/permissions/students/{permission}/edit', [App\Http\Controllers\Admin\PermissionController::class, 'editStudent'])->name('admin.permissions.students.edit');
Route::put('/admin/permissions/students/{permission}', [App\Http\Controllers\Admin\PermissionController::class, 'updateStudent'])->name('admin.permissions.students.update');
Route::delete('/admin/permissions/students/{permission}', [App\Http\Controllers\Admin\PermissionController::class, 'destroyStudent'])->name('admin.permissions.students.destroy');