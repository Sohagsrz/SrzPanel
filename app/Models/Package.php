<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'is_active',
        'disk_space',
        'bandwidth',
        'domains',
        'subdomains',
        'email_accounts',
        'databases',
        'ftp_accounts',
        'ssl_enabled',
        'backup_enabled',
        'firewall_enabled',
        'dns_management',
        'cron_jobs',
        'shell_access',
        'php_version',
        'max_execution_time',
        'memory_limit',
        'upload_max_filesize',
        'post_max_size',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ssl_enabled' => 'boolean',
        'backup_enabled' => 'boolean',
        'firewall_enabled' => 'boolean',
        'dns_management' => 'boolean',
        'cron_jobs' => 'boolean',
        'shell_access' => 'boolean',
        'price' => 'decimal:2',
    ];
} 