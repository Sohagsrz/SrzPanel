<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MonitoringService
{
    protected $isWindows;
    protected $cacheTime = 60; // Cache time in seconds

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public function getSystemMetrics(): array
    {
        return Cache::remember('system_metrics', $this->cacheTime, function () {
            return [
                'cpu' => $this->getCPUMetrics(),
                'memory' => $this->getMemoryMetrics(),
                'disk' => $this->getDiskMetrics(),
                'network' => $this->getNetworkMetrics(),
                'load' => $this->getLoadMetrics(),
                'uptime' => $this->getUptime(),
                'timestamp' => now()->toIso8601String()
            ];
        });
    }

    public function getServiceStatus(): array
    {
        return Cache::remember('service_status', $this->cacheTime, function () {
            return [
                'apache' => $this->getApacheStatus(),
                'nginx' => $this->getNginxStatus(),
                'mysql' => $this->getMySQLStatus(),
                'php' => $this->getPHPStatus(),
                'timestamp' => now()->toIso8601String()
            ];
        });
    }

    public function getProcessList(): array
    {
        return Cache::remember('process_list', $this->cacheTime, function () {
            if ($this->isWindows) {
                return $this->getWindowsProcessList();
            } else {
                return $this->getLinuxProcessList();
            }
        });
    }

    public function getResourceAlerts(): array
    {
        $metrics = $this->getSystemMetrics();
        $alerts = [];

        // CPU Usage Alert
        if ($metrics['cpu']['usage'] > 90) {
            $alerts[] = [
                'type' => 'cpu',
                'level' => 'critical',
                'message' => 'CPU usage is above 90%',
                'value' => $metrics['cpu']['usage']
            ];
        } elseif ($metrics['cpu']['usage'] > 70) {
            $alerts[] = [
                'type' => 'cpu',
                'level' => 'warning',
                'message' => 'CPU usage is above 70%',
                'value' => $metrics['cpu']['usage']
            ];
        }

        // Memory Usage Alert
        if ($metrics['memory']['used_percent'] > 90) {
            $alerts[] = [
                'type' => 'memory',
                'level' => 'critical',
                'message' => 'Memory usage is above 90%',
                'value' => $metrics['memory']['used_percent']
            ];
        } elseif ($metrics['memory']['used_percent'] > 70) {
            $alerts[] = [
                'type' => 'memory',
                'level' => 'warning',
                'message' => 'Memory usage is above 70%',
                'value' => $metrics['memory']['used_percent']
            ];
        }

        // Disk Usage Alert
        foreach ($metrics['disk'] as $disk) {
            if ($disk['used_percent'] > 90) {
                $alerts[] = [
                    'type' => 'disk',
                    'level' => 'critical',
                    'message' => "Disk {$disk['mount']} usage is above 90%",
                    'value' => $disk['used_percent']
                ];
            } elseif ($disk['used_percent'] > 70) {
                $alerts[] = [
                    'type' => 'disk',
                    'level' => 'warning',
                    'message' => "Disk {$disk['mount']} usage is above 70%",
                    'value' => $disk['used_percent']
                ];
            }
        }

        return $alerts;
    }

    protected function getCPUMetrics(): array
    {
        if ($this->isWindows) {
            return $this->getWindowsCPUMetrics();
        } else {
            return $this->getLinuxCPUMetrics();
        }
    }

    protected function getMemoryMetrics(): array
    {
        if ($this->isWindows) {
            return $this->getWindowsMemoryMetrics();
        } else {
            return $this->getLinuxMemoryMetrics();
        }
    }

    protected function getDiskMetrics(): array
    {
        if ($this->isWindows) {
            return $this->getWindowsDiskMetrics();
        } else {
            return $this->getLinuxDiskMetrics();
        }
    }

    protected function getNetworkMetrics(): array
    {
        if ($this->isWindows) {
            return $this->getWindowsNetworkMetrics();
        } else {
            return $this->getLinuxNetworkMetrics();
        }
    }

    protected function getLoadMetrics(): array
    {
        if ($this->isWindows) {
            return $this->getWindowsLoadMetrics();
        } else {
            return $this->getLinuxLoadMetrics();
        }
    }

    protected function getUptime(): string
    {
        if ($this->isWindows) {
            return $this->getWindowsUptime();
        } else {
            return $this->getLinuxUptime();
        }
    }

    protected function getWindowsCPUMetrics(): array
    {
        $output = Process::run('powershell -Command "& {Get-Counter \'\\Processor(_Total)\\% Processor Time\' | Select-Object -ExpandProperty CounterSamples | Select-Object -ExpandProperty CookedValue}"')->output();
        $usage = (float) trim($output);

        $output = Process::run('powershell -Command "& {Get-WmiObject -Class Win32_Processor | Select-Object -ExpandProperty NumberOfCores}"')->output();
        $cores = (int) trim($output);

        return [
            'usage' => $usage,
            'cores' => $cores,
            'model' => $this->getCPUModel()
        ];
    }

    protected function getLinuxCPUMetrics(): array
    {
        $output = Process::run('top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\\1/" | awk \'{print 100 - $1}\'')->output();
        $usage = (float) trim($output);

        $output = Process::run('nproc')->output();
        $cores = (int) trim($output);

        return [
            'usage' => $usage,
            'cores' => $cores,
            'model' => $this->getCPUModel()
        ];
    }

    protected function getWindowsMemoryMetrics(): array
    {
        $output = Process::run('powershell -Command "& {Get-WmiObject -Class Win32_OperatingSystem | Select-Object TotalVisibleMemorySize, FreePhysicalMemory}"')->output();
        preg_match('/TotalVisibleMemorySize\s+:\s+(\d+).*FreePhysicalMemory\s+:\s+(\d+)/s', $output, $matches);

        $total = (int) $matches[1] * 1024; // Convert KB to bytes
        $free = (int) $matches[2] * 1024;
        $used = $total - $free;

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'used_percent' => round(($used / $total) * 100, 2)
        ];
    }

    protected function getLinuxMemoryMetrics(): array
    {
        $output = Process::run('free -b')->output();
        preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $output, $matches);

        $total = (int) $matches[1];
        $used = (int) $matches[2];
        $free = (int) $matches[3];

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'used_percent' => round(($used / $total) * 100, 2)
        ];
    }

    protected function getWindowsDiskMetrics(): array
    {
        $output = Process::run('powershell -Command "& {Get-WmiObject -Class Win32_LogicalDisk | Where-Object {$_.DriveType -eq 3} | Select-Object DeviceID, Size, FreeSpace}"')->output();
        $disks = [];

        preg_match_all('/DeviceID\s+:\s+(\w:).*Size\s+:\s+(\d+).*FreeSpace\s+:\s+(\d+)/s', $output, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $total = (int) $match[2];
            $free = (int) $match[3];
            $used = $total - $free;

            $disks[] = [
                'mount' => $match[1],
                'total' => $total,
                'used' => $used,
                'free' => $free,
                'used_percent' => round(($used / $total) * 100, 2)
            ];
        }

        return $disks;
    }

    protected function getLinuxDiskMetrics(): array
    {
        $output = Process::run('df -B1')->output();
        $disks = [];

        preg_match_all('/^(\S+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)%\s+(\S+)$/m', $output, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $disks[] = [
                'mount' => $match[6],
                'total' => (int) $match[2],
                'used' => (int) $match[3],
                'free' => (int) $match[4],
                'used_percent' => (int) $match[5]
            ];
        }

        return $disks;
    }

    protected function getWindowsNetworkMetrics(): array
    {
        $output = Process::run('powershell -Command "& {Get-NetAdapter | Where-Object {$_.Status -eq \'Up\'} | Select-Object Name, InterfaceDescription, LinkSpeed}"')->output();
        $interfaces = [];

        preg_match_all('/Name\s+:\s+(.+)\s+InterfaceDescription\s+:\s+(.+)\s+LinkSpeed\s+:\s+(.+)/', $output, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $interfaces[] = [
                'name' => trim($match[1]),
                'description' => trim($match[2]),
                'speed' => trim($match[3])
            ];
        }

        return $interfaces;
    }

    protected function getLinuxNetworkMetrics(): array
    {
        $output = Process::run('ip -o link show')->output();
        $interfaces = [];

        preg_match_all('/\d+:\s+(\w+):\s+<(.+)>/', $output, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $interfaces[] = [
                'name' => $match[1],
                'status' => strpos($match[2], 'UP') !== false ? 'up' : 'down'
            ];
        }

        return $interfaces;
    }

    protected function getWindowsLoadMetrics(): array
    {
        $output = Process::run('powershell -Command "& {Get-Counter \'\\System\\Processor Queue Length\' | Select-Object -ExpandProperty CounterSamples | Select-Object -ExpandProperty CookedValue}"')->output();
        $load = (float) trim($output);

        return [
            'load1' => $load,
            'load5' => $load,
            'load15' => $load
        ];
    }

    protected function getLinuxLoadMetrics(): array
    {
        $output = Process::run('cat /proc/loadavg')->output();
        list($load1, $load5, $load15) = explode(' ', $output);

        return [
            'load1' => (float) $load1,
            'load5' => (float) $load5,
            'load15' => (float) $load15
        ];
    }

    protected function getWindowsUptime(): string
    {
        $output = Process::run('powershell -Command "& {(Get-CimInstance -ClassName Win32_OperatingSystem).LastBootUpTime}"')->output();
        $bootTime = Carbon::parse(trim($output));
        return $bootTime->diffForHumans();
    }

    protected function getLinuxUptime(): string
    {
        $output = Process::run('uptime -p')->output();
        return trim($output);
    }

    protected function getCPUModel(): string
    {
        if ($this->isWindows) {
            $output = Process::run('powershell -Command "& {Get-WmiObject -Class Win32_Processor | Select-Object -ExpandProperty Name}"')->output();
        } else {
            $output = Process::run('cat /proc/cpuinfo | grep "model name" | head -n1 | cut -d ":" -f2')->output();
        }
        return trim($output);
    }

    protected function getWindowsProcessList(): array
    {
        $output = Process::run('powershell -Command "& {Get-Process | Select-Object ProcessName, Id, CPU, WorkingSet | ConvertTo-Json}"')->output();
        return json_decode($output, true);
    }

    protected function getLinuxProcessList(): array
    {
        $output = Process::run('ps aux --sort=-%cpu | head -n 11')->output();
        $processes = [];

        $lines = explode("\n", $output);
        array_shift($lines); // Remove header

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            $parts = preg_split('/\s+/', trim($line));
            $processes[] = [
                'user' => $parts[0],
                'pid' => $parts[1],
                'cpu' => $parts[2],
                'mem' => $parts[3],
                'command' => implode(' ', array_slice($parts, 10))
            ];
        }

        return $processes;
    }

    protected function getApacheStatus(): array
    {
        try {
            if ($this->isWindows) {
                $output = Process::run('powershell -Command "& {Get-Service -Name Apache* | Select-Object Name, Status}"')->output();
                preg_match('/Name\s+:\s+(.+)\s+Status\s+:\s+(.+)/', $output, $matches);
                $status = trim($matches[2] ?? '');
            } else {
                $output = Process::run('systemctl status apache2')->output();
                $status = strpos($output, 'active (running)') !== false ? 'Running' : 'Stopped';
            }

            return [
                'status' => $status,
                'running' => $status === 'Running'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get Apache status: ' . $e->getMessage());
            return [
                'status' => 'Unknown',
                'running' => false
            ];
        }
    }

    protected function getNginxStatus(): array
    {
        try {
            if ($this->isWindows) {
                $output = Process::run('powershell -Command "& {Get-Service -Name nginx | Select-Object Name, Status}"')->output();
                preg_match('/Name\s+:\s+(.+)\s+Status\s+:\s+(.+)/', $output, $matches);
                $status = trim($matches[2] ?? '');
            } else {
                $output = Process::run('systemctl status nginx')->output();
                $status = strpos($output, 'active (running)') !== false ? 'Running' : 'Stopped';
            }

            return [
                'status' => $status,
                'running' => $status === 'Running'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get Nginx status: ' . $e->getMessage());
            return [
                'status' => 'Unknown',
                'running' => false
            ];
        }
    }

    protected function getMySQLStatus(): array
    {
        try {
            if ($this->isWindows) {
                $output = Process::run('powershell -Command "& {Get-Service -Name MySQL* | Select-Object Name, Status}"')->output();
                preg_match('/Name\s+:\s+(.+)\s+Status\s+:\s+(.+)/', $output, $matches);
                $status = trim($matches[2] ?? '');
            } else {
                $output = Process::run('systemctl status mysql')->output();
                $status = strpos($output, 'active (running)') !== false ? 'Running' : 'Stopped';
            }

            return [
                'status' => $status,
                'running' => $status === 'Running'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get MySQL status: ' . $e->getMessage());
            return [
                'status' => 'Unknown',
                'running' => false
            ];
        }
    }

    protected function getPHPStatus(): array
    {
        try {
            $version = PHP_VERSION;
            $extensions = get_loaded_extensions();
            $memoryLimit = ini_get('memory_limit');
            $maxExecutionTime = ini_get('max_execution_time');

            return [
                'version' => $version,
                'extensions' => $extensions,
                'memory_limit' => $memoryLimit,
                'max_execution_time' => $maxExecutionTime,
                'running' => true
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get PHP status: ' . $e->getMessage());
            return [
                'version' => 'Unknown',
                'extensions' => [],
                'memory_limit' => 'Unknown',
                'max_execution_time' => 'Unknown',
                'running' => false
            ];
        }
    }
} 