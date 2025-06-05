<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::paginate(10);
        return view('admin.packages.index', compact('packages'));
    }

    public function create()
    {
        return view('admin.packages.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:packages',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'billing_cycle' => 'required|string',
            'is_active' => 'boolean',
            'disk_space' => 'required|integer',
            'bandwidth' => 'required|integer',
            'domains' => 'required|integer',
            'subdomains' => 'required|integer',
            'email_accounts' => 'required|integer',
            'databases' => 'required|integer',
            'ftp_accounts' => 'required|integer',
            'ssl_enabled' => 'boolean',
            'backup_enabled' => 'boolean',
            'firewall_enabled' => 'boolean',
            'dns_management' => 'boolean',
            'cron_jobs' => 'boolean',
            'shell_access' => 'boolean',
            'php_version' => 'required|string',
            'max_execution_time' => 'required|integer',
            'memory_limit' => 'required|integer',
            'upload_max_filesize' => 'required|integer',
            'post_max_size' => 'required|integer',
        ]);
        Package::create($data);
        return redirect()->route('admin.packages.index')->with('success', 'Package created.');
    }

    public function edit(Package $package)
    {
        return view('admin.packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:packages,slug,' . $package->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'billing_cycle' => 'required|string',
            'is_active' => 'boolean',
            'disk_space' => 'required|integer',
            'bandwidth' => 'required|integer',
            'domains' => 'required|integer',
            'subdomains' => 'required|integer',
            'email_accounts' => 'required|integer',
            'databases' => 'required|integer',
            'ftp_accounts' => 'required|integer',
            'ssl_enabled' => 'boolean',
            'backup_enabled' => 'boolean',
            'firewall_enabled' => 'boolean',
            'dns_management' => 'boolean',
            'cron_jobs' => 'boolean',
            'shell_access' => 'boolean',
            'php_version' => 'required|string',
            'max_execution_time' => 'required|integer',
            'memory_limit' => 'required|integer',
            'upload_max_filesize' => 'required|integer',
            'post_max_size' => 'required|integer',
        ]);
        $package->update($data);
        return redirect()->route('admin.packages.index')->with('success', 'Package updated.');
    }

    public function destroy(Package $package)
    {
        $package->delete();
        return redirect()->route('admin.packages.index')->with('success', 'Package deleted.');
    }
} 