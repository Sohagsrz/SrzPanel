<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function index()
    {
        $webhooks = auth()->user()->webhooks;
        $availableEvents = [
            'account.created',
            'account.updated',
            'account.deleted',
            'email.created',
            'email.deleted',
            'domain.created',
            'domain.deleted',
            'database.created',
            'database.deleted',
            'cron.created',
            'cron.deleted',
            'ssl.issued',
            'ssl.expired',
            'service.restarted'
        ];

        return view('user.webhooks.index', compact('webhooks', 'availableEvents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'events' => 'required|array',
            'events.*' => 'required|string|in:account.created,account.updated,account.deleted,email.created,email.deleted,domain.created,domain.deleted,database.created,database.deleted,cron.created,cron.deleted,ssl.issued,ssl.expired,service.restarted'
        ]);

        $webhook = Webhook::create([
            'user_id' => auth()->id(),
            'url' => $validated['url'],
            'events' => $validated['events'],
            'secret' => Str::random(32),
            'is_active' => true
        ]);

        return response()->json([
            'message' => 'Webhook created successfully',
            'data' => $webhook
        ]);
    }

    public function update(Request $request, Webhook $webhook)
    {
        if ($webhook->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'url' => 'required|url',
            'events' => 'required|array',
            'events.*' => 'required|string|in:account.created,account.updated,account.deleted,email.created,email.deleted,domain.created,domain.deleted,database.created,database.deleted,cron.created,cron.deleted,ssl.issued,ssl.expired,service.restarted',
            'is_active' => 'boolean'
        ]);

        $webhook->update($validated);

        return response()->json([
            'message' => 'Webhook updated successfully',
            'data' => $webhook
        ]);
    }

    public function destroy(Webhook $webhook)
    {
        if ($webhook->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $webhook->delete();

        return response()->json([
            'message' => 'Webhook deleted successfully'
        ]);
    }

    public function regenerateSecret(Webhook $webhook)
    {
        if ($webhook->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $webhook->update([
            'secret' => Str::random(32)
        ]);

        return response()->json([
            'message' => 'Webhook secret regenerated successfully',
            'secret' => $webhook->secret
        ]);
    }
} 