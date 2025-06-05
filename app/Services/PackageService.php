<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PackageService
{
    protected $isWindows;
    protected $cacheTime = 3600; // Cache time in seconds

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public function listInstalledPackages(): array
    {
        return Cache::remember('installed_packages', $this->cacheTime, function () {
            if ($this->isWindows) {
                return $this->listWindowsPackages();
            } else {
                return $this->listLinuxPackages();
            }
        });
    }

    public function searchPackages(string $query): array
    {
        if ($this->isWindows) {
            return $this->searchWindowsPackages($query);
        } else {
            return $this->searchLinuxPackages($query);
        }
    }

    public function installPackage(string $package, array $options = []): array
    {
        if ($this->isWindows) {
            return $this->installWindowsPackage($package, $options);
        } else {
            return $this->installLinuxPackage($package, $options);
        }
    }

    public function uninstallPackage(string $package, array $options = []): array
    {
        if ($this->isWindows) {
            return $this->uninstallWindowsPackage($package, $options);
        } else {
            return $this->uninstallLinuxPackage($package, $options);
        }
    }

    public function updatePackage(string $package, array $options = []): array
    {
        if ($this->isWindows) {
            return $this->updateWindowsPackage($package, $options);
        } else {
            return $this->updateLinuxPackage($package, $options);
        }
    }

    public function getPackageInfo(string $package): array
    {
        if ($this->isWindows) {
            return $this->getWindowsPackageInfo($package);
        } else {
            return $this->getLinuxPackageInfo($package);
        }
    }

    public function checkForUpdates(): array
    {
        if ($this->isWindows) {
            return $this->checkWindowsUpdates();
        } else {
            return $this->checkLinuxUpdates();
        }
    }

    public function updateAllPackages(): array
    {
        if ($this->isWindows) {
            return $this->updateAllWindowsPackages();
        } else {
            return $this->updateAllLinuxPackages();
        }
    }

    protected function listWindowsPackages(): array
    {
        try {
            $output = Process::run('powershell -Command "& {Get-WmiObject -Class Win32_Product | Select-Object Name, Version, Vendor}"')->output();
            $packages = [];

            preg_match_all('/Name\s+:\s+(.+)\s+Version\s+:\s+(.+)\s+Vendor\s+:\s+(.+)/', $output, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $packages[] = [
                    'name' => trim($match[1]),
                    'version' => trim($match[2]),
                    'vendor' => trim($match[3])
                ];
            }

            return $packages;
        } catch (\Exception $e) {
            Log::error('Failed to list Windows packages: ' . $e->getMessage());
            return [];
        }
    }

    protected function listLinuxPackages(): array
    {
        try {
            $output = Process::run('dpkg-query -W -f=\'${Package}\t${Version}\t${Maintainer}\n\'')->output();
            $packages = [];

            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;

                list($name, $version, $maintainer) = explode("\t", $line);
                $packages[] = [
                    'name' => $name,
                    'version' => $version,
                    'maintainer' => $maintainer
                ];
            }

            return $packages;
        } catch (\Exception $e) {
            Log::error('Failed to list Linux packages: ' . $e->getMessage());
            return [];
        }
    }

    protected function searchWindowsPackages(string $query): array
    {
        try {
            $output = Process::run('powershell -Command "& {Get-WmiObject -Class Win32_Product | Where-Object {$_.Name -like \'*' . $query . '*\'} | Select-Object Name, Version, Vendor}"')->output();
            $packages = [];

            preg_match_all('/Name\s+:\s+(.+)\s+Version\s+:\s+(.+)\s+Vendor\s+:\s+(.+)/', $output, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $packages[] = [
                    'name' => trim($match[1]),
                    'version' => trim($match[2]),
                    'vendor' => trim($match[3])
                ];
            }

            return $packages;
        } catch (\Exception $e) {
            Log::error('Failed to search Windows packages: ' . $e->getMessage());
            return [];
        }
    }

    protected function searchLinuxPackages(string $query): array
    {
        try {
            $output = Process::run('apt-cache search ' . escapeshellarg($query))->output();
            $packages = [];

            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;

                if (preg_match('/^(\S+)\s+-\s+(.+)$/', $line, $matches)) {
                    $packages[] = [
                        'name' => $matches[1],
                        'description' => $matches[2]
                    ];
                }
            }

            return $packages;
        } catch (\Exception $e) {
            Log::error('Failed to search Linux packages: ' . $e->getMessage());
            return [];
        }
    }

    protected function installWindowsPackage(string $package, array $options = []): array
    {
        try {
            $command = 'powershell -Command "& {Start-Process -FilePath \'msiexec\' -ArgumentList \'/i ' . $package . ' /quiet\' -Wait -PassThru}';
            $output = Process::run($command)->output();

            return [
                'success' => true,
                'message' => 'Package installed successfully',
                'output' => $output
            ];
        } catch (\Exception $e) {
            Log::error('Failed to install Windows package: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to install package: ' . $e->getMessage()
            ];
        }
    }

    protected function installLinuxPackage(string $package, array $options = []): array
    {
        try {
            $command = 'apt-get install -y ' . escapeshellarg($package);
            $output = Process::run($command)->output();

            return [
                'success' => true,
                'message' => 'Package installed successfully',
                'output' => $output
            ];
        } catch (\Exception $e) {
            Log::error('Failed to install Linux package: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to install package: ' . $e->getMessage()
            ];
        }
    }

    protected function uninstallWindowsPackage(string $package, array $options = []): array
    {
        try {
            $command = 'powershell -Command "& {Start-Process -FilePath \'msiexec\' -ArgumentList \'/x ' . $package . ' /quiet\' -Wait -PassThru}';
            $output = Process::run($command)->output();

            return [
                'success' => true,
                'message' => 'Package uninstalled successfully',
                'output' => $output
            ];
        } catch (\Exception $e) {
            Log::error('Failed to uninstall Windows package: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to uninstall package: ' . $e->getMessage()
            ];
        }
    }

    protected function uninstallLinuxPackage(string $package, array $options = []): array
    {
        try {
            $command = 'apt-get remove -y ' . escapeshellarg($package);
            $output = Process::run($command)->output();

            return [
                'success' => true,
                'message' => 'Package uninstalled successfully',
                'output' => $output
            ];
        } catch (\Exception $e) {
            Log::error('Failed to uninstall Linux package: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to uninstall package: ' . $e->getMessage()
            ];
        }
    }

    protected function updateWindowsPackage(string $package, array $options = []): array
    {
        try {
            $command = 'powershell -Command "& {Start-Process -FilePath \'msiexec\' -ArgumentList \'/i ' . $package . ' /quiet\' -Wait -PassThru}';
            $output = Process::run($command)->output();

            return [
                'success' => true,
                'message' => 'Package updated successfully',
                'output' => $output
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update Windows package: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update package: ' . $e->getMessage()
            ];
        }
    }

    protected function updateLinuxPackage(string $package, array $options = []): array
    {
        try {
            $command = 'apt-get install --only-upgrade -y ' . escapeshellarg($package);
            $output = Process::run($command)->output();

            return [
                'success' => true,
                'message' => 'Package updated successfully',
                'output' => $output
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update Linux package: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update package: ' . $e->getMessage()
            ];
        }
    }

    protected function getWindowsPackageInfo(string $package): array
    {
        try {
            $output = Process::run('powershell -Command "& {Get-WmiObject -Class Win32_Product | Where-Object {$_.Name -eq \'' . $package . '\'} | Select-Object Name, Version, Vendor, InstallDate, InstallLocation}"')->output();
            $info = [];

            preg_match('/Name\s+:\s+(.+)\s+Version\s+:\s+(.+)\s+Vendor\s+:\s+(.+)\s+InstallDate\s+:\s+(.+)\s+InstallLocation\s+:\s+(.+)/', $output, $matches);

            if (count($matches) === 6) {
                $info = [
                    'name' => trim($matches[1]),
                    'version' => trim($matches[2]),
                    'vendor' => trim($matches[3]),
                    'install_date' => trim($matches[4]),
                    'install_location' => trim($matches[5])
                ];
            }

            return $info;
        } catch (\Exception $e) {
            Log::error('Failed to get Windows package info: ' . $e->getMessage());
            return [];
        }
    }

    protected function getLinuxPackageInfo(string $package): array
    {
        try {
            $output = Process::run('dpkg-query -W -f=\'${Package}\t${Version}\t${Maintainer}\t${Installed-Size}\t${Depends}\t${Description}\n\' ' . escapeshellarg($package))->output();
            $info = [];

            if (preg_match('/^(\S+)\t(\S+)\t(.+)\t(\d+)\t(.+)\t(.+)$/', $output, $matches)) {
                $info = [
                    'name' => $matches[1],
                    'version' => $matches[2],
                    'maintainer' => $matches[3],
                    'size' => (int) $matches[4],
                    'depends' => explode(', ', $matches[5]),
                    'description' => $matches[6]
                ];
            }

            return $info;
        } catch (\Exception $e) {
            Log::error('Failed to get Linux package info: ' . $e->getMessage());
            return [];
        }
    }

    protected function checkWindowsUpdates(): array
    {
        try {
            $output = Process::run('powershell -Command "& {Get-WindowsUpdate}"')->output();
            $updates = [];

            preg_match_all('/Title\s+:\s+(.+)\s+KB\s+:\s+(.+)\s+Status\s+:\s+(.+)/', $output, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $updates[] = [
                    'title' => trim($match[1]),
                    'kb' => trim($match[2]),
                    'status' => trim($match[3])
                ];
            }

            return $updates;
        } catch (\Exception $e) {
            Log::error('Failed to check Windows updates: ' . $e->getMessage());
            return [];
        }
    }

    protected function checkLinuxUpdates(): array
    {
        try {
            Process::run('apt-get update');
            $output = Process::run('apt-get -s upgrade')->output();
            $updates = [];

            preg_match_all('/Inst\s+(\S+)\s+\[(\S+)\]\s+\((\S+)\s+(.+)\)/', $output, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $updates[] = [
                    'package' => $match[1],
                    'current_version' => $match[2],
                    'new_version' => $match[3],
                    'repository' => $match[4]
                ];
            }

            return $updates;
        } catch (\Exception $e) {
            Log::error('Failed to check Linux updates: ' . $e->getMessage());
            return [];
        }
    }

    protected function updateAllWindowsPackages(): array
    {
        try {
            $command = 'powershell -Command "& {Install-WindowsUpdate -AcceptAll -AutoReboot $false}';
            $output = Process::run($command)->output();

            return [
                'success' => true,
                'message' => 'All packages updated successfully',
                'output' => $output
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update all Windows packages: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update all packages: ' . $e->getMessage()
            ];
        }
    }

    protected function updateAllLinuxPackages(): array
    {
        try {
            Process::run('apt-get update');
            $output = Process::run('apt-get upgrade -y')->output();

            return [
                'success' => true,
                'message' => 'All packages updated successfully',
                'output' => $output
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update all Linux packages: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update all packages: ' . $e->getMessage()
            ];
        }
    }
} 