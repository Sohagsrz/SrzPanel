<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Security extends Model
{
    protected $fillable = [
        'type',
        'severity',
        'description',
        'source',
        'status',
        'resolved_at',
        'user_id',
        'details',
        'recommendation'
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'details' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 