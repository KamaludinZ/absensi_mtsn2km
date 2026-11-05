<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\EmployeeShiftAutofill as FilledDate;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fills /admin/employee-shifts from the shifts' Hari Penerapan and Jabatan
 * Penerapan (set in /admin/schedules/pegawai):
 *
 * - only today and later dates, never past days (reports stay as they were);
 * - only active days (/admin/holidays/active-days) that are not holidays;
 * - only active employees whose position is listed on the shift;
 * - only empty cells: an assigned shift is never replaced.
 *
 * Every date that has been handled is remembered, so the daily run does not
 * refill a cell an admin cleared on purpose. The "Terapkan Otomatis" button
 * runs it again on purpose, still only filling empty cells.
 *
 * The calendar (active days + holidays) also decides which dates of
 * /admin/employee-shifts are open. When it changes, releaseDates() and
 * restoreDates() bring the assigned shifts in line.
 */
class EmployeeShiftAutoFill
{
    /**
     * Dates from today until the end of the month that were not handled yet.
     * Used by the daily schedule and when /admin/employee-shifts is opened.
     */
    public static function fillPending(int $year, int $month, ?Carbon $today = null): int
    {
        $dates = self::remainingDates($year, $month, $today);
        if (!$dates) {
            return 0;
        }

        $handled = FilledDate::whereDate('date', '>=', $dates[0]->toDateString())
            ->whereDate('date', '<=', end($dates)->toDateString())
            ->get()
            ->map(fn ($row) => $row->date->toDateString())
            ->all();

        return self::fill(array_values(array_filter($dates, fn ($d) => !in_array($d->toDateString(), $handled, true))));
    }

    /**
     * Only today, when it was not handled yet. Cheap enough to run on clock-in,
     * as a safety net when the scheduler is not running.
     */
    public static function fillToday(?Carbon $today = null): int
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();
        if (FilledDate::whereDate('date', $today->toDateString())->exists()) {
            return 0;
        }

