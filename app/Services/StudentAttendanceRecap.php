<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Permission;
use App\Models\Schedule;
use App\Models\Student;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Student attendance recap: for every student and school day in a period,
 * decide whether the student was present, late, sick, excused, absent without
 * notice (alpa), or has a permission still waiting for approval.
 *
 * School days come from the student schedule (Pengaturan Absen Siswa) minus
 * the active holidays; days after today are never counted.
 */
class StudentAttendanceRecap
{
    public const HADIR = 'H';
    public const TERLAMBAT = 'T';
    public const SAKIT = 'S';
    public const IZIN = 'I';
    public const ALPA = 'A';
    public const MENUNGGU = 'M';
    public const LIBUR = 'L';

    /** Codes that are counted per student */
    public const COUNTED = [self::HADIR, self::TERLAMBAT, self::SAKIT, self::IZIN, self::ALPA, self::MENUNGGU];

    public const LABELS = [
        self::HADIR => 'Hadir',
        self::TERLAMBAT => 'Terlambat',
        self::SAKIT => 'Sakit',
        self::IZIN => 'Izin',
        self::ALPA => 'Alpa',
        self::MENUNGGU => 'Izin belum disetujui',
        self::LIBUR => 'Libur',
    ];

    /**
     * @param  Collection<int, Student>  $students
     * @param  array<int>|null  $rombelIds  only count records made while the student was in one of these classes
     * @return array{school_days: Collection<string, Carbon>, holidays: Collection<string, string>, rows: Collection<int, array>}
     */
    public static function build(Collection $students, Carbon $start, Carbon $end, ?array $rombelIds = null): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();
        $lastCounted = $end->copy()->min(Carbon::today());

        $holidays = Holiday::where('is_active', true)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->mapWithKeys(fn ($h) => [$h->date->toDateString() => $h->name]);

        $scheduleDays = Schedule::where('type', 'siswa')->pluck('day_of_week')->map(fn ($d) => (int) $d)->unique()->all();

        $schoolDays = collect();
        if ($start->lte($lastCounted)) {
            foreach (CarbonPeriod::create($start, $lastCounted) as $day) {
                if (in_array($day->dayOfWeek, $scheduleDays, true) && !$holidays->has($day->toDateString())) {
                    $schoolDays->put($day->toDateString(), $day->copy());
                }
            }
        }

        $studentIds = $students->pluck('id')->all();
        $inClass = function ($record, Student $student) use ($rombelIds) {
            if ($rombelIds === null) {
                return true;
            }
            $rombelId = $record->rombel_id ?? $student->rombel_id;

            return in_array((int) $rombelId, $rombelIds, true);
        };

        $attendances = Attendance::where('attendable_type', Student::class)
            ->whereIn('attendable_id', $studentIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('clock_in')
            ->get()
            ->groupBy('attendable_id');

        $permissions = Permission::where('permissible_type', Student::class)
            ->whereIn('permissible_id', $studentIds)
            ->whereIn('status', ['approved', 'pending'])
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->orderBy('start_date')
            ->get()
            ->groupBy('permissible_id');

        $rows = $students->map(function (Student $student) use ($attendances, $permissions, $schoolDays, $inClass, $start, $end) {
            $byDate = [];
            foreach ($attendances->get($student->id, collect()) as $attendance) {
                if ($inClass($attendance, $student)) {
                    $byDate[$attendance->date->toDateString()] = $attendance;
                }
            }

            // Approved permissions win over pending ones on the same day
            $permissionByDate = [];
            foreach ($permissions->get($student->id, collect()) as $permission) {
                if (!$inClass($permission, $student)) {
                    continue;
                }
                $from = $permission->start_date->copy()->max($start);
                $to = $permission->end_date->copy()->min($end);
                foreach (CarbonPeriod::create($from, $to) as $day) {
                    $key = $day->toDateString();
                    if (!isset($permissionByDate[$key]) || $permission->status === 'approved') {
                        $permissionByDate[$key] = $permission;
                    }
                }
            }

            // Attendance on a day that is not a school day still counts as present
            $days = $schoolDays->keys()->merge(array_keys($byDate))->unique()->sort()->values();

            $counts = array_fill_keys(self::COUNTED, 0);
            $daily = [];
            foreach ($days as $date) {
                $attendance = $byDate[$date] ?? null;
                $permission = $permissionByDate[$date] ?? null;

                if ($attendance) {
                    $code = $attendance->status_in === 'Terlambat' ? self::TERLAMBAT : self::HADIR;
                } elseif ($permission && $permission->status === 'approved') {
                    $code = $permission->type === 'S' ? self::SAKIT : self::IZIN;
                } elseif ($permission) {
                    $code = self::MENUNGGU;
                } else {
                    $code = self::ALPA;
                }

                $counts[$code]++;
                $daily[$date] = ['code' => $code, 'attendance' => $attendance, 'permission' => $permission];
            }

            $effectiveDays = count($days);
            $present = $counts[self::HADIR] + $counts[self::TERLAMBAT];

            return [
                'student' => $student,
                'counts' => $counts,
                'present' => $present,
                'effective_days' => $effectiveDays,
                'percentage' => $effectiveDays > 0 ? round($present / $effectiveDays * 100, 1) : null,
                'daily' => $daily,
            ];
        })->values();

        return [
            'school_days' => $schoolDays,
            'holidays' => $holidays,
            'rows' => $rows,
        ];
    }

    /**
     * Every calendar day of the period with its recap code for one student,
     * including holidays and days without a student schedule.
     *
     * @return Collection<int, array{date: Carbon, code: ?string, label: string, attendance: ?Attendance, permission: ?Permission, note: ?string}>
     */
    public static function calendar(array $row, Carbon $start, Carbon $end, Collection $holidays): Collection
    {
        $scheduleDays = Schedule::where('type', 'siswa')->pluck('day_of_week')->map(fn ($d) => (int) $d)->all();

        return collect(CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()))
            ->map(function (Carbon $day) use ($row, $holidays, $scheduleDays) {
                $key = $day->toDateString();
                $entry = $row['daily'][$key] ?? null;

                if ($entry) {
                    $code = $entry['code'];
                    $label = self::LABELS[$code];
                    if ($entry['permission'] && in_array($code, [self::SAKIT, self::IZIN, self::MENUNGGU], true)) {
                        $note = $entry['permission']->reason;
                    }
                } elseif ($holidays->has($key)) {
                    $code = self::LIBUR;
                    $label = 'Libur';
                    $note = $holidays->get($key);
                } elseif (!in_array($day->dayOfWeek, $scheduleDays, true)) {
                    $code = null;
                    $label = 'Bukan hari sekolah';
                } else {
                    // School day that has not happened yet
                    $code = null;
                    $label = '-';
                }

                return [
                    'date' => $day->copy(),
                    'code' => $code,
                    'label' => $label,
                    'attendance' => $entry['attendance'] ?? null,
                    'permission' => $entry['permission'] ?? null,
                    'note' => $note ?? null,
                ];
            })->values();
    }
}
