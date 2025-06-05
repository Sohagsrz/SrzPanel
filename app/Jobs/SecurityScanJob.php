<?php

namespace App\Jobs;

use App\Models\Security;
use App\Services\SecurityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SecurityScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $scanType;
    protected $options;

    public function __construct(string $scanType = 'full', array $options = [])
    {
        $this->scanType = $scanType;
        $this->options = $options;
    }

    public function handle(SecurityService $securityService)
    {
        try {
            $result = $securityService->scanSystem($this->scanType, $this->options);

            if ($result['success']) {
                Security::create([
                    'type' => $this->scanType,
                    'status' => 'completed',
                    'details' => $result['result'],
                    'severity' => $result['severity'] ?? 'low'
                ]);
            } else {
                Security::create([
                    'type' => $this->scanType,
                    'status' => 'failed',
                    'error' => $result['message'],
                    'severity' => 'high'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Security scan job failed: ' . $e->getMessage());
            Security::create([
                'type' => $this->scanType,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'severity' => 'high'
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Security scan job failed: ' . $exception->getMessage());
        Security::create([
            'type' => $this->scanType,
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'severity' => 'high'
        ]);
    }
} 