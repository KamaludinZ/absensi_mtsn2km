<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\AttendanceSetting;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function salaryReport(Request $request)
    {
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);

        $employeeQuery = Employee::with(['salary' => function($query) {
            $query->latest('created_at');
        }])->where('is_active', true);

        // Filter by specific employee if provided
        if ($request->filled('employee_id')) {
            $employeeQuery->where('id', $request->employee_id);
        }

        // Sorting functionality - default to alphabetical order by name
        $sortField = $request->get('sort', 'full_name');
        $sortDirection = $request->get('direction', 'asc');

        if ($sortField === 'full_name') {
            $employeeQuery->orderBy('full_name', $sortDirection);
        } elseif ($sortField === 'position') {
            $employeeQuery->orderBy('position', $sortDirection);
        } else {
            $employeeQuery->orderBy('full_name', $sortDirection);
        }

        $employees = $employeeQuery->get();
        $employeeIds = $employees->pluck('id');

        // Prepare attendance query
        $attendanceQuery = Attendance::where('attendable_type', Employee::class)
            ->whereIn('attendable_id', $employeeIds);

        // Filter by month/year
        $attendanceQuery->whereMonth('date', $selectedMonth)
                      ->whereYear('date', $selectedYear);

        $attendances = $attendanceQuery->orderBy('date', 'asc')->get()->groupBy('attendable_id');

        $salaryData = [];

        foreach ($employees as $employee) {
            $employeeId = $employee->id;
            $salary = $employee->salary;

            // Skip employees without salary settings
            if (!$salary) {
                continue;
            }

            $employeeAttendances = $attendances->get($employeeId, collect());

            // Get employee shifts for the month/year
            $employeeShifts = \App\Models\EmployeeShift::where('employee_id', $employeeId)
                ->whereMonth('date', $selectedMonth)
                ->whereYear('date', $selectedYear)
                ->with('schedule')
                ->get()
                ->keyBy('date');

            // Get default schedules for non-shift assignment days
            $defaultSchedules = Schedule::where('type', 'pegawai')
                ->get()
                ->keyBy('day_of_week');

            $present_days = 0;
            $perfect_days = 0;
            $totalMealAllowance = 0;
            $totalTransportAllowance = 0;
            $totalPerformanceAllowance = 0;

            // Iterate through each day of the selected month
            $daysInMonth = Carbon::create($selectedYear, $selectedMonth, 1)->daysInMonth;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = Carbon::create($selectedYear, $selectedMonth, $day);
                $dateKey = $currentDate->format('Y-m-d');

                $attendance = $employeeAttendances->firstWhere('date', $currentDate);

                // Get shift schedule for this specific date if available, otherwise use default schedule
                $shift = $employeeShifts->get($dateKey);
                $schedule = $shift ? $shift->schedule : null;

                if (!$schedule) {
                    // Use default schedule based on day of week if no specific shift is assigned
                    // Convert to 1-7 format (Monday=1, Sunday=7) as typically used in schedules
                    $dayOfWeek = $currentDate->dayOfWeek;
                    if ($dayOfWeek == 0) $dayOfWeek = 7; // Convert Sunday (0) to 7
                    $schedule = $defaultSchedules->get($dayOfWeek);
                }

                // Calculate daily allowances based on schedule, attendance, and permissions
                $dailyMealAllowance = 0;
                $dailyTransportAllowance = 0;
                $dailyPerformanceAllowance = 0;

                if ($attendance) {
                    // There is an attendance record
                    $isPresent = $attendance->clock_in !== null || $attendance->clock_out !== null;

                    if ($isPresent) {
                        // Grant meal and transport allowances if present and it's a scheduled day
                        if ($schedule) {
                            $dailyMealAllowance = $salary->meal_allowance;
                            $dailyTransportAllowance = $salary->transport_allowance;
                        }

                        // Check for perfect attendance to grant performance allowance
                        $isPerfect = $attendance->status_in === 'Tepat Waktu' && $attendance->status_out === 'Tepat Waktu';
                        if ($isPerfect) {
                            $dailyPerformanceAllowance = $salary->performance_allowance;
                        }
                    }
                }

                // Accumulate daily allowances
                $totalMealAllowance += $dailyMealAllowance;
                $totalTransportAllowance += $dailyTransportAllowance;
                $totalPerformanceAllowance += $dailyPerformanceAllowance;

                // Count present and perfect days for summary
                if ($dailyMealAllowance > 0) { // If allowances were granted, it means employee was present on a scheduled day
                    $present_days++;
                }
                if ($dailyPerformanceAllowance > 0) { // If performance allowance was granted, it was a perfect day
                    $perfect_days++;
                }
            }

            // Calculate total salary
            $totalSalary = $salary->base_salary + $totalMealAllowance + $totalTransportAllowance + $totalPerformanceAllowance;

            $salaryData[$employeeId] = [
                'employee' => $employee,
                'base_salary' => $salary->base_salary,
                'present_days' => $present_days,
                'total_meal_allowance' => $totalMealAllowance,
                'total_transport_allowance' => $totalTransportAllowance,
                'perfect_days' => $perfect_days,
                'total_performance_allowance' => $totalPerformanceAllowance,
                'total_salary' => $totalSalary,
            ];
        }

        // Sort the final data array based on the sort parameters
        $sortField = $request->get('sort', 'full_name');
        $sortDirection = $request->get('direction', 'asc');

        $salaryData = collect($salaryData)->sortBy(function($data) use ($sortField) {
            switch ($sortField) {
                case 'position':
                    return strtolower($data['employee']->position ?? '');
                case 'total_salary':
                    return $data['total_salary'];
                case 'total_meal_allowance':
                    return $data['total_meal_allowance'];
                case 'total_transport_allowance':
                    return $data['total_transport_allowance'];
                case 'total_performance_allowance':
                    return $data['total_performance_allowance'];
                case 'base_salary':
                    return $data['base_salary'];
                case 'present_days':
                    return $data['present_days'];
                case 'perfect_days':
                    return $data['perfect_days'];
                case 'full_name':
                default:
                    return strtolower($data['employee']->full_name ?? '');
            }
        }, SORT_REGULAR, $sortDirection === 'desc');

        $salaryData = $salaryData->values()->all();

        // Data for filters - only include employees who were active during this month or before
        $allEmployees = Employee::where(function($query) use ($selectedMonth, $selectedYear) {
            // Currently active employees (not soft deleted)
            $query->whereNull('deleted_at')
                  // OR employees who were soft deleted after this month (were active during this month)
                  ->orWhere(function($q) use ($selectedMonth, $selectedYear) {
                      $q->whereNotNull('deleted_at')
                        ->where('deleted_at', '>=', \Carbon\Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth());
                  });
        })->orderBy('full_name')->get();
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        // Get unique years from attendance records
        $yearsFromAttendance = Attendance::where('attendable_type', Employee::class)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->pluck('year')
            ->map(fn($year) => (int)$year);

        // Determine min and max year for the dropdown
        $currentYear = (int)Carbon::now()->year;
        $minYear = $yearsFromAttendance->min();

        if ($minYear === null || $minYear > $currentYear) {
            // If no attendance years or all attendance years are in the future
            $minYear = $currentYear - 2; // Show current year and 2 past years
        } else {
            $minYear = min($minYear, $currentYear - 2); // Show at least 2 past years
        }

        // Generate the final list of years for the dropdown
        $years = collect(range($currentYear, $minYear))->sortDesc()->values();
        // Add one future year to ensure the current period is always selectable.
        $years->prepend($currentYear + 1);
        $years = $years->unique()->sortDesc()->values();

        if ($years->isEmpty()) {
            $years = collect([$currentYear]);
        }

        return view('monitoring.reports.salary', [
            'salaryData' => $salaryData,
            'employees' => $allEmployees,
            'months' => $months,
            'years' => $years,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
        ]);
    }
}
