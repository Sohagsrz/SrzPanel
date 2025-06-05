<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Models\Server;
use Carbon\Carbon;

class ServerService
{
    protected $isWindows;
    protected $serverPath;
    protected $configPath;
    protected $backupPath;
    protected $tempPath;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->serverPath = $this->isWindows ? 'C:\\laragon' : '/etc';
        $this->configPath = $this->isWindows ? 'C:\\laragon\\etc' : '/etc';
        $this->backupPath = storage_path('backups/servers');
        $this->tempPath = storage_path('temp/servers');

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }

        if (!File::exists($this->tempPath)) {
            File::makeDirectory($this->tempPath, 0755, true);
        }
    }

    public function getServerStatus(): array
    {
        try {
            if ($this->isWindows) {
                return $this->getWindowsServerStatus();
            } else {
                return $this->getLinuxServerStatus();
            }
        } catch (\Exception $e) {
            Log::error('Failed to get server status: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get server status: ' . $e->getMessage()
            ];
        }
    }

    public function getServerInfo(): array
    {
        try {
            if ($this->isWindows) {
                return $this->getWindowsServerInfo();
            } else {
                return $this->getLinuxServerInfo();
            }
        } catch (\Exception $e) {
            Log::error('Failed to get server info: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get server info: ' . $e->getMessage()
            ];
        }
    }

    public function getServerResources(): array
    {
        try {
            if ($this->isWindows) {
                return $this->getWindowsServerResources();
            } else {
                return $this->getLinuxServerResources();
            }
        } catch (\Exception $e) {
            Log::error('Failed to get server resources: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get server resources: ' . $e->getMessage()
            ];
        }
    }

    public function getServerLogs(array $filters = []): array
    {
        try {
            if ($this->isWindows) {
                return $this->getWindowsServerLogs($filters);
            } else {
                return $this->getLinuxServerLogs($filters);
            }
        } catch (\Exception $e) {
            Log::error('Failed to get server logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get server logs: ' . $e->getMessage()
            ];
        }
    }

    public function restartServer(): array
    {
        try {
            if ($this->isWindows) {
                return $this->restartWindowsServer();
            } else {
                return $this->restartLinuxServer();
            }
        } catch (\Exception $e) {
            Log::error('Failed to restart server: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restart server: ' . $e->getMessage()
            ];
        }
    }

    public function updateServerConfig(array $config): array
    {
        try {
            if ($this->isWindows) {
                return $this->updateWindowsServerConfig($config);
            } else {
                return $this->updateLinuxServerConfig($config);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update server config: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update server config: ' . $e->getMessage()
            ];
        }
    }

    public function backupServerConfig(): array
    {
        try {
            if ($this->isWindows) {
                return $this->backupWindowsServerConfig();
            } else {
                return $this->backupLinuxServerConfig();
            }
        } catch (\Exception $e) {
            Log::error('Failed to backup server config: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to backup server config: ' . $e->getMessage()
            ];
        }
    }

    public function restoreServerConfig(string $backupFile): array
    {
        try {
            if (!File::exists($backupFile)) {
                throw new \Exception('Backup file does not exist');
            }

            if ($this->isWindows) {
                return $this->restoreWindowsServerConfig($backupFile);
            } else {
                return $this->restoreLinuxServerConfig($backupFile);
            }
        } catch (\Exception $e) {
            Log::error('Failed to restore server config: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore server config: ' . $e->getMessage()
            ];
        }
    }

    public function getServerStats(): array
    {
        try {
            if ($this->isWindows) {
                return $this->getWindowsServerStats();
            } else {
                return $this->getLinuxServerStats();
            }
        } catch (\Exception $e) {
            Log::error('Failed to get server stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get server stats: ' . $e->getMessage()
            ];
        }
    }

    protected function getWindowsServerStatus(): array
    {
        $services = [
            'nginx' => 'nginx.exe',
            'apache' => 'httpd.exe',
            'mysql' => 'mysqld.exe',
            'php' => 'php-cgi.exe'
        ];

        $status = [];
        foreach ($services as $name => $process) {
            $command = sprintf('tasklist /FI "IMAGENAME eq %s" /NH', $process);
            $output = Process::run($command)->output();
            $status[$name] = !empty($output);
        }

        return [
            'success' => true,
            'status' => $status
        ];
    }

    protected function getLinuxServerStatus(): array
    {
        $services = [
            'nginx' => 'nginx',
            'apache' => 'apache2',
            'mysql' => 'mysql',
            'php' => 'php-fpm'
        ];

        $status = [];
        foreach ($services as $name => $service) {
            $command = sprintf('systemctl is-active %s', $service);
            $output = Process::run($command)->output();
            $status[$name] = trim($output) === 'active';
        }

        return [
            'success' => true,
            'status' => $status
        ];
    }

    protected function getWindowsServerInfo(): array
    {
        try {
            $osInfo = Process::run('systeminfo');
            $diskInfo = Process::run('wmic logicaldisk get size,freespace,caption');
            $memoryInfo = Process::run('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value');

            return [
                'success' => true,
                'info' => [
                    'os' => $osInfo->output(),
                    'disk' => $diskInfo->output(),
                    'memory' => $memoryInfo->output()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get Windows server info: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get Windows server info: ' . $e->getMessage()
            ];
        }
    }

    protected function getLinuxServerInfo(): array
    {
        try {
            $osInfo = Process::run('cat /etc/os-release');
            $diskInfo = Process::run('df -h');
            $memoryInfo = Process::run('free -h');

            return [
                'success' => true,
                'info' => [
                    'os' => $osInfo->output(),
                    'disk' => $diskInfo->output(),
                    'memory' => $memoryInfo->output()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get Linux server info: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get Linux server info: ' . $e->getMessage()
            ];
        }
    }

    protected function getWindowsServerResources(): array
    {
        $command = 'wmic cpu get loadpercentage';
        $cpuLoad = Process::run($command)->output();
        $cpuLoad = (int) preg_replace('/[^0-9]/', '', $cpuLoad);

        $command = 'wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value';
        $memory = Process::run($command)->output();
        preg_match('/FreePhysicalMemory=(\d+)/', $memory, $freeMemory);
        preg_match('/TotalVisibleMemorySize=(\d+)/', $memory, $totalMemory);

        $command = 'wmic logicaldisk get size,freespace,caption';
        $disk = Process::run($command)->output();
        $diskInfo = [];
        foreach (explode("\n", $disk) as $line) {
            if (preg_match('/^(\w:)\s+(\d+)\s+(\d+)$/', $line, $matches)) {
                $diskInfo[$matches[1]] = [
                    'total' => (int) $matches[2],
                    'free' => (int) $matches[3]
                ];
            }
        }

        return [
            'success' => true,
            'resources' => [
                'cpu' => [
                    'load' => $cpuLoad
                ],
                'memory' => [
                    'total' => (int) $totalMemory[1],
                    'free' => (int) $freeMemory[1]
                ],
                'disk' => $diskInfo
            ]
        ];
    }

    protected function getLinuxServerResources(): array
    {
        $command = 'top -bn1 | grep "Cpu(s)" | awk \'{print $2}\'';
        $cpuLoad = Process::run($command)->output();
        $cpuLoad = (float) trim($cpuLoad);

        $command = 'free -m';
        $memory = Process::run($command)->output();
        preg_match('/Mem:\s+(\d+)\s+(\d+)/', $memory, $matches);
        $totalMemory = (int) $matches[1];
        $usedMemory = (int) $matches[2];

        $command = 'df -m';
        $disk = Process::run($command)->output();
        $diskInfo = [];
        foreach (explode("\n", $disk) as $line) {
            if (preg_match('/^(\S+)\s+(\d+)\s+(\d+)\s+(\d+)/', $line, $matches)) {
                $diskInfo[$matches[1]] = [
                    'total' => (int) $matches[2],
                    'used' => (int) $matches[3],
                    'free' => (int) $matches[4]
                ];
            }
        }

        return [
            'success' => true,
            'resources' => [
                'cpu' => [
                    'load' => $cpuLoad
                ],
                'memory' => [
                    'total' => $totalMemory,
                    'used' => $usedMemory,
                    'free' => $totalMemory - $usedMemory
                ],
                'disk' => $diskInfo
            ]
        ];
    }

    protected function getWindowsServerLogs(array $filters = []): array
    {
        try {
            $logs = [];

            if (isset($filters['apache']) && $filters['apache']) {
                $logs['apache'] = Process::run('type C:\\laragon\\logs\\apache_error.log')->output();
            }

            if (isset($filters['mysql']) && $filters['mysql']) {
                $logs['mysql'] = Process::run('type C:\\laragon\\logs\\mysql_error.log')->output();
            }

            if (isset($filters['nginx']) && $filters['nginx']) {
                $logs['nginx'] = Process::run('type C:\\laragon\\logs\\nginx_error.log')->output();
            }

            return [
                'success' => true,
                'logs' => $logs
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get Windows server logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get Windows server logs: ' . $e->getMessage()
            ];
        }
    }

    protected function getLinuxServerLogs(array $filters = []): array
    {
        try {
            $logs = [];

            if (isset($filters['apache']) && $filters['apache']) {
                $logs['apache'] = Process::run('tail -n 100 /var/log/apache2/error.log')->output();
            }

            if (isset($filters['mysql']) && $filters['mysql']) {
                $logs['mysql'] = Process::run('tail -n 100 /var/log/mysql/error.log')->output();
            }

            if (isset($filters['nginx']) && $filters['nginx']) {
                $logs['nginx'] = Process::run('tail -n 100 /var/log/nginx/error.log')->output();
            }

            return [
                'success' => true,
                'logs' => $logs
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get Linux server logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get Linux server logs: ' . $e->getMessage()
            ];
        }
    }

    protected function restartWindowsServer(): array
    {
        $services = [
            'nginx' => 'nginx.exe',
            'apache' => 'httpd.exe',
            'mysql' => 'mysqld.exe',
            'php' => 'php-cgi.exe'
        ];

        foreach ($services as $name => $process) {
            $command = sprintf('taskkill /F /IM %s', $process);
            Process::run($command);

            $command = sprintf('start %s', $process);
            Process::run($command);
        }

        return [
            'success' => true,
            'message' => 'Server restarted successfully'
        ];
    }

    protected function restartLinuxServer(): array
    {
        $services = [
            'nginx' => 'nginx',
            'apache' => 'apache2',
            'mysql' => 'mysql',
            'php' => 'php-fpm'
        ];

        foreach ($services as $name => $service) {
            $command = sprintf('systemctl restart %s', $service);
            Process::run($command);
        }

        return [
            'success' => true,
            'message' => 'Server restarted successfully'
        ];
    }

    protected function updateWindowsServerConfig(array $config): array
    {
        foreach ($config as $name => $value) {
            $configFile = sprintf('%s\\%s.conf', $this->configPath, $name);
            if (File::exists($configFile)) {
                File::put($configFile, $value);
            }
        }

        return [
            'success' => true,
            'message' => 'Server config updated successfully'
        ];
    }

    protected function updateLinuxServerConfig(array $config): array
    {
        foreach ($config as $name => $value) {
            $configFile = sprintf('%s/%s.conf', $this->configPath, $name);
            if (File::exists($configFile)) {
                File::put($configFile, $value);
            }
        }

        return [
            'success' => true,
            'message' => 'Server config updated successfully'
        ];
    }

    protected function backupWindowsServerConfig(): array
    {
        $backupFile = storage_path('backups/server_config_' . date('Y-m-d_H-i-s') . '.zip');
        $command = sprintf(
            'powershell Compress-Archive -Path "%s\\*.conf" -DestinationPath "%s" -Force',
            $this->configPath,
            $backupFile
        );

        Process::run($command);

        if (File::exists($backupFile)) {
            return [
                'success' => true,
                'message' => 'Server config backed up successfully',
                'file' => $backupFile
            ];
        } else {
            throw new \Exception('Failed to create backup file');
        }
    }

    protected function backupLinuxServerConfig(): array
    {
        $backupFile = storage_path('backups/server_config_' . date('Y-m-d_H-i-s') . '.tar.gz');
        $command = sprintf(
            'tar -czf "%s" -C "%s" *.conf',
            $backupFile,
            $this->configPath
        );

        Process::run($command);

        if (File::exists($backupFile)) {
            return [
                'success' => true,
                'message' => 'Server config backed up successfully',
                'file' => $backupFile
            ];
        } else {
            throw new \Exception('Failed to create backup file');
        }
    }

    protected function restoreWindowsServerConfig(string $backupFile): array
    {
        $tempDir = storage_path('temp/' . Str::random());
        File::makeDirectory($tempDir, 0755, true);

        $command = sprintf(
            'powershell Expand-Archive -Path "%s" -DestinationPath "%s" -Force',
            $backupFile,
            $tempDir
        );

        Process::run($command);

        $files = File::files($tempDir);
        foreach ($files as $file) {
            $targetFile = sprintf('%s\\%s', $this->configPath, $file->getFilename());
            File::copy($file->getPathname(), $targetFile);
        }

        File::deleteDirectory($tempDir);

        return [
            'success' => true,
            'message' => 'Server config restored successfully'
        ];
    }

    protected function restoreLinuxServerConfig(string $backupFile): array
    {
        $tempDir = storage_path('temp/' . Str::random());
        File::makeDirectory($tempDir, 0755, true);

        $command = sprintf(
            'tar -xzf "%s" -C "%s"',
            $backupFile,
            $tempDir
        );

        Process::run($command);

        $files = File::files($tempDir);
        foreach ($files as $file) {
            $targetFile = sprintf('%s/%s', $this->configPath, $file->getFilename());
            File::copy($file->getPathname(), $targetFile);
        }

        File::deleteDirectory($tempDir);

        return [
            'success' => true,
            'message' => 'Server config restored successfully'
        ];
    }

    protected function getWindowsServerStats(): array
    {
        try {
            $cpuUsage = Process::run('wmic cpu get loadpercentage')->output();
            $memoryUsage = Process::run('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value')->output();
            $diskUsage = Process::run('wmic logicaldisk get size,freespace,caption')->output();

            return [
                'success' => true,
                'stats' => [
                    'cpu' => $cpuUsage,
                    'memory' => $memoryUsage,
                    'disk' => $diskUsage
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get Windows server stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get Windows server stats: ' . $e->getMessage()
            ];
        }
    }

    protected function getLinuxServerStats(): array
    {
        try {
            $cpuUsage = Process::run('top -bn1 | grep "Cpu(s)"')->output();
            $memoryUsage = Process::run('free -h')->output();
            $diskUsage = Process::run('df -h')->output();

            return [
                'success' => true,
                'stats' => [
                    'cpu' => $cpuUsage,
                    'memory' => $memoryUsage,
                    'disk' => $diskUsage
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get Linux server stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get Linux server stats: ' . $e->getMessage()
            ];
        }
    }
} 