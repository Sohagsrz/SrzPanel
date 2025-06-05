<?php

namespace App\Jobs\Batches;

use App\Jobs\SecurityScanJob;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class SecurityScanBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $scanTypes;
    protected $options;

    public function __construct(array $scanTypes, array $options = [])
    {
        $this->scanTypes = $scanTypes;
        $this->options = $options;
    }

    public function handle()
    {
        try {
            $jobs = collect($this->scanTypes)->map(function ($scanType) {
                return new SecurityScanJob($scanType, $this->options);
            });

            Bus::batch($jobs)
                ->name('Security Scan Batch')
                ->dispatch();
        } catch (\Exception $e) {
            Log::error('Security scan batch failed: ' . $e->getMessage());
        }
    }
} 