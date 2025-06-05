<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class VirtualHostService
{
    protected $apachePath;
    protected $nginxPath;
    protected $webRoot;

    public function __construct()
    {
        $this->apachePath = '/etc/apache2/sites-available/';
        $this->nginxPath = '/etc/nginx/sites-available/';
        $this->webRoot = '/var/www/html/';
    }

    public function createVirtualHost($domain, $user, $phpVersion = '8.1', $ssl = false)
    {
        // Create web root directory
        $webPath = $this->webRoot . $domain;
        if (!File::exists($webPath)) {
            File::makeDirectory($webPath, 0755, true);
        }

        // Generate Apache config
        $apacheConfig = $this->generateApacheConfig($domain, $user, $phpVersion, $ssl);
        File::put($this->apachePath . $domain . '.conf', $apacheConfig);

        // Generate Nginx config
        $nginxConfig = $this->generateNginxConfig($domain, $user, $phpVersion, $ssl);
        File::put($this->nginxPath . $domain . '.conf', $nginxConfig);

        // Enable sites
        $this->enableSite($domain);

        return true;
    }

    protected function generateApacheConfig($domain, $user, $phpVersion, $ssl)
    {
        $config = "<VirtualHost *:80>\n";
        $config .= "    ServerName {$domain}\n";
        $config .= "    ServerAlias www.{$domain}\n";
        $config .= "    DocumentRoot {$this->webRoot}{$domain}\n";
        $config .= "    <Directory {$this->webRoot}{$domain}>\n";
        $config .= "        Options Indexes FollowSymLinks\n";
        $config .= "        AllowOverride All\n";
        $config .= "        Require all granted\n";
        $config .= "    </Directory>\n";
        $config .= "    ErrorLog \${APACHE_LOG_DIR}/{$domain}-error.log\n";
        $config .= "    CustomLog \${APACHE_LOG_DIR}/{$domain}-access.log combined\n";
        $config .= "</VirtualHost>\n";

        if ($ssl) {
            $config .= "\n<VirtualHost *:443>\n";
            $config .= "    ServerName {$domain}\n";
            $config .= "    ServerAlias www.{$domain}\n";
            $config .= "    DocumentRoot {$this->webRoot}{$domain}\n";
            $config .= "    <Directory {$this->webRoot}{$domain}>\n";
            $config .= "        Options Indexes FollowSymLinks\n";
            $config .= "        AllowOverride All\n";
            $config .= "        Require all granted\n";
            $config .= "    </Directory>\n";
            $config .= "    ErrorLog \${APACHE_LOG_DIR}/{$domain}-error.log\n";
            $config .= "    CustomLog \${APACHE_LOG_DIR}/{$domain}-access.log combined\n";
            $config .= "    SSLEngine on\n";
            $config .= "    SSLCertificateFile /etc/letsencrypt/live/{$domain}/fullchain.pem\n";
            $config .= "    SSLCertificateKeyFile /etc/letsencrypt/live/{$domain}/privkey.pem\n";
            $config .= "</VirtualHost>\n";
        }

        return $config;
    }

    protected function generateNginxConfig($domain, $user, $phpVersion, $ssl)
    {
        $config = "server {\n";
        $config .= "    listen 80;\n";
        $config .= "    server_name {$domain} www.{$domain};\n";
        $config .= "    root {$this->webRoot}{$domain};\n";
        $config .= "    index index.php index.html;\n\n";
        $config .= "    location / {\n";
        $config .= "        try_files \$uri \$uri/ /index.php?\$query_string;\n";
        $config .= "    }\n\n";
        $config .= "    location ~ \\.php$ {\n";
        $config .= "        fastcgi_pass unix:/var/run/php/php{$phpVersion}-fpm.sock;\n";
        $config .= "        fastcgi_index index.php;\n";
        $config .= "        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;\n";
        $config .= "        include fastcgi_params;\n";
        $config .= "    }\n";
        $config .= "}\n";

        if ($ssl) {
            $config .= "\nserver {\n";
            $config .= "    listen 443 ssl;\n";
            $config .= "    server_name {$domain} www.{$domain};\n";
            $config .= "    root {$this->webRoot}{$domain};\n";
            $config .= "    index index.php index.html;\n\n";
            $config .= "    ssl_certificate /etc/letsencrypt/live/{$domain}/fullchain.pem;\n";
            $config .= "    ssl_certificate_key /etc/letsencrypt/live/{$domain}/privkey.pem;\n\n";
            $config .= "    location / {\n";
            $config .= "        try_files \$uri \$uri/ /index.php?\$query_string;\n";
            $config .= "    }\n\n";
            $config .= "    location ~ \\.php$ {\n";
            $config .= "        fastcgi_pass unix:/var/run/php/php{$phpVersion}-fpm.sock;\n";
            $config .= "        fastcgi_index index.php;\n";
            $config .= "        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;\n";
            $config .= "        include fastcgi_params;\n";
            $config .= "    }\n";
            $config .= "}\n";
        }

        return $config;
    }

    protected function enableSite($domain)
    {
        // Enable Apache site
        Process::run("a2ensite {$domain}.conf");
        Process::run("systemctl reload apache2");

        // Enable Nginx site
        Process::run("ln -s {$this->nginxPath}{$domain}.conf /etc/nginx/sites-enabled/");
        Process::run("systemctl reload nginx");
    }

    public function deleteVirtualHost($domain)
    {
        // Disable and remove Apache config
        Process::run("a2dissite {$domain}.conf");
        File::delete($this->apachePath . $domain . '.conf');

        // Disable and remove Nginx config
        File::delete("/etc/nginx/sites-enabled/{$domain}.conf");
        File::delete($this->nginxPath . $domain . '.conf');

        // Reload services
        Process::run("systemctl reload apache2");
        Process::run("systemctl reload nginx");

        return true;
    }
} 