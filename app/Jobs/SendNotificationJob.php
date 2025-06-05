<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notification;
    protected $channels;

    public function __construct(Notification $notification, array $channels = ['in-app'])
    {
        $this->notification = $notification;
        $this->channels = $channels;
    }

    public function handle(NotificationService $notificationService)
    {
        try {
            $result = $notificationService->sendNotification(
                $this->notification->type,
                $this->notification->title,
                $this->notification->message,
                $this->notification->data,
                $this->notification->user_id,
                $this->channels
            );

            if ($result['success']) {
                $this->notification->update([
                    'status' => 'sent',
                    'sent_at' => now()
                ]);
            } else {
                $this->notification->update([
                    'status' => 'failed',
                    'error' => $result['message']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Notification job failed: ' . $e->getMessage());
            $this->notification->update([
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Notification job failed: ' . $exception->getMessage());
        $this->notification->update([
            'status' => 'failed',
            'error' => $exception->getMessage()
        ]);
    }
} 