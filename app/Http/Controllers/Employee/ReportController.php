<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Permission;
use App\Models\EmployeeShift;

class ReportController extends Controller
{
    public function salaryReport(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('employee.dashboard')->with('error', 'Data pegawai tidak ditemukan.');
        }

        $selectedYear = $request->input('year', Carbon::now()->year);

        // Get unique years from employee shifts
        $yearsFromShifts = EmployeeShift::where('employee_id', $employee->id)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->pluck('year')
            ->map(fn($year) => (int)$year);

        // Get unique years from attendance records
        $yearsFromAttendance = Attendance::where('attendable_type', Employee::class)
            ->where('attendable_id', $employee->id)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->pluck('year')
            ->map(fn($year) => (int)$year);

        // Combine and sort unique years
        $allYears = $yearsFromShifts->merge($yearsFromAttendance)->unique()->sortDesc()->values();

        $currentYear = (int)Carbon::now()->year;
        
        // Ensure current year and a few past years are always present
        $maxShownYear = $currentYear;
        $minShownYear = $currentYear - 2; // Show at least current year and 2 past years

        // Adjust min/max based on actual data
        if ($allYears->isNotEmpty()) {
            $minShownYear = min($minShownYear, $allYears->min());
            $maxShownYear = max($maxShownYear, $allYears->max());
        }

        // Generate the final list of years for the dropdown
        $years = collect(range($maxShownYear, $minShownYear))->sortDesc()->values();

        if ($years->isEmpty()) {
            $years = collect([$currentYear]);
        }

        // Fetch all necessary data for the entire year
        $shifts = EmployeeShift::where('employee_id', $employee->id)
            ->whereYear('date', $selectedYear)
            ->with('schedule')
            ->get()
            ->keyBy(function ($shift) {
                return $shift->date->format('Y-m-d');
            });

        $attendances = Attendance::where('attendable_id', $employee->id)
            ->where('attendable_type', Employee::class)
            ->whereYear('date', $selectedYear)
            ->get()
            ->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

        $permissions = Permission::where('permissible_id', $employee->id)
            ->where('permissible_type', Employee::class)
            ->where('status', 'approved')
            ->where(function ($query) use ($selectedYear) {
                $query->whereYear('start_date', $selectedYear)
                      ->orWhereYear('end_date', $selectedYear);
            })
            ->get();

        $permissionMap = [];
        $permissionTypesFull = [
            'C' => 'Cuti',
            'S' => 'Sakit',
            'DL' => 'Dinas Luar',
        ];
        foreach ($permissions as $permission) {
            $current = Carbon::parse($permission->start_date);
            $end = Carbon::parse($permission->end_date);
            while ($current->lte($end)) {
                $permissionMap[$current->format('Y-m-d')] = $permissionTypesFull[$permission->type] ?? $permission->type;
                $current->addDay();
            }
        }
        
        $summaries = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthStartDate = Carbon::create($selectedYear, $month, 1);

            $summary = [
                'month_number' => $month,
                'month_name' => $monthStartDate->isoFormat('MMMM'),
                'active_days' => 0,
                'present_days' => 0,
                'on_time_days' => 0,
                'late_days' => 0,
                'early_leave_days' => 0,
                'sick_days' => 0,
                'leave_days' => 0,
                'official_duty_days' => 0,
                'absent_days' => 0,
            ];

