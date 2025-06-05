<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SecurityScanJob;
use App\Jobs\Batches\SecurityScanBatch;
use App\Models\Security;
use App\Services\SecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecurityController extends Controller
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    public function index()
    {
        try {
            $security = Security::latest()->paginate(10);
            return view('admin.security.index', compact('security'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch security records: ' . $e->getMessage());
            return back()->with('error', 'Failed to fetch security records');
        }
    }

    public function scan(Request $request)
    {
        try {
            $scanType = $request->type ?? 'full';
            $options = $request->options ?? [];

            // Dispatch security scan job
            SecurityScanJob::dispatch($scanType, $options)
                ->onQueue('security');

            return response()->json([
                'success' => true,
                'message' => 'Security scan job queued successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to start security scan: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to start security scan'], 500);
        }
    }

    public function scanBatch(Request $request)
    {
        try {
            $scanTypes = $request->types ?? ['full', 'quick', 'custom'];
            $options = $request->options ?? [];

            // Dispatch batch security scan job
            SecurityScanBatch::dispatch($scanTypes, $options)
                ->onQueue('security');

            return response()->json([
                'success' => true,
                'message' => 'Batch security scan jobs queued successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to start batch security scan: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to start batch security scan'], 500);
        }
    }

    public function getHistory()
    {
        try {
            $history = $this->securityService->getScanHistory();
            return response()->json($history);
        } catch (\Exception $e) {
            Log::error('Failed to fetch scan history: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch scan history'], 500);
        }
    }

    public function getThreatDetails($id)
    {
        try {
            $threat = Security::findOrFail($id);
            $details = $this->securityService->getThreatDetails($threat);
            return response()->json($details);
        } catch (\Exception $e) {
            Log::error('Failed to fetch threat details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch threat details'], 500);
        }
    }

    public function getStats()
    {
        try {
            $stats = $this->securityService->getSecurityStats();
            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Failed to fetch security stats: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch security stats'], 500);
        }
    }
} 