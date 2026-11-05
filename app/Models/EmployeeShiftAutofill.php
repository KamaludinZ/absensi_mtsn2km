<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A date that the automatic shift filling has already handled. Manual
 * changes made on /admin/employee-shifts for that date are kept afterwards.
 */
class EmployeeShiftAutofill extends Model
{
    protected $fillable = [
        'date',
        'filled_count',
    ];

    protected $casts = [
        'date' => 'date',
        'filled_count' => 'integer',
    ];
}
