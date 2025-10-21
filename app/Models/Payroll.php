<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'base_salary',
        'deductions',
        'bonuses',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'deductions' => 'decimal:2',
        'bonuses' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getTotalSalaryAttribute(): float
    {
        return $this->base_salary - $this->deductions + $this->bonuses;
    }
}
