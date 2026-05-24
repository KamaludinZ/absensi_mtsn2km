<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nisn',
        'full_name',
        'rombel_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    public function attendances(): MorphMany
    {
        return $this->morphMany(Attendance::class, 'attendable');
    }

    public function permissions(): MorphMany
    {
        return $this->morphMany(Permission::class, 'permissible');
    }
}
