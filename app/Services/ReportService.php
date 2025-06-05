<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Models\Report;
use App\Models\User;
use App\Models\Domain;
use App\Models\Database;
use App\Models\FtpAccount;
use App\Models\CronJob;
use App\Models\Notification;
use App\Models\Setting;

class ReportService
{
    protected $isWindows;
    protected $reportPath;
    protected $backupPath;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->reportPath = $this->isWindows ? 'C:\\laragon\\reports' : '/var/reports';
        $this->backupPath = storage_path('backups/reports');

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    public function generateSystemReport(): array
    {
        try {
            $report = [
                'timestamp' => now(),
                'system' => $this->getSystemInfo(),
                'users' => $this->getUserStats(),
                'domains' => $this->getDomainStats(),
                'databases' => $this->getDatabaseStats(),
                'ftp' => $this->getFtpStats(),
                'cron' => $this->getCronStats(),
                'notifications' => $this->getNotificationStats(),
                'settings' => $this->getSettingStats()
            ];

            $reportFile = $this->saveReport($report, 'system');

            return [
                'success' => true,
                'message' => 'System report generated successfully',
                'report' => $report,
                'file' => $reportFile
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate system report: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate system report: ' . $e->getMessage()
            ];
        }
    }

    public function generateUserReport(User $user): array
    {
        try {
            $report = [
                'timestamp' => now(),
                'user' => $this->getUserInfo($user),
                'domains' => $this->getUserDomains($user),
                'databases' => $this->getUserDatabases($user),
                'ftp' => $this->getUserFtp($user),
                'cron' => $this->getUserCron($user),
                'notifications' => $this->getUserNotifications($user)
            ];

            $reportFile = $this->saveReport($report, 'user_' . $user->id);

            return [
                'success' => true,
                'message' => 'User report generated successfully',
                'report' => $report,
                'file' => $reportFile
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate user report: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate user report: ' . $e->getMessage()
            ];
        }
    }

    public function generateDomainReport(Domain $domain): array
    {
        try {
            $report = [
                'timestamp' => now(),
                'domain' => $this->getDomainInfo($domain),
                'databases' => $this->getDomainDatabases($domain),
                'ftp' => $this->getDomainFtp($domain),
                'cron' => $this->getDomainCron($domain),
                'logs' => $this->getDomainLogs($domain)
            ];

            $reportFile = $this->saveReport($report, 'domain_' . $domain->id);

            return [
                'success' => true,
                'message' => 'Domain report generated successfully',
                'report' => $report,
                'file' => $reportFile
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate domain report: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate domain report: ' . $e->getMessage()
            ];
        }
    }

    public function generateDatabaseReport(Database $database): array
    {
        try {
            $report = [
                'timestamp' => now(),
                'database' => $this->getDatabaseInfo($database),
                'backups' => $this->getDatabaseBackups($database),
                'users' => $this->getDatabaseUsers($database),
                'logs' => $this->getDatabaseLogs($database)
            ];

            $reportFile = $this->saveReport($report, 'database_' . $database->id);

            return [
                'success' => true,
                'message' => 'Database report generated successfully',
                'report' => $report,
                'file' => $reportFile
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate database report: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate database report: ' . $e->getMessage()
            ];
        }
    }

    public function generateFtpReport(FtpAccount $ftp): array
    {
        try {
            $report = [
                'timestamp' => now(),
                'ftp' => $this->getFtpInfo($ftp),
                'backups' => $this->getFtpBackups($ftp),
                'logs' => $this->getFtpLogs($ftp)
            ];

            $reportFile = $this->saveReport($report, 'ftp_' . $ftp->id);

            return [
                'success' => true,
                'message' => 'FTP report generated successfully',
                'report' => $report,
                'file' => $reportFile
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate FTP report: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate FTP report: ' . $e->getMessage()
            ];
        }
    }

    public function generateCronReport(CronJob $cron): array
    {
        try {
            $report = [
                'timestamp' => now(),
                'cron' => $this->getCronInfo($cron),
                'logs' => $this->getCronLogs($cron)
            ];

            $reportFile = $this->saveReport($report, 'cron_' . $cron->id);

            return [
                'success' => true,
                'message' => 'Cron report generated successfully',
                'report' => $report,
                'file' => $reportFile
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate cron report: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate cron report: ' . $e->getMessage()
            ];
        }
    }

    protected function getSystemInfo(): array
    {
        return [
            'os' => PHP_OS,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
            'server_protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
            'server_port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'memory_usage' => memory_get_usage(true),
            'disk_free_space' => disk_free_space('/'),
            'disk_total_space' => disk_total_space('/')
        ];
    }

    protected function getUserStats(): array
    {
        return [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
            'by_role' => User::selectRaw('role, count(*) as count')
                ->groupBy('role')
                ->get()
                ->pluck('count', 'role')
                ->toArray()
        ];
    }

