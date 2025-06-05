<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\BackupJob;
use App\Jobs\Batches\BackupBatch;
use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    public function index()
    {
        try {
            $backups = Backup::latest()->paginate(10);
            return view('admin.backups.index', compact('backups'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch backups: ' . $e->getMessage());
            return back()->with('error', 'Failed to fetch backups');
        }
    }

    public function create(Request $request)
    {
        try {
            $backup = Backup::create([
                'name' => $request->name,
                'type' => $request->type,
                'status' => 'pending',
                'details' => $request->details ?? []
            ]);

            // Dispatch single backup job
            BackupJob::dispatch($backup, $request->type, $request->options ?? [])
                ->onQueue('backups');

            return response()->json([
                'success' => true,
                'message' => 'Backup job queued successfully',
                'backup' => $backup
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create backup: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create backup'], 500);
        }
    }

    public function createBatch(Request $request)
    {
        try {
            $backups = collect($request->backups)->map(function ($backupData) {
                return Backup::create([
                    'name' => $backupData['name'],
                    'type' => $backupData['type'],
                    'status' => 'pending',
                    'details' => $backupData['details'] ?? []
                ]);
            });

            // Dispatch batch backup job
            BackupBatch::dispatch($backups->all(), $request->type, $request->options ?? [])
                ->onQueue('backups');

            return response()->json([
                'success' => true,
                'message' => 'Batch backup jobs queued successfully',
                'backups' => $backups
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create batch backup: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create batch backup'], 500);
        }
    }

    public function restore($id)
    {
        try {
            $backup = Backup::findOrFail($id);
            
            // Dispatch restore job
            BackupJob::dispatch($backup, 'restore')
                ->onQueue('backups');

            return response()->json([
                'success' => true,
                'message' => 'Restore job queued successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to restore backup: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to restore backup'], 500);
        }
    }

    public function delete($id)
    {
        try {
            $backup = Backup::findOrFail($id);
            $backup->delete();

            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete backup: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete backup'], 500);
        }
    }

    public function getStatus($id)
    {
        try {
            $backup = Backup::findOrFail($id);
            return response()->json([
                'success' => true,
                'status' => $backup->status,
                'details' => $backup->details
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get backup status: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get backup status'], 500);
        }
    }
} 