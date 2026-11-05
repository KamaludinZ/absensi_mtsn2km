<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Permission;
use App\Models\Salary;
use App\Models\Schedule;
use Carbon\Carbon;

/**
 * One set of rules for the monthly allowance and salary reports (uang makan,
 * uang transport, tunjangan kinerja, gaji): the summary pages, their PDFs, the
 * per-employee detail pages and the employee's own salary page all count days
 * through this class.
 *
 * - Hari Aktif: a day with a named shift assigned in /admin/employee-shifts.
 * - Sakit / Cuti / Dinas Luar: an active day covered by an approved
 *   permission, or whose attendance is marked C/S/DL in /admin/reports.
 *   Permission days without a shift (weekends, holidays) are not counted, so
 *   Hadir + Tidak Hadir + Sakit + Cuti + Dinas Luar = Hari Aktif.
 * - Hadir: an active day without permission that has a clock-in or clock-out.
 * - Tidak Hadir: an active day without permission and without either.
 * - Arrival/departure status is worked out from the clock time against the
 *   assigned shift, exactly like the /admin/reports screen. The status saved
 *   at clock-in is ignored: it is checked against the default day schedule
 *   (not the assigned shift) and goes stale when a time is edited.
 */
class EmployeeAllowanceCalculator
{
    public const ON_TIME = 'Tepat Waktu';
    public const LATE = 'Terlambat';
    public const EARLY_LEAVE = 'Pulang Awal';

    public const PERMISSION_LABELS = [
        'C' => 'Cuti',
        'S' => 'Sakit',
        'DL' => 'Dinas Luar',
    ];

    /**
     * Arrival status as shown in /admin/reports: Cuti/Sakit/Dinas Luar when
     * marked so, otherwise Terlambat when clocked in after the shift start.
     * Null when there is no clock-in or no schedule to compare against.
     */
    public static function arrivalStatus(?Attendance $attendance, $schedule): ?string
    {
        if (!$attendance) {
            return null;
        }
        if ($label = self::permissionLabel($attendance->status_in)) {
            return $label;
        }
        if (!$attendance->clock_in) {
            return null;
        }

        $start = self::onDayOf($attendance->clock_in, $schedule->start_time ?? $attendance->scheduled_start_time);
        if (!$start) {
            return null;
        }

        return Carbon::parse($attendance->clock_in)->gt($start) ? self::LATE : self::ON_TIME;
    }

    /**
     * Departure status as shown in /admin/reports: Cuti/Sakit/Dinas Luar when
     * marked so, otherwise Pulang Awal when clocked out before the shift end.
     */
    public static function departureStatus(?Attendance $attendance, $schedule): ?string
    {
        if (!$attendance) {
            return null;
        }
        if ($label = self::permissionLabel($attendance->status_out)) {
            return $label;
        }
        if (!$attendance->clock_out) {
            return null;
        }

        $end = self::onDayOf($attendance->clock_out, $schedule->end_time ?? $attendance->scheduled_end_time);
        if (!$end) {
            return null;
        }

        return Carbon::parse($attendance->clock_out)->lt($end) ? self::EARLY_LEAVE : self::ON_TIME;
    }

    /**
     * The schedule an attendance day is judged against on /admin/reports and
     * its exports: the shift assigned in /admin/employee-shifts, otherwise the
     * default schedule of that weekday.
     */
    public static function scheduleFor($employeeId, $date): ?Schedule
    {
        $shift = EmployeeShift::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->whereDate('date', Carbon::parse($date)->toDateString())
            ->with('schedule')
            ->orderBy('id')
            ->first();

        return $shift?->schedule ?? self::defaultSchedule($date);
    }

