<?php

namespace App\Services;

use App\Models\Domain;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\DomainLog;
use Carbon\Carbon;

class DomainService
{
    protected $isWindows;
    protected $nginxPath;
    protected $apachePath;
    protected $hostsPath;
    protected $backupPath;
    protected $documentRoot;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->nginxPath = $this->isWindows ? 'C:\\laragon\\bin\\nginx\\nginx-1.25.3\\conf\\sites-enabled' : '/etc/nginx/sites-enabled';
        $this->apachePath = $this->isWindows ? 'C:\\laragon\\bin\\apache\\httpd-2.4.58-win64-VS17\\conf\\extra' : '/etc/apache2/sites-enabled';
        $this->hostsPath = $this->isWindows ? 'C:\\Windows\\System32\\drivers\\etc\\hosts' : '/etc/hosts';
        $this->backupPath = storage_path('backups/domains');
        $this->documentRoot = $this->isWindows ? 'C:\\laragon\\www' : '/var/www';

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    public function createDomain(array $data): array
    {
        try {
            $required = ['name', 'document_root'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("Missing required field: {$field}");
                }
            }

            $domain = $data['name'];
            $documentRoot = $data['document_root'];
            $serverType = $data['server_type'] ?? 'apache';
            $phpVersion = $data['php_version'] ?? '8.1';
            $sslEnabled = $data['ssl_enabled'] ?? false;

            // Create document root
            if (!File::exists($documentRoot)) {
                File::makeDirectory($documentRoot, 0755, true);
            }

            // Create virtual host configuration
            if ($serverType === 'apache') {
                $this->createApacheVirtualHost($domain, $documentRoot, $phpVersion, $sslEnabled);
            } else {
                $this->createNginxVirtualHost($domain, $documentRoot, $phpVersion, $sslEnabled);
            }

            // Enable SSL if requested
            if ($sslEnabled) {
                $this->enableSSL($domain);
            }

            // Restart web server
            $this->restartWebServer($serverType);

            return [
                'success' => true,
                'message' => 'Domain created successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create domain: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create domain: ' . $e->getMessage()
            ];
        }
    }

    public function updateDomain(string $domain, array $data): array
    {
        try {
            $serverType = $data['server_type'] ?? 'apache';
            $documentRoot = $data['document_root'] ?? null;
            $phpVersion = $data['php_version'] ?? null;
            $sslEnabled = $data['ssl_enabled'] ?? null;

            if ($documentRoot) {
                if (!File::exists($documentRoot)) {
                    File::makeDirectory($documentRoot, 0755, true);
                }
            }

            if ($serverType === 'apache') {
                $this->updateApacheVirtualHost($domain, $documentRoot, $phpVersion, $sslEnabled);
            } else {
                $this->updateNginxVirtualHost($domain, $documentRoot, $phpVersion, $sslEnabled);
            }

            if ($sslEnabled !== null) {
                if ($sslEnabled) {
                    $this->enableSSL($domain);
                } else {
                    $this->disableSSL($domain);
                }
            }

            $this->restartWebServer($serverType);

            return [
                'success' => true,
                'message' => 'Domain updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update domain: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update domain: ' . $e->getMessage()
            ];
        }
    }

    public function deleteDomain(string $domain, string $serverType = 'apache'): array
    {
        try {
            if ($serverType === 'apache') {
                $this->deleteApacheVirtualHost($domain);
            } else {
                $this->deleteNginxVirtualHost($domain);
            }

            $this->restartWebServer($serverType);

            return [
                'success' => true,
                'message' => 'Domain deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete domain: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete domain: ' . $e->getMessage()
            ];
        }
    }

    public function enableDomain(string $domain, string $serverType = 'apache'): array
    {
        try {
            if ($serverType === 'apache') {
                $this->enableApacheVirtualHost($domain);
            } else {
                $this->enableNginxVirtualHost($domain);
            }

            $this->restartWebServer($serverType);

            return [
                'success' => true,
                'message' => 'Domain enabled successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to enable domain: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to enable domain: ' . $e->getMessage()
            ];
        }
    }

    public function disableDomain(string $domain, string $serverType = 'apache'): array
    {
        try {
            if ($serverType === 'apache') {
                $this->disableApacheVirtualHost($domain);
            } else {
                $this->disableNginxVirtualHost($domain);
            }

            $this->restartWebServer($serverType);

            return [
                'success' => true,
                'message' => 'Domain disabled successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to disable domain: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to disable domain: ' . $e->getMessage()
            ];
        }
    }

    public function backupDomain(string $domain): array
    {
        try {
            $backupFile = $this->backupPath . '/' . $domain . '_' . date('Y-m-d_H-i-s') . '.tar.gz';
            $domainPath = $this->documentRoot . '/' . $domain;

            if (!File::exists($domainPath)) {
                throw new \Exception('Domain directory does not exist');
            }

            $command = sprintf(
                'tar -czf %s -C %s .',
                $backupFile,
                $domainPath
            );

            Process::run($command);

            if (File::exists($backupFile)) {
                return [
                    'success' => true,
                    'message' => 'Domain backed up successfully',
                    'file' => $backupFile
                ];
            } else {
                throw new \Exception('Backup file was not created');
            }
        } catch (\Exception $e) {
            Log::error('Failed to backup domain: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to backup domain: ' . $e->getMessage()
            ];
        }
    }

    public function restoreDomain(string $domain, string $backupFile): array
    {
        try {
            if (!File::exists($backupFile)) {
                throw new \Exception('Backup file does not exist');
            }

            $domainPath = $this->documentRoot . '/' . $domain;
            if (!File::exists($domainPath)) {
                File::makeDirectory($domainPath, 0755, true);
            }

            $command = sprintf(
                'tar -xzf %s -C %s',
                $backupFile,
                $domainPath
            );

            Process::run($command);

            return [
                'success' => true,
                'message' => 'Domain restored successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to restore domain: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore domain: ' . $e->getMessage()
            ];
        }
    }

    public function getDomainStats(string $domain): array
    {
        try {
            $domainPath = $this->documentRoot . '/' . $domain;
            if (!File::exists($domainPath)) {
                throw new \Exception('Domain directory does not exist');
            }

            $size = $this->getDirectorySize($domainPath);
            $files = $this->countFiles($domainPath);

            return [
                'success' => true,
                'stats' => [
                    'size' => $size,
                    'files' => $files,
                    'last_modified' => File::lastModified($domainPath)
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get domain stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get domain stats: ' . $e->getMessage()
            ];
        }
    }

    public function getDomainLogs(Domain $domain, array $filters = []): array
    {
        try {
            $query = DomainLog::where('domain_id', $domain->id);

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
            Log::error('Failed to get domain logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get domain logs: ' . $e->getMessage()
            ];
        }
    }

    protected function createApacheVirtualHost(string $domain, string $documentRoot, string $phpVersion, bool $sslEnabled): void
    {
        $config = $this->getApacheVirtualHostTemplate($domain, $documentRoot, $phpVersion, $sslEnabled);
        $configFile = $this->apachePath . '/sites-available/' . $domain . '.conf';

        File::put($configFile, $config);
        $this->enableApacheVirtualHost($domain);
    }

    protected function createNginxVirtualHost(string $domain, string $documentRoot, string $phpVersion, bool $sslEnabled): void
    {
        $config = $this->getNginxVirtualHostTemplate($domain, $documentRoot, $phpVersion, $sslEnabled);
        $configFile = $this->nginxPath . '/sites-available/' . $domain;

        File::put($configFile, $config);
        $this->enableNginxVirtualHost($domain);
    }

    protected function updateApacheVirtualHost(string $domain, ?string $documentRoot, ?string $phpVersion, ?bool $sslEnabled): void
    {
        $configFile = $this->apachePath . '/sites-available/' . $domain . '.conf';
        if (!File::exists($configFile)) {
            throw new \Exception('Virtual host configuration does not exist');
        }

        $config = File::get($configFile);
        if ($documentRoot) {
            $config = str_replace('DocumentRoot .*', 'DocumentRoot ' . $documentRoot, $config);
        }
        if ($phpVersion) {
            $config = str_replace('php-fpm-.*', 'php-fpm-' . $phpVersion, $config);
        }
        if ($sslEnabled !== null) {
            if ($sslEnabled) {
                $config = $this->addApacheSSLConfig($config, $domain);
            } else {
                $config = $this->removeApacheSSLConfig($config);
            }
        }

        File::put($configFile, $config);
    }

    protected function updateNginxVirtualHost(string $domain, ?string $documentRoot, ?string $phpVersion, ?bool $sslEnabled): void
    {
        $configFile = $this->nginxPath . '/sites-available/' . $domain;
        if (!File::exists($configFile)) {
            throw new \Exception('Virtual host configuration does not exist');
        }

        $config = File::get($configFile);
        if ($documentRoot) {
            $config = str_replace('root .*;', 'root ' . $documentRoot . ';', $config);
        }
        if ($phpVersion) {
            $config = str_replace('php-fpm-.*', 'php-fpm-' . $phpVersion, $config);
        }
        if ($sslEnabled !== null) {
            if ($sslEnabled) {
                $config = $this->addNginxSSLConfig($config, $domain);
            } else {
                $config = $this->removeNginxSSLConfig($config);
            }
        }

        File::put($configFile, $config);
    }

    protected function deleteApacheVirtualHost(string $domain): void
    {
        $configFile = $this->apachePath . '/sites-available/' . $domain . '.conf';
        if (File::exists($configFile)) {
            File::delete($configFile);
        }

        $enabledFile = $this->apachePath . '/sites-enabled/' . $domain . '.conf';
        if (File::exists($enabledFile)) {
            File::delete($enabledFile);
        }
    }

    protected function deleteNginxVirtualHost(string $domain): void
    {
        $configFile = $this->nginxPath . '/sites-available/' . $domain;
        if (File::exists($configFile)) {
            File::delete($configFile);
        }

        $enabledFile = $this->nginxPath . '/sites-enabled/' . $domain;
        if (File::exists($enabledFile)) {
            File::delete($enabledFile);
        }
    }

    protected function enableApacheVirtualHost(string $domain): void
    {
        $source = $this->apachePath . '/sites-available/' . $domain . '.conf';
        $target = $this->apachePath . '/sites-enabled/' . $domain . '.conf';

        if (!File::exists($target)) {
            File::link($source, $target);
        }
    }

    protected function enableNginxVirtualHost(string $domain): void
    {
        $source = $this->nginxPath . '/sites-available/' . $domain;
        $target = $this->nginxPath . '/sites-enabled/' . $domain;

        if (!File::exists($target)) {
            File::link($source, $target);
        }
    }

    protected function disableApacheVirtualHost(string $domain): void
    {
        $enabledFile = $this->apachePath . '/sites-enabled/' . $domain . '.conf';
        if (File::exists($enabledFile)) {
            File::delete($enabledFile);
        }
    }

    protected function disableNginxVirtualHost(string $domain): void
    {
        $enabledFile = $this->nginxPath . '/sites-enabled/' . $domain;
        if (File::exists($enabledFile)) {
            File::delete($enabledFile);
        }
    }

    protected function restartWebServer(string $serverType): void
    {
        if ($serverType === 'apache') {
            if ($this->isWindows) {
                Process::run('net stop Apache2.4 && net start Apache2.4');
            } else {
                Process::run('systemctl restart apache2');
            }
        } else {
            if ($this->isWindows) {
                Process::run('net stop nginx && net start nginx');
            } else {
                Process::run('systemctl restart nginx');
            }
        }
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

    protected function getApacheVirtualHostTemplate(string $domain, string $documentRoot, string $phpVersion, bool $sslEnabled): string
    {
        $template = "<VirtualHost *:80>\n";
        $template .= "    ServerName {$domain}\n";
        $template .= "    ServerAlias www.{$domain}\n";
        $template .= "    DocumentRoot {$documentRoot}\n";
        $template .= "    <Directory {$documentRoot}>\n";
        $template .= "        Options Indexes FollowSymLinks\n";
        $template .= "        AllowOverride All\n";
        $template .= "        Require all granted\n";
        $template .= "    </Directory>\n";
        $template .= "    ErrorLog \${APACHE_LOG_DIR}/{$domain}-error.log\n";
        $template .= "    CustomLog \${APACHE_LOG_DIR}/{$domain}-access.log combined\n";
        $template .= "</VirtualHost>\n";

        if ($sslEnabled) {
            $template .= $this->getApacheSSLVirtualHostTemplate($domain, $documentRoot, $phpVersion);
        }

        return $template;
    }

    protected function getNginxVirtualHostTemplate(string $domain, string $documentRoot, string $phpVersion, bool $sslEnabled): string
    {
        $template = "server {\n";
        $template .= "    listen 80;\n";
        $template .= "    server_name {$domain} www.{$domain};\n";
        $template .= "    root {$documentRoot};\n";
        $template .= "    index index.php index.html index.htm;\n";
        $template .= "    location / {\n";
        $template .= "        try_files \$uri \$uri/ /index.php?\$query_string;\n";
        $template .= "    }\n";
        $template .= "    location ~ \\.php$ {\n";
        $template .= "        include snippets/fastcgi-php.conf;\n";
        $template .= "        fastcgi_pass unix:/var/run/php/php{$phpVersion}-fpm.sock;\n";
        $template .= "    }\n";
        $template .= "    location ~ /\\.ht {\n";
        $template .= "        deny all;\n";
        $template .= "    }\n";
        $template .= "    error_log /var/log/nginx/{$domain}-error.log;\n";
        $template .= "    access_log /var/log/nginx/{$domain}-access.log;\n";
        $template .= "}\n";

        if ($sslEnabled) {
            $template .= $this->getNginxSSLVirtualHostTemplate($domain, $documentRoot, $phpVersion);
        }

        return $template;
    }

    protected function getApacheSSLVirtualHostTemplate(string $domain, string $documentRoot, string $phpVersion): string
    {
        $template = "<VirtualHost *:443>\n";
        $template .= "    ServerName {$domain}\n";
        $template .= "    ServerAlias www.{$domain}\n";
        $template .= "    DocumentRoot {$documentRoot}\n";
        $template .= "    <Directory {$documentRoot}>\n";
        $template .= "        Options Indexes FollowSymLinks\n";
        $template .= "        AllowOverride All\n";
        $template .= "        Require all granted\n";
        $template .= "    </Directory>\n";
        $template .= "    SSLEngine on\n";
        $template .= "    SSLCertificateFile /etc/ssl/certs/{$domain}.crt\n";
        $template .= "    SSLCertificateKeyFile /etc/ssl/private/{$domain}.key\n";
        $template .= "    ErrorLog \${APACHE_LOG_DIR}/{$domain}-ssl-error.log\n";
        $template .= "    CustomLog \${APACHE_LOG_DIR}/{$domain}-ssl-access.log combined\n";
        $template .= "</VirtualHost>\n";

        return $template;
    }

    protected function getNginxSSLVirtualHostTemplate(string $domain, string $documentRoot, string $phpVersion): string
    {
        $template = "server {\n";
        $template .= "    listen 443 ssl;\n";
        $template .= "    server_name {$domain} www.{$domain};\n";
        $template .= "    root {$documentRoot};\n";
        $template .= "    index index.php index.html index.htm;\n";
        $template .= "    ssl_certificate /etc/ssl/certs/{$domain}.crt;\n";
        $template .= "    ssl_certificate_key /etc/ssl/private/{$domain}.key;\n";
        $template .= "    location / {\n";
        $template .= "        try_files \$uri \$uri/ /index.php?\$query_string;\n";
        $template .= "    }\n";
        $template .= "    location ~ \\.php$ {\n";
        $template .= "        include snippets/fastcgi-php.conf;\n";
        $template .= "        fastcgi_pass unix:/var/run/php/php{$phpVersion}-fpm.sock;\n";
        $template .= "    }\n";
        $template .= "    location ~ /\\.ht {\n";
        $template .= "        deny all;\n";
        $template .= "    }\n";
        $template .= "    error_log /var/log/nginx/{$domain}-ssl-error.log;\n";
        $template .= "    access_log /var/log/nginx/{$domain}-ssl-access.log;\n";
        $template .= "}\n";

        return $template;
    }

    protected function addApacheSSLConfig(string $config, string $domain): string
    {
        $sslConfig = $this->getApacheSSLVirtualHostTemplate($domain, '', '');
        return $config . "\n" . $sslConfig;
    }

    protected function addNginxSSLConfig(string $config, string $domain): string
    {
        $sslConfig = $this->getNginxSSLVirtualHostTemplate($domain, '', '');
        return $config . "\n" . $sslConfig;
    }

    protected function removeApacheSSLConfig(string $config): string
    {
        return preg_replace('/<VirtualHost \*:443>.*?<\/VirtualHost>/s', '', $config);
    }

    protected function removeNginxSSLConfig(string $config): string
    {
        return preg_replace('/server {\n.*?listen 443 ssl;.*?}\n/s', '', $config);
    }

    protected function enableSSL(string $domain): void
    {
        // This is a placeholder for SSL certificate generation/installation
        // You would typically use Let's Encrypt or another SSL provider here
    }

    protected function disableSSL(string $domain): void
    {
        // This is a placeholder for SSL certificate removal
    }
} 