<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class PostgresService
{
    protected $os;

    public function __construct()
    {
        $this->os = strtoupper(substr(PHP_OS, 0, 3));
    }

    public function listDatabases()
    {
        try {
            if ($this->os === 'WIN') {
                $result = Process::run('psql -U postgres -c "\l"');
            } else {
                $result = Process::run('sudo -u postgres psql -c "\l"');
            }
            if ($result->successful()) {
                return $result->output();
            }
        } catch (\Exception $e) {
            Log::error('Failed to list PostgreSQL databases: ' . $e->getMessage());
        }
        return null;
    }

    public function createDatabase($dbName, $dbUser, $dbPassword)
    {
        try {
            if ($this->os === 'WIN') {
                $createUser = Process::run("psql -U postgres -c \"CREATE USER \"{$dbUser}\" WITH PASSWORD '{$dbPassword}';\"");
                $createDb = Process::run("psql -U postgres -c \"CREATE DATABASE \"{$dbName}\" OWNER \"{$dbUser}\";\"");
            } else {
                $createUser = Process::run("sudo -u postgres psql -c \"CREATE USER \"{$dbUser}\" WITH PASSWORD '{$dbPassword}';\"");
                $createDb = Process::run("sudo -u postgres psql -c \"CREATE DATABASE \"{$dbName}\" OWNER \"{$dbUser}\";\"");
            }
            return $createUser->successful() && $createDb->successful();
        } catch (\Exception $e) {
            Log::error('Failed to create PostgreSQL database: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteDatabase($dbName, $dbUser = null)
    {
        try {
            if ($this->os === 'WIN') {
                $dropDb = Process::run("psql -U postgres -c \"DROP DATABASE IF EXISTS \"{$dbName}\";\"");
                if ($dbUser) {
                    $dropUser = Process::run("psql -U postgres -c \"DROP USER IF EXISTS \"{$dbUser}\";\"");
                }
            } else {
                $dropDb = Process::run("sudo -u postgres psql -c \"DROP DATABASE IF EXISTS \"{$dbName}\";\"");
                if ($dbUser) {
                    $dropUser = Process::run("sudo -u postgres psql -c \"DROP USER IF EXISTS \"{$dbUser}\";\"");
                }
            }
            return $dropDb->successful();
        } catch (\Exception $e) {
            Log::error('Failed to delete PostgreSQL database: ' . $e->getMessage());
            return false;
        }
    }

    public function backupDatabase($dbName)
    {
        try {
            $backupPath = storage_path("backups/postgres/{$dbName}_" . date('Y-m-d_H-i-s') . ".sql");
            if ($this->os === 'WIN') {
                $result = Process::run("pg_dump -U postgres {$dbName} > \"{$backupPath}\"");
            } else {
                $result = Process::run("sudo -u postgres pg_dump {$dbName} > \"{$backupPath}\"");
            }
            return $result->successful();
        } catch (\Exception $e) {
            Log::error('Failed to backup PostgreSQL database: ' . $e->getMessage());
            return false;
        }
    }

    public function restoreDatabase($dbName, $backupFile)
    {
        try {
            $backupPath = $backupFile->storeAs('backups/postgres', $backupFile->getClientOriginalName());
            if ($this->os === 'WIN') {
                $result = Process::run("psql -U postgres {$dbName} < \"{$backupPath}\"");
            } else {
                $result = Process::run("sudo -u postgres psql {$dbName} < \"{$backupPath}\"");
            }
            return $result->successful();
        } catch (\Exception $e) {
            Log::error('Failed to restore PostgreSQL database: ' . $e->getMessage());
            return false;
        }
    }
} 