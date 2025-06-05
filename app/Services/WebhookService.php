<?php

namespace App\Services;

use App\Models\Webhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public function trigger(string $event, array $payload): void
    {
        $webhooks = Webhook::where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();

        foreach ($webhooks as $webhook) {
            $this->dispatchWebhook($webhook, $event, $payload);
        }
    }

    protected function dispatchWebhook(Webhook $webhook, string $event, array $payload): void
    {
        try {
            $signature = $this->generateSignature($webhook, $payload);
            
            $response = Http::withHeaders([
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Event' => $event
            ])->post($webhook->url, $payload);

            if ($response->successful()) {
                $webhook->resetFailureCount();
            } else {
                $webhook->incrementFailureCount();
                Log::error('Webhook failed', [
                    'webhook_id' => $webhook->id,
                    'event' => $event,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            $webhook->incrementFailureCount();
            Log::error('Webhook exception', [
                'webhook_id' => $webhook->id,
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function generateSignature(Webhook $webhook, array $payload): string
    {
        $payloadString = json_encode($payload);
        return hash_hmac('sha256', $payloadString, $webhook->secret);
    }
} 