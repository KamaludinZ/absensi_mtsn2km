<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LoginAttempt extends Model
{
    protected $fillable = [
        'username',
        'ip_address',
        'user_agent',
        'successful',
        'failure_reason',
        'attempted_at',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function recordAttempt(string $username, bool $successful, ?string $failureReason = null): void
    {
        static::create([
            'username' => $username,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'successful' => $successful,
            'failure_reason' => $failureReason,
            'attempted_at' => now(),
        ]);
    }

    public static function getFailedAttemptsCount(string $username, int $minutes = 15): int
    {
        return static::where('username', $username)
            ->where('successful', false)
            ->where('attempted_at', '>=', Carbon::now()->subMinutes($minutes))
            ->count();
    }

    public static function isBlocked(string $username, int $maxAttempts = 5, int $minutes = 15): bool
    {
        return static::getFailedAttemptsCount($username, $minutes) >= $maxAttempts;
    }
}
