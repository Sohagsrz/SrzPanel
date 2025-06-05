<?php

namespace App\Jobs\Batches;

use App\Jobs\BackupJob;
use App\Models\Backup;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class BackupBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $backups;
    protected $type;
    protected $options;

    public function __construct(array $backups, string $type, array $options = [])
    {
        $this->backups = $backups;
        $this->type = $type;
        $this->options = $options;
    }

    public function handle()
    {
        try {
            $jobs = collect($this->backups)->map(function ($backup) {
                return new BackupJob($backup, $this->type, $this->options);
            });

            Bus::batch($jobs)
                ->name('Backup Batch: ' . $this->type)
                ->dispatch();
        } catch (\Exception $e) {
            Log::error('Backup batch failed: ' . $e->getMessage());
        }
    }
} 