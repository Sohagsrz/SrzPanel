<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Models\LoginAttempt;
use App\Models\SecurityLog;
use Carbon\Carbon;

class SecurityService
{
    protected $google2fa;
    protected $maxLoginAttempts;
    protected $lockoutTime;
    protected $passwordHistory;
    protected $isWindows;
    protected $securityPath;
    protected $backupPath;
    protected $passwordExpiryDays;
    protected $sessionTimeout;
    protected $configPath;
    protected $logPath;
    protected $tempPath;
    protected $maxLogs;
    protected $autoScan;
    protected $notifyOnThreat;
    protected $scanTypes;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
        $this->maxLoginAttempts = 5;
        $this->lockoutTime = 15; // minutes
        $this->passwordHistory = 5; // number of previous passwords to remember
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->securityPath = $this->isWindows ? 'C:\\laragon\\security' : '/etc/security';
        $this->backupPath = storage_path('backups/security');
        $this->configPath = $this->isWindows ? 'C:\\laragon\\etc' : '/etc';
        $this->logPath = $this->isWindows ? 'C:\\laragon\\logs' : '/var/log';
        $this->tempPath = storage_path('temp/security');
        $this->maxLogs = config('security.max_logs', 5);
        $this->autoScan = config('security.auto_scan', true);
        $this->notifyOnThreat = config('security.notify_on_threat', true);
        $this->scanTypes = [
            'full' => [
                'name' => 'Full Scan',
                'description' => 'Scan all system files and directories'
            ],
            'quick' => [
                'name' => 'Quick Scan',
                'description' => 'Scan only critical system files'
            ],
            'custom' => [
                'name' => 'Custom Scan',
                'description' => 'Scan specific files and directories'
            ]
        ];

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }

        if (!File::exists($this->tempPath)) {
            File::makeDirectory($this->tempPath, 0755, true);
        }

        $this->passwordExpiryDays = config('security.password_expiry_days', 90);
        $this->sessionTimeout = config('security.session_timeout', 120);
    }

    public function enable2FA(User $user): array
    {
        // Generate secret key
        $secretKey = $this->google2fa->generateSecretKey();

        // Generate QR code URL
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secretKey
        );

        // Store secret key temporarily
        Cache::put("2fa_secret_{$user->id}", $secretKey, now()->addMinutes(10));

        return [
            'secret_key' => $secretKey,
            'qr_code_url' => $qrCodeUrl
        ];
    }

    public function verify2FA(User $user, string $code): bool
    {
        $secretKey = Cache::get("2fa_secret_{$user->id}");
        if (!$secretKey) {
            return false;
        }

        return $this->google2fa->verifyKey($secretKey, $code);
    }

    public function confirm2FA(User $user, string $code): bool
    {
        $secretKey = Cache::get("2fa_secret_{$user->id}");
        if (!$secretKey) {
            return false;
        }

        if ($this->google2fa->verifyKey($secretKey, $code)) {
            $user->update([
                'two_factor_secret' => $secretKey,
                'two_factor_enabled' => true
            ]);
            Cache::forget("2fa_secret_{$user->id}");
            return true;
        }

        return false;
    }

    public function disable2FA(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false
        ]);
    }

    public function checkLoginAttempts(User $user): array
    {
        try {
            $attempts = LoginAttempt::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subMinutes($this->lockoutTime))
                ->count();

            if ($attempts >= $this->maxLoginAttempts) {
                return [
                    'success' => true,
                    'locked' => true,
                    'message' => 'Account is locked. Please try again later.'
                ];
            }

            return [
                'success' => true,
                'locked' => false,
                'attempts' => $attempts,
                'remaining' => $this->maxLoginAttempts - $attempts
            ];
        } catch (\Exception $e) {
            Log::error('Failed to check login attempts: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to check login attempts: ' . $e->getMessage()
            ];
        }
    }

    public function recordLoginAttempt(User $user, bool $success): array
    {
        try {
            LoginAttempt::create([
                'user_id' => $user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'success' => $success
            ]);

            if (!$success) {
                $attempts = LoginAttempt::where('user_id', $user->id)
                    ->where('created_at', '>=', now()->subMinutes($this->lockoutTime))
                    ->count();

                if ($attempts >= $this->maxLoginAttempts) {
                    $user->update(['status' => 'locked']);
                }
            }

            return [
                'success' => true,
                'message' => 'Login attempt recorded successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to record login attempt: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to record login attempt: ' . $e->getMessage()
            ];
        }
    }

    public function checkPasswordExpiry(User $user): array
    {
        try {
            if (!$user->password_changed_at) {
                return [
                    'success' => true,
                    'expired' => true,
                    'message' => 'Password has never been changed'
                ];
            }

            $daysSinceChange = now()->diffInDays($user->password_changed_at);

            if ($daysSinceChange >= $this->passwordExpiryDays) {
                return [
                    'success' => true,
                    'expired' => true,
                    'message' => 'Password has expired'
                ];
            }

            return [
                'success' => true,
                'expired' => false,
                'days_remaining' => $this->passwordExpiryDays - $daysSinceChange
            ];
        } catch (\Exception $e) {
            Log::error('Failed to check password expiry: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to check password expiry: ' . $e->getMessage()
            ];
        }
    }

    public function checkSessionTimeout(User $user): array
    {
        try {
            $lastActivity = Cache::get('user.last_activity.' . $user->id);

            if (!$lastActivity) {
                return [
                    'success' => true,
                    'expired' => true,
                    'message' => 'Session has expired'
                ];
            }

            $minutesSinceLastActivity = now()->diffInMinutes($lastActivity);

            if ($minutesSinceLastActivity >= $this->sessionTimeout) {
                return [
                    'success' => true,
                    'expired' => true,
                    'message' => 'Session has expired'
                ];
            }

            return [
                'success' => true,
                'expired' => false,
                'minutes_remaining' => $this->sessionTimeout - $minutesSinceLastActivity
            ];
        } catch (\Exception $e) {
            Log::error('Failed to check session timeout: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to check session timeout: ' . $e->getMessage()
            ];
        }
    }

    public function updateLastActivity(User $user): array
    {
        try {
            Cache::put('user.last_activity.' . $user->id, now(), now()->addMinutes($this->sessionTimeout));

            return [
                'success' => true,
                'message' => 'Last activity updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update last activity: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update last activity: ' . $e->getMessage()
            ];
        }
    }

    public function validatePassword(string $password): array
    {
        try {
            $errors = [];

            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters long';
            }

            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = 'Password must contain at least one uppercase letter';
            }

            if (!preg_match('/[a-z]/', $password)) {
                $errors[] = 'Password must contain at least one lowercase letter';
            }

            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = 'Password must contain at least one number';
            }

            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                $errors[] = 'Password must contain at least one special character';
            }

            return [
                'success' => true,
                'valid' => empty($errors),
                'errors' => $errors
            ];
        } catch (\Exception $e) {
            Log::error('Failed to validate password: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to validate password: ' . $e->getMessage()
            ];
        }
    }

    public function checkPasswordHistory(User $user, string $newPassword): bool
    {
        $history = $user->passwordHistory()
            ->orderBy('created_at', 'desc')
            ->take($this->passwordHistory)
            ->get();

        foreach ($history as $record) {
            if (Hash::check($newPassword, $record->password)) {
                return false;
            }
        }

        return true;
    }

    public function addToPasswordHistory(User $user, string $password): void
    {
        $user->passwordHistory()->create([
            'password' => Hash::make($password)
        ]);
    }

    public function generateSecurePassword(): string
    {
        $length = 16;
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
        $password = '';

        // Ensure at least one of each required character type
        $password .= chr(rand(65, 90)); // Uppercase
        $password .= chr(rand(97, 122)); // Lowercase
        $password .= chr(rand(48, 57)); // Number
        $password .= chr(rand(33, 47)); // Special

        // Fill the rest randomly
        for ($i = 4; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }

        // Shuffle the password
        return str_shuffle($password);
    }

    public function getSecurityScore(User $user): int
    {
        $score = 0;

        // 2FA enabled
        if ($user->two_factor_enabled) {
            $score += 30;
        }

        // Strong password
        $passwordErrors = $this->validatePassword($user->password);
        if (empty($passwordErrors)) {
            $score += 20;
        }

        // Recent password change
        if ($user->password_changed_at && $user->password_changed_at->diffInDays(now()) < 90) {
            $score += 20;
        }

        // Email verified
        if ($user->email_verified_at) {
            $score += 15;
        }

        // Last login
        if ($user->last_login_at && $user->last_login_at->diffInDays(now()) < 30) {
            $score += 15;
        }

        return $score;
    }

    public function getSecurityRecommendations(User $user): array
    {
        $recommendations = [];

        if (!$user->two_factor_enabled) {
            $recommendations[] = 'Enable two-factor authentication';
        }

        $passwordErrors = $this->validatePassword($user->password);
        if (!empty($passwordErrors)) {
            $recommendations[] = 'Strengthen your password: ' . implode(', ', $passwordErrors);
        }

        if (!$user->password_changed_at || $user->password_changed_at->diffInDays(now()) >= 90) {
            $recommendations[] = 'Change your password (it\'s been more than 90 days)';
        }

        if (!$user->email_verified_at) {
            $recommendations[] = 'Verify your email address';
        }

        return $recommendations;
    }

    public function getSecurityLogs(array $filters = []): array
    {
        try {
            $query = SecurityLog::query();

            if (isset($filters['event'])) {
                $query->where('event', $filters['event']);
            }

            if (isset($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
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
            Log::error('Failed to get security logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get security logs: ' . $e->getMessage()
            ];
        }
    }

    public function logSecurityEvent(string $event, array $data = []): array
    {
        try {
            SecurityLog::create([
                'event' => $event,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'data' => $data
            ]);

            return [
                'success' => true,
                'message' => 'Security event logged successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to log security event: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to log security event: ' . $e->getMessage()
            ];
        }
    }

    public function getSecurityStatus(): array
    {
        try {
            $status = [
                'firewall' => $this->getFirewallStatus(),
                'ssl' => $this->getSSLStatus(),
                'updates' => $this->getUpdateStatus(),
                'antivirus' => $this->getAntivirusStatus(),
                'backups' => $this->getBackupStatus(),
                'logs' => $this->getLogStatus()
            ];

            return [
                'success' => true,
                'status' => $status
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get security status: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get security status: ' . $e->getMessage()
            ];
        }
    }

    public function getFirewallStatus(): array
    {
        if ($this->isWindows) {
            $command = 'netsh advfirewall show allprofiles state';
            $output = Process::run($command)->output();
            return $this->parseWindowsFirewallStatus($output);
        } else {
            $command = 'ufw status';
            $output = Process::run($command)->output();
            return $this->parseLinuxFirewallStatus($output);
        }
    }

    public function getSSLStatus(): array
    {
        $status = [
            'enabled' => false,
            'certificates' => [],
            'expiry' => null
        ];

        if ($this->isWindows) {
            $command = 'certutil -store -user MY';
            $output = Process::run($command)->output();
            $status = $this->parseWindowsSSLStatus($output);
        } else {
            $command = 'openssl x509 -in /etc/ssl/certs/ca-certificates.crt -text -noout';
            $output = Process::run($command)->output();
            $status = $this->parseLinuxSSLStatus($output);
        }

        return $status;
    }

    public function getUpdateStatus(): array
    {
        if ($this->isWindows) {
            $command = 'wmic qfe list brief';
            $output = Process::run($command)->output();
            return $this->parseWindowsUpdateStatus($output);
        } else {
            $command = 'apt list --upgradable';
            $output = Process::run($command)->output();
            return $this->parseLinuxUpdateStatus($output);
        }
    }

    public function getAntivirusStatus(): array
    {
        if ($this->isWindows) {
            $command = 'wmic /namespace:\\\\root\\securitycenter2 path antivirusproduct get displayname, productstate';
            $output = Process::run($command)->output();
            return $this->parseWindowsAntivirusStatus($output);
        } else {
            $command = 'clamd --version';
            $output = Process::run($command)->output();
            return $this->parseLinuxAntivirusStatus($output);
        }
    }

    public function getBackupStatus(): array
    {
        $status = [
            'last_backup' => null,
            'backup_size' => 0,
            'backup_count' => 0
        ];

        $backupPath = storage_path('backups');
        if (File::exists($backupPath)) {
            $files = File::files($backupPath);
            $status['backup_count'] = count($files);
            $status['backup_size'] = $this->getDirectorySize($backupPath);

            if ($status['backup_count'] > 0) {
                $lastFile = end($files);
                $status['last_backup'] = Carbon::createFromTimestamp($lastFile->getMTime())->toIso8601String();
            }
        }

        return $status;
    }

    public function getLogStatus(): array
    {
        $status = [
            'log_size' => 0,
            'log_count' => 0,
            'last_rotation' => null
        ];

        $logPath = storage_path('logs');
        if (File::exists($logPath)) {
            $files = File::files($logPath);
            $status['log_count'] = count($files);
            $status['log_size'] = $this->getDirectorySize($logPath);

            if ($status['log_count'] > 0) {
                $lastFile = end($files);
                $status['last_rotation'] = Carbon::createFromTimestamp($lastFile->getMTime())->toIso8601String();
            }
        }

        return $status;
    }

    public function updateFirewall(array $rules): array
    {
        try {
            if ($this->isWindows) {
                $this->updateWindowsFirewall($rules);
            } else {
                $this->updateLinuxFirewall($rules);
            }

            return [
                'success' => true,
                'message' => 'Firewall updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update firewall: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update firewall: ' . $e->getMessage()
            ];
        }
    }

    public function updateSSL(array $config): array
    {
        try {
            $this->updateSSLConfig($config);

            return [
                'success' => true,
                'message' => 'SSL configuration updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update SSL configuration: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update SSL configuration: ' . $e->getMessage()
            ];
        }
    }

    public function checkUpdates(): array
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
            Log::error('Failed to check updates: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to check updates: ' . $e->getMessage()
            ];
        }
    }

    public function installUpdates(): array
    {
        try {
            if ($this->isWindows) {
                $this->installWindowsUpdates();
            } else {
                $this->installLinuxUpdates();
            }

            return [
                'success' => true,
                'message' => 'Updates installed successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to install updates: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to install updates: ' . $e->getMessage()
            ];
        }
    }

    public function scanSystem(string $type = 'full', array $options = []): array
    {
        try {
            if (!isset($this->scanTypes[$type])) {
                throw new \Exception("Invalid scan type: {$type}");
            }

            $result = [];
            switch ($type) {
                case 'full':
                    $result = $this->performFullScan($options);
                    break;
                case 'quick':
                    $result = $this->performQuickScan($options);
                    break;
                case 'custom':
                    $result = $this->performCustomScan($options);
                    break;
            }

            if ($this->notifyOnThreat && !empty($result['threats'])) {
                $this->notifyThreatsFound($type, $result);
            }

            return [
                'success' => true,
                'message' => "{$this->scanTypes[$type]['name']} completed successfully",
                'result' => $result
            ];
        } catch (\Exception $e) {
            Log::error('Failed to scan system: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to scan system: ' . $e->getMessage()
            ];
        }
    }

    public function getScanHistory(): array
    {
        try {
            $history = [];
            $files = File::files($this->securityPath);

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
            Log::error('Failed to get scan history: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get scan history: ' . $e->getMessage()
            ];
        }
    }

    public function getThreatDetails(string $threatId): array
    {
        try {
            $threatFile = $this->securityPath . '/' . $threatId . '.json';
            if (!File::exists($threatFile)) {
                throw new \Exception("Threat not found: {$threatId}");
            }

            $data = json_decode(File::get($threatFile), true);
            if (!$data) {
                throw new \Exception("Invalid threat data");
            }

            return [
                'success' => true,
                'threat' => $data
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get threat details: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get threat details: ' . $e->getMessage()
            ];
        }
    }

    public function getSecurityStats(): array
    {
        try {
            $stats = [
                'total_scans' => 0,
                'total_threats' => 0,
                'last_scan' => null,
                'scan_types' => []
            ];

            foreach ($this->scanTypes as $type => $info) {
                $stats['scan_types'][$type] = [
                    'count' => 0,
                    'threats' => 0
                ];
            }

            $files = File::files($this->securityPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'json') {
                    $data = json_decode(File::get($file), true);
                    if ($data) {
                        $stats['total_scans']++;
                        $stats['total_threats'] += count($data['threats'] ?? []);
                        $stats['scan_types'][$data['type']]['count']++;
                        $stats['scan_types'][$data['type']]['threats'] += count($data['threats'] ?? []);

                        if (!$stats['last_scan'] || $data['date'] > $stats['last_scan']) {
                            $stats['last_scan'] = $data['date'];
                        }
                    }
                }
            }

            return [
                'success' => true,
                'stats' => $stats
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get security stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get security stats: ' . $e->getMessage()
            ];
        }
    }

    protected function performFullScan(array $options = []): array
    {
        $result = [
            'type' => 'full',
            'date' => now()->toDateTimeString(),
            'threats' => []
        ];

        // Scan system files
        $systemFiles = $this->scanSystemFiles();
        $result['threats'] = array_merge($result['threats'], $systemFiles);

        // Scan user files
        $userFiles = $this->scanUserFiles();
        $result['threats'] = array_merge($result['threats'], $userFiles);

        // Scan network
        $networkThreats = $this->scanNetwork();
        $result['threats'] = array_merge($result['threats'], $networkThreats);

        // Save scan results
        $this->saveScanResults($result);

        return $result;
    }

    protected function performQuickScan(array $options = []): array
    {
        $result = [
            'type' => 'quick',
            'date' => now()->toDateTimeString(),
            'threats' => []
        ];

        // Scan critical system files
        $criticalFiles = $this->scanCriticalFiles();
        $result['threats'] = array_merge($result['threats'], $criticalFiles);

        // Scan active processes
        $processThreats = $this->scanActiveProcesses();
        $result['threats'] = array_merge($result['threats'], $processThreats);

        // Save scan results
        $this->saveScanResults($result);

        return $result;
    }

    protected function performCustomScan(array $options = []): array
    {
        $result = [
            'type' => 'custom',
            'date' => now()->toDateTimeString(),
            'threats' => []
        ];

        if (isset($options['paths'])) {
            foreach ($options['paths'] as $path) {
                $threats = $this->scanPath($path);
                $result['threats'] = array_merge($result['threats'], $threats);
            }
        }

        // Save scan results
        $this->saveScanResults($result);

        return $result;
    }

    protected function scanSystemFiles(): array
    {
        $threats = [];
        $systemPaths = [
            base_path(),
            storage_path(),
            public_path()
        ];

        foreach ($systemPaths as $path) {
            $threats = array_merge($threats, $this->scanPath($path));
        }

        return $threats;
    }

    protected function scanUserFiles(): array
    {
        $threats = [];
        $userPaths = [
            storage_path('app/public'),
            public_path('uploads')
        ];

        foreach ($userPaths as $path) {
            if (File::exists($path)) {
                $threats = array_merge($threats, $this->scanPath($path));
            }
        }

        return $threats;
    }

    protected function scanCriticalFiles(): array
    {
        $threats = [];
        $criticalPaths = [
            base_path('.env'),
            base_path('config'),
            base_path('bootstrap/cache')
        ];

        foreach ($criticalPaths as $path) {
            if (File::exists($path)) {
                $threats = array_merge($threats, $this->scanPath($path));
            }
        }

        return $threats;
    }

    protected function scanActiveProcesses(): array
    {
        $threats = [];
        if ($this->isWindows) {
            $command = 'tasklist /v /fo csv';
        } else {
            $command = 'ps aux';
        }

        $output = Process::run($command)->output();
        $processes = $this->parseProcessList($output);

        foreach ($processes as $process) {
            if ($this->isSuspiciousProcess($process)) {
                $threats[] = [
                    'type' => 'suspicious_process',
                    'path' => $process['path'],
                    'pid' => $process['pid'],
                    'severity' => 'high',
                    'description' => 'Suspicious process detected'
                ];
            }
        }

        return $threats;
    }

    protected function scanNetwork(): array
    {
        $threats = [];
        if ($this->isWindows) {
            $command = 'netstat -an';
        } else {
            $command = 'netstat -tuln';
        }

        $output = Process::run($command)->output();
        $connections = $this->parseNetworkConnections($output);

        foreach ($connections as $connection) {
            if ($this->isSuspiciousConnection($connection)) {
                $threats[] = [
                    'type' => 'suspicious_connection',
                    'local' => $connection['local'],
                    'remote' => $connection['remote'],
                    'state' => $connection['state'],
                    'severity' => 'high',
                    'description' => 'Suspicious network connection detected'
                ];
            }
        }

        return $threats;
    }

    protected function scanPath(string $path): array
    {
        $threats = [];
        if (File::isFile($path)) {
            if ($this->isSuspiciousFile($path)) {
                $threats[] = [
                    'type' => 'suspicious_file',
                    'path' => $path,
                    'severity' => 'high',
                    'description' => 'Suspicious file detected'
                ];
            }
        } else {
            $files = File::allFiles($path);
            foreach ($files as $file) {
                if ($this->isSuspiciousFile($file->getRealPath())) {
                    $threats[] = [
                        'type' => 'suspicious_file',
                        'path' => $file->getRealPath(),
                        'severity' => 'high',
                        'description' => 'Suspicious file detected'
                    ];
                }
            }
        }

        return $threats;
    }

    protected function isSuspiciousFile(string $path): bool
    {
        // Check file extension
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $suspiciousExtensions = ['exe', 'dll', 'bat', 'cmd', 'vbs', 'js', 'php', 'sh'];
        if (in_array($extension, $suspiciousExtensions)) {
            return true;
        }

        // Check file content
        $content = File::get($path);
        $suspiciousPatterns = [
            'eval\s*\(',
            'base64_decode\s*\(',
            'system\s*\(',
            'exec\s*\(',
            'shell_exec\s*\(',
            'passthru\s*\('
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $content)) {
                return true;
            }
        }

        return false;
    }

    protected function isSuspiciousProcess(array $process): bool
    {
        // Check process name
        $suspiciousProcesses = [
            'nc.exe',
            'netcat.exe',
            'nmap.exe',
            'wireshark.exe',
            'tcpdump.exe'
        ];

        if (in_array(strtolower($process['name']), $suspiciousProcesses)) {
            return true;
        }

        // Check process path
        if ($this->isSuspiciousPath($process['path'])) {
            return true;
        }

        return false;
    }

    protected function isSuspiciousConnection(array $connection): bool
    {
        // Check for known malicious IPs
        $maliciousIPs = [
            '192.168.1.100',
            '10.0.0.1'
        ];

        if (in_array($connection['remote'], $maliciousIPs)) {
            return true;
        }

        // Check for suspicious ports
        $suspiciousPorts = [
            22,   // SSH
            23,   // Telnet
            445,  // SMB
            3389  // RDP
        ];

        if (in_array($connection['remote_port'], $suspiciousPorts)) {
            return true;
        }

        return false;
    }

    protected function isSuspiciousPath(string $path): bool
    {
        $suspiciousPaths = [
            'C:\\Windows\\Temp',
            'C:\\Users\\Public',
            '/tmp',
            '/var/tmp'
        ];

        foreach ($suspiciousPaths as $suspiciousPath) {
            if (stripos($path, $suspiciousPath) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function parseProcessList(string $output): array
    {
        $processes = [];
        $lines = explode("\n", trim($output));

        foreach ($lines as $line) {
            if ($this->isWindows) {
                $parts = str_getcsv($line);
                if (count($parts) >= 2) {
                    $processes[] = [
                        'name' => $parts[0],
                        'pid' => $parts[1],
                        'path' => $parts[7] ?? ''
                    ];
                }
            } else {
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) >= 2) {
                    $processes[] = [
                        'name' => $parts[10] ?? '',
                        'pid' => $parts[1],
                        'path' => $parts[10] ?? ''
                    ];
                }
            }
        }

        return $processes;
    }

    protected function parseNetworkConnections(string $output): array
    {
        $connections = [];
        $lines = explode("\n", trim($output));

        foreach ($lines as $line) {
            if ($this->isWindows) {
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) >= 4) {
                    $connections[] = [
                        'local' => $parts[1],
                        'remote' => $parts[2],
                        'state' => $parts[3],
                        'local_port' => $this->extractPort($parts[1]),
                        'remote_port' => $this->extractPort($parts[2])
                    ];
                }
            } else {
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) >= 4) {
                    $connections[] = [
                        'local' => $parts[3],
                        'remote' => $parts[4],
                        'state' => $parts[5],
                        'local_port' => $this->extractPort($parts[3]),
                        'remote_port' => $this->extractPort($parts[4])
                    ];
                }
            }
        }

        return $connections;
    }

    protected function extractPort(string $address): int
    {
        $parts = explode(':', $address);
        return (int) end($parts);
    }

    protected function saveScanResults(array $result): void
    {
        $filename = date('Y-m-d_H-i-s') . '_' . $result['type'] . '.json';
        File::put(
            $this->securityPath . '/' . $filename,
            json_encode($result, JSON_PRETTY_PRINT)
        );
    }

    protected function notifyThreatsFound(string $type, array $result): void
    {
        // Implement notification logic here
        // This could be email, SMS, or any other notification method
    }

    protected function parseWindowsFirewallStatus(string $output): array
    {
        $status = [
            'enabled' => false,
            'profiles' => []
        ];

        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, 'State') !== false) {
                $status['enabled'] = strpos($line, 'ON') !== false;
            }
            if (strpos($line, 'Profile') !== false) {
                $status['profiles'][] = trim($line);
            }
        }

        return $status;
    }

    protected function parseLinuxFirewallStatus(string $output): array
    {
        $status = [
            'enabled' => false,
            'rules' => []
        ];

        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, 'Status') !== false) {
                $status['enabled'] = strpos($line, 'active') !== false;
            }
            if (strpos($line, 'To') !== false) {
                $status['rules'][] = trim($line);
            }
        }

        return $status;
    }

    protected function parseWindowsSSLStatus(string $output): array
    {
        $status = [
            'enabled' => false,
            'certificates' => [],
            'expiry' => null
        ];

        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, 'Certificate') !== false) {
                $status['enabled'] = true;
                $status['certificates'][] = trim($line);
            }
            if (strpos($line, 'NotAfter') !== false) {
                $status['expiry'] = trim($line);
            }
        }

        return $status;
    }

    protected function parseLinuxSSLStatus(string $output): array
    {
        $status = [
            'enabled' => false,
            'certificates' => [],
            'expiry' => null
        ];

        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, 'Certificate') !== false) {
                $status['enabled'] = true;
                $status['certificates'][] = trim($line);
            }
            if (strpos($line, 'Not After') !== false) {
                $status['expiry'] = trim($line);
            }
        }

        return $status;
    }

    protected function parseWindowsUpdateStatus(string $output): array
    {
        $updates = [
            'available' => false,
            'updates' => []
        ];

        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, 'HotFixID') === false) {
                $updates['updates'][] = trim($line);
            }
        }

        $updates['available'] = count($updates['updates']) > 0;

        return $updates;
    }

    protected function parseLinuxUpdateStatus(string $output): array
    {
        $updates = [
            'available' => false,
            'updates' => []
        ];

        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, 'Listing') === false) {
                $updates['updates'][] = trim($line);
            }
        }

        $updates['available'] = count($updates['updates']) > 0;

        return $updates;
    }

    protected function parseWindowsAntivirusStatus(string $output): array
    {
        $status = [
            'enabled' => false,
            'products' => []
        ];

        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, 'displayname') === false) {
                $status['products'][] = trim($line);
            }
        }

        $status['enabled'] = count($status['products']) > 0;

        return $status;
    }

    protected function parseLinuxAntivirusStatus(string $output): array
    {
        $status = [
            'enabled' => false,
            'version' => null
        ];

        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, 'ClamAV') !== false) {
                $status['enabled'] = true;
                $status['version'] = trim($line);
            }
        }

        return $status;
    }

    protected function parseWindowsFirewallLogs(string $output): array
    {
        $logs = [];
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, 'Firewall') !== false) {
                $logs[] = trim($line);
            }
        }
        return $logs;
    }

    protected function parseLinuxFirewallLogs(string $output): array
    {
        $logs = [];
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, 'UFW') !== false) {
                $logs[] = trim($line);
            }
        }
        return $logs;
    }

    protected function parseWindowsAntivirusLogs(string $output): array
    {
        $logs = [];
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, 'Antivirus') !== false) {
                $logs[] = trim($line);
            }
        }
        return $logs;
    }

    protected function parseLinuxAntivirusLogs(string $output): array
    {
        $logs = [];
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, 'ClamAV') !== false) {
                $logs[] = trim($line);
            }
        }
        return $logs;
    }

    protected function parseWindowsSystemLogs(string $output): array
    {
        $logs = [];
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, 'System') !== false) {
                $logs[] = trim($line);
            }
        }
        return $logs;
    }

    protected function parseLinuxSystemLogs(string $output): array
    {
        $logs = [];
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (strpos($line, 'kernel') !== false) {
                $logs[] = trim($line);
            }
        }
        return $logs;
    }

    protected function updateWindowsFirewall(array $rules): void
    {
        foreach ($rules as $rule) {
            $command = sprintf(
                'netsh advfirewall firewall add rule name="%s" dir=%s action=%s protocol=%s localport=%s',
                $rule['name'],
                $rule['direction'],
                $rule['action'],
                $rule['protocol'],
                $rule['port']
            );

            Process::run($command);
        }
    }

    protected function updateLinuxFirewall(array $rules): void
    {
        foreach ($rules as $rule) {
            $command = sprintf(
                'ufw allow %s %s',
                $rule['port'],
                $rule['protocol']
            );

            Process::run($command);
        }
    }

    protected function updateSSLConfig(array $config): void
    {
        if ($this->isWindows) {
            $this->updateWindowsSSLConfig($config);
        } else {
            $this->updateLinuxSSLConfig($config);
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
        $command = 'apt list --upgradable';
        $output = Process::run($command)->output();
        return $this->parseLinuxUpdates($output);
    }

    protected function installWindowsUpdates(): void
    {
        $command = 'wuauclt /detectnow /updatenow';
        Process::run($command);
    }

    protected function installLinuxUpdates(): void
    {
        $commands = [
            'apt update',
            'apt upgrade -y'
        ];

        foreach ($commands as $command) {
            Process::run($command);
        }
    }

    protected function scanWindowsSystem(): array
    {
        $scan = [
            'antivirus' => $this->scanWindowsAntivirus(),
            'firewall' => $this->scanWindowsFirewall(),
            'updates' => $this->scanWindowsUpdates(),
            'services' => $this->scanWindowsServices()
        ];

        return $scan;
    }

    protected function scanLinuxSystem(): array
    {
        $scan = [
            'antivirus' => $this->scanLinuxAntivirus(),
            'firewall' => $this->scanLinuxFirewall(),
            'updates' => $this->scanLinuxUpdates(),
            'services' => $this->scanLinuxServices()
        ];

        return $scan;
    }

    protected function scanWindowsAntivirus(): bool
    {
        // Implementation of scanWindowsAntivirus method
        return false; // Placeholder return, actual implementation needed
    }

    protected function scanWindowsFirewall(): bool
    {
        // Implementation of scanWindowsFirewall method
        return false; // Placeholder return, actual implementation needed
    }

    protected function scanWindowsUpdates(): bool
    {
        // Implementation of scanWindowsUpdates method
        return false; // Placeholder return, actual implementation needed
    }

    protected function scanWindowsServices(): bool
    {
        // Implementation of scanWindowsServices method
        return false; // Placeholder return, actual implementation needed
    }

    protected function scanLinuxAntivirus(): bool
    {
        // Implementation of scanLinuxAntivirus method
        return false; // Placeholder return, actual implementation needed
    }

    protected function scanLinuxFirewall(): bool
    {
        // Implementation of scanLinuxFirewall method
        return false; // Placeholder return, actual implementation needed
    }

    protected function scanLinuxUpdates(): bool
    {
        // Implementation of scanLinuxUpdates method
        return false; // Placeholder return, actual implementation needed
    }

    protected function scanLinuxServices(): bool
    {
        // Implementation of scanLinuxServices method
        return false; // Placeholder return, actual implementation needed
    }

    protected function parseWindowsUpdates(string $output): bool
    {
        // Implementation of parseWindowsUpdates method
        return false; // Placeholder return, actual implementation needed
    }

    protected function parseLinuxUpdates(string $output): bool
    {
        // Implementation of parseLinuxUpdates method
        return false; // Placeholder return, actual implementation needed
    }

    protected function updateWindowsSSLConfig(array $config): void
    {
        // Implementation of updateWindowsSSLConfig method
    }

    protected function updateLinuxSSLConfig(array $config): void
    {
        // Implementation of updateLinuxSSLConfig method
    }

    public function getLoginAttempts(array $filters = []): array
    {
        try {
            $query = LoginAttempt::query();

            if (isset($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }

            if (isset($filters['success'])) {
                $query->where('success', $filters['success']);
            }

            if (isset($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }

            $attempts = $query->orderBy('created_at', 'desc')->paginate(20);

            return [
                'success' => true,
                'attempts' => $attempts
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get login attempts: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get login attempts: ' . $e->getMessage()
            ];
        }
    }

    public function clearLoginAttempts(User $user): array
    {
        try {
            LoginAttempt::where('user_id', $user->id)->delete();

            return [
                'success' => true,
                'message' => 'Login attempts cleared successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to clear login attempts: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to clear login attempts: ' . $e->getMessage()
            ];
        }
    }

    public function unlockAccount(User $user): array
    {
        try {
            $user->update(['status' => 'active']);
            $this->clearLoginAttempts($user);

            return [
                'success' => true,
                'message' => 'Account unlocked successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to unlock account: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to unlock account: ' . $e->getMessage()
            ];
        }
    }
} 