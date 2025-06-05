<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Backup extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'type',
        'size',
        'path',
        'status',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'size' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'status', 'completed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function database()
    {
        return $this->belongsTo(Database::class);
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
} 