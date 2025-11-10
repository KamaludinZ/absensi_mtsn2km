<?php

namespace App\Helpers;

use App\Models\AttendanceSetting;
use Carbon\Carbon;

class AttendanceHelper
{
    /**
     * Format time difference based on attendance settings
     *
     * @param int $secondsDifference Difference in seconds
     * @return string Formatted time difference
     */
    public static function formatTimeDifference($secondsDifference)
    {
        // Get format setting
        $formatSetting = AttendanceSetting::where('setting_key', 'time_difference_format')->first();
        $format = $formatSetting ? $formatSetting->setting_value : 'time';

        // Handle null or zero difference
        if ($secondsDifference === null) {
            return '-';
        }

        if ($format === 'minutes') {
            // Convert to minutes
            $minutes = round($secondsDifference / 60);

            if ($minutes > 0) {
                return '+' . $minutes . ' menit';
            } elseif ($minutes < 0) {
                return $minutes . ' menit'; // Already has minus sign
            } else {
                return '0 menit';
            }
        } else {
            // Format as HH:mm:ss (default)
            $absSeconds = abs($secondsDifference);

            $hours = floor($absSeconds / 3600);
            $remainingSeconds = $absSeconds - ($hours * 3600);
            $minutes = floor($remainingSeconds / 60);
            $seconds = $remainingSeconds - ($minutes * 60);

            $timeString = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

            if ($secondsDifference > 0) {
                return '+' . $timeString;
            } elseif ($secondsDifference < 0) {
                return '-' . $timeString;
            } else {
                return '00:00:00';
            }
        }
    }

    /**
     * Calculate time difference in seconds between two time strings
     *
     * @param string $actualTime Actual time (HH:mm:ss)
     * @param string $scheduledTime Scheduled time (HH:mm:ss)
     * @return int Difference in seconds
     */
    public static function calculateTimeDifferenceInSeconds($actualTime, $scheduledTime)
    {
        if (!$actualTime || !$scheduledTime) {
            return null;
        }

        // Parse time strings
        list($h1, $i1, $s1) = explode(':', $actualTime);
        $actualSeconds = ($h1 * 3600) + ($i1 * 60) + $s1;

        list($h2, $i2, $s2) = explode(':', $scheduledTime);
        $scheduledSeconds = ($h2 * 3600) + ($i2 * 60) + $s2;

        return $actualSeconds - $scheduledSeconds;
    }

    /**
     * Format check-in time difference
     *
     * @param \App\Models\Attendance $attendance
     * @return string
     */
    public static function formatCheckInDifference($attendance)
    {
        if (!$attendance->clock_in) {
            return '-';
        }

        $schedule = self::getScheduleForAttendance($attendance);
        if (!$schedule) {
            return '-';
        }

        $actualTime = Carbon::parse($attendance->clock_in)->format('H:i:s');
        $scheduledTime = Carbon::parse($schedule->start_time)->format('H:i:s');

        $diffSeconds = self::calculateTimeDifferenceInSeconds($actualTime, $scheduledTime);
        return self::formatTimeDifference($diffSeconds);
    }

    /**
     * Format check-out time difference
     *
     * @param \App\Models\Attendance $attendance
     * @return string
     */
    public static function formatCheckOutDifference($attendance)
    {
        if (!$attendance->clock_out) {
            return '-';
        }

        $schedule = self::getScheduleForAttendance($attendance);
        if (!$schedule) {
            return '-';
        }

        $actualTime = Carbon::parse($attendance->clock_out)->format('H:i:s');
        $scheduledTime = Carbon::parse($schedule->end_time)->format('H:i:s');

        $diffSeconds = self::calculateTimeDifferenceInSeconds($actualTime, $scheduledTime);
        return self::formatTimeDifference($diffSeconds);
    }

    /**
     * Get schedule for attendance record
     *
     * @param \App\Models\Attendance $attendance
     * @return \App\Models\Schedule|null
     */
    private static function getScheduleForAttendance($attendance)
    {
        // Determine attendance type
        $attendableType = $attendance->attendable_type;
        $scheduleType = null;

        if (strpos($attendableType, 'Employee') !== false) {
            $scheduleType = 'pegawai';

            // Try to get shift from employee_shifts table
            $employeeShift = \App\Models\EmployeeShift::where('employee_id', $attendance->attendable_id)
                ->where('date', $attendance->date)
                ->with('schedule')
                ->first();

            if ($employeeShift && $employeeShift->schedule) {
                return $employeeShift->schedule;
            }
        } elseif (strpos($attendableType, 'Student') !== false) {
            $scheduleType = 'siswa';
        }

        if (!$scheduleType) {
            return null;
        }

        // Try to get schedule by day of week
        $dayOfWeek = $attendance->date->dayOfWeek;
        return \App\Models\Schedule::where('type', $scheduleType)
            ->where('day_of_week', $dayOfWeek)
            ->first();
    }
}
