<?php

namespace App\Services;

use App\Models\FTP;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\FtpAccount;
use App\Models\FtpLog;

class FTPService
{
    protected $isWindows;
    protected $vsftpdPath;
    protected $backupPath;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->vsftpdPath = $this->isWindows ? 'C:\\laragon\\bin\\vsftpd' : '/etc/vsftpd';
        $this->backupPath = storage_path('backups/ftp');

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    public function createFTP(array $data): FTP
    {
        // Create FTP account
        $this->createFTPAccount($data['username'], $data['password'], $data['directory']);

        // Create FTP record
        return FTP::create($data);
    }

    public function updateFTP(FTP $ftp, array $data): void
    {
        // Update password if provided
        if (isset($data['password'])) {
            $this->updateFTPPassword($ftp->username, $data['password']);
        }

        // Update directory if provided
        if (isset($data['directory'])) {
            $this->updateFTPDirectory($ftp->username, $data['directory']);
        }

        $ftp->update($data);
    }

    public function deleteFTP(FTP $ftp): void
    {
        // Delete FTP account
        $this->deleteFTPAccount($ftp->username);

        $ftp->delete();
    }

    public function enableFTP(FTP $ftp): void
    {
        $this->enableFTPAccount($ftp->username);
        $ftp->update(['status' => 'active']);
    }

    public function disableFTP(FTP $ftp): void
    {
        $this->disableFTPAccount($ftp->username);
        $ftp->update(['status' => 'inactive']);
    }

    public function backupFTP(FTP $ftp): array
    {
        try {
            $backupFile = $this->backupPath . '/' . $ftp->username . '_' . date('Y-m-d_H-i-s') . '.tar.gz';
            $ftpPath = $this->getFTPPath($ftp->username);

            $command = sprintf(
                'tar -czf %s -C %s .',
                $backupFile,
                $ftpPath
            );

            Process::run($command);

            if (File::exists($backupFile)) {
                return [
                    'success' => true,
                    'message' => 'FTP backed up successfully',
                    'file' => $backupFile
                ];
            } else {
                throw new \Exception('Backup file was not created');
            }
        } catch (\Exception $e) {
            Log::error('Failed to backup FTP: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to backup FTP: ' . $e->getMessage()
            ];
        }
    }

    public function restoreFTP(string $username, string $backupFile): array
    {
        try {
            if (!File::exists($backupFile)) {
                throw new \Exception('Backup file does not exist');
            }

            $ftpPath = $this->getFTPPath($username);
            if (!File::exists($ftpPath)) {
                File::makeDirectory($ftpPath, 0755, true);
            }

            $command = sprintf(
                'tar -xzf %s -C %s',
                $backupFile,
                $ftpPath
            );

            Process::run($command);

            return [
                'success' => true,
                'message' => 'FTP restored successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to restore FTP: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore FTP: ' . $e->getMessage()
            ];
        }
    }

    public function getFTPStats(FTP $ftp): array
    {
        try {
            $ftpPath = $this->getFTPPath($ftp->username);
            $size = $this->getDirectorySize($ftpPath);
            $files = $this->countFiles($ftpPath);

            return [
                'success' => true,
                'size' => $size,
                'files' => $files,
                'last_modified' => File::lastModified($ftpPath)
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get FTP stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get FTP stats: ' . $e->getMessage()
            ];
        }
    }

    public function createFtpAccount(array $data): array
    {
        try {
            // Create FTP user
            $this->createFtpUser($data['username'], $data['password']);

            // Create FTP directory
            $this->createFtpDirectory($data['username']);

            // Set directory permissions
            $this->setDirectoryPermissions($data['username']);

            // Create FTP account record
            $ftpAccount = FtpAccount::create($data);

            // Reload FTP server
            $this->reloadFtpServer();

            return [
                'success' => true,
                'message' => 'FTP account created successfully',
                'account' => $ftpAccount
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create FTP account: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create FTP account: ' . $e->getMessage()
            ];
        }
    }

    public function updateFtpAccount(FtpAccount $ftpAccount, array $data): array
    {
        try {
            // Update password if provided
            if (isset($data['password'])) {
                $this->updateFtpPassword($ftpAccount->username, $data['password']);
            }

            // Update quota if provided
            if (isset($data['quota'])) {
                $this->updateFtpQuota($ftpAccount->username, $data['quota']);
            }

            // Update FTP account record
            $ftpAccount->update($data);

            // Reload FTP server
            $this->reloadFtpServer();

            return [
                'success' => true,
                'message' => 'FTP account updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update FTP account: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update FTP account: ' . $e->getMessage()
            ];
        }
    }

