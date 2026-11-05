<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'description',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];
    
    protected $dates = [
        'date'
    ];
    
    /**
     * Keep /admin/employee-shifts in line with the holidays, whichever way
     * they change (form, bulk (de)activation, import, delete): a new holiday
     * releases that date's shifts, a removed one is filled again (today on).
     */
    protected static function booted(): void
    {
        static::created(function (self $holiday) {
            if ($holiday->is_active) {
                self::syncShifts(release: Carbon::parse($holiday->date)->startOfDay());
            }
        });

        static::updated(function (self $holiday) {
            // getOriginal() still holds the values from before this update
            $newOff = $holiday->is_active ? Carbon::parse($holiday->date)->startOfDay() : null;
            $oldOff = $holiday->getOriginal('is_active') && $holiday->getOriginal('date')
                ? Carbon::parse($holiday->getOriginal('date'))->startOfDay()
                : null;

            self::syncShifts(
                release: $newOff && !($oldOff && $oldOff->isSameDay($newOff)) ? $newOff : null,
                restore: $oldOff && !($newOff && $newOff->isSameDay($oldOff)) ? $oldOff : null,
            );
        });

        static::deleted(function (self $holiday) {
            if ($holiday->is_active) {
                self::syncShifts(restore: Carbon::parse($holiday->date)->startOfDay());
            }
        });
    }

    private static function syncShifts(?Carbon $release = null, ?Carbon $restore = null): void
    {
        try {
            if ($restore) {
                \App\Services\EmployeeShiftAutoFill::restoreDates([$restore]);
            }
            if ($release) {
                \App\Services\EmployeeShiftAutoFill::releaseDates([$release]);
            }
        } catch (\Throwable $e) {
            // Never block saving a holiday; the shift page can be fixed by hand
            \Illuminate\Support\Facades\Log::error('Syncing employee shifts with holidays failed: ' . $e->getMessage());
        }
    }

    public function getFormattedDateAttribute()
    {
        return Carbon::parse($this->date)->translatedFormat('d F Y');
    }
}