<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use App\Models\SSH;
use Illuminate\Support\Facades\Log;

class SSHService
{
    protected $isWindows;
    protected $sshPath;
    protected $authorizedKeysPath;
    protected $backupPath;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->sshPath = $this->isWindows ? 'C:\\laragon\\bin\\openssh' : '/etc/ssh';
        $this->authorizedKeysPath = '/home';
        $this->backupPath = storage_path('backups/ssh');

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    public function generateKeyPair(User $user): array
    {
        $keyName = "id_rsa_{$user->id}";
        $privateKeyPath = storage_path("app/ssh/{$keyName}");
        $publicKeyPath = "{$privateKeyPath}.pub";

        // Generate key pair
        Process::run("ssh-keygen -t rsa -b 4096 -f {$privateKeyPath} -N ''");

        // Read keys
        $privateKey = File::get($privateKeyPath);
        $publicKey = File::get($publicKeyPath);

        // Store keys in user's authorized_keys
        $this->addAuthorizedKey($user, $publicKey);

        // Clean up temporary files
        File::delete([$privateKeyPath, $publicKeyPath]);

        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey
        ];
    }

    public function addAuthorizedKey(User $user, string $publicKey): void
    {
        $userHome = "{$this->authorizedKeysPath}/{$user->username}";
        $sshDir = "{$userHome}/.ssh";
        $authorizedKeysFile = "{$sshDir}/authorized_keys";

        // Create .ssh directory if it doesn't exist
        if (!File::exists($sshDir)) {
            File::makeDirectory($sshDir, 0700, true);
            Process::run("chown {$user->username}:{$user->username} {$sshDir}");
        }

        // Add public key to authorized_keys
        File::append($authorizedKeysFile, "\n{$publicKey}");
        Process::run("chown {$user->username}:{$user->username} {$authorizedKeysFile}");
        Process::run("chmod 600 {$authorizedKeysFile}");
    }

    public function removeAuthorizedKey(User $user, string $publicKey): void
    {
        $authorizedKeysFile = "{$this->authorizedKeysPath}/{$user->username}/.ssh/authorized_keys";

        if (File::exists($authorizedKeysFile)) {
            $content = File::get($authorizedKeysFile);
            $content = str_replace($publicKey, '', $content);
            File::put($authorizedKeysFile, $content);
        }
    }

    public function getAuthorizedKeys(User $user): array
    {
        $authorizedKeysFile = "{$this->authorizedKeysPath}/{$user->username}/.ssh/authorized_keys";
        $keys = [];

        if (File::exists($authorizedKeysFile)) {
            $content = File::get($authorizedKeysFile);
            $keys = array_filter(explode("\n", $content));
        }

        return $keys;
    }

    public function updateSSHConfig(array $settings): void
    {
        $configFile = "{$this->sshPath}/sshd_config";
        
        if (!File::exists($configFile)) {
            throw new \Exception('SSH configuration file not found');
        }

        $content = File::get($configFile);
        foreach ($settings as $key => $value) {
            $content = preg_replace(
                "/^{$key}\s+.*/m",
                "{$key} {$value}",
                $content
            );
        }

        File::put($configFile, $content);
        Process::run('systemctl restart sshd');
    }

    public function getSSHConfig(): array
    {
        $configFile = "{$this->sshPath}/sshd_config";
        $config = [];

        if (File::exists($configFile)) {
            $content = File::get($configFile);
            $lines = explode("\n", $content);

            foreach ($lines as $line) {
                if (empty(trim($line)) || strpos($line, '#') === 0) {
                    continue;
                }

                if (preg_match('/^(\w+)\s+(.+)$/', $line, $matches)) {
                    $config[$matches[1]] = $matches[2];
                }
            }
        }

        return $config;
    }

    public function getActiveSessions(): array
    {
        $output = Process::run('who')->output();
        $sessions = [];

        foreach (explode("\n", $output) as $line) {
            if (empty(trim($line))) {
                continue;
            }

            if (preg_match('/^(\w+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(.+)$/', $line, $matches)) {
                $sessions[] = [
                    'user' => $matches[1],
                    'tty' => $matches[2],
                    'from' => $matches[3],
                    'login_time' => $matches[4],
                    'idle_time' => $matches[5]
                ];
            }
        }

        return $sessions;
    }

    public function terminateSession(string $tty): void
    {
        Process::run("pkill -9 -t {$tty}");
    }

    public function getSSHStatus(): array
    {
        return [
            'service_status' => Process::run('systemctl is-active sshd')->successful() ? 'active' : 'inactive',
            'port' => $this->getSSHConfig()['Port'] ?? '22',
            'active_sessions' => count($this->getActiveSessions()),
            'last_restart' => Process::run('systemctl show sshd -p ActiveEnterTimestamp')->output()
        ];
    }

    public function createSSH(array $data): SSH
    {
        // Create SSH account
        $this->createSSHAccount($data['username'], $data['password']);

        // Create SSH record
        return SSH::create($data);
    }

    public function updateSSH(SSH $ssh, array $data): void
    {
        // Update password if provided
        if (isset($data['password'])) {
            $this->updateSSHPassword($ssh->username, $data['password']);
        }

        // Update public key if provided
        if (isset($data['public_key'])) {
            $this->updateSSHPublicKey($ssh->username, $data['public_key']);
        }

        $ssh->update($data);
    }

    public function deleteSSH(SSH $ssh): void
    {
        // Delete SSH account
        $this->deleteSSHAccount($ssh->username);

        $ssh->delete();
    }

    public function enableSSH(SSH $ssh): void
    {
        $this->enableSSHAccount($ssh->username);
        $ssh->update(['status' => 'active']);
    }

    public function disableSSH(SSH $ssh): void
    {
        $this->disableSSHAccount($ssh->username);
        $ssh->update(['status' => 'inactive']);
    }

    public function backupSSH(SSH $ssh): array
    {
        try {
            $backupFile = $this->backupPath . '/' . $ssh->username . '_' . date('Y-m-d_H-i-s') . '.tar.gz';
            $sshPath = $this->getSSHPath($ssh->username);

            $command = sprintf(
                'tar -czf %s -C %s .',
                $backupFile,
                $sshPath
            );

            Process::run($command);

            if (File::exists($backupFile)) {
                return [
                    'success' => true,
                    'message' => 'SSH backed up successfully',
                    'file' => $backupFile
                ];
            } else {
                throw new \Exception('Backup file was not created');
            }
        } catch (\Exception $e) {
            Log::error('Failed to backup SSH: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to backup SSH: ' . $e->getMessage()
            ];
        }
    }

    public function restoreSSH(string $username, string $backupFile): array
    {
        try {
            if (!File::exists($backupFile)) {
                throw new \Exception('Backup file does not exist');
            }

            $sshPath = $this->getSSHPath($username);
            if (!File::exists($sshPath)) {
                File::makeDirectory($sshPath, 0755, true);
            }

            $command = sprintf(
                'tar -xzf %s -C %s',
                $backupFile,
                $sshPath
            );

            Process::run($command);

            return [
                'success' => true,
                'message' => 'SSH restored successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to restore SSH: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore SSH: ' . $e->getMessage()
            ];
        }
    }

    public function getSSHStats(SSH $ssh): array
    {
        try {
            $sshPath = $this->getSSHPath($ssh->username);
            $size = $this->getDirectorySize($sshPath);
            $files = $this->countFiles($sshPath);

            return [
                'success' => true,
                'size' => $size,
                'files' => $files,
                'last_modified' => File::lastModified($sshPath)
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get SSH stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get SSH stats: ' . $e->getMessage()
            ];
        }
    }

    public function generateSSHKey(string $username): array
    {
        try {
            $sshPath = $this->getSSHPath($username);
            $privateKeyPath = $sshPath . '/id_rsa';
            $publicKeyPath = $sshPath . '/id_rsa.pub';

            $command = sprintf(
                'ssh-keygen -t rsa -b 4096 -f %s -N ""',
                $privateKeyPath
            );

            Process::run($command);

            if (File::exists($privateKeyPath) && File::exists($publicKeyPath)) {
                return [
                    'success' => true,
                    'private_key' => File::get($privateKeyPath),
                    'public_key' => File::get($publicKeyPath)
                ];
            } else {
                throw new \Exception('SSH key files were not created');
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate SSH key: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate SSH key: ' . $e->getMessage()
            ];
        }
    }

    protected function createSSHAccount(string $username, string $password): void
    {
        if ($this->isWindows) {
            $this->createWindowsSSHAccount($username, $password);
        } else {
            $this->createLinuxSSHAccount($username, $password);
        }
    }

    protected function updateSSHPassword(string $username, string $password): void
    {
        if ($this->isWindows) {
            $this->updateWindowsSSHPassword($username, $password);
        } else {
            $this->updateLinuxSSHPassword($username, $password);
        }
    }

    protected function updateSSHPublicKey(string $username, string $publicKey): void
    {
        if ($this->isWindows) {
            $this->updateWindowsSSHPublicKey($username, $publicKey);
        } else {
            $this->updateLinuxSSHPublicKey($username, $publicKey);
        }
    }

    protected function deleteSSHAccount(string $username): void
    {
        if ($this->isWindows) {
            $this->deleteWindowsSSHAccount($username);
        } else {
            $this->deleteLinuxSSHAccount($username);
        }
    }

    protected function enableSSHAccount(string $username): void
    {
        if ($this->isWindows) {
            $this->enableWindowsSSHAccount($username);
        } else {
            $this->enableLinuxSSHAccount($username);
        }
    }

    protected function disableSSHAccount(string $username): void
    {
        if ($this->isWindows) {
            $this->disableWindowsSSHAccount($username);
        } else {
            $this->disableLinuxSSHAccount($username);
        }
    }

    protected function createWindowsSSHAccount(string $username, string $password): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Create the SSH account in the Windows SSH server
        // 2. Set up the necessary directories and permissions
    }

    protected function createLinuxSSHAccount(string $username, string $password): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Create the SSH account in OpenSSH
        // 2. Set up the necessary directories and permissions
    }

    protected function updateWindowsSSHPassword(string $username, string $password): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Update the SSH account password in the Windows SSH server
    }

    protected function updateLinuxSSHPassword(string $username, string $password): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Update the SSH account password in OpenSSH
    }

    protected function updateWindowsSSHPublicKey(string $username, string $publicKey): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Update the SSH account public key in the Windows SSH server
    }

    protected function updateLinuxSSHPublicKey(string $username, string $publicKey): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Update the SSH account public key in OpenSSH
    }

    protected function deleteWindowsSSHAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Delete the SSH account from the Windows SSH server
        // 2. Remove the account directories and files
    }

    protected function deleteLinuxSSHAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Delete the SSH account from OpenSSH
        // 2. Remove the account directories and files
    }

    protected function enableWindowsSSHAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Enable the SSH account in the Windows SSH server
    }

    protected function enableLinuxSSHAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Enable the SSH account in OpenSSH
    }

    protected function disableWindowsSSHAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Disable the SSH account in the Windows SSH server
    }

    protected function disableLinuxSSHAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Disable the SSH account in OpenSSH
    }

    protected function getSSHPath(string $username): string
    {
        if ($this->isWindows) {
            return 'C:\\laragon\\ssh\\' . $username;
        } else {
            return '/home/' . $username . '/.ssh';
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
} 