    /**
     * Default employee schedule of a weekday. Named shifts can carry the same
     * day number, so a schedule without a shift name wins.
     */
    public static function defaultSchedule($date): ?Schedule
    {
        return Schedule::where('type', 'pegawai')
            ->where('day_of_week', Carbon::parse($date)->dayOfWeek)
            ->orderByRaw('CASE WHEN shift_name IS NULL OR shift_name = \'\' THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->first();
    }

    /**
     * Salary settings used for a month: that month's row, like the summary
     * pages; the latest row when the month has none.
     */
    public static function salaryFor(Employee $employee, int $year, int $month): ?Salary
    {
        return $employee->salary()->where('month', $month)->where('year', $year)->latest('created_at')->first()
            ?? $employee->salary()->latest('created_at')->first();
    }

    /**
     * Summary rows for the employees that have salary settings for the month
     * (the salary relation must be loaded for that month).
     */
    public static function rows($employees, int $year, int $month): array
    {
        $loaded = self::loadMonth($employees->pluck('id'), $year, $month);

        $rows = [];
        foreach ($employees as $employee) {
            if (!$employee->salary) {
                continue;
            }
            $summary = self::summarize($employee->salary, $year, $month, $loaded[$employee->id]);
            unset($summary['days']);
            $rows[] = ['employee' => $employee] + $summary;
        }

        return $rows;
    }

    /**
     * Load assigned shifts, attendances and approved permissions of the given
     * employees for one month, ready for summarize().
     *
     * @return array<int, array{shifts: array, attendances: array, permissions: array}> keyed by employee id
     */
    public static function loadMonth($employeeIds, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $employeeIds = collect($employeeIds)->values();

        $data = [];
        foreach ($employeeIds as $id) {
            $data[$id] = ['shifts' => [], 'attendances' => [], 'permissions' => []];
        }
        if ($employeeIds->isEmpty()) {
            return $data;
        }

        $shifts = EmployeeShift::whereIn('employee_id', $employeeIds)
            ->where('is_active', true)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->with('schedule')
            ->orderBy('id')
            ->get();
        foreach ($shifts as $shift) {
            $data[$shift->employee_id]['shifts'][$shift->date->format('Y-m-d')] ??= $shift;
        }

        $attendances = Attendance::whereIn('attendable_id', $employeeIds)
            ->where('attendable_type', Employee::class)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->orderBy('id')
            ->get();
        foreach ($attendances as $attendance) {
            $data[$attendance->attendable_id]['attendances'][$attendance->date->format('Y-m-d')] ??= $attendance;
        }

        $permissions = Permission::whereIn('permissible_id', $employeeIds)
            ->where('permissible_type', Employee::class)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get();
        foreach ($permissions as $permission) {
            $day = Carbon::parse($permission->start_date)->max($start);
            $last = Carbon::parse($permission->end_date)->min($end);
            while ($day->lte($last)) {
                $data[$permission->permissible_id]['permissions'][$day->format('Y-m-d')] =
                    self::permissionLabel($permission->type) ?? $permission->type;
                $day->addDay();
            }
        }

        return $data;
    }

    /**
     * Count one employee's month and work out the allowances.
     *
     * @param  array  $loaded  one entry of loadMonth()
     */
    public static function summarize(?Salary $salary, int $year, int $month, array $loaded): array
    {
        $baseSalary = (float) ($salary->base_salary ?? 0);
        $mealRate = (float) ($salary->meal_allowance ?? 0);
        $transportRate = (float) ($salary->transport_allowance ?? 0);
        $performanceRate = (float) ($salary->performance_allowance ?? 0);

        $summary = [
            'active_days' => 0,
            'present_days' => 0,
            'absent_days' => 0,
            'on_time_arrival_days' => 0,
            'on_time_departure_days' => 0,
            'late_arrival_days' => 0,
            'early_departure_days' => 0,
            'sick_days' => 0,
            'leave_days' => 0,
            'official_duty_days' => 0,
            'on_time_both_days' => 0,
            'total_meal_allowance' => 0,
            'total_transport_allowance' => 0,
            'total_performance_allowance' => 0,
        ];
        $days = [];

        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::create($year, $month, $d);
            $key = $date->format('Y-m-d');

            $shift = $loaded['shifts'][$key] ?? null;
            $schedule = $shift?->schedule;
            $attendance = $loaded['attendances'][$key] ?? null;
            $hasShift = $schedule && !empty($schedule->shift_name);
            $clockedIn = $attendance && ($attendance->clock_in || $attendance->clock_out);

            $permission = $loaded['permissions'][$key]
                ?? self::permissionLabel($attendance?->status_in);

            $row = [
                'date' => $date,
                'attendance' => $attendance,
                'shift' => $shift,
                'schedule' => $schedule,
                'status_in' => '-',
                'status_out' => '-',
                'is_present' => false,
                'is_on_time_both' => false,
                'daily_meal_allowance' => 0,
                'daily_transport_allowance' => 0,
                'daily_performance_allowance' => 0,
            ];

            if ($hasShift) {
                $summary['active_days']++;
            }

            if (!$hasShift) {
                // Not an active day: nothing is counted, even with an attendance or permission.
                $row['status_in'] = $row['status_out'] = $clockedIn ? 'Tanpa Shift' : 'Hari Libur';
            } elseif ($permission) {
                match ($permission) {
                    'Sakit' => $summary['sick_days']++,
                    'Cuti' => $summary['leave_days']++,
                    'Dinas Luar' => $summary['official_duty_days']++,
                    default => null,
                };
                $row['status_in'] = $row['status_out'] = $permission;
            } elseif (!$clockedIn) {
                $summary['absent_days']++;
                $row['status_in'] = $row['status_out'] = 'Tidak Hadir';
            } else {
                $summary['present_days']++;
                $row['is_present'] = true;

                $in = self::arrivalStatus($attendance, $schedule);
                $out = self::departureStatus($attendance, $schedule);

                if ($in === self::ON_TIME) {
                    $summary['on_time_arrival_days']++;
                } elseif ($in === self::LATE) {
                    $summary['late_arrival_days']++;
                }
                if ($out === self::ON_TIME) {
                    $summary['on_time_departure_days']++;
                } elseif ($out === self::EARLY_LEAVE) {
                    $summary['early_departure_days']++;
                }

                $row['status_in'] = $in ?? 'Tidak Absen Masuk';
                $row['status_out'] = $out ?? 'Belum Pulang';
                $row['daily_meal_allowance'] = $mealRate;
                $row['daily_transport_allowance'] = $transportRate;

                if ($in === self::ON_TIME && $out === self::ON_TIME) {
                    $summary['on_time_both_days']++;
                    $row['is_on_time_both'] = true;
                    $row['daily_performance_allowance'] = $performanceRate;
                }
            }

            $days[] = $row;
        }

        $summary['total_meal_allowance'] = $mealRate * $summary['present_days'];
        $summary['total_transport_allowance'] = $transportRate * $summary['present_days'];
        $summary['total_performance_allowance'] = $performanceRate * $summary['on_time_both_days'];
        $summary['perfect_days'] = $summary['on_time_both_days'];
        $summary['base_salary'] = $baseSalary;
        $summary['total_salary'] = $baseSalary + $summary['total_meal_allowance']
            + $summary['total_transport_allowance'] + $summary['total_performance_allowance'];
        $summary['days'] = $days;

        return $summary;
    }

    /**
     * Cuti / Sakit / Dinas Luar for the codes C / S / DL, otherwise null.
     */
    private static function permissionLabel($code): ?string
    {
        return is_string($code) ? (self::PERMISSION_LABELS[$code] ?? null) : null;
    }

    /**
     * The schedule's clock time placed on the calendar day of $moment.
     */
    private static function onDayOf($moment, $time): ?Carbon
    {
        if (empty($time)) {
            return null;
        }
        $moment = Carbon::parse($moment);

        return Carbon::parse($time)->setDate($moment->year, $moment->month, $moment->day);
    }
}
