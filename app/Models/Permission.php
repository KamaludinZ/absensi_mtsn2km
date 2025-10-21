<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'permissible_id',
        'permissible_type',
        'start_date',
        'end_date',
        'reason',
        'supporting_document',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function permissible(): MorphTo
    {
        return $this->morphTo();
    }
}
