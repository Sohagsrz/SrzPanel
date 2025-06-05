<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Update extends Model
{
    protected $fillable = [
        'version',
        'type',
        'description',
        'status',
        'installed_at',
        'installed_by',
        'rollback_version',
        'details',
        'is_required'
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'details' => 'array',
        'is_required' => 'boolean'
    ];

    public function installer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installed_by');
    }
} 