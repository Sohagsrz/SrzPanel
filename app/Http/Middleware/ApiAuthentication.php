<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiToken;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'API token is required'
            ], 401);
        }

        // Check cache first
        $cachedToken = Cache::get('api_token:' . $token);
        if ($cachedToken) {
            $request->merge(['api_token' => $cachedToken]);
            return $next($request);
        }

        // Validate token
        $apiToken = ApiToken::where('token', $token)
            ->where('is_active', true)
            ->first();

        if (!$apiToken) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid API token'
            ], 401);
        }

        // Check IP allowlist
        if (!empty($apiToken->allowed_ips)) {
            $clientIp = $request->ip();
            $allowedIps = explode(',', $apiToken->allowed_ips);
            
            if (!in_array($clientIp, $allowedIps)) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'IP address not allowed'
                ], 403);
            }
        }

        // Check rate limit
        $key = 'api_rate_limit:' . $token;
        $requests = Cache::get($key, 0);
        
        if ($requests >= $apiToken->rate_limit) {
            return response()->json([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded'
            ], 429);
        }

        // Increment request count
        Cache::put($key, $requests + 1, 60);

        // Cache token for 5 minutes
        Cache::put('api_token:' . $token, $apiToken, 300);

        // Add token to request
        $request->merge(['api_token' => $apiToken]);

        return $next($request);
    }
} 