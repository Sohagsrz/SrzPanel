<?php

namespace App\Jobs;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $backup;
    protected $type;
    protected $options;

    public function __construct(Backup $backup, string $type, array $options = [])
    {
        $this->backup = $backup;
        $this->type = $type;
        $this->options = $options;
    }

    public function handle(BackupService $backupService)
    {
        try {
            $result = $backupService->createBackup(
                $this->backup->name,
                $this->type,
                $this->options
            );

            if ($result['success']) {
                $this->backup->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'details' => $result['result']
                ]);
            } else {
                $this->backup->update([
                    'status' => 'failed',
                    'error' => $result['message']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Backup job failed: ' . $e->getMessage());
            $this->backup->update([
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Backup job failed: ' . $exception->getMessage());
        $this->backup->update([
            'status' => 'failed',
            'error' => $exception->getMessage()
        ]);
    }
} 