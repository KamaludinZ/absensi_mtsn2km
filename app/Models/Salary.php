<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salary extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'base_salary',
        'meal_allowance',
        'transport_allowance',
        'performance_allowance',
        'other_allowances',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'meal_allowance' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'performance_allowance' => 'decimal:2',
        'other_allowances' => 'decimal:2',
    ];

    public function getTotalSalaryAttribute()
    {
        return $this->base_salary + $this->meal_allowance + $this->transport_allowance + $this->performance_allowance;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
