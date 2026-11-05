<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'shift_type',
        'shift_name',
        'day_of_week',
        'start_time',
        'end_time',
        'apply_days',
        'apply_positions',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'apply_days' => 'array',
        'apply_positions' => 'array',
    ];

    /** ISO weekday numbers used by apply_days and the active days setting */
    public const ISO_DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    /**
     * Whether this shift is filled in automatically: it needs a shift name
     * (only named shifts count as active days), days and positions.
     */
    public function isAutoApplied(): bool
    {
        return !empty($this->shift_name) && !empty($this->apply_days) && !empty($this->apply_positions);
    }

    public function appliesTo(int $isoDay, ?string $position): bool
    {
        if (!$this->isAutoApplied() || !in_array($isoDay, array_map('intval', $this->apply_days), true)) {
            return false;
        }

        $position = self::normalizePosition($position);

        return $position !== '' && in_array($position, array_map([self::class, 'normalizePosition'], $this->apply_positions), true);
    }

    public static function normalizePosition(?string $position): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $position)));
    }

    /** "Senin - Jumat", "Senin, Rabu, Jumat" or "-" */
    public function applyDaysLabel(): string
    {
        $days = collect($this->apply_days ?? [])->map(fn ($d) => (int) $d)->filter(fn ($d) => isset(self::ISO_DAYS[$d]))->unique()->sort()->values();
        if ($days->isEmpty()) {
            return '-';
        }
        if ($days->count() >= 3 && $days->last() - $days->first() === $days->count() - 1) {
            return self::ISO_DAYS[$days->first()] . ' - ' . self::ISO_DAYS[$days->last()];
        }

        return $days->map(fn ($d) => self::ISO_DAYS[$d])->implode(', ');
    }
}
