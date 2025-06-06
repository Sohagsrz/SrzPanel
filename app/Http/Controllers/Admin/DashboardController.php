<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Domain;
use App\Models\Database;
use App\Models\Email;
use App\Models\Server;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Get all dashboard data in a single cache
        $dashboardData = Cache::remember('dashboard.data', 300, function () {
            return [
                'system' => $this->getSystemStats(),
                'counts' => $this->getCounts(),
                'activity' => $this->getRecentActivity(),
                'servers' => $this->getServerStatus()
            ];
        });
        // var_dump($dashboardData);
        // exit;

        return view('admin.dashboard', [
            'systemStats' => $dashboardData['system'],
            'counts' => $dashboardData['counts'],
            'recentActivity' => $dashboardData['activity'],
            'serverStatus' => $dashboardData['servers']
        ]);
    }

    public function refresh()
    {
        // Clear the dashboard cache
        Cache::forget('dashboard.data');
        
        // Get fresh data
        $dashboardData = [
            'system' => $this->getSystemStats(),
            'counts' => $this->getCounts(),
            'activity' => $this->getRecentActivity(),
            'servers' => $this->getServerStatus()
        ];

        return response()->json($dashboardData);
    }

    private function getSystemStats()
    {
        return Cache::remember('system.stats', 60, function () {
            return [
                'cpu' => $this->getCpuUsage(),
                'ram' => $this->getRamUsage(),
                'disk' => $this->getDiskUsage(),
                'bandwidth' => $this->getBandwidthUsage(),
            ];
        });
    }

    private function getCounts()
    {
        return Cache::remember('dashboard.counts', 300, function () {
            // Get all counts in a single query
            $userCounts = User::count();
            $resellerCounts = User::role('reseller')->count();
            $adminCounts = User::role('admin')->count();
            $domains = Domain::count();
            $databases = Database::count();
            $emails = Email::count();
            $activeServers = Server::where('status', 'active')->count();

            $resourceCounts = DB::table('servers')
                ->selectRaw('
                    COUNT(*) as total_servers,
                    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_servers
                ')
                ->first();

            return [
                'totalUsers' => $userCounts,
                'totalResellers' => $resellerCounts,
                'totalAdmins' => $adminCounts,
                'domains' => $domains,
                'databases' => $databases,
                'emails' => $emails,
                'activeServers' => $activeServers,
            ];
        });
    }

    private function getRecentActivity()
    {
        return Cache::remember('dashboard.recent_activity', 60, function () {
            return Activity::latest()
                ->take(10)
                ->select(['id', 'description', 'created_at'])
                ->get();
        });
    }

    private function getServerStatus()
    {
        return Cache::remember('dashboard.server_status', 60, function () {
            return Server::select(['id', 'name', 'status', 'last_check_at'])
                ->orderBy('last_check_at', 'desc')
                ->take(5)
                ->get();
        });
    }

    private function getCpuUsage()
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                'usage' => round($load[0] * 100),
                'cores' => (int)shell_exec('nproc'),
                'load' => $load
            ];
        }
        return ['usage' => 0, 'cores' => 0, 'load' => [0, 0, 0]];
    }

    private function getRamUsage()
    {
        if (PHP_OS === 'Linux') {
            $free = shell_exec('free');
            $free = (string)trim($free);
            $free_arr = explode("\n", $free);
            $mem = explode(" ", $free_arr[1]);
            $mem = array_filter($mem);
            $mem = array_merge($mem);
            $memory_usage = $mem[2]/$mem[1]*100;

            return [
                'total' => (int)$mem[1],
                'used' => (int)$mem[2],
                'free' => (int)$mem[3],
                'usage' => round($memory_usage)
            ];
        }
        return ['total' => 0, 'used' => 0, 'free' => 0, 'usage' => 0];
    }

    private function getDiskUsage()
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        $usage = ($used / $total) * 100;

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'usage' => round($usage)
        ];
    }

    private function getBandwidthUsage()
    {
        // This is a placeholder. Implement actual bandwidth monitoring
        return [
            'total' => 100000,
            'used' => 45000,
            'free' => 55000,
            'usage' => 45
        ];
    }
} 