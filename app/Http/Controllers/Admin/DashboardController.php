<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Database;
use App\Models\Domain;
use App\Models\Email;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index()
    {
        // Get resource usage stats with caching
        $stats = $this->cacheService->remember(
            CacheService::KEY_SYSTEM_STATS,
            fn() => [
                'cpu' => $this->getCPUUsage(),
                'ram' => $this->getRAMUsage(),
                'disk' => $this->getDiskUsage(),
                'bandwidth' => $this->getBandwidthUsage(),
            ],
            300 // Cache for 5 minutes
        );

        // Get counts with caching
        $counts = $this->cacheService->remember(
            'dashboard.counts',
            fn() => [
                'domains' => Domain::count(),
                'databases' => Database::count(),
                'emails' => Email::count(),
            ],
            3600 // Cache for 1 hour
        );

        // Get recent activity
        $recentActivity = $this->cacheService->remember(
            'dashboard.recent_activity',
            fn() => DB::table('activity_log')
                ->latest()
                ->take(10)
                ->get(),
            300 // Cache for 5 minutes
        );

        return view('admin.dashboard', compact('stats', 'counts', 'recentActivity'));
    }

    protected function getCPUUsage()
    {
        return $this->cacheService->remember(
            'system.cpu_usage',
            function () {
                // TODO: Implement actual CPU usage monitoring
                return [
                    'usage' => 25, // percentage
                    'cores' => 4,
                    'load' => [0.5, 0.3, 0.2], // 1min, 5min, 15min
                ];
            },
            60 // Cache for 1 minute
        );
    }

    protected function getRAMUsage()
    {
        return $this->cacheService->remember(
            'system.ram_usage',
            function () {
                // TODO: Implement actual RAM usage monitoring
                return [
                    'total' => 8192, // MB
                    'used' => 4096, // MB
                    'free' => 4096, // MB
                    'usage' => 50, // percentage
                ];
            },
            60 // Cache for 1 minute
        );
    }

    protected function getDiskUsage()
    {
        return $this->cacheService->remember(
            'system.disk_usage',
            function () {
                // TODO: Implement actual disk usage monitoring
                return [
                    'total' => 500000, // MB
                    'used' => 120000, // MB
                    'free' => 380000, // MB
                    'usage' => 24, // percentage
                ];
            },
            300 // Cache for 5 minutes
        );
    }

    protected function getBandwidthUsage()
    {
        return $this->cacheService->remember(
            'system.bandwidth_usage',
            function () {
                // TODO: Implement actual bandwidth usage monitoring
                return [
                    'total' => 100000, // MB
                    'used' => 45000, // MB
                    'free' => 55000, // MB
                    'usage' => 45, // percentage
                ];
            },
            300 // Cache for 5 minutes
        );
    }
} 