<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\VirtualHostService;
use Illuminate\Http\Request;

class VirtualHostController extends Controller
{
    protected $virtualHostService;

    public function __construct(VirtualHostService $virtualHostService)
    {
        $this->virtualHostService = $virtualHostService;
    }

    public function index()
    {
        $domains = $this->getDomains();
        return view('admin.vhosts.index', compact('domains'));
    }

    public function create()
    {
        return view('admin.vhosts.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'domain' => 'required|string|max:255',
            'user' => 'required|string|max:255',
            'php_version' => 'required|string',
            'ssl' => 'boolean',
        ]);

        $this->virtualHostService->createVirtualHost(
            $data['domain'],
            $data['user'],
            $data['php_version'],
            $data['ssl'] ?? false
        );

        return redirect()->route('admin.vhosts.index')
            ->with('success', 'Virtual host created successfully.');
    }

    public function destroy($domain)
    {
        $this->virtualHostService->deleteVirtualHost($domain);
        return redirect()->route('admin.vhosts.index')
            ->with('success', 'Virtual host deleted successfully.');
    }

    protected function getDomains()
    {
        // Get list of domains from Apache/Nginx configs
        $domains = [];
        $apacheConfigs = glob('/etc/apache2/sites-enabled/*.conf');
        $nginxConfigs = glob('/etc/nginx/sites-enabled/*.conf');

        foreach ($apacheConfigs as $config) {
            $domain = basename($config, '.conf');
            $domains[] = [
                'name' => $domain,
                'type' => 'Apache',
                'config' => file_get_contents($config)
            ];
        }

        foreach ($nginxConfigs as $config) {
            $domain = basename($config, '.conf');
            $domains[] = [
                'name' => $domain,
                'type' => 'Nginx',
                'config' => file_get_contents($config)
            ];
        }

        return $domains;
    }
} 