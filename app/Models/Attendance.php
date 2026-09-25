<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendable_id',
        'attendable_type',
        'date',
        'shift_name',
        'shift_type',
        'scheduled_start_time',
        'scheduled_end_time',
        'clock_in',
        'clock_out',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_in_accuracy',
        'clock_out_latitude',
        'clock_out_longitude',
        'clock_out_accuracy',
        'selfie_path_in',
        'selfie_path_out',
        'status_in',
        'status_out',
        'rombel_id',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'scheduled_start_time' => 'datetime',
        'scheduled_end_time' => 'datetime',
        'clock_in_latitude' => 'decimal:8',
        'clock_in_longitude' => 'decimal:8',
        'clock_in_accuracy' => 'decimal:2',
        'clock_out_latitude' => 'decimal:8',
        'clock_out_longitude' => 'decimal:8',
        'clock_out_accuracy' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Remember the student's class at the time of recording, so history
        // stays correct after class changes and promotions.
        static::creating(function (self $model) {
            if ($model->rombel_id === null && $model->attendable_type === Student::class) {
                $model->rombel_id = Student::withTrashed()->whereKey($model->attendable_id)->value('rombel_id');
            }
        });

        // Record moved to another person: take that person's current class.
        static::updating(function (self $model) {
            if ($model->isDirty(['attendable_id', 'attendable_type'])) {
                $model->rombel_id = $model->attendable_type === Student::class
                    ? Student::withTrashed()->whereKey($model->attendable_id)->value('rombel_id')
                    : null;
            }
        });
    }

    /**
     * Class of the student when this record was made.
     */
    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    /**
     * Name of the class at the time of recording (falls back to the
     * student's current class for old records without one).
     */
    public function getRombelNameAttribute(): ?string
    {
        return $this->rombel?->name ?? $this->attendable?->rombel?->name;
    }

    public function attendable(): MorphTo
    {
        return $this->morphTo();
    }
}
