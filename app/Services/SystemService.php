<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;

class SystemService
{
    protected $isWindows;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public function getSystemInfo(): array
    {
        return [
            'hostname' => $this->getHostname(),
            'os' => $this->getOSInfo(),
            'kernel' => $this->getKernelVersion(),
            'uptime' => $this->getUptime(),
            'cpu' => $this->getCPUInfo(),
            'memory' => $this->getMemoryInfo(),
            'disk' => $this->getDiskInfo(),
            'network' => $this->getNetworkInfo(),
            'time' => $this->getSystemTime(),
            'timezone' => $this->getTimezone()
        ];
    }

    public function getHostname(): string
    {
        return gethostname();
    }

    public function setHostname(string $hostname): void
    {
        if ($this->isWindows) {
            Process::run("powershell -Command \"& {Rename-Computer -NewName '{$hostname}' -Force}\"");
        } else {
            Process::run("hostnamectl set-hostname {$hostname}");
        }
    }

    public function getOSInfo(): array
    {
        if ($this->isWindows) {
            $output = Process::run('powershell -Command "& {Get-WmiObject -Class Win32_OperatingSystem | Select-Object Caption, Version, OSArchitecture}"')->output();
            preg_match('/Caption\s+:\s+(.+)\s+Version\s+:\s+(.+)\s+OSArchitecture\s+:\s+(.+)/', $output, $matches);
            
            return [
                'name' => trim($matches[1] ?? 'Windows'),
                'version' => trim($matches[2] ?? ''),
                'architecture' => trim($matches[3] ?? '')
            ];
        } else {
            $output = Process::run('cat /etc/os-release')->output();
            preg_match('/PRETTY_NAME="(.+)"/', $output, $matches);
            
            return [
                'name' => trim($matches[1] ?? 'Linux'),
                'version' => php_uname('r'),
                'architecture' => php_uname('m')
            ];
        }
    }

    public function getKernelVersion(): string
    {
        if ($this->isWindows) {
            return 'N/A';
        }
        return trim(Process::run('uname -r')->output());
    }

    public function getUptime(): array
    {
        if ($this->isWindows) {
            $output = Process::run('powershell -Command "& {Get-CimInstance -ClassName Win32_OperatingSystem | Select-Object LastBootUpTime}"')->output();
            preg_match('/LastBootUpTime\s+:\s+(.+)/', $output, $matches);
            $bootTime = strtotime($matches[1] ?? 'now');
            $uptime = time() - $bootTime;
        } else {
            $uptime = (int)trim(Process::run('cat /proc/uptime')->output());
        }

        return [
            'seconds' => $uptime,
            'formatted' => $this->formatUptime($uptime)
        ];
    }

    public function getCPUInfo(): array
    {
        if ($this->isWindows) {
            $output = Process::run('powershell -Command "& {Get-WmiObject -Class Win32_Processor | Select-Object Name, NumberOfCores, NumberOfLogicalProcessors}"')->output();
            preg_match('/Name\s+:\s+(.+)\s+NumberOfCores\s+:\s+(\d+)\s+NumberOfLogicalProcessors\s+:\s+(\d+)/', $output, $matches);
            
            return [
                'model' => trim($matches[1] ?? ''),
                'cores' => (int)($matches[2] ?? 0),
                'threads' => (int)($matches[3] ?? 0),
                'usage' => $this->getCPUUsage()
            ];
        } else {
            $output = Process::run('cat /proc/cpuinfo')->output();
            preg_match('/model name\s+:\s+(.+)/', $output, $matches);
            
            return [
                'model' => trim($matches[1] ?? ''),
                'cores' => (int)trim(Process::run('nproc')->output()),
                'threads' => (int)trim(Process::run('nproc --all')->output()),
                'usage' => $this->getCPUUsage()
            ];
        }
    }

