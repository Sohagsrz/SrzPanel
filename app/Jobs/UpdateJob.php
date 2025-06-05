<?php

namespace App\Jobs;

use App\Models\Update;
use App\Services\UpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $update;
    protected $version;

    public function __construct(Update $update, string $version)
    {
        $this->update = $update;
        $this->version = $version;
    }

    public function handle(UpdateService $updateService)
    {
        try {
            $result = $updateService->installUpdates($this->version);

            if ($result['success']) {
                $this->update->update([
                    'status' => 'completed',
                    'installed_at' => now(),
                    'details' => $result['result']
                ]);
            } else {
                $this->update->update([
                    'status' => 'failed',
                    'error' => $result['message']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Update job failed: ' . $e->getMessage());
            $this->update->update([
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Update job failed: ' . $exception->getMessage());
        $this->update->update([
            'status' => 'failed',
            'error' => $exception->getMessage()
        ]);
    }
} 