<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Check if the request has a valid API token
        if (!$this->validateApiToken($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API token'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check rate limiting
        if (!$this->checkRateLimit($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests'
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return $next($request);
    }

    /**
     * Validate the API token
     *
     * @param Request $request
     * @return bool
     */
    protected function validateApiToken(Request $request): bool
    {
        $token = $request->header('X-API-Token');
        
        if (!$token) {
            return false;
        }

        // TODO: Implement your token validation logic here
        // For example, check if the token exists in the database
        // and is not expired

        return true;
    }

    /**
     * Check rate limiting
     *
     * @param Request $request
     * @return bool
     */
    protected function checkRateLimit(Request $request): bool
    {
        $key = 'api:' . $request->ip();

        return RateLimiter::attempt(
            $key,
            $maxAttempts = 60, // 60 requests
            function () {
                return true;
            },
            $decaySeconds = 60 // per minute
        );
    }
} 