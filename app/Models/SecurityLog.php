<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'event_type',
        'description',
        'metadata',
        'severity',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a security event
     */
    public static function logEvent(string $eventType, string $description, array $details = [], string $severity = 'info', ?int $userId = null): void
    {
        self::create([
            'user_id' => $userId ?? auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'event_type' => $eventType,
            'description' => $description,
            'metadata' => $details,
            'severity' => $severity,
        ]);
    }
}