    public function deleteFtpAccount(FtpAccount $ftpAccount): array
    {
        try {
            // Delete FTP user
            $this->deleteFtpUser($ftpAccount->username);

            // Delete FTP directory
            $this->deleteFtpDirectory($ftpAccount->username);

            // Delete FTP account record
            $ftpAccount->delete();

            // Reload FTP server
            $this->reloadFtpServer();

            return [
                'success' => true,
                'message' => 'FTP account deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete FTP account: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete FTP account: ' . $e->getMessage()
            ];
        }
    }

    public function enableFtpAccount(FtpAccount $ftpAccount): array
    {
        try {
            $this->enableFtpUser($ftpAccount->username);
            $ftpAccount->update(['status' => 'active']);

            // Reload FTP server
            $this->reloadFtpServer();

            return [
                'success' => true,
                'message' => 'FTP account enabled successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to enable FTP account: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to enable FTP account: ' . $e->getMessage()
            ];
        }
    }

    public function disableFtpAccount(FtpAccount $ftpAccount): array
    {
        try {
            $this->disableFtpUser($ftpAccount->username);
            $ftpAccount->update(['status' => 'inactive']);

            // Reload FTP server
            $this->reloadFtpServer();

            return [
                'success' => true,
                'message' => 'FTP account disabled successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to disable FTP account: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to disable FTP account: ' . $e->getMessage()
            ];
        }
    }

    public function backupFtpAccount(FtpAccount $ftpAccount): array
    {
        try {
            $backupFile = $this->backupPath . '/' . $ftpAccount->username . '_' . date('Y-m-d_H-i-s') . '.tar.gz';
            $ftpPath = $this->getFtpPath($ftpAccount->username);

            $command = sprintf(
                'tar -czf %s -C %s .',
                $backupFile,
                $ftpPath
            );

            Process::run($command);

            if (File::exists($backupFile)) {
                return [
                    'success' => true,
                    'message' => 'FTP account backed up successfully',
                    'file' => $backupFile
                ];
            } else {
                throw new \Exception('Backup file was not created');
            }
        } catch (\Exception $e) {
            Log::error('Failed to backup FTP account: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to backup FTP account: ' . $e->getMessage()
            ];
        }
    }

    public function restoreFtpAccount(string $username, string $backupFile): array
    {
        try {
            if (!File::exists($backupFile)) {
                throw new \Exception('Backup file does not exist');
            }

            $ftpPath = $this->getFtpPath($username);
            if (!File::exists($ftpPath)) {
                File::makeDirectory($ftpPath, 0755, true);
            }

            $command = sprintf(
                'tar -xzf %s -C %s',
                $backupFile,
                $ftpPath
            );

            Process::run($command);

            return [
                'success' => true,
                'message' => 'FTP account restored successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to restore FTP account: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore FTP account: ' . $e->getMessage()
            ];
        }
    }

    public function getFtpStats(FtpAccount $ftpAccount): array
    {
        try {
            $ftpPath = $this->getFtpPath($ftpAccount->username);
            $size = $this->getDirectorySize($ftpPath);
            $files = $this->countFiles($ftpPath);

            return [
                'success' => true,
                'stats' => [
                    'size' => $size,
                    'files' => $files,
                    'last_modified' => File::lastModified($ftpPath)
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get FTP stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get FTP stats: ' . $e->getMessage()
            ];
        }
    }

    public function getFtpLogs(FtpAccount $ftpAccount, array $filters = []): array
    {
        try {
            $query = FtpLog::where('ftp_account_id', $ftpAccount->id);

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }

            $logs = $query->orderBy('created_at', 'desc')->paginate(20);

            return [
                'success' => true,
                'logs' => $logs
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get FTP logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get FTP logs: ' . $e->getMessage()
            ];
        }
    }

    protected function createFTPAccount(string $username, string $password, string $directory): void
    {
        if ($this->isWindows) {
            $this->createWindowsFTPAccount($username, $password, $directory);
        } else {
            $this->createLinuxFTPAccount($username, $password, $directory);
        }
    }

