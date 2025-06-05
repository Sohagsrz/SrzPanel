<?php

namespace App\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

abstract class ApiCache
{
    /**
     * Cache duration in seconds
     *
     * @var int
     */
    protected int $duration = 3600;

    /**
     * Cache prefix
     *
     * @var string
     */
    protected string $prefix = 'api:';

    /**
     * Get cache key
     *
     * @param string $key
     * @return string
     */
    protected function getCacheKey(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * Get from cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        try {
            return Cache::get($this->getCacheKey($key), $default);
        } catch (\Exception $e) {
            Log::error('Cache get failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return $default;
        }
    }

    /**
     * Put in cache
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $duration
     * @return bool
     */
    public function put(string $key, $value, ?int $duration = null): bool
    {
        try {
            return Cache::put(
                $this->getCacheKey($key),
                $value,
                $duration ?? $this->duration
            );
        } catch (\Exception $e) {
            Log::error('Cache put failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Add to cache if not exists
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $duration
     * @return bool
     */
    public function add(string $key, $value, ?int $duration = null): bool
    {
        try {
            return Cache::add(
                $this->getCacheKey($key),
                $value,
                $duration ?? $this->duration
            );
        } catch (\Exception $e) {
            Log::error('Cache add failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Remove from cache
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        try {
            return Cache::forget($this->getCacheKey($key));
        } catch (\Exception $e) {
            Log::error('Cache forget failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Check if exists in cache
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        try {
            return Cache::has($this->getCacheKey($key));
        } catch (\Exception $e) {
            Log::error('Cache has failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get or put in cache
     *
     * @param string $key
     * @param callable $callback
     * @param int|null $duration
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $duration = null)
    {
        try {
            return Cache::remember(
                $this->getCacheKey($key),
                $duration ?? $this->duration,
                $callback
            );
        } catch (\Exception $e) {
            Log::error('Cache remember failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return $callback();
        }
    }

    /**
     * Get or put in cache forever
     *
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public function rememberForever(string $key, callable $callback)
    {
        try {
            return Cache::rememberForever(
                $this->getCacheKey($key),
                $callback
            );
        } catch (\Exception $e) {
            Log::error('Cache rememberForever failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return $callback();
        }
    }

    /**
     * Increment cache value
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function increment(string $key, int $value = 1)
    {
        try {
            return Cache::increment($this->getCacheKey($key), $value);
        } catch (\Exception $e) {
            Log::error('Cache increment failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Decrement cache value
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function decrement(string $key, int $value = 1)
    {
        try {
            return Cache::decrement($this->getCacheKey($key), $value);
        } catch (\Exception $e) {
            Log::error('Cache decrement failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Clear cache
     *
     * @return bool
     */
    public function clear(): bool
    {
        try {
            return Cache::flush();
        } catch (\Exception $e) {
            Log::error('Cache clear failed', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get cache duration
     *
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * Set cache duration
     *
     * @param int $duration
     * @return void
     */
    public function setDuration(int $duration): void
    {
        $this->duration = $duration;
    }

    /**
     * Get cache prefix
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Set cache prefix
     *
     * @param string $prefix
     * @return void
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }
} 