        return self::fill([$today]);
    }

    /**
     * "Terapkan Otomatis" button: fill the empty cells from today until the
     * end of the month again (for example after adding an employee or
     * changing a shift's days or positions).
     */
    public static function fillMonth(int $year, int $month, ?Carbon $today = null): int
    {
        return self::fill(self::remainingDates($year, $month, $today));
    }

    /**
     * Active weekdays from /admin/holidays/active-days (ISO, 1 = Senin).
     */
    public static function activeDays(): array
    {
        return array_values(array_unique(array_map('intval', json_decode(Setting::get('active_days', '[1,2,3,4,5]'), true) ?: [1, 2, 3, 4, 5])));
    }

    /**
     * Dates of a period that are not work days, with the reason:
     * [Y-m-d => 'Libur: ...' | 'Bukan hari aktif'].
     */
    public static function offDays(Carbon $from, Carbon $to): array
    {
        $activeDays = self::activeDays();
        $holidays = Holiday::where('is_active', true)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->get()
            ->mapWithKeys(fn ($h) => [$h->date->toDateString() => $h->name]);

        $off = [];
        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            if ($holidays->has($key)) {
                $off[$key] = 'Libur: ' . $holidays[$key];
            } elseif (!in_array($day->dayOfWeekIso, $activeDays, true)) {
                $off[$key] = 'Bukan hari aktif';
            }
        }

        return $off;
    }

    /**
     * Dates that became off days (holiday added, weekday no longer active).
     * Their shifts are removed for employees who did not clock in or out that
     * day, with their empty attendance rows; whoever did work keeps the shift,
     * so the day still counts for them.
     *
     * @param  iterable<Carbon|string>  $dates
     */
    public static function releaseDates(iterable $dates): int
    {
        $removed = 0;

        DB::transaction(function () use ($dates, &$removed) {
            foreach (collect($dates)->map(fn ($d) => Carbon::parse($d)->toDateString())->unique() as $date) {
                $shifts = EmployeeShift::whereDate('date', $date)->get();
                foreach ($shifts as $shift) {
                    $attendance = Attendance::where('attendable_type', Employee::class)
                        ->where('attendable_id', $shift->employee_id)
                        ->whereDate('date', $date)
                        ->first();

                    if ($attendance && ($attendance->clock_in || $attendance->clock_out)) {
                        continue;
                    }
                    if ($attendance && !in_array($attendance->status_in, ['C', 'S', 'DL'], true)) {
                        $attendance->delete();
                    }
                    $shift->delete();
                    $removed++;
                }
            }
        });

        return $removed;
    }

    /**
     * Dates that became work days again (holiday removed, weekday active
     * again): from today on they are filled automatically again, empty cells
     * only. Past dates are left as they are.
     *
     * @param  iterable<Carbon|string>  $dates
     */
    public static function restoreDates(iterable $dates): int
    {
        $today = Carbon::today();
        $dates = collect($dates)
            ->map(fn ($d) => Carbon::parse($d)->startOfDay())
            ->filter(fn ($d) => $d->gte($today))
            ->unique(fn ($d) => $d->toDateString())
            ->sort()
            ->values();

        foreach ($dates as $date) {
            FilledDate::whereDate('date', $date->toDateString())->delete();
        }

        return self::fill($dates->all());
    }

    /**
     * The active days setting changed: from today on, weekdays that are no
     * longer active are released and newly active ones are filled again.
     */
    public static function activeDaysChanged(array $old, array $new): void
    {
        $old = array_map('intval', $old);
        $new = array_map('intval', $new);
        $removed = array_diff($old, $new);
        $added = array_diff($new, $old);
        $today = Carbon::today();

        if ($removed) {
            $dates = EmployeeShift::whereDate('date', '>=', $today->toDateString())
                ->get(['date'])
                ->map(fn ($s) => $s->date->startOfDay())
                ->filter(fn ($d) => in_array($d->dayOfWeekIso, $removed, true));
            self::releaseDates($dates);
        }

        if ($added) {
            // Up to the furthest month that was already filled, at least this month
            $lastHandled = FilledDate::max('date');
            $until = $today->copy()->endOfMonth()->max($lastHandled ? Carbon::parse($lastHandled)->endOfMonth() : $today);
            $dates = [];
            for ($day = $today->copy(); $day->lte($until); $day->addDay()) {
                if (in_array($day->dayOfWeekIso, $added, true)) {
                    $dates[] = $day->copy();
                }
            }
            self::restoreDates($dates);
        }
    }

    /**
     * Whether any shift has days and positions set.
     */
    public static function hasRules(): bool
    {
        return self::rules()->isNotEmpty();
    }

    /**
     * Write schedule details of the given shifts into the attendance rows of
     * those days (creating empty rows), as /admin/employee-shifts does.
     */
    public static function applyToAttendance(Collection $shifts): void
    {
        foreach ($shifts as $shift) {
            if (!$shift->schedule) {
                continue;
            }

            $attendance = Attendance::where('attendable_type', Employee::class)
                ->where('attendable_id', $shift->employee_id)
                ->whereDate('date', $shift->date->toDateString())
                ->first()
                ?? new Attendance([
                    'attendable_type' => Employee::class,
                    'attendable_id' => $shift->employee_id,
                    'date' => $shift->date->toDateString(),
                ]);

            // Clock times and statuses are left untouched
            $attendance->shift_name = $shift->schedule->shift_name;
            $attendance->shift_type = $shift->schedule->shift_type;
            $attendance->scheduled_start_time = $shift->schedule->start_time;
            $attendance->scheduled_end_time = $shift->schedule->end_time;
            $attendance->save();
        }
    }

    /**
     * @param  Carbon[]  $dates
     */
    private static function fill(array $dates): int
    {
        if (!$dates) {
            return 0;
        }

        $rules = self::rules();
        if ($rules->isEmpty()) {
            // Nothing configured yet: leave the dates unhandled so they are
            // filled once a shift gets days and positions.
            return 0;
        }

        $first = $dates[0]->toDateString();
        $last = end($dates)->toDateString();

        $offDays = self::offDays(Carbon::parse($first), Carbon::parse($last));

        $employees = Employee::where('is_active', true)->orderBy('id')->get(['id', 'position']);
        $taken = EmployeeShift::whereIn('employee_id', $employees->pluck('id'))
            ->whereDate('date', '>=', $first)
            ->whereDate('date', '<=', $last)
            ->get(['employee_id', 'date'])
            ->mapWithKeys(fn ($s) => [$s->employee_id . '|' . $s->date->toDateString() => true])
            ->all();

        return DB::transaction(function () use ($dates, $rules, $offDays, $employees, $taken) {
            $created = collect();

            foreach ($dates as $date) {
                $key = $date->toDateString();
                $count = 0;

                if (!isset($offDays[$key])) {
                    foreach ($employees as $employee) {
                        if (isset($taken[$employee->id . '|' . $key])) {
                            continue;
                        }
                        $schedule = $rules->first(fn ($s) => $s->appliesTo($date->dayOfWeekIso, $employee->position));
                        if (!$schedule) {
                            continue;
                        }

                        $shift = EmployeeShift::create([
                            'employee_id' => $employee->id,
                            'schedule_id' => $schedule->id,
                            'date' => $key,
                            'is_active' => true,
                        ]);
                        $shift->setRelation('schedule', $schedule);
                        $created->push($shift);
                        $count++;
                    }
                }

                $handled = FilledDate::whereDate('date', $key)->first() ?? new FilledDate(['date' => $key]);
                $handled->filled_count = ($handled->filled_count ?? 0) + $count;
                $handled->save();
            }

            self::applyToAttendance($created);

            return $created->count();
        });
    }

    /** Shifts that are filled in automatically, oldest first */
    private static function rules(): Collection
    {
        return Schedule::where('type', 'pegawai')->orderBy('id')->get()->filter->isAutoApplied()->values();
    }

    /**
     * @return Carbon[] today (or the 1st, for a future month) until the end of the month
     */
    private static function remainingDates(int $year, int $month, ?Carbon $today): array
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();
        $day = Carbon::create($year, $month, 1)->startOfDay()->max($today);
        $end = Carbon::create($year, $month, 1)->endOfMonth()->startOfDay();

        $dates = [];
        while ($day->lte($end)) {
            $dates[] = $day->copy();
            $day->addDay();
        }

        return $dates;
    }
}