    protected function getDomainStats(): array
    {
        return [
            'total' => Domain::count(),
            'active' => Domain::where('status', 'active')->count(),
            'inactive' => Domain::where('status', 'inactive')->count(),
            'by_type' => Domain::selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type')
                ->toArray()
        ];
    }

    protected function getDatabaseStats(): array
    {
        return [
            'total' => Database::count(),
            'active' => Database::where('status', 'active')->count(),
            'inactive' => Database::where('status', 'inactive')->count(),
            'by_type' => Database::selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type')
                ->toArray()
        ];
    }

    protected function getFtpStats(): array
    {
        return [
            'total' => FtpAccount::count(),
            'active' => FtpAccount::where('status', 'active')->count(),
            'inactive' => FtpAccount::where('status', 'inactive')->count()
        ];
    }

    protected function getCronStats(): array
    {
        return [
            'total' => CronJob::count(),
            'active' => CronJob::where('status', 'active')->count(),
            'inactive' => CronJob::where('status', 'inactive')->count()
        ];
    }

    protected function getNotificationStats(): array
    {
        return [
            'total' => Notification::count(),
            'unread' => Notification::whereNull('read_at')->count(),
            'read' => Notification::whereNotNull('read_at')->count(),
            'by_type' => Notification::selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type')
                ->toArray()
        ];
    }

    protected function getSettingStats(): array
    {
        return [
            'total' => Setting::count(),
            'by_group' => Setting::selectRaw('group, count(*) as count')
                ->groupBy('group')
                ->get()
                ->pluck('count', 'group')
                ->toArray()
        ];
    }

    protected function getUserInfo(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at
        ];
    }

    protected function getUserDomains(User $user): array
    {
        return $user->domains()->get()->toArray();
    }

    protected function getUserDatabases(User $user): array
    {
        return $user->databases()->get()->toArray();
    }

    protected function getUserFtp(User $user): array
    {
        return $user->ftpAccounts()->get()->toArray();
    }

    protected function getUserCron(User $user): array
    {
        return $user->cronJobs()->get()->toArray();
    }

    protected function getUserNotifications(User $user): array
    {
        return $user->notifications()->get()->toArray();
    }

    protected function getDomainInfo(Domain $domain): array
    {
        return [
            'id' => $domain->id,
            'name' => $domain->name,
            'type' => $domain->type,
            'status' => $domain->status,
            'created_at' => $domain->created_at,
            'updated_at' => $domain->updated_at
        ];
    }

    protected function getDomainDatabases(Domain $domain): array
    {
        return $domain->databases()->get()->toArray();
    }

    protected function getDomainFtp(Domain $domain): array
    {
        return $domain->ftpAccounts()->get()->toArray();
    }

    protected function getDomainCron(Domain $domain): array
    {
        return $domain->cronJobs()->get()->toArray();
    }

    protected function getDomainLogs(Domain $domain): array
    {
        return $domain->logs()->get()->toArray();
    }

    protected function getDatabaseInfo(Database $database): array
    {
        return [
            'id' => $database->id,
            'name' => $database->name,
            'type' => $database->type,
            'status' => $database->status,
            'created_at' => $database->created_at,
            'updated_at' => $database->updated_at
        ];
    }

    protected function getDatabaseBackups(Database $database): array
    {
        return $database->backups()->get()->toArray();
    }

    protected function getDatabaseUsers(Database $database): array
    {
        return $database->users()->get()->toArray();
    }

    protected function getDatabaseLogs(Database $database): array
    {
        return $database->logs()->get()->toArray();
    }

    protected function getFtpInfo(FtpAccount $ftp): array
    {
        return [
            'id' => $ftp->id,
            'username' => $ftp->username,
            'status' => $ftp->status,
            'created_at' => $ftp->created_at,
            'updated_at' => $ftp->updated_at
        ];
    }

    protected function getFtpBackups(FtpAccount $ftp): array
    {
        return $ftp->backups()->get()->toArray();
    }

    protected function getFtpLogs(FtpAccount $ftp): array
    {
        return $ftp->logs()->get()->toArray();
    }

    protected function getCronInfo(CronJob $cron): array
    {
        return [
            'id' => $cron->id,
            'name' => $cron->name,
            'command' => $cron->command,
            'schedule' => $cron->schedule,
            'status' => $cron->status,
            'created_at' => $cron->created_at,
            'updated_at' => $cron->updated_at
        ];
    }

    protected function getCronLogs(CronJob $cron): array
    {
        return $cron->logs()->get()->toArray();
    }

    protected function saveReport(array $report, string $type): string
    {
        $reportFile = $this->reportPath . '/' . $type . '_' . date('Y-m-d_H-i-s') . '.json';
        File::put($reportFile, json_encode($report, JSON_PRETTY_PRINT));
        return $reportFile;
    }
} 