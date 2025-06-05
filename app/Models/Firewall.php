<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Firewall extends Model
{
    protected $fillable = [
        'name',
        'type',
        'action',
        'source',
        'destination',
        'port',
        'protocol',
        'description',
        'is_active',
        'user_id',
        'last_modified'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_modified' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 