<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\UpdateJob;
use App\Jobs\Batches\UpdateBatch;
use App\Models\Update;
use App\Services\UpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UpdateController extends Controller
{
    protected $updateService;

    public function __construct(UpdateService $updateService)
    {
        $this->updateService = $updateService;
    }

    public function index()
    {
        try {
            $updates = Update::latest()->paginate(10);
            return view('admin.updates.index', compact('updates'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch updates: ' . $e->getMessage());
            return back()->with('error', 'Failed to fetch updates');
        }
    }

    public function checkForUpdates()
    {
        try {
            $result = $this->updateService->checkForUpdates();
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to check for updates: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to check for updates'], 500);
        }
    }

    public function installUpdate(Request $request)
    {
        try {
            $update = Update::create([
                'version' => $request->version,
                'type' => $request->type ?? 'system',
                'status' => 'pending',
                'description' => $request->description
            ]);

            // Dispatch update job
            UpdateJob::dispatch($update, $request->version)
                ->onQueue('updates');

            return response()->json([
                'success' => true,
                'message' => 'Update job queued successfully',
                'update' => $update
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to install update: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to install update'], 500);
        }
    }

    public function installBatch(Request $request)
    {
        try {
            $updates = collect($request->updates)->map(function ($updateData) {
                return Update::create([
                    'version' => $updateData['version'],
                    'type' => $updateData['type'] ?? 'system',
                    'status' => 'pending',
                    'description' => $updateData['description']
                ]);
            });

            // Dispatch batch update job
            UpdateBatch::dispatch($updates->all(), $request->version)
                ->onQueue('updates');

            return response()->json([
                'success' => true,
                'message' => 'Batch update jobs queued successfully',
                'updates' => $updates
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to install batch updates: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to install batch updates'], 500);
        }
    }

    public function rollback(Request $request)
    {
        try {
            $update = Update::where('version', $request->version)->firstOrFail();
            
            // Dispatch rollback job
            UpdateJob::dispatch($update, $request->version)
                ->onQueue('updates');

            return response()->json([
                'success' => true,
                'message' => 'Rollback job queued successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to rollback update: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to rollback update'], 500);
        }
    }

    public function getStatus($id)
    {
        try {
            $update = Update::findOrFail($id);
            return response()->json([
                'success' => true,
                'status' => $update->status,
                'details' => $update->details
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get update status: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get update status'], 500);
        }
    }
} 