<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ApiService
{
    /**
     * Base URL for API requests
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * API token
     *
     * @var string
     */
    protected string $token;

    /**
     * Cache duration in seconds
     *
     * @var int
     */
    protected int $cacheDuration = 3600;

    /**
     * Constructor
     *
     * @param string $baseUrl
     * @param string $token
     */
    public function __construct(string $baseUrl, string $token)
    {
        $this->baseUrl = $baseUrl;
        $this->token = $token;
    }

    /**
     * Make a GET request
     *
     * @param string $endpoint
     * @param array $params
     * @param bool $useCache
     * @return array
     */
    public function get(string $endpoint, array $params = [], bool $useCache = false): array
    {
        $cacheKey = $this->getCacheKey($endpoint, $params);

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::withToken($this->token)
                ->get($this->baseUrl . $endpoint, $params);

            $data = $response->json();

            if ($useCache) {
                Cache::put($cacheKey, $data, $this->cacheDuration);
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('API GET request failed', [
                'endpoint' => $endpoint,
                'params' => $params,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Make a POST request
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function post(string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withToken($this->token)
                ->post($this->baseUrl . $endpoint, $data);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('API POST request failed', [
                'endpoint' => $endpoint,
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Make a PUT request
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function put(string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withToken($this->token)
                ->put($this->baseUrl . $endpoint, $data);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('API PUT request failed', [
                'endpoint' => $endpoint,
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Make a DELETE request
     *
     * @param string $endpoint
     * @return array
     */
    public function delete(string $endpoint): array
    {
        try {
            $response = Http::withToken($this->token)
                ->delete($this->baseUrl . $endpoint);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('API DELETE request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get cache key for request
     *
     * @param string $endpoint
     * @param array $params
     * @return string
     */
    protected function getCacheKey(string $endpoint, array $params = []): string
    {
        return 'api:' . md5($endpoint . json_encode($params));
    }

    /**
     * Clear cache for endpoint
     *
     * @param string $endpoint
     * @param array $params
     * @return void
     */
    public function clearCache(string $endpoint, array $params = []): void
    {
        $cacheKey = $this->getCacheKey($endpoint, $params);
        Cache::forget($cacheKey);
    }

    /**
     * Set cache duration
     *
     * @param int $seconds
     * @return void
     */
    public function setCacheDuration(int $seconds): void
    {
        $this->cacheDuration = $seconds;
    }
} 