<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'permissible_id',
        'permissible_type',
        'start_date',
        'end_date',
        'reason',
        'supporting_document',
        'status',
        'approved_by',
        'rombel_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static function booted(): void
    {
        // Remember the student's class at the time of recording, so history
        // stays correct after class changes and promotions.
        static::creating(function (self $model) {
            if ($model->rombel_id === null && $model->permissible_type === Student::class) {
                $model->rombel_id = Student::withTrashed()->whereKey($model->permissible_id)->value('rombel_id');
            }
        });

        // Record moved to another person: take that person's current class.
        static::updating(function (self $model) {
            if ($model->isDirty(['permissible_id', 'permissible_type'])) {
                $model->rombel_id = $model->permissible_type === Student::class
                    ? Student::withTrashed()->whereKey($model->permissible_id)->value('rombel_id')
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
        return $this->rombel?->name ?? $this->permissible?->rombel?->name;
    }

    public function permissible(): MorphTo
    {
        return $this->morphTo();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
