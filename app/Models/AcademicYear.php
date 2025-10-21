<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'year_name',
        'status',
    ];

    public function rombels(): HasMany
    {
        return $this->hasMany(Rombel::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
