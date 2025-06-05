<?php

namespace App\Jobs\Batches;

use App\Jobs\UpdateJob;
use App\Models\Update;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class UpdateBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $updates;
    protected $version;

    public function __construct(array $updates, string $version)
    {
        $this->updates = $updates;
        $this->version = $version;
    }

    public function handle()
    {
        try {
            $jobs = collect($this->updates)->map(function ($update) {
                return new UpdateJob($update, $this->version);
            });

            Bus::batch($jobs)
                ->name('Update Batch: ' . $this->version)
                ->dispatch();
        } catch (\Exception $e) {
            Log::error('Update batch failed: ' . $e->getMessage());
        }
    }
} 