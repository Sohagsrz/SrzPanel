<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class MySQLService
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
                $result = Process::run('mysql -u root -e "SHOW DATABASES;"');
            } else {
                $result = Process::run('sudo mysql -e "SHOW DATABASES;"');
            }
            if ($result->successful()) {
                return $result->output();
            }
        } catch (\Exception $e) {
            Log::error('Failed to list MySQL databases: ' . $e->getMessage());
        }
        return null;
    }

    public function createDatabase($dbName, $dbUser, $dbPassword)
    {
        try {
            if ($this->os === 'WIN') {
                $createUser = Process::run("mysql -u root -e \"CREATE USER '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPassword}';\""); 
                $createDb = Process::run("mysql -u root -e \"CREATE DATABASE {$dbName};\""); 
                $grant = Process::run("mysql -u root -e \"GRANT ALL PRIVILEGES ON {$dbName}.* TO '{$dbUser}'@'localhost';\""); 
                $flush = Process::run("mysql -u root -e \"FLUSH PRIVILEGES;\"");
            } else {
                $createUser = Process::run("sudo mysql -e \"CREATE USER '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPassword}';\""); 
                $createDb = Process::run("sudo mysql -e \"CREATE DATABASE {$dbName};\""); 
                $grant = Process::run("sudo mysql -e \"GRANT ALL PRIVILEGES ON {$dbName}.* TO '{$dbUser}'@'localhost';\""); 
                $flush = Process::run("sudo mysql -e \"FLUSH PRIVILEGES;\"");
            }
            return $createUser->successful() && $createDb->successful() && $grant->successful() && $flush->successful();
        } catch (\Exception $e) {
            Log::error('Failed to create MySQL database: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteDatabase($dbName, $dbUser = null)
    {
        try {
            if ($this->os === 'WIN') {
                $dropDb = Process::run("mysql -u root -e \"DROP DATABASE IF EXISTS {$dbName};\"");
                if ($dbUser) {
                    $dropUser = Process::run("mysql -u root -e \"DROP USER IF EXISTS '{$dbUser}'@'localhost';\""); 
                    $flush = Process::run("mysql -u root -e \"FLUSH PRIVILEGES;\"");
                }
            } else {
                $dropDb = Process::run("sudo mysql -e \"DROP DATABASE IF EXISTS {$dbName};\"");
                if ($dbUser) {
                    $dropUser = Process::run("sudo mysql -e \"DROP USER IF EXISTS '{$dbUser}'@'localhost';\""); 
                    $flush = Process::run("sudo mysql -e \"FLUSH PRIVILEGES;\"");
                }
            }
            return $dropDb->successful();
        } catch (\Exception $e) {
            Log::error('Failed to delete MySQL database: ' . $e->getMessage());
            return false;
        }
    }

    public function backupDatabase($dbName)
    {
        try {
            $backupPath = storage_path("backups/mysql/{$dbName}_" . date('Y-m-d_H-i-s') . ".sql");
            if ($this->os === 'WIN') {
                $result = Process::run("mysqldump -u root {$dbName} > \"{$backupPath}\"");
            } else {
                $result = Process::run("sudo mysqldump {$dbName} > \"{$backupPath}\"");
            }
            return $result->successful();
        } catch (\Exception $e) {
            Log::error('Failed to backup MySQL database: ' . $e->getMessage());
            return false;
        }
    }

    public function restoreDatabase($dbName, $backupFile)
    {
        try {
            $backupPath = $backupFile->storeAs('backups/mysql', $backupFile->getClientOriginalName());
            if ($this->os === 'WIN') {
                $result = Process::run("mysql -u root {$dbName} < \"{$backupPath}\"");
            } else {
                $result = Process::run("sudo mysql {$dbName} < \"{$backupPath}\"");
            }
            return $result->successful();
        } catch (\Exception $e) {
            Log::error('Failed to restore MySQL database: ' . $e->getMessage());
            return false;
        }
    }
} 