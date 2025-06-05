<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ServerMonitorService
{
    protected $os;
    protected $cacheTime = 60; // Cache results for 1 minute
    protected $isWindows;

    public function __construct()
    {
        $this->os = strtoupper(substr(PHP_OS, 0, 3));
        $this->isWindows = $this->os === 'WIN';
    }

    public function getProcessList()
    {
        return Cache::remember('server_processes', $this->cacheTime, function () {
            try {
                if ($this->os === 'WIN') {
                    return $this->getWindowsProcesses();
                } else {
                    return $this->getLinuxProcesses();
                }
            } catch (\Exception $e) {
                Log::error("Failed to get process list: " . $e->getMessage());
                return [];
            }
        });
    }

    public function getSystemStats()
    {
        return Cache::remember('server_stats', $this->cacheTime, function () {
            try {
                if ($this->os === 'WIN') {
                    return $this->getWindowsStats();
                } else {
                    return $this->getLinuxStats();
                }
            } catch (\Exception $e) {
                Log::error("Failed to get system stats: " . $e->getMessage());
                return [];
            }
        });
    }

    public function getCpuUsage()
    {
        if ($this->isWindows) {
            return $this->getWindowsCpuUsage();
        }
        return $this->getLinuxCpuUsage();
    }

    public function getRamUsage()
    {
        if ($this->isWindows) {
            return $this->getWindowsRamUsage();
        }
        return $this->getLinuxRamUsage();
    }

    public function getDiskUsage()
    {
        if ($this->isWindows) {
            return $this->getWindowsDiskUsage();
        }
        return $this->getLinuxDiskUsage();
    }

    public function getBandwidthUsage()
    {
        // Bandwidth usage is more complex; for now, return dummy data
        return [
            'rx' => rand(100, 1000),
            'tx' => rand(100, 1000)
        ];
    }

    protected function getWindowsProcesses()
    {
        $result = Process::run("powershell -Command \"& {Get-Process | Select-Object ProcessName, Id, CPU, WorkingSet, StartTime | ConvertTo-Json}\"");
        
        if ($result->successful()) {
            $processes = json_decode($result->output(), true);
            return array_map(function ($process) {
                return [
                    'name' => $process['ProcessName'],
                    'pid' => $process['Id'],
                    'cpu' => $process['CPU'],
                    'memory' => $process['WorkingSet'] / 1024 / 1024, // Convert to MB
                    'started' => $process['StartTime'],
                ];
            }, $processes);
        }

        return [];
    }

    protected function getLinuxProcesses()
    {
        $result = Process::run("ps aux --sort=-%cpu | head -n 20");
        
        if ($result->successful()) {
            $lines = explode("\n", trim($result->output()));
            array_shift($lines); // Remove header
            
            $processes = [];
            foreach ($lines as $line) {
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) >= 11) {
                    $processes[] = [
                        'name' => $parts[10],
                        'pid' => $parts[1],
                        'cpu' => $parts[2],
                        'memory' => $parts[5],
                        'started' => $parts[8] . ' ' . $parts[9],
                    ];
                }
            }
            return $processes;
        }

        return [];
    }

    protected function getWindowsStats()
    {
        $result = Process::run("powershell -Command \"& {
            \$cpu = Get-Counter '\Processor(_Total)\% Processor Time' | Select-Object -ExpandProperty CounterSamples | Select-Object -ExpandProperty CookedValue
            \$memory = Get-Counter '\Memory\Available MBytes' | Select-Object -ExpandProperty CounterSamples | Select-Object -ExpandProperty CookedValue
            \$disk = Get-PSDrive C | Select-Object Used,Free
            \$network = Get-NetAdapter | Where-Object Status -eq 'Up' | Select-Object Name,ReceivedBytes,SentBytes
            [PSCustomObject]@{
                CPU = \$cpu
                MemoryAvailable = \$memory
                DiskUsed = \$disk.Used
                DiskFree = \$disk.Free
                Network = \$network
            } | ConvertTo-Json
        }\"");

        if ($result->successful()) {
            return json_decode($result->output(), true);
        }

        return [];
    }

    protected function getLinuxStats()
    {
        $stats = [];

        // CPU Usage
        $cpuResult = Process::run("top -bn1 | grep 'Cpu(s)' | awk '{print $2}'");
        if ($cpuResult->successful()) {
            $stats['cpu'] = floatval(trim($cpuResult->output()));
        }

        // Memory Usage
        $memResult = Process::run("free -m | grep Mem");
        if ($memResult->successful()) {
            $memParts = preg_split('/\s+/', trim($memResult->output()));
            $stats['memory'] = [
                'total' => $memParts[1],
                'used' => $memParts[2],
                'free' => $memParts[3],
            ];
        }

        // Disk Usage
        $diskResult = Process::run("df -h / | tail -n 1");
        if ($diskResult->successful()) {
            $diskParts = preg_split('/\s+/', trim($diskResult->output()));
            $stats['disk'] = [
                'total' => $diskParts[1],
                'used' => $diskParts[2],
                'free' => $diskParts[3],
                'usage' => $diskParts[4],
            ];
        }

        // Network Usage
        $netResult = Process::run("netstat -i | grep -v Kernel | grep -v Iface");
        if ($netResult->successful()) {
            $netLines = explode("\n", trim($netResult->output()));
            $stats['network'] = [];
            foreach ($netLines as $line) {
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) >= 4) {
                    $stats['network'][] = [
                        'interface' => $parts[0],
                        'rx_bytes' => $parts[3],
                        'tx_bytes' => $parts[7],
                    ];
                }
            }
        }

        return $stats;
    }

    protected function getLinuxCpuUsage()
    {
        $load = sys_getloadavg();
        return [
            'load1' => $load[0],
            'load5' => $load[1],
            'load15' => $load[2]
        ];
    }

    protected function getWindowsCpuUsage()
    {
        $output = shell_exec('wmic cpu get loadpercentage /value');
        preg_match('/LoadPercentage=(\d+)/', $output, $matches);
        return [
            'load1' => isset($matches[1]) ? (int)$matches[1] : 0,
            'load5' => 0,
            'load15' => 0
        ];
    }

    protected function getLinuxRamUsage()
    {
        $meminfo = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
        preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);
        $total = isset($total[1]) ? (int)$total[1] : 1;
        $available = isset($available[1]) ? (int)$available[1] : 0;
        $used = $total - $available;
        return [
            'total' => $total,
            'used' => $used,
            'free' => $available
        ];
    }

    protected function getWindowsRamUsage()
    {
        $output = shell_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value');
        preg_match('/FreePhysicalMemory=(\d+)/', $output, $free);
        preg_match('/TotalVisibleMemorySize=(\d+)/', $output, $total);
        $total = isset($total[1]) ? (int)$total[1] : 1;
        $free = isset($free[1]) ? (int)$free[1] : 0;
        $used = $total - $free;
        return [
            'total' => $total,
            'used' => $used,
            'free' => $free
        ];
    }

    protected function getLinuxDiskUsage()
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        return [
            'total' => $total,
            'used' => $used,
            'free' => $free
        ];
    }

    protected function getWindowsDiskUsage()
    {
        $total = disk_total_space('C:');
        $free = disk_free_space('C:');
        $used = $total - $free;
        return [
            'total' => $total,
            'used' => $used,
            'free' => $free
        ];
    }

    public function killProcess($pid)
    {
        try {
            if ($this->os === 'WIN') {
                Process::run("taskkill /F /PID {$pid}");
            } else {
                Process::run("kill -9 {$pid}");
            }
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to kill process: " . $e->getMessage());
            return false;
        }
    }
} 