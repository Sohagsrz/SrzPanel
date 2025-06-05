<?php

namespace App\Throttlers;

use App\RateLimiters\ApiRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ApiThrottler
{
    /**
     * Rate limiter
     *
     * @var ApiRateLimiter
     */
    protected ApiRateLimiter $limiter;

    /**
     * Request
     *
     * @var Request
     */
    protected Request $request;

    /**
     * Constructor
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->limiter = new ApiRateLimiter($request);
    }

    /**
     * Check if too many attempts
     *
     * @param string $key
     * @param int|null $maxAttempts
     * @param int|null $decaySeconds
     * @return bool
     */
    public function tooManyAttempts(string $key, ?int $maxAttempts = null, ?int $decaySeconds = null): bool
    {
        return $this->limiter->tooManyAttempts($key, $maxAttempts, $decaySeconds);
    }

    /**
     * Hit the rate limiter
     *
     * @param string $key
     * @param int|null $maxAttempts
     * @param int|null $decaySeconds
     * @return bool
     */
    public function hit(string $key, ?int $maxAttempts = null, ?int $decaySeconds = null): bool
    {
        return $this->limiter->hit($key, $maxAttempts, $decaySeconds);
    }

    /**
     * Get attempts
     *
     * @param string $key
     * @return int
     */
    public function attempts(string $key): int
    {
        return $this->limiter->attempts($key);
    }

    /**
     * Get remaining attempts
     *
     * @param string $key
     * @param int|null $maxAttempts
     * @return int
     */
    public function remaining(string $key, ?int $maxAttempts = null): int
    {
        return $this->limiter->remaining($key, $maxAttempts);
    }

    /**
     * Get available attempts
     *
     * @param string $key
     * @return int
     */
    public function availableIn(string $key): int
    {
        return $this->limiter->availableIn($key);
    }

    /**
     * Clear attempts
     *
     * @param string $key
     * @return void
     */
    public function clear(string $key): void
    {
        $this->limiter->clear($key);
    }

    /**
     * Get too many attempts response
     *
     * @param string $key
     * @param int|null $maxAttempts
     * @param int|null $decaySeconds
     * @return Response
     */
    public function getTooManyAttemptsResponse(string $key, ?int $maxAttempts = null, ?int $decaySeconds = null): Response
    {
        $maxAttempts = $maxAttempts ?? $this->limiter->getMaxAttempts();
        $retryAfter = $this->availableIn($key);

        return response()->json([
            'success' => false,
            'message' => 'Too many attempts',
            'errors' => [
                'message' => 'Too many attempts, please try again later',
                'max_attempts' => $maxAttempts,
                'retry_after' => $retryAfter
            ]
        ], 429, [
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => time() + $retryAfter
        ]);
    }

    /**
     * Get rate limiter
     *
     * @return ApiRateLimiter
     */
    public function getLimiter(): ApiRateLimiter
    {
        return $this->limiter;
    }

    /**
     * Set rate limiter
     *
     * @param ApiRateLimiter $limiter
     * @return void
     */
    public function setLimiter(ApiRateLimiter $limiter): void
    {
        $this->limiter = $limiter;
    }

    /**
     * Get request
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Set request
     *
     * @param Request $request
     * @return void
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
        $this->limiter->setRequest($request);
    }
} 