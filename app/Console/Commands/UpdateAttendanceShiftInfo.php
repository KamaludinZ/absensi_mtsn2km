<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\EmployeeShift;
use Carbon\Carbon;

class UpdateAttendanceShiftInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:update-shift-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update attendance records with shift information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating attendance records with shift information...');

        $attendances = Attendance::all();
        $total = $attendances->count();
        $updated = 0;
        $bar = $this->output->createProgressBar($total);

        foreach ($attendances as $attendance) {
            $bar->advance();

            // Determine if this is an employee or student attendance
            $attendableType = $attendance->attendable_type;
            
            // Get the day of week for the attendance date
            $dayOfWeek = Carbon::parse($attendance->date)->dayOfWeek;
            
            $shiftInfo = null;
            
            // For employees, check if there's a specific employee shift assigned for that date
            if ($attendableType === 'App\Models\Employee') {
                $employeeShift = EmployeeShift::where('employee_id', $attendance->attendable_id)
                    ->where('date', $attendance->date)
                    ->with('schedule')
                    ->first();
                
                if ($employeeShift && $employeeShift->schedule) {
                    $shiftInfo = $employeeShift->schedule;
                }
            }
            
            // If no specific employee shift or for students, look up by day of week
            if (!$shiftInfo) {
                $type = ($attendableType === 'App\Models\Employee') ? 'pegawai' : 'siswa';
                
                $shiftInfo = Schedule::where('type', $type)
                    ->where('day_of_week', $dayOfWeek)
                    ->first();
            }
            
            // Update the attendance record with shift information
            if ($shiftInfo) {
                $attendance->update([
                    'shift_name' => $shiftInfo->shift_name,
                    'shift_type' => $shiftInfo->shift_type,
                    'scheduled_start_time' => $shiftInfo->start_time,
                    'scheduled_end_time' => $shiftInfo->end_time,
                ]);
                $updated++;
            }
        }

        $bar->finish();
        
        $this->info("\nSuccessfully updated {$updated} out of {$total} attendance records with shift information.");
    }
}
