<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'selfie_path_in',
        'selfie_path_out',
        'status_in',
        'status_out',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'scheduled_start_time' => 'datetime',
        'scheduled_end_time' => 'datetime',
    ];

    public function attendable(): MorphTo
    {
        return $this->morphTo();
    }
}
