<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cron extends Model
{
    protected $fillable = [
        'name',
        'command',
        'schedule',
        'description',
        'is_active',
        'last_run',
        'next_run',
        'user_id',
        'output',
        'error',
        'status'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_run' => 'datetime',
        'next_run' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 