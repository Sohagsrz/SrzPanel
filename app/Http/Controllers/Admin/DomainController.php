<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use Illuminate\Http\Request;
use App\Services\DomainService;
use App\Services\DnsService;
use App\Services\SslService;
use App\Services\CacheService;

class DomainController extends Controller
{
    protected $domainService;
    protected $dnsService;
    protected $sslService;
    protected $cacheService;

    public function __construct(
        DomainService $domainService,
        DnsService $dnsService,
        SslService $sslService,
        CacheService $cacheService
    ) {
        $this->domainService = $domainService;
        $this->dnsService = $dnsService;
        $this->sslService = $sslService;
        $this->cacheService = $cacheService;
    }

    public function index()
    {
        $domains = $this->cacheService->remember(
            CacheService::KEY_DOMAIN_LIST,
            fn() => Domain::with(['databases', 'emailAccounts'])->paginate(10),
            300 // Cache for 5 minutes
        );
        
        return view('admin.domains.index', compact('domains'));
    }

    public function create()
    {
        return view('admin.domains.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:domains',
            'document_root' => 'required|string',
            'ssl_enabled' => 'boolean',
        ]);

        $domain = $this->domainService->createDomain($validated);
        
        // Clear domain list cache
        $this->cacheService->forget(CacheService::KEY_DOMAIN_LIST);

        return redirect()->route('domains.show', $domain)
            ->with('success', 'Domain created successfully.');
    }

    public function show(Domain $domain)
    {
        $domain->load(['databases', 'emailAccounts']);
        
        $dnsRecords = $this->dnsService->getRecords($domain);
        $sslStatus = $this->sslService->getStatus($domain);

        return view('admin.domains.show', compact('domain', 'dnsRecords', 'sslStatus'));
    }

    public function edit(Domain $domain)
    {
        return view('admin.domains.edit', compact('domain'));
    }

    public function update(Request $request, Domain $domain)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:domains,name,' . $domain->id,
            'document_root' => 'required|string',
            'ssl_enabled' => 'boolean',
            'status' => 'required|string',
        ]);

        $this->domainService->updateDomain($domain, $validated);
        
        // Clear domain list cache
        $this->cacheService->forget(CacheService::KEY_DOMAIN_LIST);

        return redirect()->route('domains.show', $domain)
            ->with('success', 'Domain updated successfully.');
    }

    public function destroy(Domain $domain)
    {
        $this->domainService->deleteDomain($domain);
        
        // Clear domain list cache
        $this->cacheService->forget(CacheService::KEY_DOMAIN_LIST);

        return redirect()->route('domains.index')
            ->with('success', 'Domain deleted successfully.');
    }

    public function updateDns(Request $request, Domain $domain)
    {
        $validated = $request->validate([
            'records' => 'required|array',
            'records.*.type' => 'required|string',
            'records.*.name' => 'required|string',
            'records.*.content' => 'required|string',
            'records.*.ttl' => 'required|integer',
        ]);

        $this->dnsService->updateRecords($domain, $validated['records']);

        return redirect()->route('domains.show', $domain)
            ->with('success', 'DNS records updated successfully.');
    }

    public function updateSsl(Request $request, Domain $domain)
    {
        $validated = $request->validate([
            'ssl_enabled' => 'required|boolean',
            'certificate' => 'required_if:ssl_enabled,true|string',
            'private_key' => 'required_if:ssl_enabled,true|string',
        ]);

        $this->sslService->updateCertificate($domain, $validated);
        
        // Clear domain list cache
        $this->cacheService->forget(CacheService::KEY_DOMAIN_LIST);

        return redirect()->route('domains.show', $domain)
            ->with('success', 'SSL certificate updated successfully.');
    }

    public function createSubdomain(Request $request, Domain $domain)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'document_root' => 'required|string',
        ]);

        $subdomain = $this->domainService->createSubdomain($domain, $validated);
        
        // Clear domain list cache
        $this->cacheService->forget(CacheService::KEY_DOMAIN_LIST);

        return redirect()->route('domains.show', $domain)
            ->with('success', 'Subdomain created successfully.');
    }

    public function deleteSubdomain(Domain $domain, Domain $subdomain)
    {
        $this->domainService->deleteSubdomain($domain, $subdomain);
        
        // Clear domain list cache
        $this->cacheService->forget(CacheService::KEY_DOMAIN_LIST);

        return redirect()->route('domains.show', $domain)
            ->with('success', 'Subdomain deleted successfully.');
    }
} 