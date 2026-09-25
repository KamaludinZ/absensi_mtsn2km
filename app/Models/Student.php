<?php

namespace App\Models;

use App\Services\StudentKiosk;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nisn',
        'rfid_uid',
        'full_name',
        'rombel_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Free the RFID card when a student is (soft) deleted, so the card
        // can be given to another student.
        static::deleting(function (Student $student) {
            if ($student->rfid_uid !== null && !$student->isForceDeleting()) {
                $student->forceFill(['rfid_uid' => null])->saveQuietly();
            }
        });
    }

    /**
     * Store card numbers in one form (upper case, no spaces), whatever the
     * RFID reader sends.
     */
    protected function rfidUid(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => StudentKiosk::normalizeRfid($value),
        );
    }

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    public function attendances(): MorphMany
    {
        return $this->morphMany(Attendance::class, 'attendable');
    }

    public function permissions(): MorphMany
    {
        return $this->morphMany(Permission::class, 'permissible');
    }
}
