<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\EmployeeShift;
use Carbon\Carbon;

class UpdateAttendanceStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:update-status {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update attendance status based on schedule for corrected records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date');

        $query = Attendance::whereNotNull('clock_in')
            ->orWhereNotNull('clock_out');

        if ($date) {
            $query->whereDate('date', $date);
            $this->info("Updating attendance status for date: {$date}");
        } else {
            $this->info("Updating all attendance status records...");
        }

        $attendances = $query->get();
        $updated = 0;

        foreach ($attendances as $attendance) {
            $dateKey = Carbon::parse($attendance->date)->format('Y-m-d');

            // Get employee ID based on attendable_type
            if ($attendance->attendable_type === 'App\Models\Employee') {
                $employeeId = $attendance->attendable_id;
            } else {
                continue; // Skip non-employee records for now
            }

            // Get employee shift for this date
            $employeeShift = EmployeeShift::where('employee_id', $employeeId)
                ->whereDate('date', $dateKey)
                ->with('schedule')
                ->first();

            if (!$employeeShift || !$employeeShift->schedule) {
                continue; // Skip if no schedule found
            }

            $schedule = $employeeShift->schedule;
            $changed = false;

            // Calculate status_in
            if ($attendance->clock_in) {
                $diffIn = $this->calculateTimeDifference($attendance->clock_in, $schedule->start_time);
                $newStatusIn = ($diffIn['seconds'] > 0) ? 'Terlambat' : 'Tepat Waktu';

                if ($attendance->status_in !== $newStatusIn) {
                    $attendance->status_in = $newStatusIn;
                    $changed = true;
                }
            }

            // Calculate status_out
            if ($attendance->clock_out) {
                $diffOut = $this->calculateTimeDifference($attendance->clock_out, $schedule->end_time);
                $newStatusOut = ($diffOut['seconds'] < 0) ? 'Pulang Awal' : 'Tepat Waktu';

                if ($attendance->status_out !== $newStatusOut) {
                    $attendance->status_out = $newStatusOut;
                    $changed = true;
                }
            }

            if ($changed) {
                $attendance->save();
                $updated++;
                $this->info("Updated attendance ID {$attendance->id} for date {$dateKey}");
            }
        }

        $this->info("Total records updated: {$updated}");
        return 0;
    }

    /**
     * Calculate time difference between actual and scheduled time
     */
    private function calculateTimeDifference($actualTime, $scheduleTime)
    {
        $actual = Carbon::parse($actualTime);
        $scheduled = Carbon::parse($scheduleTime)->setDate($actual->year, $actual->month, $actual->day);
        // Positive = late (actual > scheduled), Negative = early (actual < scheduled)
        $diffSeconds = $scheduled->diffInSeconds($actual, false);

        return ['seconds' => $diffSeconds];
    }
}
