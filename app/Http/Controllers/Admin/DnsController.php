<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DnsService;
use App\Services\CacheService;
use Illuminate\Http\Request;

class DnsController extends Controller
{
    protected $dnsService;
    protected $cacheService;

    public function __construct(DnsService $dnsService, CacheService $cacheService)
    {
        $this->dnsService = $dnsService;
        $this->cacheService = $cacheService;
    }

    public function index()
    {
        $zones = $this->cacheService->remember('dns.zones', function () {
            return $this->dnsService->listZones();
        }, 300);

        return view('admin.dns.index', compact('zones'));
    }

    public function create()
    {
        return view('admin.dns.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'zone_name' => 'required|string|max:255',
            'type' => 'required|in:master,slave'
        ]);

        $this->dnsService->createZone($request->zone_name, $request->type);
        $this->cacheService->forget('dns.zones');

        return redirect()->route('admin.dns.index')
            ->with('success', 'DNS zone created successfully.');
    }

    public function addRecord(Request $request)
    {
        $request->validate([
            'zone_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'type' => 'required|in:A,AAAA,CNAME,MX,TXT,NS,PTR,SRV',
            'value' => 'required|string|max:255',
            'ttl' => 'required|integer|min:60|max:86400'
        ]);

        $this->dnsService->addRecord(
            $request->zone_name,
            $request->name,
            $request->type,
            $request->value,
            $request->ttl
        );
        $this->cacheService->forget('dns.zones');

        return redirect()->route('admin.dns.index')
            ->with('success', 'DNS record added successfully.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'zone_name' => 'required|string|max:255'
        ]);

        $this->dnsService->deleteZone($request->zone_name);
        $this->cacheService->forget('dns.zones');

        return redirect()->route('admin.dns.index')
            ->with('success', 'DNS zone deleted successfully.');
    }
} 