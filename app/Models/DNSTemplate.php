<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DNSTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'records'
    ];

    protected $casts = [
        'records' => 'array'
    ];

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }
} 