    protected function updateFTPPassword(string $username, string $password): void
    {
        if ($this->isWindows) {
            $this->updateWindowsFTPPassword($username, $password);
        } else {
            $this->updateLinuxFTPPassword($username, $password);
        }
    }

    protected function updateFTPDirectory(string $username, string $directory): void
    {
        if ($this->isWindows) {
            $this->updateWindowsFTPDirectory($username, $directory);
        } else {
            $this->updateLinuxFTPDirectory($username, $directory);
        }
    }

    protected function deleteFTPAccount(string $username): void
    {
        if ($this->isWindows) {
            $this->deleteWindowsFTPAccount($username);
        } else {
            $this->deleteLinuxFTPAccount($username);
        }
    }

    protected function enableFTPAccount(string $username): void
    {
        if ($this->isWindows) {
            $this->enableWindowsFTPAccount($username);
        } else {
            $this->enableLinuxFTPAccount($username);
        }
    }

    protected function disableFTPAccount(string $username): void
    {
        if ($this->isWindows) {
            $this->disableWindowsFTPAccount($username);
        } else {
            $this->disableLinuxFTPAccount($username);
        }
    }

    protected function createWindowsFTPAccount(string $username, string $password, string $directory): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Create the FTP account in the Windows FTP server
        // 2. Set up the necessary directories and permissions
    }

    protected function createLinuxFTPAccount(string $username, string $password, string $directory): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Create the FTP account in vsftpd
        // 2. Set up the necessary directories and permissions
    }

    protected function updateWindowsFTPPassword(string $username, string $password): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Update the FTP account password in the Windows FTP server
    }

    protected function updateLinuxFTPPassword(string $username, string $password): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Update the FTP account password in vsftpd
    }

    protected function updateWindowsFTPDirectory(string $username, string $directory): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Update the FTP account directory in the Windows FTP server
    }

    protected function updateLinuxFTPDirectory(string $username, string $directory): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Update the FTP account directory in vsftpd
    }

    protected function deleteWindowsFTPAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Delete the FTP account from the Windows FTP server
        // 2. Remove the account directories and files
    }

    protected function deleteLinuxFTPAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Delete the FTP account from vsftpd
        // 2. Remove the account directories and files
    }

    protected function enableWindowsFTPAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Enable the FTP account in the Windows FTP server
    }

    protected function enableLinuxFTPAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Enable the FTP account in vsftpd
    }

    protected function disableWindowsFTPAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Disable the FTP account in the Windows FTP server
    }

    protected function disableLinuxFTPAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Disable the FTP account in vsftpd
    }

    protected function getFTPPath(string $username): string
    {
        if ($this->isWindows) {
            return 'C:\\laragon\\ftp\\' . $username;
        } else {
            return '/home/' . $username . '/ftp';
        }
    }

    protected function getDirectorySize(string $path): int
    {
        $size = 0;
        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    protected function countFiles(string $path): int
    {
        return count(File::allFiles($path));
    }

    protected function createFtpUser(string $username, string $password): void
    {
        if ($this->isWindows) {
            $this->createWindowsFtpUser($username, $password);
        } else {
            $this->createLinuxFtpUser($username, $password);
        }
    }

    protected function updateFtpPassword(string $username, string $password): void
    {
        if ($this->isWindows) {
            $this->updateWindowsFtpPassword($username, $password);
        } else {
            $this->updateLinuxFtpPassword($username, $password);
        }
    }

    protected function deleteFtpUser(string $username): void
    {
        if ($this->isWindows) {
            $this->deleteWindowsFtpUser($username);
        } else {
            $this->deleteLinuxFtpUser($username);
        }
    }

    protected function enableFtpUser(string $username): void
    {
        if ($this->isWindows) {
            $this->enableWindowsFtpUser($username);
        } else {
            $this->enableLinuxFtpUser($username);
        }
    }

    protected function disableFtpUser(string $username): void
    {
        if ($this->isWindows) {
            $this->disableWindowsFtpUser($username);
        } else {
            $this->disableLinuxFtpUser($username);
        }
    }

    protected function createFtpDirectory(string $username): void
    {
        $path = $this->getFtpPath($username);
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    protected function deleteFtpDirectory(string $username): void
    {
        $path = $this->getFtpPath($username);
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }

    protected function setDirectoryPermissions(string $username): void
    {
        $path = $this->getFtpPath($username);
        if ($this->isWindows) {
            $this->setWindowsDirectoryPermissions($path);
        } else {
            $this->setLinuxDirectoryPermissions($path);
        }
    }

    protected function updateFtpQuota(string $username, int $quota): void
    {
        if ($this->isWindows) {
            $this->updateWindowsFtpQuota($username, $quota);
        } else {
            $this->updateLinuxFtpQuota($username, $quota);
        }
    }

    protected function reloadFtpServer(): void
    {
        if ($this->isWindows) {
            $this->reloadWindowsFtpServer();
        } else {
            $this->reloadLinuxFtpServer();
        }
    }

    protected function getFtpPath(string $username): string
    {
        return $this->isWindows
            ? 'C:\\laragon\\www\\ftp\\' . $username
            : '/var/www/ftp/' . $username;
    }

    protected function getDirectorySize(string $path): int
    {
        $size = 0;
        $files = File::allFiles($path);
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    protected function countFiles(string $path): int
    {
        return count(File::allFiles($path));
    }

    protected function createWindowsFtpUser(string $username, string $password): void
    {
        $command = sprintf(
            'net user %s %s /add',
            $username,
            $password
        );

        Process::run($command);
    }

    protected function createLinuxFtpUser(string $username, string $password): void
    {
        $command = sprintf(
            'useradd -m -s /bin/false %s && echo "%s:%s" | chpasswd',
            $username,
            $username,
            $password
        );

        Process::run($command);
    }

    protected function updateWindowsFtpPassword(string $username, string $password): void
    {
        $command = sprintf(
            'net user %s %s',
            $username,
            $password
        );

        Process::run($command);
    }

    protected function updateLinuxFtpPassword(string $username, string $password): void
    {
        $command = sprintf(
            'echo "%s:%s" | chpasswd',
            $username,
            $password
        );

        Process::run($command);
    }

    protected function deleteWindowsFtpUser(string $username): void
    {
        $command = sprintf(
            'net user %s /delete',
            $username
        );

        Process::run($command);
    }

    protected function deleteLinuxFtpUser(string $username): void
    {
        $command = sprintf(
            'userdel -r %s',
            $username
        );

        Process::run($command);
    }

    protected function enableWindowsFtpUser(string $username): void
    {
        $command = sprintf(
            'net user %s /active:yes',
            $username
        );

        Process::run($command);
    }

    protected function enableLinuxFtpUser(string $username): void
    {
        $command = sprintf(
            'usermod -U %s',
            $username
        );

        Process::run($command);
    }

    protected function disableWindowsFtpUser(string $username): void
    {
        $command = sprintf(
            'net user %s /active:no',
            $username
        );

        Process::run($command);
    }

    protected function disableLinuxFtpUser(string $username): void
    {
        $command = sprintf(
            'usermod -L %s',
            $username
        );

        Process::run($command);
    }

    protected function setWindowsDirectoryPermissions(string $path): void
    {
        $command = sprintf(
            'icacls %s /grant %s:(OI)(CI)F /T',
            $path,
            $this->getCurrentUser()
        );

        Process::run($command);
    }

    protected function setLinuxDirectoryPermissions(string $path): void
    {
        $command = sprintf(
            'chown -R %s:%s %s && chmod -R 755 %s',
            $this->getCurrentUser(),
            $this->getCurrentUser(),
            $path,
            $path
        );

        Process::run($command);
    }

    protected function updateWindowsFtpQuota(string $username, int $quota): void
    {
        // Windows FTP quota management is typically handled by the FTP server software
        // This is a placeholder for the actual implementation
    }

    protected function updateLinuxFtpQuota(string $username, int $quota): void
    {
        $command = sprintf(
            'setquota -u %s %d %d 0 0 /',
            $username,
            $quota,
            $quota
        );

        Process::run($command);
    }

    protected function reloadWindowsFtpServer(): void
    {
        Process::run('net stop ftpsvc');
        Process::run('net start ftpsvc');
    }

    protected function reloadLinuxFtpServer(): void
    {
        Process::run('systemctl restart vsftpd');
    }

    protected function getCurrentUser(): string
    {
        return $this->isWindows ? get_current_user() : get_current_user();
    }
} 