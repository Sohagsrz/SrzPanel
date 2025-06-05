<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Update;
use App\Models\UpdateLog;
use Carbon\Carbon;

class UpdateService
{
    protected $isWindows;
    protected $updatePath;
    protected $backupPath;
    protected $tempPath;
    protected $maxBackups;
    protected $autoBackup;
    protected $notifyOnUpdate;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->updatePath = $this->isWindows ? 'C:\\laragon\\updates' : '/var/updates';
        $this->backupPath = storage_path('backups/updates');
        $this->tempPath = storage_path('temp/updates');
        $this->maxBackups = config('update.max_backups', 5);
        $this->autoBackup = config('update.auto_backup', true);
        $this->notifyOnUpdate = config('update.notify_on_update', true);

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }

        if (!File::exists($this->tempPath)) {
            File::makeDirectory($this->tempPath, 0755, true);
        }
    }

    public function checkForUpdates(): array
    {
        try {
            if ($this->isWindows) {
                $updates = $this->checkWindowsUpdates();
            } else {
                $updates = $this->checkLinuxUpdates();
            }

            return [
                'success' => true,
                'updates' => $updates
            ];
        } catch (\Exception $e) {
            Log::error('Failed to check for updates: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to check for updates: ' . $e->getMessage()
            ];
        }
    }

    public function installUpdates(array $updates = []): array
    {
        try {
            if ($this->autoBackup) {
                $this->backupSystem();
            }

            if ($this->isWindows) {
                $result = $this->installWindowsUpdates($updates);
            } else {
                $result = $this->installLinuxUpdates($updates);
            }

            if ($this->notifyOnUpdate) {
                $this->notifyUpdateComplete($result);
            }

            return [
                'success' => true,
                'message' => 'Updates installed successfully',
                'result' => $result
            ];
        } catch (\Exception $e) {
            Log::error('Failed to install updates: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to install updates: ' . $e->getMessage()
            ];
        }
    }

    public function getUpdateHistory(): array
    {
        try {
            $history = [];
            $files = File::files($this->backupPath);

            foreach ($files as $file) {
                if ($file->getExtension() === 'json') {
                    $data = json_decode(File::get($file), true);
                    if ($data) {
                        $history[] = $data;
                    }
                }
            }

            return [
                'success' => true,
                'history' => $history
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get update history: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get update history: ' . $e->getMessage()
            ];
        }
    }

    public function rollbackUpdate(string $version): array
    {
        try {
            $backupFile = $this->backupPath . '/' . $version . '.zip';
            if (!File::exists($backupFile)) {
                throw new \Exception("Backup file not found: {$version}");
            }

            if ($this->isWindows) {
                $this->rollbackWindowsUpdate($backupFile);
            } else {
                $this->rollbackLinuxUpdate($backupFile);
            }

            return [
                'success' => true,
                'message' => 'Update rolled back successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to rollback update: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to rollback update: ' . $e->getMessage()
            ];
        }
    }

    public function getUpdateStats(): array
    {
        try {
            $stats = [
                'total_updates' => 0,
                'last_update' => null,
                'failed_updates' => 0,
                'pending_updates' => 0,
                'update_size' => 0
            ];

            $files = File::files($this->backupPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'json') {
                    $data = json_decode(File::get($file), true);
                    if ($data) {
                        $stats['total_updates']++;
                        if ($data['status'] === 'failed') {
                            $stats['failed_updates']++;
                        }
                        if ($data['status'] === 'pending') {
                            $stats['pending_updates']++;
                        }
                        if (!$stats['last_update'] || $data['date'] > $stats['last_update']) {
                            $stats['last_update'] = $data['date'];
                        }
                    }
                }
            }

            return [
                'success' => true,
                'stats' => $stats
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get update stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get update stats: ' . $e->getMessage()
            ];
        }
    }

    protected function checkWindowsUpdates(): array
    {
        $command = 'wmic qfe list brief';
        $output = Process::run($command)->output();
        return $this->parseWindowsUpdates($output);
    }

    protected function checkLinuxUpdates(): array
    {
        $commands = [
            'apt update',
            'apt list --upgradable'
        ];

        $updates = [];
        foreach ($commands as $command) {
            $output = Process::run($command)->output();
            $updates = array_merge($updates, $this->parseLinuxUpdates($output));
        }

        return $updates;
    }

    protected function installWindowsUpdates(array $updates = []): array
    {
        $command = 'wuauclt /detectnow /updatenow';
        Process::run($command);

        return [
            'status' => 'success',
            'message' => 'Windows updates installed successfully'
        ];
    }

    protected function installLinuxUpdates(array $updates = []): array
    {
        $commands = [
            'apt update',
            'apt upgrade -y'
        ];

        $results = [];
        foreach ($commands as $command) {
            $output = Process::run($command)->output();
            $results[] = [
                'command' => $command,
                'output' => $output
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Linux updates installed successfully',
            'results' => $results
        ];
    }

    protected function backupSystem(): void
    {
        $backupFile = $this->backupPath . '/' . date('Y-m-d_H-i-s') . '.zip';
        $files = [
            base_path(),
            storage_path(),
            public_path()
        ];

        $zip = new \ZipArchive();
        if ($zip->open($backupFile, \ZipArchive::CREATE) === true) {
            foreach ($files as $file) {
                $this->addToZip($zip, $file);
            }
            $zip->close();
        }

        $this->cleanupOldBackups();
    }

    protected function rollbackWindowsUpdate(string $backupFile): void
    {
        $tempDir = $this->tempPath . '/' . uniqid();
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($backupFile) === true) {
            $zip->extractTo($tempDir);
            $zip->close();
        }

        $this->restoreFromBackup($tempDir);
        File::deleteDirectory($tempDir);
    }

    protected function rollbackLinuxUpdate(string $backupFile): void
    {
        $tempDir = $this->tempPath . '/' . uniqid();
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($backupFile) === true) {
            $zip->extractTo($tempDir);
            $zip->close();
        }

        $this->restoreFromBackup($tempDir);
        File::deleteDirectory($tempDir);
    }

    protected function parseWindowsUpdates(string $output): array
    {
        $updates = [];
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, 'HotFixID') === false) {
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) >= 3) {
                    $updates[] = [
                        'id' => $parts[0],
                        'description' => $parts[1],
                        'installed_on' => $parts[2]
                    ];
                }
            }
        }
        return $updates;
    }

    protected function parseLinuxUpdates(string $output): array
    {
        $updates = [];
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, 'Listing') === false) {
                $parts = explode('/', $line);
                if (count($parts) >= 2) {
                    $updates[] = [
                        'package' => $parts[0],
                        'version' => $parts[1],
                        'repository' => $parts[2] ?? null
                    ];
                }
            }
        }
        return $updates;
    }

    protected function addToZip(\ZipArchive $zip, string $path, string $relativePath = ''): void
    {
        $files = File::allFiles($path);
        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $relativePath ? $relativePath . '/' . $file->getRelativePathname() : $file->getRelativePathname();
            $zip->addFile($filePath, $relativePath);
        }
    }

    protected function restoreFromBackup(string $backupDir): void
    {
        $files = File::allFiles($backupDir);
        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $targetPath = base_path($relativePath);
            if (File::exists($targetPath)) {
                File::copy($file->getRealPath(), $targetPath);
            }
        }
    }

    protected function cleanupOldBackups(): void
    {
        $files = File::files($this->backupPath);
        usort($files, function ($a, $b) {
            return $b->getMTime() - $a->getMTime();
        });

        for ($i = $this->maxBackups; $i < count($files); $i++) {
            File::delete($files[$i]);
        }
    }

    protected function notifyUpdateComplete(array $result): void
    {
        // Implement notification logic here
        // This could be email, SMS, or any other notification method
    }
} 