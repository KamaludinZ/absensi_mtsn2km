<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    protected $fillable = [
        'setting_key',
        'setting_value',
        'description'
    ];

    protected $casts = [
        'setting_value' => 'string',
    ];

    protected $table = 'attendance_settings';
}
