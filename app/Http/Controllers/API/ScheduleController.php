<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\EmployeeShift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ScheduleController extends Controller
{
    /**
     * Get current schedule status for real-time display
     * Returns whether it's time for clock-in (Jam Datang) or clock-out (Jam Pulang)
     */
    public function getCurrentStatus()
    {
        $cacheKey = 'current_schedule_status';
        
        $data = Cache::remember($cacheKey, 60, function () { // Cache for 1 minute
            $now = Carbon::now();
            $dayOfWeek = $now->dayOfWeek;

            // Get today's general schedules
            $generalEmployeeSchedule = Schedule::where('type', 'pegawai')
                ->where('day_of_week', $dayOfWeek)
                ->first();

            $studentSchedule = Schedule::where('type', 'siswa')
                ->where('day_of_week', $dayOfWeek)
                ->first();

            $badge = null; // Default to null (no badge)

            // Determine student badge - only show if within 60 minutes of scheduled time
            if ($studentSchedule) {
                $studentStartTime = Carbon::parse($studentSchedule->start_time);
                $studentEndTime = Carbon::parse($studentSchedule->end_time);
                
                // Check if current time is within 60 minutes before or after start time (clock-in)
                $clockInStartWindow = $studentStartTime->copy()->subMinutes(60);
                $clockInEndWindow = $studentStartTime->copy()->addMinutes(60);
                
                // Check if current time is within 60 minutes before or after end time (clock-out)
                $clockOutStartWindow = $studentEndTime->copy()->subMinutes(60);
                $clockOutEndWindow = $studentEndTime->copy()->addMinutes(60);
                
                if ($now->between($clockInStartWindow, $clockInEndWindow)) {
                    $badge = 'Jam Datang';
                } elseif ($now->between($clockOutStartWindow, $clockOutEndWindow)) {
                    $badge = 'Jam Pulang';
                }
            }

            // If no student badge and we're authenticated as an employee, check employee-specific shifts
            if (!$badge && auth()->check() && auth()->user()->isEmployee()) {
                $employee = auth()->user()->employee; // Assuming there's a relationship to employee
                if ($employee) {
                    $employeeShift = EmployeeShift::where('employee_id', $employee->id)
                        ->where('date', $now->format('Y-m-d'))
                        ->where('is_active', true)
                        ->with('schedule') // Eager load the schedule relationship
                        ->first();
                    
                    // If employee has a specific shift for today, use that
                    if ($employeeShift && $employeeShift->schedule) {
                        $employeeSchedule = $employeeShift->schedule;
                    } else {
                        // Otherwise use the general schedule for employees
                        $employeeSchedule = $generalEmployeeSchedule;
                    }

                    if ($employeeSchedule) {
                        $employeeStartTime = Carbon::parse($employeeSchedule->start_time);
                        $employeeEndTime = Carbon::parse($employeeSchedule->end_time);
                        
                        // Check if current time is within 60 minutes before or after start time (clock-in)
                        $clockInStartWindow = $employeeStartTime->copy()->subMinutes(60);
                        $clockInEndWindow = $employeeStartTime->copy()->addMinutes(60);
                        
                        // Check if current time is within 60 minutes before or after end time (clock-out)
                        $clockOutStartWindow = $employeeEndTime->copy()->subMinutes(60);
                        $clockOutEndWindow = $employeeEndTime->copy()->addMinutes(60);
                        
                        if ($now->between($clockInStartWindow, $clockInEndWindow)) {
                            $badge = 'Jam Datang';
                        } elseif ($now->between($clockOutStartWindow, $clockOutEndWindow)) {
                            $badge = 'Jam Pulang';
                        }
                    }
                }
            } elseif (!$badge && $generalEmployeeSchedule) {
                // Fallback to general employee schedule if not authenticated as employee
                $employeeStartTime = Carbon::parse($generalEmployeeSchedule->start_time);
                $employeeEndTime = Carbon::parse($generalEmployeeSchedule->end_time);
                
                // Check if current time is within 60 minutes before or after start time (clock-in)
                $clockInStartWindow = $employeeStartTime->copy()->subMinutes(60);
                $clockInEndWindow = $employeeStartTime->copy()->addMinutes(60);
                
                // Check if current time is within 60 minutes before or after end time (clock-out)
                $clockOutStartWindow = $employeeEndTime->copy()->subMinutes(60);
                $clockOutEndWindow = $employeeEndTime->copy()->addMinutes(60);
                
                if ($now->between($clockInStartWindow, $clockInEndWindow)) {
                    $badge = 'Jam Datang';
                } elseif ($now->between($clockOutStartWindow, $clockOutEndWindow)) {
                    $badge = 'Jam Pulang';
                }
            }

            // Determine which employee schedule to return in the response
            $responseEmployeeSchedule = null;
            if (auth()->check() && auth()->user()->isEmployee()) {
                $employee = auth()->user()->employee;
                if ($employee) {
                    $employeeShift = EmployeeShift::where('employee_id', $employee->id)
                        ->where('date', $now->format('Y-m-d'))
                        ->where('is_active', true)
                        ->with('schedule')
                        ->first();
                    
                    if ($employeeShift && $employeeShift->schedule) {
                        $responseEmployeeSchedule = $employeeShift->schedule;
                    } else {
                        $responseEmployeeSchedule = $generalEmployeeSchedule;
                    }
                } else {
                    $responseEmployeeSchedule = $generalEmployeeSchedule;
                }
            } else {
                $responseEmployeeSchedule = $generalEmployeeSchedule;
            }

            return [
                'current_time' => $now->format('H:i:s'),
                'current_date' => $now->translatedFormat('l, d F Y'),
                'formatted_datetime' => $now->translatedFormat('l, d F Y H:i'),
                'badge' => $badge, // Will be Jam Datang, Jam Pulang or null
                'employee_schedule' => $responseEmployeeSchedule ? [
                    'start' => Carbon::parse($responseEmployeeSchedule->start_time)->format('H:i'),
                    'end' => Carbon::parse($responseEmployeeSchedule->end_time)->format('H:i'),
                ] : null,
                'student_schedule' => $studentSchedule ? [
                    'start' => Carbon::parse($studentSchedule->start_time)->format('H:i'),
                    'end' => Carbon::parse($studentSchedule->end_time)->format('H:i'),
                ] : null,
            ];
        });

        return response()->json($data);
    }
}