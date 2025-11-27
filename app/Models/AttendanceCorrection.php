<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'correctable_id',
        'correctable_type',
        'date',
        'original_clock_in',
        'corrected_clock_in',
        'original_clock_out',
        'corrected_clock_out',
        'reason',
        'supporting_document',
        'status',
        'approved_by',
    ];

    protected $casts = [
        'date' => 'date',
        'original_clock_in' => 'datetime',
        'corrected_clock_in' => 'datetime',
        'original_clock_out' => 'datetime',
        'corrected_clock_out' => 'datetime',
    ];

    public function correctable(): MorphTo
    {
        return $this->morphTo();
    }
    
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
