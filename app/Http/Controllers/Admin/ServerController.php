<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\ServerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServerController extends Controller
{
    protected $serverService;

    public function __construct(ServerService $serverService)
    {
        $this->serverService = $serverService;
    }

    public function index()
    {
        try {
            $servers = Server::latest()->paginate(10);
            return view('admin.servers.index', compact('servers'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch servers: ' . $e->getMessage());
            return back()->with('error', 'Failed to fetch servers');
        }
    }

    public function store(Request $request)
    {
        try {
            $result = $this->serverService->createServer(
                $request->name,
                $request->hostname,
                $request->ip_address,
                $request->type,
                $request->os,
                $request->details ?? []
            );
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to create server: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create server'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $result = $this->serverService->updateServer(
                $id,
                $request->name,
                $request->hostname,
                $request->ip_address,
                $request->type,
                $request->os,
                $request->details ?? []
            );
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to update server: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update server'], 500);
        }
    }

    public function delete($id)
    {
        try {
            $result = $this->serverService->deleteServer($id);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to delete server: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete server'], 500);
        }
    }

    public function getStats($id)
    {
        try {
            $stats = $this->serverService->getServerStats($id);
            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Failed to fetch server stats: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server stats'], 500);
        }
    }

    public function checkStatus($id)
    {
        try {
            $status = $this->serverService->checkServerStatus($id);
            return response()->json($status);
        } catch (\Exception $e) {
            Log::error('Failed to check server status: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to check server status'], 500);
        }
    }
} 