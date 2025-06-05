<?php

namespace App\Services;

use App\Models\Domain;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use App\Models\SslCertificate;
use App\Models\SslLog;

class SslService
{
    protected $isWindows;
    protected $certbotPath;
    protected $backupPath;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->certbotPath = $this->isWindows ? 'C:\\laragon\\bin\\certbot' : '/usr/bin/certbot';
        $this->backupPath = storage_path('backups/ssl');

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    public function getStatus(Domain $domain): array
    {
        if (!$domain->ssl_enabled) {
            return [
                'enabled' => false,
                'expires_at' => null,
                'issuer' => null,
                'valid' => false,
            ];
        }

        // This is a placeholder. In a real application, you would:
        // 1. Check if the certificate exists
        // 2. Validate the certificate
        // 3. Get certificate details
        return [
            'enabled' => true,
            'expires_at' => $domain->ssl_expires_at,
            'issuer' => 'Let\'s Encrypt',
            'valid' => $domain->ssl_expires_at->isFuture(),
        ];
    }

    public function updateCertificate(Domain $domain, array $data): void
    {
        if ($data['ssl_enabled']) {
            $this->installCertificate($domain, $data);
        } else {
            $this->removeCertificate($domain);
        }
    }

    public function installCertificate(Domain $domain, array $data): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Validate the certificate and private key
        // 2. Store the certificate files
        // 3. Update web server configuration
        // 4. Reload the web server

