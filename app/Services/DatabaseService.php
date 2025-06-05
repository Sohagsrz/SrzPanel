<?php

namespace App\Services;

use App\Models\Database;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseService
{
    protected $isWindows;
    protected $databasePath;
    protected $backupPath;
    protected $tempPath;
    protected $maxBackups;
    protected $autoBackup;
    protected $notifyOnBackup;
    protected $databaseTypes;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->databasePath = $this->isWindows ? 'C:\\laragon\\databases' : '/var/lib/mysql';
        $this->backupPath = storage_path('backups/databases');
        $this->tempPath = storage_path('temp/databases');
        $this->maxBackups = config('database.max_backups', 5);
        $this->autoBackup = config('database.auto_backup', true);
        $this->notifyOnBackup = config('database.notify_on_backup', true);
        $this->databaseTypes = [
            'mysql' => [
                'name' => 'MySQL',
                'description' => 'MySQL database'
            ],
            'postgresql' => [
                'name' => 'PostgreSQL',
                'description' => 'PostgreSQL database'
            ],
            'sqlite' => [
                'name' => 'SQLite',
                'description' => 'SQLite database'
            ]
        ];

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }

        if (!File::exists($this->tempPath)) {
            File::makeDirectory($this->tempPath, 0755, true);
        }
    }

    public function createDatabase(string $name, string $type = 'mysql', array $options = []): array
    {
        try {
            if (!isset($this->databaseTypes[$type])) {
                throw new \Exception("Invalid database type: {$type}");
            }

            $result = [];
            switch ($type) {
                case 'mysql':
                    $result = $this->createMySQLDatabase($name, $options);
                    break;
                case 'postgresql':
                    $result = $this->createPostgreSQLDatabase($name, $options);
                    break;
                case 'sqlite':
                    $result = $this->createSQLiteDatabase($name, $options);
                    break;
            }

            if ($this->autoBackup) {
                $this->backupDatabase($name, $type);
            }

            return [
                'success' => true,
                'message' => "{$this->databaseTypes[$type]['name']} created successfully",
                'result' => $result
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create database: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create database: ' . $e->getMessage()
            ];
        }
    }

    public function deleteDatabase(string $name, string $type = 'mysql'): array
    {
        try {
            if (!isset($this->databaseTypes[$type])) {
                throw new \Exception("Invalid database type: {$type}");
            }

            $result = [];
            switch ($type) {
                case 'mysql':
                    $result = $this->deleteMySQLDatabase($name);
                    break;
                case 'postgresql':
                    $result = $this->deletePostgreSQLDatabase($name);
                    break;
                case 'sqlite':
                    $result = $this->deleteSQLiteDatabase($name);
                    break;
            }

            return [
                'success' => true,
                'message' => "{$this->databaseTypes[$type]['name']} deleted successfully",
                'result' => $result
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete database: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete database: ' . $e->getMessage()
            ];
        }
    }

    public function backupDatabase(string $name, string $type = 'mysql'): array
    {
        try {
            if (!isset($this->databaseTypes[$type])) {
                throw new \Exception("Invalid database type: {$type}");
            }

            $result = [];
            switch ($type) {
                case 'mysql':
                    $result = $this->backupMySQLDatabase($name);
                    break;
                case 'postgresql':
                    $result = $this->backupPostgreSQLDatabase($name);
                    break;
                case 'sqlite':
                    $result = $this->backupSQLiteDatabase($name);
                    break;
            }

            if ($this->notifyOnBackup) {
                $this->notifyBackupComplete($name, $type, $result);
            }

            $this->cleanupOldBackups();

            return [
                'success' => true,
                'message' => "{$this->databaseTypes[$type]['name']} backed up successfully",
                'result' => $result
            ];
        } catch (\Exception $e) {
            Log::error('Failed to backup database: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to backup database: ' . $e->getMessage()
            ];
        }
    }

    public function restoreDatabase(string $name, string $type = 'mysql', string $backupFile = null): array
    {
        try {
            if (!isset($this->databaseTypes[$type])) {
                throw new \Exception("Invalid database type: {$type}");
            }

            $result = [];
            switch ($type) {
                case 'mysql':
                    $result = $this->restoreMySQLDatabase($name, $backupFile);
                    break;
                case 'postgresql':
                    $result = $this->restorePostgreSQLDatabase($name, $backupFile);
                    break;
                case 'sqlite':
                    $result = $this->restoreSQLiteDatabase($name, $backupFile);
                    break;
            }

            return [
                'success' => true,
                'message' => "{$this->databaseTypes[$type]['name']} restored successfully",
                'result' => $result
            ];
        } catch (\Exception $e) {
            Log::error('Failed to restore database: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore database: ' . $e->getMessage()
            ];
        }
    }

    public function getDatabaseStats(string $name, string $type = 'mysql'): array
    {
        try {
            if (!isset($this->databaseTypes[$type])) {
                throw new \Exception("Invalid database type: {$type}");
            }

            $result = [];
            switch ($type) {
                case 'mysql':
                    $result = $this->getMySQLDatabaseStats($name);
                    break;
                case 'postgresql':
                    $result = $this->getPostgreSQLDatabaseStats($name);
                    break;
                case 'sqlite':
                    $result = $this->getSQLiteDatabaseStats($name);
                    break;
            }

            return [
                'success' => true,
                'stats' => $result
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get database stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get database stats: ' . $e->getMessage()
            ];
        }
    }

    protected function createMySQLDatabase(string $name, array $options = []): array
    {
        $command = sprintf(
            'mysql -u %s -p%s -e "CREATE DATABASE %s CHARACTER SET %s COLLATE %s"',
            $options['username'] ?? 'root',
            $options['password'] ?? '',
            $name,
            $options['charset'] ?? 'utf8mb4',
            $options['collation'] ?? 'utf8mb4_unicode_ci'
        );

        Process::run($command);

        return [
            'name' => $name,
            'type' => 'mysql',
            'charset' => $options['charset'] ?? 'utf8mb4',
            'collation' => $options['collation'] ?? 'utf8mb4_unicode_ci'
        ];
    }

    protected function createPostgreSQLDatabase(string $name, array $options = []): array
    {
        $command = sprintf(
            'createdb -U %s -E %s -l %s %s',
            $options['username'] ?? 'postgres',
            $options['encoding'] ?? 'UTF8',
            $options['locale'] ?? 'en_US.UTF-8',
            $name
        );

        Process::run($command);

        return [
            'name' => $name,
            'type' => 'postgresql',
            'encoding' => $options['encoding'] ?? 'UTF8',
            'locale' => $options['locale'] ?? 'en_US.UTF-8'
        ];
    }

    protected function createSQLiteDatabase(string $name, array $options = []): array
    {
        $path = $this->databasePath . '/' . $name . '.db';
        File::put($path, '');

        return [
            'name' => $name,
            'type' => 'sqlite',
            'path' => $path
        ];
    }

    protected function deleteMySQLDatabase(string $name): array
    {
        $command = sprintf(
            'mysql -u %s -p%s -e "DROP DATABASE %s"',
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            $name
        );

        Process::run($command);

        return [
            'name' => $name,
            'type' => 'mysql'
        ];
    }

    protected function deletePostgreSQLDatabase(string $name): array
    {
        $command = sprintf(
            'dropdb -U %s %s',
            config('database.connections.pgsql.username'),
            $name
        );

        Process::run($command);

        return [
            'name' => $name,
            'type' => 'postgresql'
        ];
    }

    protected function deleteSQLiteDatabase(string $name): array
    {
        $path = $this->databasePath . '/' . $name . '.db';
        if (File::exists($path)) {
            File::delete($path);
        }

        return [
            'name' => $name,
            'type' => 'sqlite',
            'path' => $path
        ];
    }

    protected function backupMySQLDatabase(string $name): array
    {
        $filename = date('Y-m-d_H-i-s') . '_' . $name . '.sql';
        $path = $this->backupPath . '/' . $filename;

        $command = sprintf(
            'mysqldump -u %s -p%s %s > %s',
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            $name,
            $path
        );

        Process::run($command);

        return [
            'name' => $name,
            'type' => 'mysql',
            'path' => $path,
            'size' => File::size($path)
        ];
    }

    protected function backupPostgreSQLDatabase(string $name): array
    {
        $filename = date('Y-m-d_H-i-s') . '_' . $name . '.sql';
        $path = $this->backupPath . '/' . $filename;

        $command = sprintf(
            'pg_dump -U %s %s > %s',
            config('database.connections.pgsql.username'),
            $name,
            $path
        );

        Process::run($command);

        return [
            'name' => $name,
            'type' => 'postgresql',
            'path' => $path,
            'size' => File::size($path)
        ];
    }

    protected function backupSQLiteDatabase(string $name): array
    {
        $filename = date('Y-m-d_H-i-s') . '_' . $name . '.db';
        $path = $this->backupPath . '/' . $filename;

        $sourcePath = $this->databasePath . '/' . $name . '.db';
        File::copy($sourcePath, $path);

        return [
            'name' => $name,
            'type' => 'sqlite',
            'path' => $path,
            'size' => File::size($path)
        ];
    }

    protected function restoreMySQLDatabase(string $name, string $backupFile = null): array
    {
        if (!$backupFile) {
            $backupFile = $this->getLatestBackup($name, 'mysql');
        }

        $command = sprintf(
            'mysql -u %s -p%s %s < %s',
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            $name,
            $backupFile
        );

        Process::run($command);

        return [
            'name' => $name,
            'type' => 'mysql',
            'backup' => $backupFile
        ];
    }

    protected function restorePostgreSQLDatabase(string $name, string $backupFile = null): array
    {
        if (!$backupFile) {
            $backupFile = $this->getLatestBackup($name, 'postgresql');
        }

        $command = sprintf(
            'psql -U %s %s < %s',
            config('database.connections.pgsql.username'),
            $name,
            $backupFile
        );

        Process::run($command);

        return [
            'name' => $name,
            'type' => 'postgresql',
            'backup' => $backupFile
        ];
    }

    protected function restoreSQLiteDatabase(string $name, string $backupFile = null): array
    {
        if (!$backupFile) {
            $backupFile = $this->getLatestBackup($name, 'sqlite');
        }

        $path = $this->databasePath . '/' . $name . '.db';
        File::copy($backupFile, $path);

        return [
            'name' => $name,
            'type' => 'sqlite',
            'backup' => $backupFile,
            'path' => $path
        ];
    }

    protected function getMySQLDatabaseStats(string $name): array
    {
        $command = sprintf(
            'mysql -u %s -p%s -e "SELECT table_name, table_rows, data_length, index_length FROM information_schema.tables WHERE table_schema = \'%s\'"',
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            $name
        );

        $output = Process::run($command)->output();
        $stats = $this->parseMySQLStats($output);

        return [
            'name' => $name,
            'type' => 'mysql',
            'tables' => $stats['tables'],
            'total_rows' => $stats['total_rows'],
            'total_size' => $stats['total_size']
        ];
    }

    protected function getPostgreSQLDatabaseStats(string $name): array
    {
        $command = sprintf(
            'psql -U %s %s -c "SELECT relname as table_name, n_live_tup as row_count, pg_total_relation_size(relid) as total_size FROM pg_stat_user_tables"',
            config('database.connections.pgsql.username'),
            $name
        );

        $output = Process::run($command)->output();
        $stats = $this->parsePostgreSQLStats($output);

        return [
            'name' => $name,
            'type' => 'postgresql',
            'tables' => $stats['tables'],
            'total_rows' => $stats['total_rows'],
            'total_size' => $stats['total_size']
        ];
    }

    protected function getSQLiteDatabaseStats(string $name): array
    {
        $path = $this->databasePath . '/' . $name . '.db';
        if (!File::exists($path)) {
            throw new \Exception("Database not found: {$name}");
        }

        return [
            'name' => $name,
            'type' => 'sqlite',
            'path' => $path,
            'size' => File::size($path)
        ];
    }

    protected function getLatestBackup(string $name, string $type): string
    {
        $pattern = $this->backupPath . '/*_' . $name . '.' . ($type === 'sqlite' ? 'db' : 'sql');
        $files = glob($pattern);
        if (empty($files)) {
            throw new \Exception("No backup found for database: {$name}");
        }

        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return $files[0];
    }

    protected function parseMySQLStats(string $output): array
    {
        $stats = [
            'tables' => [],
            'total_rows' => 0,
            'total_size' => 0
        ];

        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 4) {
                $stats['tables'][] = [
                    'name' => $parts[0],
                    'rows' => (int) $parts[1],
                    'data_size' => (int) $parts[2],
                    'index_size' => (int) $parts[3]
                ];
                $stats['total_rows'] += (int) $parts[1];
                $stats['total_size'] += (int) $parts[2] + (int) $parts[3];
            }
        }

        return $stats;
    }

    protected function parsePostgreSQLStats(string $output): array
    {
        $stats = [
            'tables' => [],
            'total_rows' => 0,
            'total_size' => 0
        ];

        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 3) {
                $stats['tables'][] = [
                    'name' => $parts[0],
                    'rows' => (int) $parts[1],
                    'size' => (int) $parts[2]
                ];
                $stats['total_rows'] += (int) $parts[1];
                $stats['total_size'] += (int) $parts[2];
            }
        }

        return $stats;
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

    protected function notifyBackupComplete(string $name, string $type, array $result): void
    {
        // Implement notification logic here
        // This could be email, SMS, or any other notification method
    }
} 