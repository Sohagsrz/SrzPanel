<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;

class WebServerService
{
    protected $apacheConfigPath;
    protected $nginxConfigPath;
    protected $isWindows;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->apacheConfigPath = $this->isWindows ? 'C:\\Apache24\\conf' : '/etc/apache2';
        $this->nginxConfigPath = $this->isWindows ? 'C:\\nginx\\conf' : '/etc/nginx';
    }

    public function getStatus(): array
    {
        return [
            'apache' => $this->getApacheStatus(),
            'nginx' => $this->getNginxStatus()
        ];
    }

    public function startApache(): void
    {
        if ($this->isWindows) {
            Process::run('net start Apache2.4');
        } else {
            Process::run('systemctl start apache2');
        }
    }

    public function stopApache(): void
    {
        if ($this->isWindows) {
            Process::run('net stop Apache2.4');
        } else {
            Process::run('systemctl stop apache2');
        }
    }

    public function restartApache(): void
    {
        if ($this->isWindows) {
            Process::run('net stop Apache2.4 && net start Apache2.4');
        } else {
            Process::run('systemctl restart apache2');
        }
    }

    public function startNginx(): void
    {
        if ($this->isWindows) {
            Process::run('net start nginx');
        } else {
            Process::run('systemctl start nginx');
        }
    }

    public function stopNginx(): void
    {
        if ($this->isWindows) {
            Process::run('net stop nginx');
        } else {
            Process::run('systemctl stop nginx');
        }
    }

    public function restartNginx(): void
    {
        if ($this->isWindows) {
            Process::run('net stop nginx && net start nginx');
        } else {
            Process::run('systemctl restart nginx');
        }
    }

    public function reloadNginx(): void
    {
        if ($this->isWindows) {
            Process::run('nginx -s reload');
        } else {
            Process::run('systemctl reload nginx');
        }
    }

    public function getApacheConfig(): array
    {
        $config = [];
        $configFile = "{$this->apacheConfigPath}/apache2.conf";

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

    public function getNginxConfig(): array
    {
        $config = [];
        $configFile = "{$this->nginxConfigPath}/nginx.conf";

        if (File::exists($configFile)) {
            $content = File::get($configFile);
            $lines = explode("\n", $content);

            foreach ($lines as $line) {
                if (empty(trim($line)) || strpos($line, '#') === 0) {
                    continue;
                }

                if (preg_match('/^(\w+)\s+(.+);$/', $line, $matches)) {
                    $config[$matches[1]] = $matches[2];
                }
            }
        }

        return $config;
    }

    public function updateApacheConfig(array $settings): void
    {
        $configFile = "{$this->apacheConfigPath}/apache2.conf";
        
        if (!File::exists($configFile)) {
            throw new \Exception('Apache configuration file not found');
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
        $this->restartApache();
    }

    public function updateNginxConfig(array $settings): void
    {
        $configFile = "{$this->nginxConfigPath}/nginx.conf";
        
        if (!File::exists($configFile)) {
            throw new \Exception('Nginx configuration file not found');
        }

        $content = File::get($configFile);
        foreach ($settings as $key => $value) {
            $content = preg_replace(
                "/^{$key}\s+.*;/m",
                "{$key} {$value};",
                $content
            );
        }

        File::put($configFile, $content);
        $this->reloadNginx();
    }

    protected function getApacheStatus(): array
    {
        if ($this->isWindows) {
            $status = Process::run('sc query Apache2.4')->output();
            $isRunning = strpos($status, 'RUNNING') !== false;
        } else {
            $status = Process::run('systemctl status apache2')->output();
            $isRunning = strpos($status, 'active (running)') !== false;
        }

        return [
            'status' => $isRunning ? 'running' : 'stopped',
            'version' => $this->getApacheVersion(),
            'last_restart' => $this->getApacheLastRestart()
        ];
    }

    protected function getNginxStatus(): array
    {
        if ($this->isWindows) {
            $status = Process::run('sc query nginx')->output();
            $isRunning = strpos($status, 'RUNNING') !== false;
        } else {
            $status = Process::run('systemctl status nginx')->output();
            $isRunning = strpos($status, 'active (running)') !== false;
        }

        return [
            'status' => $isRunning ? 'running' : 'stopped',
            'version' => $this->getNginxVersion(),
            'last_restart' => $this->getNginxLastRestart()
        ];
    }

    protected function getApacheVersion(): string
    {
        if ($this->isWindows) {
            $output = Process::run('httpd -v')->output();
        } else {
            $output = Process::run('apache2 -v')->output();
        }

        if (preg_match('/Apache\/([\d.]+)/', $output, $matches)) {
            return $matches[1];
        }

        return 'unknown';
    }

    protected function getNginxVersion(): string
    {
        $output = Process::run('nginx -v')->output();

        if (preg_match('/nginx\/([\d.]+)/', $output, $matches)) {
            return $matches[1];
        }

        return 'unknown';
    }

    protected function getApacheLastRestart(): string
    {
        if ($this->isWindows) {
            $output = Process::run('sc qc Apache2.4')->output();
            if (preg_match('/START_TIME\s+:\s+(.+)/', $output, $matches)) {
                return $matches[1];
            }
        } else {
            $output = Process::run('systemctl show apache2 -p ActiveEnterTimestamp')->output();
            if (preg_match('/ActiveEnterTimestamp=(.+)/', $output, $matches)) {
                return $matches[1];
            }
        }

        return 'unknown';
    }

    protected function getNginxLastRestart(): string
    {
        if ($this->isWindows) {
            $output = Process::run('sc qc nginx')->output();
            if (preg_match('/START_TIME\s+:\s+(.+)/', $output, $matches)) {
                return $matches[1];
            }
        } else {
            $output = Process::run('systemctl show nginx -p ActiveEnterTimestamp')->output();
            if (preg_match('/ActiveEnterTimestamp=(.+)/', $output, $matches)) {
                return $matches[1];
            }
        }

        return 'unknown';
    }
} 