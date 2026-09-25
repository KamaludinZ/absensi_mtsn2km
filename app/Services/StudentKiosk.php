<?php

namespace App\Services;

use App\Models\AttendanceDevice;
use App\Models\AttendanceSetting;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Rules for the public student attendance page (kiosk):
 * only registered school devices, and only during the attendance hours.
 */
class StudentKiosk
{
    public const COOKIE = 'attendance_device';

    public const DEFAULT_OPEN_BEFORE = 60;   // minutes before the earliest start time
    public const DEFAULT_CLOSE_AFTER = 60;   // minutes after the latest end time

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Register a new device and return the plain token to store in its cookie.
     */
    public static function registerDevice(string $name, ?int $userId): array
    {
        $token = Str::random(64);

        $device = AttendanceDevice::create([
            'name' => $name,
            'token_hash' => self::hashToken($token),
            'is_active' => true,
            'created_by' => $userId,
        ]);

        return [$device, $token];
    }

    public static function currentDevice(Request $request): ?AttendanceDevice
    {
        $token = $request->cookie(self::COOKIE);

        if (!is_string($token) || $token === '') {
            return null;
        }

        return AttendanceDevice::where('token_hash', self::hashToken($token))
            ->where('is_active', true)
            ->first();
    }

    public static function requiresRegisteredDevice(): bool
    {
        return filter_var(self::setting('student_require_registered_device', 'true'), FILTER_VALIDATE_BOOLEAN);
    }

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

        // active_days uses ISO numbers: 1 = Monday ... 7 = Sunday
        $activeDays = json_decode(Setting::get('active_days', '[1,2,3,4,5]'), true) ?: [1, 2, 3, 4, 5];
        if (!in_array($now->dayOfWeekIso, array_map('intval', $activeDays), true)) {
            return $closed('Hari ini bukan hari aktif sekolah.');
        }

        // Schedules use Carbon dayOfWeek: 0 = Sunday ... 6 = Saturday
        $schedules = Schedule::where('type', 'siswa')
            ->where('day_of_week', $now->dayOfWeek)
            ->get();
        if ($schedules->isEmpty()) {
            return $closed('Tidak ada jadwal absensi siswa untuk hari ini.');
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
     * Find an active student by the identifier typed on the kiosk.
     * Today this is the NISN. When RFID cards are introduced, look up the
     * card number here as well; the kiosk page does not need to change,
     * because USB RFID readers "type" the card number followed by Enter.
     */
    public static function findStudent(?string $identifier): ?Student
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        return Student::where('nisn', $identifier)
            ->where('is_active', true)
            ->with('rombel.grade')
            ->first();
    }

    private static function setting(string $key, $default)
    {
        $setting = AttendanceSetting::where('setting_key', $key)->first();

        return $setting ? $setting->setting_value : $default;
    }
}
