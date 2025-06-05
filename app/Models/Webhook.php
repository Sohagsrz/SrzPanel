<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $fillable = [
        'user_id',
        'url',
        'events',
        'secret',
        'is_active',
        'last_triggered_at',
        'failure_count'
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'failure_count' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shouldTrigger(string $event): bool
    {
        return $this->is_active && in_array($event, $this->events);
    }

    public function incrementFailureCount(): void
    {
        $this->increment('failure_count');
        
        if ($this->failure_count >= 5) {
            $this->update(['is_active' => false]);
        }
    }

    public function resetFailureCount(): void
    {
        $this->update([
            'failure_count' => 0,
            'last_triggered_at' => now()
        ]);
    }
} 