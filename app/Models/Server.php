<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    protected $fillable = [
        'name',
        'hostname',
        'ip_address',
        'type',
        'status',
        'os',
        'cpu',
        'memory',
        'disk',
        'last_check',
        'details',
        'is_active'
    ];

    protected $casts = [
        'last_check' => 'datetime',
        'details' => 'array',
        'is_active' => 'boolean'
    ];

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }

    public function ftpAccounts(): HasMany
    {
        return $this->hasMany(FTP::class);
    }
} 