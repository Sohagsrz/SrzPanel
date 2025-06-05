<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Email extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'domain_id',
        'username',
        'password',
        'quota',
        'used_quota',
        'status',
        'forward_to',
        'autoresponder_enabled',
        'autoresponder_subject',
        'autoresponder_message',
        'notes',
    ];

    protected $casts = [
        'quota' => 'integer',
        'used_quota' => 'integer',
        'autoresponder_enabled' => 'boolean',
    ];

    protected $hidden = [
        'password',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['username', 'status', 'quota', 'used_quota', 'autoresponder_enabled'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    public function getFullEmailAttribute()
    {
        return $this->username . '@' . $this->domain->name;
    }
} 