    public function getMemoryInfo(): array
    {
        if ($this->isWindows) {
            $output = Process::run('powershell -Command "& {Get-WmiObject -Class Win32_OperatingSystem | Select-Object TotalVisibleMemorySize, FreePhysicalMemory}"')->output();
            preg_match('/TotalVisibleMemorySize\s+:\s+(\d+)\s+FreePhysicalMemory\s+:\s+(\d+)/', $output, $matches);
            
            $total = (int)($matches[1] ?? 0) * 1024; // Convert KB to bytes
            $free = (int)($matches[2] ?? 0) * 1024;
            $used = $total - $free;
        } else {
            $output = Process::run('free -b')->output();
            preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $output, $matches);
            
            $total = (int)($matches[1] ?? 0);
            $used = (int)($matches[2] ?? 0);
            $free = (int)($matches[3] ?? 0);
        }

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'usage_percent' => $total > 0 ? round(($used / $total) * 100, 2) : 0
        ];
    }

    public function getDiskInfo(): array
    {
        if ($this->isWindows) {
            $output = Process::run('powershell -Command "& {Get-WmiObject -Class Win32_LogicalDisk | Where-Object {$_.DriveType -eq 3} | Select-Object DeviceID, Size, FreeSpace}"')->output();
            $disks = [];
            
            preg_match_all('/DeviceID\s+:\s+(\w:)\s+Size\s+:\s+(\d+)\s+FreeSpace\s+:\s+(\d+)/', $output, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $disks[] = [
                    'device' => $match[1],
                    'total' => (int)$match[2],
                    'free' => (int)$match[3],
                    'used' => (int)$match[2] - (int)$match[3],
                    'usage_percent' => round(((int)$match[2] - (int)$match[3]) / (int)$match[2] * 100, 2)
                ];
            }
        } else {
            $output = Process::run('df -B1')->output();
            $disks = [];
            
            preg_match_all('/^(\S+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)%\s+(.+)$/m', $output, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $disks[] = [
                    'device' => $match[1],
                    'total' => (int)$match[2],
                    'used' => (int)$match[3],
                    'free' => (int)$match[4],
                    'usage_percent' => (int)$match[5],
                    'mount' => $match[6]
                ];
            }
        }

        return $disks;
    }

    public function getNetworkInfo(): array
    {
        if ($this->isWindows) {
            $output = Process::run('powershell -Command "& {Get-NetAdapter | Where-Object {$_.Status -eq \'Up\'} | Select-Object Name, InterfaceDescription, MacAddress, LinkSpeed}"')->output();
            $interfaces = [];
            
            preg_match_all('/Name\s+:\s+(.+)\s+InterfaceDescription\s+:\s+(.+)\s+MacAddress\s+:\s+(.+)\s+LinkSpeed\s+:\s+(.+)/', $output, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $interfaces[] = [
                    'name' => trim($match[1]),
                    'description' => trim($match[2]),
                    'mac' => trim($match[3]),
                    'speed' => trim($match[4])
                ];
            }
        } else {
            $output = Process::run('ip -o link show')->output();
            $interfaces = [];
            
            preg_match_all('/\d+:\s+(\w+):\s+<(.+)>\s+mtu\s+(\d+).+link\/(\w+)\s+([0-9a-f:]+)/', $output, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $interfaces[] = [
                    'name' => $match[1],
                    'state' => strpos($match[2], 'UP') !== false ? 'up' : 'down',
                    'mtu' => (int)$match[3],
                    'type' => $match[4],
                    'mac' => $match[5]
                ];
            }
        }

        return $interfaces;
    }

    public function getSystemTime(): string
    {
        return date('Y-m-d H:i:s');
    }

    public function setSystemTime(string $datetime): void
    {
        if ($this->isWindows) {
            Process::run("powershell -Command \"& {Set-Date -Date '{$datetime}'}\"");
        } else {
            Process::run("date -s '{$datetime}'");
        }
    }

    public function getTimezone(): string
    {
        return date_default_timezone_get();
    }

    public function setTimezone(string $timezone): void
    {
        if ($this->isWindows) {
            Process::run("powershell -Command \"& {Set-TimeZone -Id '{$timezone}'}\"");
        } else {
            Process::run("timedatectl set-timezone {$timezone}");
        }
    }

    public function reboot(): void
    {
        if ($this->isWindows) {
            Process::run('shutdown /r /t 0');
        } else {
            Process::run('reboot');
        }
    }

    public function shutdown(): void
    {
        if ($this->isWindows) {
            Process::run('shutdown /s /t 0');
        } else {
            Process::run('shutdown -h now');
        }
    }

    protected function getCPUUsage(): float
    {
        if ($this->isWindows) {
            $output = Process::run('powershell -Command "& {Get-Counter -Counter \'\\Processor(_Total)\\% Processor Time\' -SampleInterval 1 -MaxSamples 1}"')->output();
            preg_match('/\d+\.\d+/', $output, $matches);
            return (float)($matches[0] ?? 0);
        } else {
            $load = sys_getloadavg();
            return $load[0] * 100;
        }
    }

    protected function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";
        if ($seconds > 0) $parts[] = "{$seconds}s";

        return implode(' ', $parts);
    }
} 