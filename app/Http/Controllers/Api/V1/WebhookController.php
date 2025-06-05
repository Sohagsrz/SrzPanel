<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebhookController extends BaseApiController
{
    protected $model = Webhook::class;
    protected $validationRules = [
        'url' => ['required', 'url'],
        'events' => ['required', 'array'],
        'events.*' => ['required', 'string', Rule::in([
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
        ])],
    ];

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules);
        
        $webhook = $this->model::create([
            'user_id' => $request->user()->id,
            'url' => $validated['url'],
            'events' => $validated['events'],
            'secret' => Str::random(32),
            'is_active' => true
        ]);

        return response()->json([
            'message' => 'Webhook created successfully',
            'data' => $webhook
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $webhook = $this->model::findOrFail($id);
        
        if ($webhook->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate($this->validationRules);
        
        $webhook->update([
            'url' => $validated['url'],
            'events' => $validated['events'],
            'is_active' => $request->input('is_active', $webhook->is_active)
        ]);

        return response()->json([
            'message' => 'Webhook updated successfully',
            'data' => $webhook
        ]);
    }

    public function destroy($id)
    {
        $webhook = $this->model::findOrFail($id);
        
        if ($webhook->user_id !== request()->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $webhook->delete();

        return response()->json([
            'message' => 'Webhook deleted successfully'
        ]);
    }

    public function regenerateSecret($id)
    {
        $webhook = $this->model::findOrFail($id);
        
        if ($webhook->user_id !== request()->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $webhook->update([
            'secret' => Str::random(32)
        ]);

        return response()->json([
            'message' => 'Webhook secret regenerated successfully',
            'data' => [
                'secret' => $webhook->secret
            ]
        ]);
    }
} 