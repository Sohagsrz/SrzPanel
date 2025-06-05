<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class PackagesController extends Controller
{
    public function index()
    {
        $available = $this->getAvailablePackages();
        $installed = $this->getInstalledPackages();
        return view('admin.packages.index', compact('available', 'installed'));
    }

    public function install(Request $request)
    {
        $request->validate([
            'package' => 'required|string',
        ]);
        $package = $request->input('package');
        $result = Process::run("apt-get install -y {$package}");
        if ($result->successful()) {
            return redirect()->route('admin.packages.index')->with('success', "{$package} installed successfully.");
        }
        return redirect()->route('admin.packages.index')->with('error', "Failed to install {$package}: " . $result->errorOutput());
    }

    public function remove(Request $request)
    {
        $request->validate([
            'package' => 'required|string',
        ]);
        $package = $request->input('package');
        $result = Process::run("apt-get remove -y {$package}");
        if ($result->successful()) {
            return redirect()->route('admin.packages.index')->with('success', "{$package} removed successfully.");
        }
        return redirect()->route('admin.packages.index')->with('error', "Failed to remove {$package}: " . $result->errorOutput());
    }

    protected function getAvailablePackages()
    {
        // Example: List of common packages
        return [
            'nginx', 'apache2', 'mysql-server', 'mariadb-server', 'php', 'php-fpm', 'nodejs', 'npm', 'composer', 'git', 'curl', 'ufw', 'fail2ban',
        ];
    }

    protected function getInstalledPackages()
    {
        $result = Process::run("dpkg --get-selections");
        $installed = [];
        if ($result->successful()) {
            foreach (explode("\n", $result->output()) as $line) {
                if (preg_match('/^([\w\-\.]+)\s+install$/', $line, $matches)) {
                    $installed[] = $matches[1];
                }
            }
        }
        return $installed;
    }
} 