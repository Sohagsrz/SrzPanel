<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiTokenController extends Controller
{
    public function index()
    {
        $tokens = auth()->user()->apiTokens;
        return view('reseller.api-tokens.index', compact('tokens'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'allowed_ips' => 'nullable|string',
            'rate_limit' => 'required|integer|min:1|max:1000',
            'expires_at' => 'nullable|date|after:now'
        ]);

        $token = ApiToken::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'token' => Str::random(64),
            'allowed_ips' => $validated['allowed_ips'],
            'rate_limit' => $validated['rate_limit'],
            'expires_at' => $validated['expires_at'],
            'is_active' => true
        ]);

        return response()->json([
            'message' => 'API token created successfully',
            'token' => $token->token
        ]);
    }

    public function show(ApiToken $token)
    {
        if ($token->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'token' => $token->token
        ]);
    }

    public function update(Request $request, ApiToken $token)
    {
        if ($token->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'allowed_ips' => 'nullable|string',
            'rate_limit' => 'required|integer|min:1|max:1000',
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'boolean'
        ]);

        $token->update($validated);

        return response()->json([
            'message' => 'API token updated successfully',
            'data' => $token
        ]);
    }

    public function destroy(ApiToken $token)
    {
        if ($token->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $token->delete();

        return response()->json([
            'message' => 'API token deleted successfully'
        ]);
    }
} 