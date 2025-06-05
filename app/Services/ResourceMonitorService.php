<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;

class ResourceMonitorService
{
    protected $cacheTime = 60; // Cache results for 1 minute

    public function getCpuUsage()
    {
        return Cache::remember('cpu_usage', $this->cacheTime, function () {
            if (PHP_OS_FAMILY === 'Windows') {
                $output = Process::run('wmic cpu get loadpercentage')->output();
                preg_match('/\d+/', $output, $matches);
                return $matches[0] ?? 0;
            } else {
                $load = sys_getloadavg();
                return round($load[0] * 100 / $this->getCpuCores());
            }
        });
    }

    public function getRamUsage()
    {
        return Cache::remember('ram_usage', $this->cacheTime, function () {
            if (PHP_OS_FAMILY === 'Windows') {
                $output = Process::run('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value')->output();
                preg_match('/TotalVisibleMemorySize=(\d+)/', $output, $total);
                preg_match('/FreePhysicalMemory=(\d+)/', $output, $free);
                $used = $total[1] - $free[1];
                return round(($used / $total[1]) * 100, 2);
            } else {
                $free = shell_exec('free');
                $free = (string)trim($free);
                $free_arr = explode("\n", $free);
                $mem = explode(" ", $free_arr[1]);
                $mem = array_filter($mem);
                $mem = array_merge($mem);
                $used = $mem[2];
                $total = $mem[1];
                return round(($used / $total) * 100, 2);
            }
        });
    }

    public function getDiskUsage()
    {
        return Cache::remember('disk_usage', $this->cacheTime, function () {
            $total = disk_total_space('/');
            $free = disk_free_space('/');
            $used = $total - $free;
            return [
                'total' => $this->formatBytes($total),
                'used' => $this->formatBytes($used),
                'free' => $this->formatBytes($free),
                'percentage' => round(($used / $total) * 100, 2)
            ];
        });
    }

    public function getBandwidthUsage()
    {
        return Cache::remember('bandwidth_usage', $this->cacheTime, function () {
            if (PHP_OS_FAMILY === 'Windows') {
                // Windows: Use Performance Counters
                $output = Process::run('typeperf "\Network Interface(*)\Bytes Total/sec" -sc 1')->output();
                preg_match('/"([^"]+)","([^"]+)"/', $output, $matches);
                return [
                    'current' => $this->formatBytes($matches[2] ?? 0),
                    'total' => 'N/A' // Windows doesn't provide total bandwidth easily
                ];
            } else {
                // Linux: Use /proc/net/dev
                $output = Process::run('cat /proc/net/dev')->output();
                $lines = explode("\n", $output);
                $totalRx = 0;
                $totalTx = 0;

                foreach ($lines as $line) {
                    if (preg_match('/^\s*(\w+):\s*(\d+)\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+(\d+)/', $line, $matches)) {
                        $totalRx += $matches[2];
                        $totalTx += $matches[3];
                    }
                }

                return [
                    'current' => $this->formatBytes($totalRx + $totalTx),
                    'total' => 'N/A' // Linux doesn't provide total bandwidth easily
                ];
            }
        });
    }

    public function getProcessList()
    {
        return Cache::remember('process_list', $this->cacheTime, function () {
            if (PHP_OS_FAMILY === 'Windows') {
                $output = Process::run('tasklist /FO CSV /NH')->output();
                $processes = [];
                foreach (explode("\n", $output) as $line) {
                    if (preg_match('/"([^"]+)","(\d+)","([^"]+)","(\d+)"/', $line, $matches)) {
                        $processes[] = [
                            'name' => $matches[1],
                            'pid' => $matches[2],
                            'memory' => $this->formatBytes($matches[4] * 1024)
                        ];
                    }
                }
            } else {
                $output = Process::run('ps aux --sort=-%mem | head -n 11')->output();
                $processes = [];
                $lines = explode("\n", $output);
                array_shift($lines); // Remove header

                foreach ($lines as $line) {
                    if (preg_match('/^\S+\s+(\d+)\s+(\d+\.\d+)\s+(\d+\.\d+)\s+(\d+)\s+(\d+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(.+)$/', $line, $matches)) {
                        $processes[] = [
                            'name' => $matches[11],
                            'pid' => $matches[1],
                            'memory' => $this->formatBytes($matches[4] * 1024)
                        ];
                    }
                }
            }

            return $processes;
        });
    }

    protected function getCpuCores()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = Process::run('wmic cpu get NumberOfCores')->output();
            preg_match('/\d+/', $output, $matches);
            return $matches[0] ?? 1;
        } else {
            return (int)shell_exec('nproc');
        }
    }

    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
} 