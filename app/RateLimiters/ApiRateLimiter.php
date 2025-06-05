<?php

namespace App\RateLimiters;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiRateLimiter
{
    /**
     * Rate limiter
     *
     * @var RateLimiter
     */
    protected RateLimiter $limiter;

    /**
     * Request
     *
     * @var Request
     */
    protected Request $request;

    /**
     * Default max attempts
     *
     * @var int
     */
    protected int $maxAttempts = 60;

    /**
     * Default decay seconds
     *
     * @var int
     */
    protected int $decaySeconds = 60;

    /**
     * Constructor
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->limiter = app(RateLimiter::class);
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
        return $this->limiter->tooManyAttempts(
            $this->getKey($key),
            $maxAttempts ?? $this->maxAttempts
        );
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
        try {
            return $this->limiter->hit(
                $this->getKey($key),
                $decaySeconds ?? $this->decaySeconds
            );
        } catch (\Exception $e) {
            Log::error('Rate limiter hit failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get attempts
     *
     * @param string $key
     * @return int
     */
    public function attempts(string $key): int
    {
        return $this->limiter->attempts($this->getKey($key));
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
        return $this->limiter->remaining(
            $this->getKey($key),
            $maxAttempts ?? $this->maxAttempts
        );
    }

    /**
     * Get available attempts
     *
     * @param string $key
     * @param int|null $maxAttempts
     * @return int
     */
    public function availableIn(string $key): int
    {
        return $this->limiter->availableIn($this->getKey($key));
    }

    /**
     * Clear attempts
     *
     * @param string $key
     * @return void
     */
    public function clear(string $key): void
    {
        $this->limiter->clear($this->getKey($key));
    }

    /**
     * Get key
     *
     * @param string $key
     * @return string
     */
    protected function getKey(string $key): string
    {
        return 'api:' . $key . ':' . $this->request->ip();
    }

    /**
     * Get max attempts
     *
     * @return int
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Set max attempts
     *
     * @param int $maxAttempts
     * @return void
     */
    public function setMaxAttempts(int $maxAttempts): void
    {
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * Get decay seconds
     *
     * @return int
     */
    public function getDecaySeconds(): int
    {
        return $this->decaySeconds;
    }

    /**
     * Set decay seconds
     *
     * @param int $decaySeconds
     * @return void
     */
    public function setDecaySeconds(int $decaySeconds): void
    {
        $this->decaySeconds = $decaySeconds;
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
    }

    /**
     * Get rate limiter
     *
     * @return RateLimiter
     */
    public function getLimiter(): RateLimiter
    {
        return $this->limiter;
    }

    /**
     * Set rate limiter
     *
     * @param RateLimiter $limiter
     * @return void
     */
    public function setLimiter(RateLimiter $limiter): void
    {
        $this->limiter = $limiter;
    }
} 