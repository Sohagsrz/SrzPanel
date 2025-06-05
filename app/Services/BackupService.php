<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\Database;
use App\Models\Domain;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use ZipArchive;

class BackupService
{
    protected $backupPath;
    protected $isWindows;
    protected $tempPath;
    protected $maxBackups;
    protected $compressionLevel;
    protected $autoBackup;
    protected $notifyOnBackup;
    protected $backupTypes;

    public function __construct()
    {
        $this->backupPath = storage_path('backups');
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->tempPath = storage_path('temp/backups');
        $this->maxBackups = config('backup.max_backups', 5);
        $this->compressionLevel = config('backup.compression_level', 9);
        $this->autoBackup = config('backup.auto_backup', true);
        $this->notifyOnBackup = config('backup.notify_on_backup', true);
        $this->backupTypes = [
            'full' => [
                'name' => 'Full Backup',
                'description' => 'Backup all system files and databases'
            ],
            'files' => [
                'name' => 'Files Backup',
                'description' => 'Backup only system files'
            ],
            'database' => [
                'name' => 'Database Backup',
                'description' => 'Backup only databases'
            ],
            'config' => [
                'name' => 'Configuration Backup',
                'description' => 'Backup only configuration files'
            ]
        ];
        
        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }

        if (!File::exists($this->tempPath)) {
            File::makeDirectory($this->tempPath, 0755, true);
        }
    }

    public function createDatabaseBackup(Database $database): Backup
    {
        $filename = sprintf(
            'database_%s_%s.sql',
            $database->name,
            now()->format('Y-m-d_His')
        );

        $path = "backups/databases/{$database->id}/{$filename}";

        // Create backup file
        $this->backupDatabase($database, $path);

        // Create backup record
        $backup = Backup::create([
            'name' => $filename,
            'type' => 'database',
            'database_id' => $database->id,
            'path' => $path,
            'size' => Storage::size($path),
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Update database record
        $database->update(['last_backup_at' => now()]);

        return $backup;
    }

    public function createDomainBackup(Domain $domain): Backup
    {
        $filename = sprintf(
            'domain_%s_%s.tar.gz',
            $domain->name,
            now()->format('Y-m-d_His')
        );

        $path = "backups/domains/{$domain->id}/{$filename}";

        // Create backup file
        $this->backupDomain($domain, $path);

        // Create backup record
        $backup = Backup::create([
            'name' => $filename,
            'type' => 'domain',
            'domain_id' => $domain->id,
            'path' => $path,
            'size' => Storage::size($path),
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return $backup;
    }

    public function restoreDatabaseBackup(Database $database, int $backupId): void
    {
        $backup = Backup::where('database_id', $database->id)
            ->where('id', $backupId)
            ->firstOrFail();

        // Restore database from backup
        $this->restoreDatabase($database, $backup->path);
    }

    public function restoreDomainBackup(Domain $domain, int $backupId): void
    {
        $backup = Backup::where('domain_id', $domain->id)
            ->where('id', $backupId)
            ->firstOrFail();

        // Restore domain from backup
        $this->restoreDomain($domain, $backup->path);
    }

    public function getDatabaseBackups(Database $database): array
    {
        return Backup::where('database_id', $database->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function getDomainBackups(Domain $domain): array
    {
        return Backup::where('domain_id', $domain->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function deleteBackup(Backup $backup): void
    {
        // Delete backup file
        Storage::delete($backup->path);

        // Delete backup record
        $backup->delete();
    }

    protected function backupDatabase(Database $database, string $path): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Connect to the database
        // 2. Create a SQL dump
        // 3. Save the dump to the specified path
        $command = sprintf(
            'mysqldump -u %s -p%s %s > %s',
            $database->username,
            $database->password,
            $database->name,
            storage_path('app/' . $path)
        );

        exec($command);
    }

    protected function backupDomain(Domain $domain, string $path): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Create a tar archive of the domain's document root
        // 2. Save the archive to the specified path
        $command = sprintf(
            'tar -czf %s -C %s .',
            storage_path('app/' . $path),
            $domain->document_root
        );

        exec($command);
    }

    protected function restoreDatabase(Database $database, string $path): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Connect to the database
        // 2. Restore the SQL dump
        $command = sprintf(
            'mysql -u %s -p%s %s < %s',
            $database->username,
            $database->password,
            $database->name,
            storage_path('app/' . $path)
        );

        exec($command);
    }

    protected function restoreDomain(Domain $domain, string $path): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Extract the tar archive to the domain's document root
        $command = sprintf(
            'tar -xzf %s -C %s',
            storage_path('app/' . $path),
            $domain->document_root
        );

        exec($command);
    }

    public function createBackup(string $type = 'full', array $options = []): array
    {
        try {
            if (!isset($this->backupTypes[$type])) {
                throw new \Exception("Invalid backup type: {$type}");
            }

            $backupDir = $this->backupPath . '/' . date('Y-m-d_H-i-s') . '_' . $type;
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            $result = [];
            switch ($type) {
                case 'full':
                    $result = $this->createFullBackup($backupDir, $options);
                    break;
                case 'files':
                    $result = $this->createFilesBackup($backupDir, $options);
                    break;
                case 'database':
                    $result = $this->createDatabaseBackup($backupDir, $options);
                    break;
                case 'config':
                    $result = $this->createConfigBackup($backupDir, $options);
                    break;
            }

            if ($this->notifyOnBackup) {
                $this->notifyBackupComplete($type, $result);
            }

            $this->cleanupOldBackups();

            return [
                'success' => true,
                'message' => "{$this->backupTypes[$type]['name']} created successfully",
                'result' => $result
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create backup: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create backup: ' . $e->getMessage()
            ];
        }
    }

    public function updateBackup(Backup $backup, array $data): void
    {
        try {
            $backup->update($data);
        } catch (\Exception $e) {
            Log::error('Failed to update backup: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteBackup(Backup $backup): array
    {
        try {
            if (File::exists($backup->path)) {
                File::delete($backup->path);
            }

            $backup->delete();

            return [
                'success' => true,
                'message' => 'Backup deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete backup: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete backup: ' . $e->getMessage()
            ];
        }
    }

    public function restoreBackup(string $backupId): array
    {
        try {
            $backupDir = $this->backupPath . '/' . $backupId;
            if (!File::exists($backupDir)) {
                throw new \Exception("Backup not found: {$backupId}");
            }

            $type = $this->getBackupType($backupDir);
            if (!$type) {
                throw new \Exception("Invalid backup type");
            }

            $result = [];
            switch ($type) {
                case 'full':
                    $result = $this->restoreFullBackup($backupDir);
                    break;
                case 'files':
                    $result = $this->restoreFilesBackup($backupDir);
                    break;
                case 'database':
                    $result = $this->restoreDatabaseBackup($backupDir);
                    break;
                case 'config':
                    $result = $this->restoreConfigBackup($backupDir);
                    break;
            }

            if ($this->notifyOnBackup) {
                $this->notifyRestoreComplete($type, $result);
            }

            return [
                'success' => true,
                'message' => "{$this->backupTypes[$type]['name']} restored successfully",
                'result' => $result
            ];
        } catch (\Exception $e) {
            Log::error('Failed to restore backup: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore backup: ' . $e->getMessage()
            ];
        }
    }

    public function getBackupStatus(Backup $backup): array
    {
        try {
            $status = [
                'id' => $backup->id,
                'name' => $backup->name,
                'type' => $backup->type,
                'status' => $backup->status,
                'size' => $backup->size,
                'created_at' => $backup->created_at,
                'updated_at' => $backup->updated_at
            ];

            if (File::exists($backup->path)) {
                $status['exists'] = true;
                $status['size'] = File::size($backup->path);
                $status['last_modified'] = File::lastModified($backup->path);
            } else {
                $status['exists'] = false;
            }

            return [
                'success' => true,
                'status' => $status
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get backup status: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get backup status: ' . $e->getMessage()
            ];
        }
    }

    public function getBackupHistory(Backup $backup): array
    {
        try {
            return $backup->history()
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get backup history: ' . $e->getMessage());
            return [];
        }
    }

    public function getBackupStats(): array
    {
        try {
            $stats = [
                'total_backups' => 0,
                'total_size' => 0,
                'last_backup' => null,
                'backup_types' => []
            ];

            foreach ($this->backupTypes as $type => $info) {
                $stats['backup_types'][$type] = [
                    'count' => 0,
                    'size' => 0
                ];
            }

            $directories = File::directories($this->backupPath);
            foreach ($directories as $dir) {
                $type = $this->getBackupType($dir);
                if ($type) {
                    $stats['total_backups']++;
                    $size = $this->getDirectorySize($dir);
                    $stats['total_size'] += $size;
                    $stats['backup_types'][$type]['count']++;
                    $stats['backup_types'][$type]['size'] += $size;

                    $createdAt = Carbon::createFromFormat('Y-m-d_H-i-s', substr(basename($dir), 0, 19));
                    if (!$stats['last_backup'] || $createdAt > $stats['last_backup']) {
                        $stats['last_backup'] = $createdAt->toDateTimeString();
                    }
                }
            }

            return [
                'success' => true,
                'stats' => $stats
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get backup stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get backup stats: ' . $e->getMessage()
            ];
        }
    }

    protected function createFullBackup(string $backupDir, array $options = []): array
    {
        $result = [
            'files' => $this->createFilesBackup($backupDir . '/files', $options),
            'database' => $this->createDatabaseBackup($backupDir . '/database', $options),
            'config' => $this->createConfigBackup($backupDir . '/config', $options)
        ];

        return $result;
    }

    protected function createFilesBackup(string $backupDir, array $options = []): array
    {
        $files = [
            base_path(),
            storage_path(),
            public_path()
        ];

        $zip = new \ZipArchive();
        $zipFile = $backupDir . '.zip';
        if ($zip->open($zipFile, \ZipArchive::CREATE) === true) {
            foreach ($files as $file) {
                $this->addToZip($zip, $file);
            }
            $zip->close();
        }

        return [
            'path' => $zipFile,
            'size' => File::size($zipFile)
        ];
    }

    protected function createDatabaseBackup(string $backupDir, array $options = []): array
    {
        $result = [];
        $databases = config('database.connections');

        foreach ($databases as $name => $config) {
            if ($this->isWindows) {
                $command = "mysqldump -h {$config['host']} -u {$config['username']} -p{$config['password']} {$config['database']} > {$backupDir}/{$name}.sql";
            } else {
                $command = "mysqldump -h {$config['host']} -u {$config['username']} -p{$config['password']} {$config['database']} > {$backupDir}/{$name}.sql";
            }

            Process::run($command);
            $result[$name] = [
                'path' => "{$backupDir}/{$name}.sql",
                'size' => File::size("{$backupDir}/{$name}.sql")
            ];
        }

        return $result;
    }

    protected function createConfigBackup(string $backupDir, array $options = []): array
    {
        $configFiles = [
            base_path('.env'),
            base_path('config'),
            base_path('bootstrap/cache')
        ];

        $zip = new \ZipArchive();
        $zipFile = $backupDir . '.zip';
        if ($zip->open($zipFile, \ZipArchive::CREATE) === true) {
            foreach ($configFiles as $file) {
                $this->addToZip($zip, $file);
            }
            $zip->close();
        }

        return [
            'path' => $zipFile,
            'size' => File::size($zipFile)
        ];
    }

    protected function restoreFullBackup(string $backupDir): array
    {
        $result = [
            'files' => $this->restoreFilesBackup($backupDir . '/files'),
            'database' => $this->restoreDatabaseBackup($backupDir . '/database'),
            'config' => $this->restoreConfigBackup($backupDir . '/config')
        ];

        return $result;
    }

    protected function restoreFilesBackup(string $backupDir): array
    {
        $zipFile = $backupDir . '.zip';
        if (!File::exists($zipFile)) {
            throw new \Exception("Backup file not found: {$zipFile}");
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipFile) === true) {
            $zip->extractTo($this->tempPath);
            $zip->close();
        }

        $this->restoreFromBackup($this->tempPath);
        File::deleteDirectory($this->tempPath);

        return [
            'path' => $zipFile,
            'restored' => true
        ];
    }

    protected function restoreDatabaseBackup(string $backupDir): array
    {
        $result = [];
        $databases = config('database.connections');

        foreach ($databases as $name => $config) {
            $sqlFile = "{$backupDir}/{$name}.sql";
            if (!File::exists($sqlFile)) {
                continue;
            }

            if ($this->isWindows) {
                $command = "mysql -h {$config['host']} -u {$config['username']} -p{$config['password']} {$config['database']} < {$sqlFile}";
            } else {
                $command = "mysql -h {$config['host']} -u {$config['username']} -p{$config['password']} {$config['database']} < {$sqlFile}";
            }

            Process::run($command);
            $result[$name] = [
                'path' => $sqlFile,
                'restored' => true
            ];
        }

        return $result;
    }

    protected function restoreConfigBackup(string $backupDir): array
    {
        $zipFile = $backupDir . '.zip';
        if (!File::exists($zipFile)) {
            throw new \Exception("Backup file not found: {$zipFile}");
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipFile) === true) {
            $zip->extractTo($this->tempPath);
            $zip->close();
        }

        $this->restoreFromBackup($this->tempPath);
        File::deleteDirectory($this->tempPath);

        return [
            'path' => $zipFile,
            'restored' => true
        ];
    }

    protected function getBackupType(string $backupDir): ?string
    {
        $parts = explode('_', basename($backupDir));
        return end($parts);
    }

    protected function getDirectorySize(string $dir): int
    {
        $size = 0;
        $files = File::allFiles($dir);
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        return $size;
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
        $directories = File::directories($this->backupPath);
        usort($directories, function ($a, $b) {
            return $b->getMTime() - $a->getMTime();
        });

        for ($i = $this->maxBackups; $i < count($directories); $i++) {
            File::deleteDirectory($directories[$i]);
        }
    }

    protected function notifyBackupComplete(string $type, array $result): void
    {
        // Implement notification logic here
        // This could be email, SMS, or any other notification method
    }

    protected function notifyRestoreComplete(string $type, array $result): void
    {
        // Implement notification logic here
        // This could be email, SMS, or any other notification method
    }
} 