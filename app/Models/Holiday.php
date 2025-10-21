<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'description',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];
    
    protected $dates = [
        'date'
    ];
    
    public function getFormattedDateAttribute()
    {
        return Carbon::parse($this->date)->translatedFormat('d F Y');
    }
}