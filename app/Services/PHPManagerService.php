<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;

class PHPManagerService
{
    protected $phpVersions = ['7.4', '8.0', '8.1', '8.2'];
    protected $configPath = '/etc/php';
    protected $fpmPath = '/etc/php-fpm.d';

    public function getInstalledVersions()
    {
        $versions = [];
        foreach ($this->phpVersions as $version) {
            if ($this->isVersionInstalled($version)) {
                $versions[] = [
                    'version' => $version,
                    'status' => $this->getVersionStatus($version),
                    'modules' => $this->getInstalledModules($version)
                ];
            }
        }
        return $versions;
    }

    public function installVersion($version)
    {
        if (!in_array($version, $this->phpVersions)) {
            throw new \Exception('Invalid PHP version');
        }

        if ($this->isVersionInstalled($version)) {
            throw new \Exception('PHP version already installed');
        }

        // Install PHP and required extensions
        Process::run("yum install -y php{$version} php{$version}-fpm php{$version}-cli php{$version}-common php{$version}-mysql php{$version}-xml php{$version}-curl php{$version}-gd php{$version}-mbstring php{$version}-zip php{$version}-bcmath");

        // Start and enable PHP-FPM service
        Process::run("systemctl start php-fpm-{$version}");
        Process::run("systemctl enable php-fpm-{$version}");

        return true;
    }

    public function uninstallVersion($version)
    {
        if (!$this->isVersionInstalled($version)) {
            throw new \Exception('PHP version not installed');
        }

        // Stop and disable PHP-FPM service
        Process::run("systemctl stop php-fpm-{$version}");
        Process::run("systemctl disable php-fpm-{$version}");

        // Remove PHP packages
        Process::run("yum remove -y php{$version} php{$version}-fpm php{$version}-cli php{$version}-common php{$version}-mysql php{$version}-xml php{$version}-curl php{$version}-gd php{$version}-mbstring php{$version}-zip php{$version}-bcmath");

        return true;
    }

    public function updatePHPIni($version, $settings)
    {
        $iniFile = "{$this->configPath}/{$version}/php.ini";
        
        if (!File::exists($iniFile)) {
            throw new \Exception('PHP configuration file not found');
        }

        $content = File::get($iniFile);
        foreach ($settings as $key => $value) {
            $content = preg_replace(
                "/^{$key}\s*=.*/m",
                "{$key} = {$value}",
                $content
            );
        }

        File::put($iniFile, $content);
        Process::run("systemctl restart php-fpm-{$version}");

        return true;
    }

    public function updateFPMConfig($version, $settings)
    {
        $fpmFile = "{$this->fpmPath}/www.conf";
        
        if (!File::exists($fpmFile)) {
            throw new \Exception('PHP-FPM configuration file not found');
        }

        $content = File::get($fpmFile);
        foreach ($settings as $key => $value) {
            $content = preg_replace(
                "/^{$key}\s*=.*/m",
                "{$key} = {$value}",
                $content
            );
        }

        File::put($fpmFile, $content);
        Process::run("systemctl restart php-fpm-{$version}");

        return true;
    }

    public function setDomainPHPVersion($domain, $version)
    {
        if (!$this->isVersionInstalled($version)) {
            throw new \Exception('PHP version not installed');
        }

        // Update Apache/Nginx configuration
        $config = $this->generateVHostConfig($domain, $version);
        File::put("/etc/httpd/conf.d/{$domain}.conf", $config);
        
        Process::run("systemctl restart httpd");

        return true;
    }

    protected function isVersionInstalled($version)
    {
        return Process::run("php{$version} -v")->successful();
    }

    protected function getVersionStatus($version)
    {
        return Process::run("systemctl is-active php-fpm-{$version}")->successful() ? 'active' : 'inactive';
    }

    protected function getInstalledModules($version)
    {
        $output = Process::run("php{$version} -m")->output();
        return array_filter(explode("\n", $output));
    }

    protected function generateVHostConfig($domain, $version)
    {
        return <<<CONFIG
<VirtualHost *:80>
    ServerName {$domain}
    DocumentRoot /var/www/{$domain}/public
    
    <Directory /var/www/{$domain}/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/var/run/php-fpm/php-fpm-{$version}.sock|fcgi://localhost"
    </FilesMatch>
</VirtualHost>
CONFIG;
    }
} 