            for ($day = 1; $day <= $monthStartDate->daysInMonth; $day++) {
                $currentDate = Carbon::create($selectedYear, $month, $day);
                $dateKey = $currentDate->format('Y-m-d');

                $permissionType = $permissionMap[$dateKey] ?? null;
                $shift = $shifts->get($dateKey);
                $attendance = $attendances->get($dateKey);

                $isWorkDay = ($shift && !$permissionType);
                $isPresent = ($attendance && ($attendance->clock_in || $attendance->clock_out) && !$permissionType);

                // Count permission days
                if ($permissionType) {
                    if ($permissionType === 'Sakit') $summary['sick_days']++;
                    if ($permissionType === 'Cuti') $summary['leave_days']++;
                    if ($permissionType === 'Dinas Luar') $summary['official_duty_days']++;
                }

                // Count active (shift) days
                if ($shift) {
                    $summary['active_days']++;
                }
                
                // Count present, absent, and statuses only on work days
                if ($isWorkDay) {
                    if ($isPresent) {
                        $summary['present_days']++;
                        
                        $schedule = $shift->schedule;
                        if ($schedule) {
                            $statusIn = 'Tidak Hadir';
                            $statusOut = 'Tidak Hadir';

                            if ($attendance->clock_in) {
                                $diffIn = $this->calculateTimeDifference($attendance->clock_in, $schedule->start_time);
                                $statusIn = ($diffIn['seconds'] < 0) ? 'Terlambat' : 'Tepat Waktu';
                            }
                            
                            if ($attendance->clock_out) {
                                $diffOut = $this->calculateTimeDifference($attendance->clock_out, $schedule->end_time);
                                $statusOut = ($diffOut['seconds'] > 0) ? 'Pulang Awal' : 'Tepat Waktu';
                            } else {
                                $statusOut = 'Belum Pulang';
                            }

                            if ($statusIn === 'Terlambat') $summary['late_days']++;
                            if ($statusOut === 'Pulang Awal') $summary['early_leave_days']++;
                            if ($statusIn === 'Tepat Waktu' && $statusOut === 'Tepat Waktu') $summary['on_time_days']++;
                        }
                    } else {
                        // It's a workday, but the user was not present and had no permission
                        $summary['absent_days']++;
                    }
                }
            }
            $summaries[] = $summary;
        }

        return view('employee.salary.index', compact('summaries', 'years', 'selectedYear'));
    }

    public function salaryReportDetail(Request $request, $year, $month)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('employee.dashboard')->with('error', 'Data pegawai tidak ditemukan.');
        }

        $monthName = Carbon::create($year, $month)->isoFormat('MMMM');

        $data = $this->getSalaryDetailData($employee, $year, $month);

        return view('employee.salary.show', array_merge($data, [
            'year' => $year,
            'month' => $month, // Pass month number as well
            'monthName' => $monthName
        ]));
    }

    private function getSalaryDetailData(Employee $employee, $year, $month)
    {
        $employee->load('salary');
        $salary = $employee->salary;

        if (!$salary) {
            return [
                'employee' => $employee,
                'summary' => null,
                'detailData' => [],
                'permissionMap' => [],
            ];
        }

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        $employeeShifts = \App\Models\EmployeeShift::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('schedule')
            ->get()
            ->keyBy(function ($shift) {
                return $shift->date->format('Y-m-d');
            });

        $attendances = Attendance::where('attendable_id', $employee->id)
            ->where('attendable_type', Employee::class)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

        $permissions = \App\Models\Permission::where('permissible_id', $employee->id)
            ->where('permissible_type', Employee::class)
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->get();

        $permissionMap = [];
        $permissionTypesFull = ['C' => 'Cuti', 'S' => 'Sakit', 'DL' => 'Dinas Luar'];
        foreach ($permissions as $permission) {
            $current = Carbon::parse($permission->start_date)->max($startDate);
            $end = Carbon::parse($permission->end_date)->min($endDate);
            while ($current->lte($end)) {
                $permissionMap[$current->format('Y-m-d')] = $permissionTypesFull[$permission->type] ?? $permission->type;
                $current->addDay();
            }
        }

        $detailData = [];
        $totalMealAllowance = 0;
        $totalTransportAllowance = 0;
        $totalPerformanceAllowance = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::create($year, $month, $day);
            $dateKey = $currentDate->format('Y-m-d');
            
            $attendance = $attendances->get($dateKey);
            $employeeShift = $employeeShifts->get($dateKey);
            $schedule = $employeeShift ? $employeeShift->schedule : null;
            $permissionType = $permissionMap[$dateKey] ?? null;

            $dailyMealAllowance = 0;
            $dailyTransportAllowance = 0;
            $dailyPerformanceAllowance = 0;
            
            $statusIn = '-';
            $statusOut = '-';

            if ($permissionType) {
                $statusIn = $permissionType;
                $statusOut = $permissionType;
            } elseif ($schedule) {
                if ($attendance) {
                    if ($attendance->clock_in) {
                        $diffIn = $this->calculateTimeDifference($attendance->clock_in, $schedule->start_time);
                        $statusIn = ($diffIn['seconds'] < 0) ? 'Terlambat' : 'Tepat Waktu';
                    } else {
                        $statusIn = 'Tidak Hadir';
                    }

                    if ($attendance->clock_out) {
                        $diffOut = $this->calculateTimeDifference($attendance->clock_out, $schedule->end_time);
                        $statusOut = ($diffOut['seconds'] > 0) ? 'Pulang Awal' : 'Tepat Waktu';
                    } else {
                        $statusOut = 'Belum Pulang';
                    }
                    
                    $isPresent = $attendance->clock_in !== null || $attendance->clock_out !== null;
                    if ($isPresent) {
                        $dailyMealAllowance = $salary->meal_allowance;
                        $dailyTransportAllowance = $salary->transport_allowance;
                        $totalMealAllowance += $dailyMealAllowance;
                        $totalTransportAllowance += $dailyTransportAllowance;
                        
                        $isPerfect = $statusIn === 'Tepat Waktu' && $statusOut === 'Tepat Waktu';
                        if ($isPerfect) {
                            $dailyPerformanceAllowance = $salary->performance_allowance;
                            $totalPerformanceAllowance += $dailyPerformanceAllowance;
                        }
                    }
                } else {
                    $statusIn = 'Tidak Hadir';
                    $statusOut = 'Tidak Hadir';
                }
            } else {
                $statusIn = 'Hari Libur';
                $statusOut = 'Hari Libur';
            }

            $detailData[] = [
                'date' => $currentDate,
                'attendance' => $attendance,
                'schedule' => $schedule,
                'status_in' => $statusIn,
                'status_out' => $statusOut,
                'daily_meal_allowance' => $dailyMealAllowance,
                'daily_transport_allowance' => $dailyTransportAllowance,
                'daily_performance_allowance' => $dailyPerformanceAllowance,
            ];
        }

        $totalSalary = $salary->base_salary + $totalMealAllowance + $totalTransportAllowance + $totalPerformanceAllowance;

        $summary = [
            'base_salary' => $salary->base_salary,
            'total_meal_allowance' => $totalMealAllowance,
            'total_transport_allowance' => $totalTransportAllowance,
            'total_performance_allowance' => $totalPerformanceAllowance,
            'total_salary' => $totalSalary,
        ];

        return compact('employee', 'detailData', 'summary', 'permissionMap');
    }

    private function calculateTimeDifference($actualTime, $scheduleTime)
    {
        $actual = Carbon::parse($actualTime);
        $scheduled = Carbon::parse($scheduleTime)->setDate($actual->year, $actual->month, $actual->day);
        $diffSeconds = $actual->diffInSeconds($scheduled, false);

        return ['seconds' => $diffSeconds];
    }
}
