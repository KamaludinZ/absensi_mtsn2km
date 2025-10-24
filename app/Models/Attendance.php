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

    public function attendable(): MorphTo
    {
        return $this->morphTo();
    }
}
