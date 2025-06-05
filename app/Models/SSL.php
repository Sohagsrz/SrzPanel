<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SSL extends Model
{
    protected $fillable = [
        'domain_id',
        'provider',
        'certificate',
        'private_key',
        'chain',
        'issued_at',
        'expires_at',
        'status',
        'auto_renew',
        'last_renewed',
        'user_id'
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_renewed' => 'datetime',
        'auto_renew' => 'boolean'
    ];

    protected $hidden = [
        'certificate',
        'private_key',
        'chain'
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 