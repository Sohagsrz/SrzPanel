<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Database extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'domain_id',
        'username',
        'password',
        'size',
        'status',
        'last_backup_at',
        'notes',
    ];

    protected $casts = [
        'last_backup_at' => 'datetime',
        'size' => 'integer',
    ];

    protected $hidden = [
        'password',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'size', 'last_backup_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    public function backups()
    {
        return $this->hasMany(Backup::class);
    }
} 