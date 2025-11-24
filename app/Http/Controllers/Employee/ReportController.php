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

        $years = EmployeeShift::where('employee_id', $employee->id)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');
        
        if ($years->isEmpty()) {
            $years = collect([Carbon::now()->year]);
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
        foreach ($permissions as $permission) {
            $current = Carbon::parse($permission->start_date);
            $end = Carbon::parse($permission->end_date);
            while ($current->lte($end)) {
                $permissionMap[$current->format('Y-m-d')] = $permission->type;
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

                if ($permissionType) {
                    if ($permissionType === 'Sakit') $summary['sick_days']++;
                    if ($permissionType === 'Cuti') $summary['leave_days']++;
                    if ($permissionType === 'Dinas Luar') $summary['official_duty_days']++;
                }

                if ($shift) {
                    $summary['active_days']++;

                    if ($attendance && !$permissionType) {
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
                    } elseif (!$attendance && !$permissionType) {
                        // This is an absence without permission on a scheduled day
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

        $salaryData = $this->getSalaryDetailData($employee, $year, $month);

        return view('employee.salary.show', array_merge($salaryData, ['year' => $year, 'monthName' => $monthName]));
    }

    private function getSalaryDetailData(Employee $employee, $year, $month)
    {
        $employee->load('salary');
        $salary = $employee->salary;

        if (!$salary) {
            return [
                'salaryData' => null,
                'permissionMap' => [],
            ];
        }

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
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

        $present_days = 0;
        $perfect_days = 0;
        $totalMealAllowance = 0;
        $totalTransportAllowance = 0;
        $totalPerformanceAllowance = 0;

        for ($day = 1; $day <= $endDate->day; $day++) {
            $currentDate = Carbon::create($year, $month, $day);
            $dateKey = $currentDate->format('Y-m-d');
            
            $attendance = $attendances->get($dateKey);
            $employeeShift = $employeeShifts->get($dateKey);
            $schedule = $employeeShift ? $employeeShift->schedule : null;
            $permissionType = $permissionMap[$dateKey] ?? null;
            
            $statusIn = '-';
            $statusOut = '-';

            if (!$permissionType && $schedule && $attendance) {
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
                
                $isPresent = true;
                $isPerfect = $statusIn === 'Tepat Waktu' && ($statusOut === 'Tepat Waktu' || $statusOut === 'Belum Pulang');

                $present_days++;
                $totalMealAllowance += $salary->meal_allowance;
                $totalTransportAllowance += $salary->transport_allowance;

                if ($isPerfect) {
                    $perfect_days++;
                    $totalPerformanceAllowance += $salary->performance_allowance;
                }
            }
        }

        $totalSalary = $salary->base_salary + $totalMealAllowance + $totalTransportAllowance + $totalPerformanceAllowance;

        $salaryData = [
            'base_salary' => $salary->base_salary,
            'present_days' => $present_days,
            'total_meal_allowance' => $totalMealAllowance,
            'total_transport_allowance' => $totalTransportAllowance,
            'perfect_days' => $perfect_days,
            'total_performance_allowance' => $totalPerformanceAllowance,
            'total_salary' => $totalSalary,
        ];

        return compact('salaryData', 'permissionMap');
    }

    private function calculateTimeDifference($actualTime, $scheduleTime)
    {
        $actual = Carbon::parse($actualTime);
        $scheduled = Carbon::parse($scheduleTime)->setDate($actual->year, $actual->month, $actual->day);
        $diffSeconds = $actual->diffInSeconds($scheduled, false);

        return ['seconds' => $diffSeconds];
    }
}
