<?php

namespace App\Services;

use App\Models\Domain;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DnsService
{
    protected $cacheService;
    protected $isWindows;
    protected $bindConfigPath;
    protected $windowsDnsPath;
    protected $bindPath;
    protected $backupPath;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->bindConfigPath = '/etc/bind';
        $this->windowsDnsPath = 'C:\\Windows\\System32\\dns';
        $this->bindPath = $this->isWindows ? 'C:\\laragon\\bin\\bind' : '/etc/bind';
        $this->backupPath = storage_path('backups/dns');

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    public function getRecords(Domain $domain): array
    {
        return $this->cacheService->remember(
            CacheService::KEY_DNS_RECORDS . ".{$domain->id}",
            function () use ($domain) {
                // This is a placeholder. In a real application, you would:
                // 1. Query the DNS server for records
                // 2. Format and return the records
                return [
                    [
                        'type' => 'A',
                        'name' => $domain->name,
                        'content' => '192.168.1.1',
                        'ttl' => 3600,
                    ],
                    [
                        'type' => 'MX',
                        'name' => $domain->name,
                        'content' => 'mail.' . $domain->name,
                        'ttl' => 3600,
                    ],
                ];
            },
            3600 // Cache for 1 hour
        );
    }

    public function updateRecords(Domain $domain, array $records): void
    {
        try {
            // This is a placeholder. In a real application, you would:
            // 1. Validate the records
            // 2. Update the DNS server
            // 3. Clear the cache
            $this->cacheService->forget(CacheService::KEY_DNS_RECORDS . ".{$domain->id}");
        } catch (\Exception $e) {
            Log::error("Failed to update DNS records for domain {$domain->name}: " . $e->getMessage());
            throw $e;
        }
    }

    public function addRecord(Domain $domain, array $record): void
    {
        try {
            $records = $this->getRecords($domain);
            $records[] = $record;
            $this->updateRecords($domain, $records);
        } catch (\Exception $e) {
            Log::error("Failed to add DNS record for domain {$domain->name}: " . $e->getMessage());
            throw $e;
        }
    }

    public function removeRecord(Domain $domain, string $type, string $name): void
    {
        try {
            $records = $this->getRecords($domain);
            $records = array_filter($records, function ($record) use ($type, $name) {
                return !($record['type'] === $type && $record['name'] === $name);
            });
            $this->updateRecords($domain, array_values($records));
        } catch (\Exception $e) {
            Log::error("Failed to remove DNS record for domain {$domain->name}: " . $e->getMessage());
            throw $e;
        }
    }

    public function validateRecord(array $record): bool
    {
        $required = ['type', 'name', 'content', 'ttl'];
        if (count(array_intersect_key(array_flip($required), $record)) !== count($required)) {
            return false;
        }

        // Validate record type
        $validTypes = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'SRV', 'NS'];
        if (!in_array($record['type'], $validTypes)) {
            return false;
        }

        // Validate TTL
        if (!is_numeric($record['ttl']) || $record['ttl'] < 60) {
            return false;
        }

        return true;
    }

    public function listZones()
    {
        if ($this->isWindows) {
            return $this->listWindowsZones();
        }
        return $this->listBindZones();
    }

    protected function listBindZones()
    {
        $zones = [];
        $namedConf = File::get($this->bindConfigPath . '/named.conf.local');
        
        preg_match_all('/zone\s+"([^"]+)"\s+{/', $namedConf, $matches);
        
        foreach ($matches[1] as $zone) {
            $zones[] = [
                'name' => $zone,
                'type' => 'BIND',
                'records' => $this->getZoneRecords($zone)
            ];
        }
        
        return $zones;
    }

    protected function listWindowsZones()
    {
        $zones = [];
        $output = Process::run('powershell -Command "Get-DnsServerZone | Select-Object ZoneName, ZoneType"')->output();
        
        preg_match_all('/ZoneName\s+ZoneType\s+-----\s+([^\n]+)/', $output, $matches);
        
        foreach ($matches[1] as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 2) {
                $zones[] = [
                    'name' => $parts[0],
                    'type' => 'Windows DNS',
                    'records' => $this->getWindowsZoneRecords($parts[0])
                ];
            }
        }
        
        return $zones;
    }

    public function createZone($zoneName, $type = 'master')
    {
        if ($this->isWindows) {
            return $this->createWindowsZone($zoneName, $type);
        }
        return $this->createBindZone($zoneName, $type);
    }

    protected function createBindZone($zoneName, $type)
    {
        // Create zone file
        $zoneFile = $this->bindConfigPath . '/zones/' . $zoneName . '.db';
        $zoneContent = $this->generateBindZoneFile($zoneName);
        File::put($zoneFile, $zoneContent);

        // Add zone to named.conf.local
        $config = "zone \"{$zoneName}\" {\n";
        $config .= "    type {$type};\n";
        $config .= "    file \"zones/{$zoneName}.db\";\n";
        $config .= "};\n\n";
        
        File::append($this->bindConfigPath . '/named.conf.local', $config);

        // Reload BIND
        Process::run('rndc reload');

        return true;
    }

    protected function createWindowsZone($zoneName, $type)
    {
        $command = "Add-DnsServerPrimaryZone -Name \"{$zoneName}\" -ZoneFile \"{$zoneName}.dns\" -DynamicUpdate None";
        Process::run("powershell -Command \"{$command}\"");
        return true;
    }

    public function addRecord($zoneName, $name, $type, $value, $ttl = 3600)
    {
        if ($this->isWindows) {
            return $this->addWindowsRecord($zoneName, $name, $type, $value, $ttl);
        }
        return $this->addBindRecord($zoneName, $name, $type, $value, $ttl);
    }

    protected function addBindRecord($zoneName, $name, $type, $value, $ttl)
    {
        $zoneFile = $this->bindConfigPath . '/zones/' . $zoneName . '.db';
        $record = "{$name}\t{$ttl}\tIN\t{$type}\t{$value}\n";
        File::append($zoneFile, $record);
        
        Process::run('rndc reload');
        return true;
    }

    protected function addWindowsRecord($zoneName, $name, $type, $value, $ttl)
    {
        $command = "Add-DnsServerResourceRecord -ZoneName \"{$zoneName}\" -Name \"{$name}\" -RecordType {$type} -RecordData \"{$value}\" -TTL {$ttl}";
        Process::run("powershell -Command \"{$command}\"");
        return true;
    }

    public function deleteZone($zoneName)
    {
        if ($this->isWindows) {
            return $this->deleteWindowsZone($zoneName);
        }
        return $this->deleteBindZone($zoneName);
    }

    protected function deleteBindZone($zoneName)
    {
        // Remove zone file
        $zoneFile = $this->bindConfigPath . '/zones/' . $zoneName . '.db';
        if (File::exists($zoneFile)) {
            File::delete($zoneFile);
        }

        // Remove from named.conf.local
        $config = File::get($this->bindConfigPath . '/named.conf.local');
        $pattern = "/zone\s+\"{$zoneName}\"\s+{[^}]+};/s";
        $config = preg_replace($pattern, '', $config);
        File::put($this->bindConfigPath . '/named.conf.local', $config);

        Process::run('rndc reload');
        return true;
    }

    protected function deleteWindowsZone($zoneName)
    {
        $command = "Remove-DnsServerZone -Name \"{$zoneName}\" -Force";
        Process::run("powershell -Command \"{$command}\"");
        return true;
    }

    protected function getZoneRecords($zoneName)
    {
        if ($this->isWindows) {
            return $this->getWindowsZoneRecords($zoneName);
        }
        return $this->getBindZoneRecords($zoneName);
    }

    protected function getBindZoneRecords($zoneName)
    {
        $zoneFile = $this->bindConfigPath . '/zones/' . $zoneName . '.db';
        if (!File::exists($zoneFile)) {
            return [];
        }

        $records = [];
        $content = File::get($zoneFile);
        preg_match_all('/^([^\s]+)\s+(\d+)\s+IN\s+([^\s]+)\s+(.+)$/m', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $records[] = [
                'name' => $match[1],
                'ttl' => $match[2],
                'type' => $match[3],
                'value' => $match[4]
            ];
        }

        return $records;
    }

    protected function getWindowsZoneRecords($zoneName)
    {
        $command = "Get-DnsServerResourceRecord -ZoneName \"{$zoneName}\" | Select-Object HostName, RecordType, RecordData, TTL";
        $output = Process::run("powershell -Command \"{$command}\"")->output();
        
        $records = [];
        preg_match_all('/HostName\s+RecordType\s+RecordData\s+TTL\s+-----\s+([^\n]+)/', $output, $matches);
        
        foreach ($matches[1] as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 4) {
                $records[] = [
                    'name' => $parts[0],
                    'type' => $parts[1],
                    'value' => $parts[2],
                    'ttl' => $parts[3]
                ];
            }
        }
        
        return $records;
    }

    protected function generateBindZoneFile($zoneName)
    {
        $content = "\$TTL    3600\n";
        $content .= "@       IN      SOA     ns1.{$zoneName}. admin.{$zoneName}. (\n";
        $content .= "                        " . time() . "         ; Serial\n";
        $content .= "                        3600        ; Refresh\n";
        $content .= "                        1800        ; Retry\n";
        $content .= "                        604800      ; Expire\n";
        $content .= "                        86400 )     ; Minimum TTL\n\n";
        $content .= "@       IN      NS      ns1.{$zoneName}.\n";
        $content .= "@       IN      A       127.0.0.1\n";
        $content .= "ns1     IN      A       127.0.0.1\n";
        
        return $content;
    }

    public function createDNS(array $data): DNS
    {
        // Create DNS record
        $this->createDNSRecord($data);

        // Create DNS record in database
        return DNS::create($data);
    }

    public function updateDNS(DNS $dns, array $data): void
    {
        // Update DNS record
        $this->updateDNSRecord($dns, $data);

        $dns->update($data);
    }

    public function deleteDNS(DNS $dns): void
    {
        // Delete DNS record
        $this->deleteDNSRecord($dns);

        $dns->delete();
    }

    public function enableDNS(DNS $dns): void
    {
        $this->enableDNSRecord($dns);
        $dns->update(['status' => 'active']);
    }

    public function disableDNS(DNS $dns): void
    {
        $this->disableDNSRecord($dns);
        $dns->update(['status' => 'inactive']);
    }

    public function backupDNS(DNS $dns): array
    {
        try {
            $backupFile = $this->backupPath . '/' . $dns->domain . '_' . date('Y-m-d_H-i-s') . '.tar.gz';
            $dnsPath = $this->getDNSPath($dns->domain);

            $command = sprintf(
                'tar -czf %s -C %s .',
                $backupFile,
                $dnsPath
            );

            Process::run($command);

            if (File::exists($backupFile)) {
                return [
                    'success' => true,
                    'message' => 'DNS backed up successfully',
                    'file' => $backupFile
                ];
            } else {
                throw new \Exception('Backup file was not created');
            }
        } catch (\Exception $e) {
            Log::error('Failed to backup DNS: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to backup DNS: ' . $e->getMessage()
            ];
        }
    }

    public function restoreDNS(string $domain, string $backupFile): array
    {
        try {
            if (!File::exists($backupFile)) {
                throw new \Exception('Backup file does not exist');
            }

            $dnsPath = $this->getDNSPath($domain);
            if (!File::exists($dnsPath)) {
                File::makeDirectory($dnsPath, 0755, true);
            }

            $command = sprintf(
                'tar -xzf %s -C %s',
                $backupFile,
                $dnsPath
            );

            Process::run($command);

            return [
                'success' => true,
                'message' => 'DNS restored successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to restore DNS: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore DNS: ' . $e->getMessage()
            ];
        }
    }

    public function getDNSStats(DNS $dns): array
    {
        try {
            $dnsPath = $this->getDNSPath($dns->domain);
            $size = $this->getDirectorySize($dnsPath);
            $files = $this->countFiles($dnsPath);

            return [
                'success' => true,
                'size' => $size,
                'files' => $files,
                'last_modified' => File::lastModified($dnsPath)
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get DNS stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get DNS stats: ' . $e->getMessage()
            ];
        }
    }

    public function validateDNSRecord(array $data): array
    {
        try {
            $errors = [];

            // Validate domain
            if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/', $data['domain'])) {
                $errors[] = 'Invalid domain format';
            }

            // Validate record type
            $validTypes = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'SRV', 'NS'];
            if (!in_array($data['type'], $validTypes)) {
                $errors[] = 'Invalid record type';
            }

            // Validate TTL
            if (!is_numeric($data['ttl']) || $data['ttl'] < 0) {
                $errors[] = 'Invalid TTL value';
            }

            // Validate value based on record type
            switch ($data['type']) {
                case 'A':
                    if (!filter_var($data['value'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        $errors[] = 'Invalid IPv4 address';
                    }
                    break;
                case 'AAAA':
                    if (!filter_var($data['value'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        $errors[] = 'Invalid IPv6 address';
                    }
                    break;
                case 'CNAME':
                    if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/', $data['value'])) {
                        $errors[] = 'Invalid CNAME value';
                    }
                    break;
                case 'MX':
                    if (!preg_match('/^\d+\s+[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/', $data['value'])) {
                        $errors[] = 'Invalid MX value';
                    }
                    break;
                case 'TXT':
                    if (empty($data['value'])) {
                        $errors[] = 'TXT value cannot be empty';
                    }
                    break;
                case 'SRV':
                    if (!preg_match('/^\d+\s+\d+\s+\d+\s+[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/', $data['value'])) {
                        $errors[] = 'Invalid SRV value';
                    }
                    break;
                case 'NS':
                    if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/', $data['value'])) {
                        $errors[] = 'Invalid NS value';
                    }
                    break;
            }

            return [
                'success' => empty($errors),
                'errors' => $errors
            ];
        } catch (\Exception $e) {
            Log::error('Failed to validate DNS record: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to validate DNS record: ' . $e->getMessage()
            ];
        }
    }

    protected function createDNSRecord(array $data): void
    {
        if ($this->isWindows) {
            $this->createWindowsDNSRecord($data);
        } else {
            $this->createLinuxDNSRecord($data);
        }
    }

    protected function updateDNSRecord(DNS $dns, array $data): void
    {
        if ($this->isWindows) {
            $this->updateWindowsDNSRecord($dns, $data);
        } else {
            $this->updateLinuxDNSRecord($dns, $data);
        }
    }

    protected function deleteDNSRecord(DNS $dns): void
    {
        if ($this->isWindows) {
            $this->deleteWindowsDNSRecord($dns);
        } else {
            $this->deleteLinuxDNSRecord($dns);
        }
    }

    protected function enableDNSRecord(DNS $dns): void
    {
        if ($this->isWindows) {
            $this->enableWindowsDNSRecord($dns);
        } else {
            $this->enableLinuxDNSRecord($dns);
        }
    }

    protected function disableDNSRecord(DNS $dns): void
    {
        if ($this->isWindows) {
            $this->disableWindowsDNSRecord($dns);
        } else {
            $this->disableLinuxDNSRecord($dns);
        }
    }

    protected function createWindowsDNSRecord(array $data): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Create the DNS record in the Windows DNS server
        // 2. Set up the necessary configuration files
    }

    protected function createLinuxDNSRecord(array $data): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Create the DNS record in BIND
        // 2. Set up the necessary configuration files
    }

    protected function updateWindowsDNSRecord(DNS $dns, array $data): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Update the DNS record in the Windows DNS server
        // 2. Update the necessary configuration files
    }

    protected function updateLinuxDNSRecord(DNS $dns, array $data): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Update the DNS record in BIND
        // 2. Update the necessary configuration files
    }

    protected function deleteWindowsDNSRecord(DNS $dns): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Delete the DNS record from the Windows DNS server
        // 2. Remove the necessary configuration files
    }

    protected function deleteLinuxDNSRecord(DNS $dns): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Delete the DNS record from BIND
        // 2. Remove the necessary configuration files
    }

    protected function enableWindowsDNSRecord(DNS $dns): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Enable the DNS record in the Windows DNS server
    }

    protected function enableLinuxDNSRecord(DNS $dns): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Enable the DNS record in BIND
    }

    protected function disableWindowsDNSRecord(DNS $dns): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Disable the DNS record in the Windows DNS server
    }

    protected function disableLinuxDNSRecord(DNS $dns): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Disable the DNS record in BIND
    }

    protected function getDNSPath(string $domain): string
    {
        if ($this->isWindows) {
            return 'C:\\laragon\\dns\\' . $domain;
        } else {
            return '/etc/bind/zones/' . $domain;
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