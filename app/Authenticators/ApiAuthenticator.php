<?php

namespace App\Authenticators;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ApiAuthenticator
{
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
    }

    /**
     * Authenticate
     *
     * @return bool
     */
    public function authenticate(): bool
    {
        try {
            $token = $this->getToken();

            if (!$token) {
                return false;
            }

            return Auth::guard('api')->check();
        } catch (\Exception $e) {
            Log::error('API authentication failed', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get token
     *
     * @return string|null
     */
    protected function getToken(): ?string
    {
        $token = $this->request->bearerToken();

        if (!$token) {
            $token = $this->request->header('X-API-Token');
        }

        if (!$token) {
            $token = $this->request->input('api_token');
        }

        return $token;
    }

    /**
     * Get user
     *
     * @return mixed
     */
    public function getUser()
    {
        return Auth::guard('api')->user();
    }

    /**
     * Get user ID
     *
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return Auth::guard('api')->id();
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return Auth::guard('api')->check();
    }

    /**
     * Check if user is guest
     *
     * @return bool
     */
    public function isGuest(): bool
    {
        return Auth::guard('api')->guest();
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
     * Get authentication guard
     *
     * @return string
     */
    public function getGuard(): string
    {
        return 'api';
    }

    /**
     * Get authentication driver
     *
     * @return string
     */
    public function getDriver(): string
    {
        return 'token';
    }

    /**
     * Get authentication provider
     *
     * @return string
     */
    public function getProvider(): string
    {
        return 'users';
    }

    /**
     * Get authentication model
     *
     * @return string
     */
    public function getModel(): string
    {
        return \App\Models\User::class;
    }

    /**
     * Get authentication table
     *
     * @return string
     */
    public function getTable(): string
    {
        return 'users';
    }

    /**
     * Get authentication key
     *
     * @return string
     */
    public function getKey(): string
    {
        return 'id';
    }

    /**
     * Get authentication token key
     *
     * @return string
     */
    public function getTokenKey(): string
    {
        return 'api_token';
    }

    /**
     * Get authentication token length
     *
     * @return int
     */
    public function getTokenLength(): int
    {
        return 60;
    }

    /**
     * Get authentication token expiry
     *
     * @return int
     */
    public function getTokenExpiry(): int
    {
        return 60 * 24 * 7; // 7 days
    }

    /**
     * Get authentication token header
     *
     * @return string
     */
    public function getTokenHeader(): string
    {
        return 'Authorization';
    }

    /**
     * Get authentication token prefix
     *
     * @return string
     */
    public function getTokenPrefix(): string
    {
        return 'Bearer';
    }

    /**
     * Get authentication token separator
     *
     * @return string
     */
    public function getTokenSeparator(): string
    {
        return ' ';
    }

    /**
     * Get authentication token format
     *
     * @return string
     */
    public function getTokenFormat(): string
    {
        return '%s%s%s';
    }

    /**
     * Get authentication token
     *
     * @return string
     */
    public function getFormattedToken(): string
    {
        return sprintf(
            $this->getTokenFormat(),
            $this->getTokenPrefix(),
            $this->getTokenSeparator(),
            $this->getToken()
        );
    }
} 