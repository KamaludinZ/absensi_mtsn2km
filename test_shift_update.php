<?php

require_once __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Schedule;
use Carbon\Carbon;

// Create a Laravel application instance
$app = require_once __DIR__.'/bootstrap/app.php';

// Create a test attendance record for an employee
$employee = Employee::first();
if ($employee) {
    echo "Found employee: " . $employee->full_name . "\n";
    
    // Get today's schedule for employees
    $dayOfWeek = Carbon::today()->dayOfWeek;
    $schedule = Schedule::where('type', 'pegawai')->where('day_of_week', $dayOfWeek)->first();
    
    if ($schedule) {
        echo "Found schedule for employee: " . $schedule->shift_name . " (" . $schedule->shift_type . ") - " . 
             $schedule->start_time . " to " . $schedule->end_time . "\n";
        
        // Create an attendance record
        $attendance = Attendance::create([
            'attendable_type' => 'App\Models\Employee',
            'attendable_id' => $employee->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->addHours(1), // Coming 1 hour late
            'status_in' => 'Terlambat',
        ]);
        
        echo "Created attendance ID: " . $attendance->id . " for employee\n";
        
        // Now update with shift information
        $attendance->update([
            'shift_name' => $schedule->shift_name,
            'shift_type' => $schedule->shift_type,
            'scheduled_start_time' => $schedule->start_time,
            'scheduled_end_time' => $schedule->end_time,
        ]);
        
        echo "Updated attendance with shift information:\n";
        echo "  Shift Name: " . $attendance->shift_name . "\n";
        echo "  Shift Type: " . $attendance->shift_type . "\n";
        echo "  Scheduled Start: " . $attendance->scheduled_start_time . "\n";
        echo "  Scheduled End: " . $attendance->scheduled_end_time . "\n";
    } else {
        echo "No schedule found for employees on day: " . $dayOfWeek . "\n";
    }
} else {
    echo "No employees found in database\n";
}

// Create a test attendance record for a student
$student = Student::first();
if ($student) {
    echo "\nFound student: " . $student->full_name . "\n";
    
    // Get today's schedule for students
    $dayOfWeek = Carbon::today()->dayOfWeek;
    $schedule = Schedule::where('type', 'siswa')->where('day_of_week', $dayOfWeek)->first();
    
    if ($schedule) {
        echo "Found schedule for student: " . $schedule->shift_name . " (" . $schedule->shift_type . ") - " . 
             $schedule->start_time . " to " . $schedule->end_time . "\n";
        
        // Create an attendance record
        $attendance = Attendance::create([
            'attendable_type' => 'App\Models\Student',
            'attendable_id' => $student->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subMinutes(30), // Arriving 30 minutes early
            'status_in' => 'Tepat Waktu',
        ]);
        
        echo "Created attendance ID: " . $attendance->id . " for student\n";
        
        // Now update with shift information
        $attendance->update([
            'shift_name' => $schedule->shift_name,
            'shift_type' => $schedule->shift_type,
            'scheduled_start_time' => $schedule->start_time,
            'scheduled_end_time' => $schedule->end_time,
        ]);
        
        echo "Updated attendance with shift information:\n";
        echo "  Shift Name: " . $attendance->shift_name . "\n";
        echo "  Shift Type: " . $attendance->shift_type . "\n";
        echo "  Scheduled Start: " . $attendance->scheduled_start_time . "\n";
        echo "  Scheduled End: " . $attendance->scheduled_end_time . "\n";
    } else {
        echo "No schedule found for students on day: " . $dayOfWeek . "\n";
    }
} else {
    echo "No students found in database\n";
}

echo "\nTest completed successfully!\n";