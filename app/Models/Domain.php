<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Domain extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'status',
        'document_root',
        'ssl_enabled',
        'ssl_expires_at',
        'dns_records',
        'notes',
    ];

    protected $casts = [
        'ssl_enabled' => 'boolean',
        'ssl_expires_at' => 'datetime',
        'dns_records' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'ssl_enabled', 'ssl_expires_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function databases()
    {
        return $this->hasMany(Database::class);
    }

    public function emailAccounts()
    {
        return $this->hasMany(Email::class);
    }
} 