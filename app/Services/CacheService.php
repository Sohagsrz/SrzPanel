<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CacheService
{
    /**
     * Cache duration in seconds
     */
    const CACHE_DURATION = 3600; // 1 hour

    /**
     * Cache keys
     */
    const KEY_SYSTEM_STATS = 'system.stats';
    const KEY_DNS_RECORDS = 'dns.records';
    const KEY_DOMAIN_STATS = 'domain.stats';
    const KEY_DATABASE_STATS = 'database.stats';
    const KEY_EMAIL_STATS = 'email.stats';
    const KEY_EMAIL_LIST = 'email.list';
    const KEY_BACKUP_STATS = 'backup.stats';
    const KEY_PHP_VERSIONS = 'php.versions';
    const KEY_SYSTEM_SETTINGS = 'system.settings';
    const KEY_DOMAIN_LIST = 'domain.list';

    protected $isWindows;
    protected $cachePath;
    protected $backupPath;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->cachePath = $this->isWindows ? 'C:\\laragon\\cache' : '/var/cache';
        $this->backupPath = storage_path('backups/cache');

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    /**
     * Get cached data or store it if not exists
     */
    public function remember(string $key, callable $callback, int $duration = self::CACHE_DURATION): mixed
    {
        try {
            return Cache::remember($key, $duration, $callback);
        } catch (\Exception $e) {
            Log::error("Cache error for key {$key}: " . $e->getMessage());
            return $callback();
        }
    }

    /**
     * Clear specific cache key
     */
    public function forget(string $key): void
    {
        try {
            Cache::forget($key);
        } catch (\Exception $e) {
            Log::error("Failed to forget cache key {$key}: " . $e->getMessage());
        }
    }

    /**
     * Clear multiple cache keys
     */
    public function forgetMultiple(array $keys): void
    {
        foreach ($keys as $key) {
            $this->forget($key);
        }
    }

    /**
     * Clear all cache
     */
    public function clearAll(): void
    {
        try {
            Cache::flush();
        } catch (\Exception $e) {
            Log::error("Failed to clear all cache: " . $e->getMessage());
        }
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        return [
            'driver' => config('cache.default'),
            'prefix' => config('cache.prefix'),
            'stores' => array_keys(config('cache.stores')),
        ];
    }

    public function getCacheStats(): array
    {
        try {
            if ($this->isWindows) {
                return $this->getWindowsCacheStats();
            } else {
                return $this->getLinuxCacheStats();
            }
        } catch (\Exception $e) {
            Log::error('Failed to get cache stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get cache stats: ' . $e->getMessage()
            ];
        }
    }

    public function clearCache(): array
    {
        try {
            if ($this->isWindows) {
                return $this->clearWindowsCache();
            } else {
                return $this->clearLinuxCache();
            }
        } catch (\Exception $e) {
            Log::error('Failed to clear cache: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ];
        }
    }

    public function backupCache(): array
    {
        try {
            $backupFile = $this->backupPath . '/cache_' . date('Y-m-d_H-i-s') . '.tar.gz';
            $cachePath = $this->cachePath;

            $command = sprintf(
                'tar -czf %s -C %s .',
                $backupFile,
                $cachePath
            );

            Process::run($command);

            if (File::exists($backupFile)) {
                return [
                    'success' => true,
                    'message' => 'Cache backed up successfully',
                    'file' => $backupFile
                ];
            } else {
                throw new \Exception('Backup file was not created');
            }
        } catch (\Exception $e) {
            Log::error('Failed to backup cache: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to backup cache: ' . $e->getMessage()
            ];
        }
    }

    public function restoreCache(string $backupFile): array
    {
        try {
            if (!File::exists($backupFile)) {
                throw new \Exception('Backup file does not exist');
            }

            $cachePath = $this->cachePath;
            if (!File::exists($cachePath)) {
                File::makeDirectory($cachePath, 0755, true);
            }

            $command = sprintf(
                'tar -xzf %s -C %s',
                $backupFile,
                $cachePath
            );

            Process::run($command);

            return [
                'success' => true,
                'message' => 'Cache restored successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to restore cache: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore cache: ' . $e->getMessage()
            ];
        }
    }

    public function getCacheSize(): array
    {
        try {
            if ($this->isWindows) {
                $output = Process::run('powershell -Command "(Get-ChildItem -Path ' . $this->cachePath . ' -Recurse | Measure-Object -Property Length -Sum).Sum"')->output();
            } else {
                $output = Process::run('du -sb ' . $this->cachePath)->output();
            }

            $size = (int)trim($output);
            return [
                'success' => true,
                'size' => $size
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get cache size: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get cache size: ' . $e->getMessage()
            ];
        }
    }

    public function getCacheFiles(): array
    {
        try {
            $files = [];
            $cachePath = $this->cachePath;

            if ($this->isWindows) {
                $output = Process::run('powershell -Command "Get-ChildItem -Path ' . $cachePath . ' -Recurse -File | Select-Object FullName, Length, LastWriteTime"')->output();
                foreach (explode("\n", $output) as $line) {
                    if (preg_match('/^(.+)\s+(\d+)\s+(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})$/', trim($line), $matches)) {
                        $files[] = [
                            'path' => $matches[1],
                            'size' => (int)$matches[2],
                            'last_modified' => $matches[3]
                        ];
                    }
                }
            } else {
                $output = Process::run('find ' . $cachePath . ' -type f -ls')->output();
                foreach (explode("\n", $output) as $line) {
                    if (preg_match('/^\s*\d+\s+\d+\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(.+)$/', trim($line), $matches)) {
                        $files[] = [
                            'path' => $matches[7],
                            'size' => (int)$matches[3],
                            'last_modified' => $matches[4] . '/' . $matches[5] . '/' . $matches[6]
                        ];
                    }
                }
            }

            return [
                'success' => true,
                'files' => $files
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get cache files: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get cache files: ' . $e->getMessage()
            ];
        }
    }

    protected function getWindowsCacheStats(): array
    {
        $stats = [
            'size' => 0,
            'files' => 0,
            'last_cleared' => null
        ];

        try {
            $output = Process::run('powershell -Command "(Get-ChildItem -Path ' . $this->cachePath . ' -Recurse | Measure-Object -Property Length -Sum).Sum"')->output();
            $stats['size'] = (int)trim($output);

            $output = Process::run('powershell -Command "(Get-ChildItem -Path ' . $this->cachePath . ' -Recurse -File | Measure-Object).Count"')->output();
            $stats['files'] = (int)trim($output);

            $output = Process::run('powershell -Command "(Get-ChildItem -Path ' . $this->cachePath . ' -Recurse -File | Sort-Object LastWriteTime -Descending | Select-Object -First 1).LastWriteTime"')->output();
            $stats['last_cleared'] = trim($output);
        } catch (\Exception $e) {
            Log::error('Failed to get Windows cache stats: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'stats' => $stats
        ];
    }

    protected function getLinuxCacheStats(): array
    {
        $stats = [
            'size' => 0,
            'files' => 0,
            'last_cleared' => null
        ];

        try {
            $output = Process::run('du -sb ' . $this->cachePath)->output();
            $stats['size'] = (int)trim($output);

            $output = Process::run('find ' . $this->cachePath . ' -type f | wc -l')->output();
            $stats['files'] = (int)trim($output);

            $output = Process::run('find ' . $this->cachePath . ' -type f -printf "%T+\n" | sort -r | head -n 1')->output();
            $stats['last_cleared'] = trim($output);
        } catch (\Exception $e) {
            Log::error('Failed to get Linux cache stats: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'stats' => $stats
        ];
    }

    protected function clearWindowsCache(): array
    {
        try {
            Process::run('powershell -Command "Remove-Item -Path ' . $this->cachePath . '\\* -Recurse -Force"');
            return [
                'success' => true,
                'message' => 'Cache cleared successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to clear Windows cache: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to clear Windows cache: ' . $e->getMessage()
            ];
        }
    }

    protected function clearLinuxCache(): array
    {
        try {
            Process::run('rm -rf ' . $this->cachePath . '/*');
            return [
                'success' => true,
                'message' => 'Cache cleared successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to clear Linux cache: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to clear Linux cache: ' . $e->getMessage()
            ];
        }
    }
} 