        $domain->update([
            'ssl_enabled' => true,
            'ssl_expires_at' => now()->addYear(), // This should be calculated from the certificate
        ]);
    }

    public function removeCertificate(Domain $domain): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Remove certificate files
        // 2. Update web server configuration
        // 3. Reload the web server

        $domain->update([
            'ssl_enabled' => false,
            'ssl_expires_at' => null,
        ]);
    }

    public function autoRenewCertificate(Domain $domain): void
    {
        if (!$domain->ssl_enabled) {
            return;
        }

        // This is a placeholder. In a real application, you would:
        // 1. Check if the certificate needs renewal
        // 2. Request a new certificate from Let's Encrypt
        // 3. Install the new certificate
        // 4. Update the domain record
    }

    protected function validateCertificate(string $certificate, string $privateKey): bool
    {
        // This is a placeholder. In a real application, you would:
        // 1. Validate the certificate format
        // 2. Validate the private key format
        // 3. Check if they match
        return true;
    }

    protected function storeCertificate(Domain $domain, string $certificate, string $privateKey): void
    {
        $path = "ssl/{$domain->id}";
        
        Storage::put("{$path}/certificate.pem", $certificate);
        Storage::put("{$path}/private.key", $privateKey);
    }

    protected function removeCertificateFiles(Domain $domain): void
    {
        $path = "ssl/{$domain->id}";
        
        Storage::delete([
            "{$path}/certificate.pem",
            "{$path}/private.key",
        ]);
    }

    public function createCertificate(array $data): array
    {
        try {
            // Create SSL certificate
            $this->createSSLCertificate($data['domain'], $data['email']);

            // Create SSL certificate record
            $certificate = SslCertificate::create($data);

            return [
                'success' => true,
                'message' => 'SSL certificate created successfully',
                'certificate' => $certificate
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create SSL certificate: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create SSL certificate: ' . $e->getMessage()
            ];
        }
    }

    public function updateCertificate(SslCertificate $certificate, array $data): array
    {
        try {
            // Update SSL certificate
            $this->updateSSLCertificate($certificate->domain, $data);

            // Update SSL certificate record
            $certificate->update($data);

            return [
                'success' => true,
                'message' => 'SSL certificate updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update SSL certificate: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update SSL certificate: ' . $e->getMessage()
            ];
        }
    }

    public function deleteCertificate(SslCertificate $certificate): array
    {
        try {
            // Delete SSL certificate
            $this->deleteSSLCertificate($certificate->domain);

            // Delete SSL certificate record
            $certificate->delete();

            return [
                'success' => true,
                'message' => 'SSL certificate deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete SSL certificate: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete SSL certificate: ' . $e->getMessage()
            ];
        }
    }

    public function renewCertificate(SslCertificate $certificate): array
    {
        try {
            // Renew SSL certificate
            $this->renewSSLCertificate($certificate->domain);

            // Update SSL certificate record
            $certificate->update([
                'renewed_at' => now(),
                'expires_at' => now()->addDays(90)
            ]);

            return [
                'success' => true,
                'message' => 'SSL certificate renewed successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to renew SSL certificate: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to renew SSL certificate: ' . $e->getMessage()
            ];
        }
    }

    public function backupCertificate(SslCertificate $certificate): array
    {
        try {
            $backupFile = $this->backupPath . '/' . $certificate->domain . '_' . date('Y-m-d_H-i-s') . '.tar.gz';
            $certPath = $this->getCertificatePath($certificate->domain);

            $command = sprintf(
                'tar -czf %s -C %s .',
                $backupFile,
                $certPath
            );

            Process::run($command);

            if (File::exists($backupFile)) {
                return [
                    'success' => true,
                    'message' => 'SSL certificate backed up successfully',
                    'file' => $backupFile
                ];
            } else {
                throw new \Exception('Backup file was not created');
            }
        } catch (\Exception $e) {
            Log::error('Failed to backup SSL certificate: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to backup SSL certificate: ' . $e->getMessage()
            ];
        }
    }

    public function restoreCertificate(string $domain, string $backupFile): array
    {
        try {
            if (!File::exists($backupFile)) {
                throw new \Exception('Backup file does not exist');
            }

            $certPath = $this->getCertificatePath($domain);
            if (!File::exists($certPath)) {
                File::makeDirectory($certPath, 0755, true);
            }

            $command = sprintf(
                'tar -xzf %s -C %s',
                $backupFile,
                $certPath
            );

            Process::run($command);

            return [
                'success' => true,
                'message' => 'SSL certificate restored successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to restore SSL certificate: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore SSL certificate: ' . $e->getMessage()
            ];
        }
    }

    public function getCertificateInfo(SslCertificate $certificate): array
    {
        try {
            $certPath = $this->getCertificatePath($certificate->domain);
            $certFile = $certPath . '/fullchain.pem';

            if (!File::exists($certFile)) {
                throw new \Exception('Certificate file does not exist');
            }

            $command = sprintf(
                'openssl x509 -in %s -text -noout',
                $certFile
            );

            $output = Process::run($command)->output();

            return [
                'success' => true,
                'info' => $this->parseCertificateInfo($output)
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get certificate info: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get certificate info: ' . $e->getMessage()
            ];
        }
    }

    public function getCertificateLogs(SslCertificate $certificate, array $filters = []): array
    {
        try {
            $query = SslLog::where('ssl_certificate_id', $certificate->id);

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
            Log::error('Failed to get certificate logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get certificate logs: ' . $e->getMessage()
            ];
        }
    }

    protected function createSSLCertificate(string $domain, string $email): void
    {
        if ($this->isWindows) {
            $this->createWindowsSSLCertificate($domain, $email);
        } else {
            $this->createLinuxSSLCertificate($domain, $email);
        }
    }

    protected function updateSSLCertificate(string $domain, array $data): void
    {
        if ($this->isWindows) {
            $this->updateWindowsSSLCertificate($domain, $data);
        } else {
            $this->updateLinuxSSLCertificate($domain, $data);
        }
    }

    protected function deleteSSLCertificate(string $domain): void
    {
        if ($this->isWindows) {
            $this->deleteWindowsSSLCertificate($domain);
        } else {
            $this->deleteLinuxSSLCertificate($domain);
        }
    }

    protected function renewSSLCertificate(string $domain): void
    {
        if ($this->isWindows) {
            $this->renewWindowsSSLCertificate($domain);
        } else {
            $this->renewLinuxSSLCertificate($domain);
        }
    }

    protected function getCertificatePath(string $domain): string
    {
        return $this->isWindows
            ? 'C:\\laragon\\etc\\ssl\\' . $domain
            : '/etc/letsencrypt/live/' . $domain;
    }

    protected function createWindowsSSLCertificate(string $domain, string $email): void
    {
        $command = sprintf(
            '%s certonly --webroot -w %s -d %s --agree-tos --email %s --non-interactive',
            $this->certbotPath,
            public_path(),
            $domain,
            $email
        );

        Process::run($command);
    }

    protected function createLinuxSSLCertificate(string $domain, string $email): void
    {
        $command = sprintf(
            '%s certonly --webroot -w %s -d %s --agree-tos --email %s --non-interactive',
            $this->certbotPath,
            public_path(),
            $domain,
            $email
        );

        Process::run($command);
    }

    protected function updateWindowsSSLCertificate(string $domain, array $data): void
    {
        // Update certificate configuration if needed
        if (isset($data['config'])) {
            $configPath = $this->getCertificatePath($domain) . '/config.json';
            File::put($configPath, json_encode($data['config']));
        }
    }

    protected function updateLinuxSSLCertificate(string $domain, array $data): void
    {
        // Update certificate configuration if needed
        if (isset($data['config'])) {
            $configPath = $this->getCertificatePath($domain) . '/config.json';
            File::put($configPath, json_encode($data['config']));
        }
    }

    protected function deleteWindowsSSLCertificate(string $domain): void
    {
        $command = sprintf(
            '%s delete --cert-name %s --non-interactive',
            $this->certbotPath,
            $domain
        );

        Process::run($command);
    }

    protected function deleteLinuxSSLCertificate(string $domain): void
    {
        $command = sprintf(
            '%s delete --cert-name %s --non-interactive',
            $this->certbotPath,
            $domain
        );

        Process::run($command);
    }

    protected function renewWindowsSSLCertificate(string $domain): void
    {
        $command = sprintf(
            '%s renew --cert-name %s --non-interactive',
            $this->certbotPath,
            $domain
        );

        Process::run($command);
    }

    protected function renewLinuxSSLCertificate(string $domain): void
    {
        $command = sprintf(
            '%s renew --cert-name %s --non-interactive',
            $this->certbotPath,
            $domain
        );

        Process::run($command);
    }

    protected function parseCertificateInfo(string $output): array
    {
        $info = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $info[trim($key)] = trim($value);
            }
        }

        return $info;
    }
} 