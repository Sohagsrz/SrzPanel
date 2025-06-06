<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DNSTemplate;
use App\Models\Domain;
use Illuminate\Http\Request;

class DNSTemplateController extends Controller
{
    public function index()
    {
        $templates = DNSTemplate::all();
        return view('admin.dns-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.dns-templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'records' => 'required|array',
            'records.*.type' => 'required|string|in:A,AAAA,CNAME,MX,TXT,NS,SRV',
            'records.*.name' => 'required|string|max:255',
            'records.*.content' => 'required|string|max:255',
            'records.*.ttl' => 'required|integer|min:60',
            'records.*.priority' => 'nullable|integer|min:0',
        ]);

        $template = DNSTemplate::create([
            'name' => $validated['name'],
            'records' => $validated['records'],
        ]);

        return redirect()->route('admin.dns-templates.index')
            ->with('success', 'DNS template created successfully.');
    }

    public function show(DNSTemplate $template)
    {
        return view('admin.dns-templates.show', compact('template'));
    }

    public function edit(DNSTemplate $template)
    {
        return view('admin.dns-templates.edit', compact('template'));
    }

    public function update(Request $request, DNSTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'records' => 'required|array',
            'records.*.type' => 'required|string|in:A,AAAA,CNAME,MX,TXT,NS,SRV',
            'records.*.name' => 'required|string|max:255',
            'records.*.content' => 'required|string|max:255',
            'records.*.ttl' => 'required|integer|min:60',
            'records.*.priority' => 'nullable|integer|min:0',
        ]);

        $template->update([
            'name' => $validated['name'],
            'records' => $validated['records'],
        ]);

        return redirect()->route('admin.dns-templates.index')
            ->with('success', 'DNS template updated successfully.');
    }

    public function destroy(DNSTemplate $template)
    {
        $template->delete();
        return redirect()->route('admin.dns-templates.index')
            ->with('success', 'DNS template deleted successfully.');
    }

    public function apply(DNSTemplate $template, Domain $domain)
    {
        // Apply DNS records from template to domain
        foreach ($template->records as $record) {
            $domain->dnsRecords()->create([
                'type' => $record['type'],
                'name' => $record['name'],
                'content' => $record['content'],
                'ttl' => $record['ttl'],
                'priority' => $record['priority'] ?? null,
            ]);
        }

        return redirect()->route('admin.domains.show', $domain)
            ->with('success', 'DNS template applied successfully.');
    }
} 