<?php

namespace App\Services;

use App\Models\AttendanceSetting;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Models\Student;
use Carbon\Carbon;

/**
 * Rules for the public student attendance page: it has no login, so it is
 * only open during the attendance hours, and students identify themselves
 * with their NISN or their RFID student card.
 */
class StudentKiosk
{
    public const DEFAULT_OPEN_BEFORE = 60;   // minutes before the earliest start time
    public const DEFAULT_CLOSE_AFTER = 60;   // minutes after the latest end time

    public static function openMinutesBefore(): int
    {
        return (int) self::setting('student_open_minutes_before', self::DEFAULT_OPEN_BEFORE);
    }

    public static function closeMinutesAfter(): int
    {
        return (int) self::setting('student_close_minutes_after', self::DEFAULT_CLOSE_AFTER);
    }

    /**
     * Is the student attendance page open right now?
     *
     * @return array{open: bool, reason: ?string, opens_at: ?Carbon, closes_at: ?Carbon}
     */
    public static function window(?Carbon $now = null): array
    {
        $now = $now ?: Carbon::now();
        $closed = fn (string $reason) => ['open' => false, 'reason' => $reason, 'opens_at' => null, 'closes_at' => null];

        $holiday = Holiday::whereDate('date', $now->toDateString())
            ->where('is_active', true)
            ->first();
        if ($holiday) {
            return $closed('Hari ini libur: ' . $holiday->name . '.');
        }

        // School days are the days of the student schedule (Pengaturan Absen
        // Siswa), independent of the employee working days.
        // Schedules use Carbon dayOfWeek: 0 = Sunday ... 6 = Saturday
        $schedules = Schedule::where('type', 'siswa')
            ->where('day_of_week', $now->dayOfWeek)
            ->get();
        if ($schedules->isEmpty()) {
            return $closed('Hari ini bukan hari sekolah (tidak ada jadwal absensi siswa).');
        }

        $start = $schedules->map(fn ($s) => Carbon::parse($s->start_time)->format('H:i:s'))->min();
        $end = $schedules->map(fn ($s) => Carbon::parse($s->end_time)->format('H:i:s'))->max();

        $opensAt = $now->copy()->setTimeFromTimeString($start)->subMinutes(self::openMinutesBefore());
        $closesAt = $now->copy()->setTimeFromTimeString($end)->addMinutes(self::closeMinutesAfter());

        $open = $now->betweenIncluded($opensAt, $closesAt);

        return [
            'open' => $open,
            'reason' => $open ? null : 'Absensi siswa hanya dibuka pukul ' . $opensAt->format('H:i') . ' – ' . $closesAt->format('H:i') . '.',
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
        ];
    }

    /**
     * RFID readers differ in what they send (spaces, lower/upper case hex),
     * so card numbers are always stored and compared in this form.
     */
    public static function normalizeRfid(?string $value): ?string
    {
        $value = strtoupper(preg_replace('/\s+/', '', (string) $value));

        return $value === '' ? null : $value;
    }

    /**
     * Find an active student by what was typed or tapped on the attendance
     * page: the NISN, or the student card number from a USB RFID reader
     * (which "types" the card number followed by Enter).
     */
    public static function findStudent(?string $identifier): ?Student
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        return Student::where('is_active', true)
            ->where(function ($query) use ($identifier) {
                $query->where('nisn', $identifier)
                    ->orWhere('rfid_uid', self::normalizeRfid($identifier));
            })
            ->with('rombel.grade')
            ->first();
    }

    private static function setting(string $key, $default)
    {
        $setting = AttendanceSetting::where('setting_key', $key)->first();

        return $setting ? $setting->setting_value : $default;
    }
}
