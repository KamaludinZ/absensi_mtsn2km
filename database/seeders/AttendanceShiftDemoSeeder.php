<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Schedule;
use Carbon\Carbon;

class AttendanceShiftDemoSeeder extends Seeder
{
    /**
     * Run the database seeds to demonstrate shift information in attendance records.
     */
    public function run(): void
    {
        $this->command->info('Creating demo attendance records with shift information...');
        
        // Create attendance record for an employee
        $employee = Employee::first();
        if ($employee) {
            $dayOfWeek = Carbon::today()->dayOfWeek;
            $schedule = Schedule::where('type', 'pegawai')
                ->where('day_of_week', $dayOfWeek)
                ->first();
            
            if ($schedule) {
                $attendance = Attendance::create([
                    'attendable_type' => 'App\Models\Employee',
                    'attendable_id' => $employee->id,
                    'date' => Carbon::today(),
                    'shift_name' => $schedule->shift_name,
                    'shift_type' => $schedule->shift_type,
                    'scheduled_start_time' => $schedule->start_time,
                    'scheduled_end_time' => $schedule->end_time,
                    'clock_in' => Carbon::today()->setTime(7, 30, 0), // 7:30 AM
                    'clock_out' => Carbon::today()->setTime(15, 0, 0), // 3:00 PM
                    'status_in' => 'Terlambat',
                    'status_out' => 'Tepat Waktu',
                ]);
                
                $this->command->info("Created employee attendance for: {$employee->full_name}");
                $this->command->info("Shift: {$schedule->shift_name} ({$schedule->shift_type})");
                $this->command->info("Scheduled: {$schedule->start_time} - {$schedule->end_time}");
            }
        }
        
        // Create attendance record for a student
        $student = Student::first();
        if ($student) {
            $dayOfWeek = Carbon::today()->dayOfWeek;
            $schedule = Schedule::where('type', 'siswa')
                ->where('day_of_week', $dayOfWeek)
                ->first();
            
            if ($schedule) {
                $attendance = Attendance::create([
                    'attendable_type' => 'App\Models\Student',
                    'attendable_id' => $student->id,
                    'date' => Carbon::today(),
                    'shift_name' => $schedule->shift_name,
                    'shift_type' => $schedule->shift_type,
                    'scheduled_start_time' => $schedule->start_time,
                    'scheduled_end_time' => $schedule->end_time,
                    'clock_in' => Carbon::today()->setTime(7, 45, 0), // 7:45 AM
                    'clock_out' => Carbon::today()->setTime(13, 45, 0), // 1:45 PM
                    'status_in' => 'Terlambat',
                    'status_out' => 'Tepat Waktu',
                ]);
                
                $this->command->info("Created student attendance for: {$student->full_name}");
                $this->command->info("Shift: {$schedule->shift_name} ({$schedule->shift_type})");
                $this->command->info("Scheduled: {$schedule->start_time} - {$schedule->end_time}");
            }
        }
        
        $this->command->info('Demo attendance records created successfully!');
    }
}