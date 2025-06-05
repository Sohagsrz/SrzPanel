<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class FirewallService
{
    protected $isWindows;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public function getStatus(): array
    {
        if ($this->isWindows) {
            return $this->getWindowsFirewallStatus();
        } else {
            return $this->getLinuxFirewallStatus();
        }
    }

    public function enable(): bool
    {
        if ($this->isWindows) {
            return $this->enableWindowsFirewall();
        } else {
            return $this->enableLinuxFirewall();
        }
    }

    public function disable(): bool
    {
        if ($this->isWindows) {
            return $this->disableWindowsFirewall();
        } else {
            return $this->disableLinuxFirewall();
        }
    }

    public function addRule(array $rule): bool
    {
        if ($this->isWindows) {
            return $this->addWindowsRule($rule);
        } else {
            return $this->addLinuxRule($rule);
        }
    }

    public function removeRule(string $name): bool
    {
        if ($this->isWindows) {
            return $this->removeWindowsRule($name);
        } else {
            return $this->removeLinuxRule($name);
        }
    }

    public function listRules(): array
    {
        if ($this->isWindows) {
            return $this->listWindowsRules();
        } else {
            return $this->listLinuxRules();
        }
    }

    public function getRule(string $name): array
    {
        if ($this->isWindows) {
            return $this->getWindowsRule($name);
        } else {
            return $this->getLinuxRule($name);
        }
    }

    public function updateRule(string $name, array $rule): bool
    {
        if ($this->removeRule($name)) {
            return $this->addRule($rule);
        }
        return false;
    }

    public function enableRule(string $name): bool
    {
        if ($this->isWindows) {
            return $this->enableWindowsRule($name);
        } else {
            return $this->enableLinuxRule($name);
        }
    }

    public function disableRule(string $name): bool
    {
        if ($this->isWindows) {
            return $this->disableWindowsRule($name);
        } else {
            return $this->disableLinuxRule($name);
        }
    }

    protected function getWindowsFirewallStatus(): array
    {
        try {
            $output = Process::run('powershell -Command "& {Get-NetFirewallProfile | Select-Object Name, Enabled}"')->output();
            $status = [];

            preg_match_all('/Name\s+:\s+(.+)\s+Enabled\s+:\s+(.+)/', $output, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $status[trim($match[1])] = trim($match[2]) === 'True';
            }

            return $status;
        } catch (\Exception $e) {
            Log::error('Failed to get Windows firewall status: ' . $e->getMessage());
            return [];
        }
    }

    protected function getLinuxFirewallStatus(): array
    {
        try {
            $output = Process::run('ufw status')->output();
            $status = [];

            if (strpos($output, 'Status: active') !== false) {
                $status['enabled'] = true;
            } else {
                $status['enabled'] = false;
            }

            return $status;
        } catch (\Exception $e) {
            Log::error('Failed to get Linux firewall status: ' . $e->getMessage());
            return ['enabled' => false];
        }
    }

    protected function enableWindowsFirewall(): bool
    {
        try {
            Process::run('powershell -Command "& {Set-NetFirewallProfile -Profile Domain,Public,Private -Enabled True}"');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to enable Windows firewall: ' . $e->getMessage());
            return false;
        }
    }

    protected function enableLinuxFirewall(): bool
    {
        try {
            Process::run('ufw enable');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to enable Linux firewall: ' . $e->getMessage());
            return false;
        }
    }

    protected function disableWindowsFirewall(): bool
    {
        try {
            Process::run('powershell -Command "& {Set-NetFirewallProfile -Profile Domain,Public,Private -Enabled False}"');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to disable Windows firewall: ' . $e->getMessage());
            return false;
        }
    }

    protected function disableLinuxFirewall(): bool
    {
        try {
            Process::run('ufw disable');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to disable Linux firewall: ' . $e->getMessage());
            return false;
        }
    }

    protected function addWindowsRule(array $rule): bool
    {
        try {
            $command = 'powershell -Command "& {New-NetFirewallRule -DisplayName "' . $rule['name'] . '"';
            
            if (isset($rule['direction'])) {
                $command .= ' -Direction ' . $rule['direction'];
            }
            
            if (isset($rule['action'])) {
                $command .= ' -Action ' . $rule['action'];
            }
            
            if (isset($rule['protocol'])) {
                $command .= ' -Protocol ' . $rule['protocol'];
            }
            
            if (isset($rule['local_port'])) {
                $command .= ' -LocalPort ' . $rule['local_port'];
            }
            
            if (isset($rule['remote_port'])) {
                $command .= ' -RemotePort ' . $rule['remote_port'];
            }
            
            if (isset($rule['local_address'])) {
                $command .= ' -LocalAddress ' . $rule['local_address'];
            }
            
            if (isset($rule['remote_address'])) {
                $command .= ' -RemoteAddress ' . $rule['remote_address'];
            }
            
            if (isset($rule['profile'])) {
                $command .= ' -Profile ' . $rule['profile'];
            }
            
            if (isset($rule['enabled'])) {
                $command .= ' -Enabled ' . ($rule['enabled'] ? '$true' : '$false');
            }
            
            $command .= '}"';
            
            Process::run($command);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to add Windows firewall rule: ' . $e->getMessage());
            return false;
        }
    }

    protected function addLinuxRule(array $rule): bool
    {
        try {
            $command = 'ufw';
            
            if (isset($rule['action'])) {
                $command .= ' ' . $rule['action'];
            }
            
            if (isset($rule['protocol'])) {
                $command .= ' ' . $rule['protocol'];
            }
            
            if (isset($rule['port'])) {
                $command .= ' ' . $rule['port'];
            }
            
            if (isset($rule['from'])) {
                $command .= ' from ' . $rule['from'];
            }
            
            if (isset($rule['to'])) {
                $command .= ' to ' . $rule['to'];
            }
            
            Process::run($command);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to add Linux firewall rule: ' . $e->getMessage());
            return false;
        }
    }

    protected function removeWindowsRule(string $name): bool
    {
        try {
            Process::run('powershell -Command "& {Remove-NetFirewallRule -DisplayName \"' . $name . '\"}"');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove Windows firewall rule: ' . $e->getMessage());
            return false;
        }
    }

    protected function removeLinuxRule(string $name): bool
    {
        try {
            Process::run('ufw delete ' . $name);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove Linux firewall rule: ' . $e->getMessage());
            return false;
        }
    }

    protected function listWindowsRules(): array
    {
        try {
            $output = Process::run('powershell -Command "& {Get-NetFirewallRule | Select-Object DisplayName, Enabled, Direction, Action, Profile}"')->output();
            $rules = [];

            preg_match_all('/DisplayName\s+:\s+(.+)\s+Enabled\s+:\s+(.+)\s+Direction\s+:\s+(.+)\s+Action\s+:\s+(.+)\s+Profile\s+:\s+(.+)/', $output, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $rules[] = [
                    'name' => trim($match[1]),
                    'enabled' => trim($match[2]) === 'True',
                    'direction' => trim($match[3]),
                    'action' => trim($match[4]),
                    'profile' => trim($match[5])
                ];
            }

            return $rules;
        } catch (\Exception $e) {
            Log::error('Failed to list Windows firewall rules: ' . $e->getMessage());
            return [];
        }
    }

    protected function listLinuxRules(): array
    {
        try {
            $output = Process::run('ufw status numbered')->output();
            $rules = [];

            preg_match_all('/\[\s*(\d+)\]\s+(.+)/', $output, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $rules[] = [
                    'number' => (int) $match[1],
                    'rule' => trim($match[2])
                ];
            }

            return $rules;
        } catch (\Exception $e) {
            Log::error('Failed to list Linux firewall rules: ' . $e->getMessage());
            return [];
        }
    }

    protected function getWindowsRule(string $name): array
    {
        try {
            $output = Process::run('powershell -Command "& {Get-NetFirewallRule -DisplayName \"' . $name . '\" | Select-Object DisplayName, Enabled, Direction, Action, Profile, Protocol, LocalPort, RemotePort, LocalAddress, RemoteAddress}"')->output();
            $rule = [];

            preg_match('/DisplayName\s+:\s+(.+)\s+Enabled\s+:\s+(.+)\s+Direction\s+:\s+(.+)\s+Action\s+:\s+(.+)\s+Profile\s+:\s+(.+)\s+Protocol\s+:\s+(.+)\s+LocalPort\s+:\s+(.+)\s+RemotePort\s+:\s+(.+)\s+LocalAddress\s+:\s+(.+)\s+RemoteAddress\s+:\s+(.+)/', $output, $matches);

            if (count($matches) === 11) {
                $rule = [
                    'name' => trim($matches[1]),
                    'enabled' => trim($matches[2]) === 'True',
                    'direction' => trim($matches[3]),
                    'action' => trim($matches[4]),
                    'profile' => trim($matches[5]),
                    'protocol' => trim($matches[6]),
                    'local_port' => trim($matches[7]),
                    'remote_port' => trim($matches[8]),
                    'local_address' => trim($matches[9]),
                    'remote_address' => trim($matches[10])
                ];
            }

            return $rule;
        } catch (\Exception $e) {
            Log::error('Failed to get Windows firewall rule: ' . $e->getMessage());
            return [];
        }
    }

    protected function getLinuxRule(string $name): array
    {
        try {
            $output = Process::run('ufw status numbered | grep "' . $name . '"')->output();
            $rule = [];

            if (preg_match('/\[\s*(\d+)\]\s+(.+)/', $output, $matches)) {
                $rule = [
                    'number' => (int) $matches[1],
                    'rule' => trim($matches[2])
                ];
            }

            return $rule;
        } catch (\Exception $e) {
            Log::error('Failed to get Linux firewall rule: ' . $e->getMessage());
            return [];
        }
    }

    protected function enableWindowsRule(string $name): bool
    {
        try {
            Process::run('powershell -Command "& {Enable-NetFirewallRule -DisplayName \"' . $name . '\"}"');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to enable Windows firewall rule: ' . $e->getMessage());
            return false;
        }
    }

    protected function enableLinuxRule(string $name): bool
    {
        try {
            Process::run('ufw enable ' . $name);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to enable Linux firewall rule: ' . $e->getMessage());
            return false;
        }
    }

    protected function disableWindowsRule(string $name): bool
    {
        try {
            Process::run('powershell -Command "& {Disable-NetFirewallRule -DisplayName \"' . $name . '\"}"');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to disable Windows firewall rule: ' . $e->getMessage());
            return false;
        }
    }

    protected function disableLinuxRule(string $name): bool
    {
        try {
            Process::run('ufw disable ' . $name);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to disable Linux firewall rule: ' . $e->getMessage());
            return false;
        }
    }
} 