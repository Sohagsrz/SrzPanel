<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FirewallService;
use Illuminate\Http\Request;

class FirewallController extends Controller
{
    protected $firewall;
    public function __construct(FirewallService $firewall)
    {
        $this->firewall = $firewall;
    }

    public function index()
    {
        $rules = $this->firewall->listRules();
        return view('admin.firewall.index', compact('rules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'port' => 'required|integer',
            'protocol' => 'required|in:tcp,udp',
            'action' => 'required|in:allow,deny',
        ]);
        $this->firewall->addRule($request->port, $request->protocol, $request->action);
        return redirect()->route('admin.firewall.index')->with('success', 'Rule added.');
    }

    public function destroy($id)
    {
        $this->firewall->deleteRule($id);
        return redirect()->route('admin.firewall.index')->with('success', 'Rule deleted.');
    }
} 