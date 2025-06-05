<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PHPManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class PHPController extends Controller
{
    protected $phpManager;

    public function __construct(PHPManagerService $phpManager)
    {
        $this->phpManager = $phpManager;
    }

    public function index()
    {
        $versions = $this->phpManager->getInstalledVersions();
        $currentVersion = $this->getCurrentVersion();
        $extensions = $this->getInstalledExtensions();
        $configs = $this->getPHPConfigs();

        return view('admin.php.index', compact('versions', 'currentVersion', 'extensions', 'configs'));
    }

    public function install(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string'
        ]);

        try {
            $this->phpManager->installVersion($validated['version']);
            return redirect()->route('admin.php.index')
                ->with('success', "PHP {$validated['version']} installed successfully.");
        } catch (\Exception $e) {
            return redirect()->route('admin.php.index')
                ->with('error', $e->getMessage());
        }
    }

    public function uninstall(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string'
        ]);

        try {
            $this->phpManager->uninstallVersion($validated['version']);
            return redirect()->route('admin.php.index')
                ->with('success', "PHP {$validated['version']} uninstalled successfully.");
        } catch (\Exception $e) {
            return redirect()->route('admin.php.index')
                ->with('error', $e->getMessage());
        }
    }

    public function updateIni(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string',
            'settings' => 'required|array'
        ]);

        try {
            $this->phpManager->updatePHPIni($validated['version'], $validated['settings']);
            return redirect()->route('admin.php.index')
                ->with('success', "PHP {$validated['version']} configuration updated successfully.");
        } catch (\Exception $e) {
            return redirect()->route('admin.php.index')
                ->with('error', $e->getMessage());
        }
    }

    public function updateFPM(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string',
            'settings' => 'required|array'
        ]);

        try {
            $this->phpManager->updateFPMConfig($validated['version'], $validated['settings']);
            return redirect()->route('admin.php.index')
                ->with('success', "PHP-FPM {$validated['version']} configuration updated successfully.");
        } catch (\Exception $e) {
            return redirect()->route('admin.php.index')
                ->with('error', $e->getMessage());
        }
    }

    public function setDomainVersion(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string',
            'version' => 'required|string'
        ]);

        try {
            $this->phpManager->setDomainPHPVersion($validated['domain'], $validated['version']);
            return redirect()->route('admin.domains.show', $validated['domain'])
                ->with('success', "Domain PHP version updated to {$validated['version']} successfully.");
        } catch (\Exception $e) {
            return redirect()->route('admin.domains.show', $validated['domain'])
                ->with('error', $e->getMessage());
        }
    }

    public function installExtension(Request $request)
    {
        $request->validate([
            'extension' => 'required|string',
            'version' => 'required|string',
        ]);

        $extension = $request->input('extension');
        $version = $request->input('version');

        // Install PHP extension
        $result = Process::run("apt-get install -y php{$version}-{$extension}");
        
        if ($result->successful()) {
            return redirect()->route('admin.php.index')
                ->with('success', "PHP extension {$extension} installed successfully.");
        }

        return redirect()->route('admin.php.index')
            ->with('error', "Failed to install PHP extension {$extension}: " . $result->errorOutput());
    }

    public function uninstallExtension(Request $request)
    {
        $request->validate([
            'extension' => 'required|string',
            'version' => 'required|string',
        ]);

        $extension = $request->input('extension');
        $version = $request->input('version');

        // Uninstall PHP extension
        $result = Process::run("apt-get remove -y php{$version}-{$extension}");
        
        if ($result->successful()) {
            return redirect()->route('admin.php.index')
                ->with('success', "PHP extension {$extension} uninstalled successfully.");
        }

        return redirect()->route('admin.php.index')
            ->with('error', "Failed to uninstall PHP extension {$extension}: " . $result->errorOutput());
    }

    protected function getInstalledVersions()
    {
        $result = Process::run("ls /usr/bin/php* | grep -E 'php[0-9]+\.[0-9]+$'");
        $versions = [];
        
        if ($result->successful()) {
            foreach (explode("\n", trim($result->output())) as $path) {
                if (preg_match('/php(\d+\.\d+)$/', $path, $matches)) {
                    $versions[] = $matches[1];
                }
            }
        }

        return $versions;
    }

    protected function getCurrentVersion()
    {
        $result = Process::run('php -v');
        if ($result->successful() && preg_match('/PHP (\d+\.\d+)/', $result->output(), $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function getInstalledExtensions()
    {
        $extensions = [];
        $versions = $this->getInstalledVersions();

        foreach ($versions as $version) {
            $result = Process::run("php{$version} -m");
            if ($result->successful()) {
                $extensions[$version] = array_filter(explode("\n", $result->output()));
            }
        }

        return $extensions;
    }

    protected function getPHPConfigs()
    {
        $configs = [];
        $versions = $this->getInstalledVersions();

        foreach ($versions as $version) {
            $configPath = "/etc/php/{$version}/fpm/php.ini";
            if (file_exists($configPath)) {
                $configs[$version] = parse_ini_file($configPath);
            }
        }

        return $configs;
    }
} 