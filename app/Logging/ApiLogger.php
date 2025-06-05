<?php

namespace App\Logging;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApiLogger
{
    /**
     * Log levels
     */
    const LEVEL_EMERGENCY = 'emergency';
    const LEVEL_ALERT = 'alert';
    const LEVEL_CRITICAL = 'critical';
    const LEVEL_ERROR = 'error';
    const LEVEL_WARNING = 'warning';
    const LEVEL_NOTICE = 'notice';
    const LEVEL_INFO = 'info';
    const LEVEL_DEBUG = 'debug';

    /**
     * Log context
     *
     * @var array
     */
    protected array $context = [];

    /**
     * Add context
     *
     * @param array $context
     * @return void
     */
    public function addContext(array $context): void
    {
        $this->context = array_merge($this->context, $context);
    }

    /**
     * Clear context
     *
     * @return void
     */
    public function clearContext(): void
    {
        $this->context = [];
    }

    /**
     * Get context
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Log emergency
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_EMERGENCY, $message, $context);
    }

    /**
     * Log alert
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_ALERT, $message, $context);
    }

    /**
     * Log critical
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Log error
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log warning
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log notice
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_NOTICE, $message, $context);
    }

    /**
     * Log info
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log debug
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log with level
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $context = array_merge($this->context, $context, [
            'user_id' => Auth::id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ]);

        Log::channel('api')->$level($message, $context);
    }

    /**
     * Log API request
     *
     * @param array $request
     * @return void
     */
    public function logRequest(array $request): void
    {
        $this->info('API Request', [
            'request' => $request
        ]);
    }

    /**
     * Log API response
     *
     * @param array $response
     * @return void
     */
    public function logResponse(array $response): void
    {
        $this->info('API Response', [
            'response' => $response
        ]);
    }

    /**
     * Log API error
     *
     * @param \Throwable $e
     * @return void
     */
    public function logError(\Throwable $e): void
    {
        $this->error('API Error', [
            'error' => [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]
        ]);
    }

    /**
     * Log API performance
     *
     * @param float $startTime
     * @param float $endTime
     * @return void
     */
    public function logPerformance(float $startTime, float $endTime): void
    {
        $duration = $endTime - $startTime;

        $this->info('API Performance', [
            'duration' => $duration,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);
    }

    /**
     * Get log levels
     *
     * @return array
     */
    public function getLogLevels(): array
    {
        return [
            self::LEVEL_EMERGENCY,
            self::LEVEL_ALERT,
            self::LEVEL_CRITICAL,
            self::LEVEL_ERROR,
            self::LEVEL_WARNING,
            self::LEVEL_NOTICE,
            self::LEVEL_INFO,
            self::LEVEL_DEBUG
        ];
    }

    /**
     * Get log level name
     *
     * @param string $level
     * @return string
     */
    public function getLogLevelName(string $level): string
    {
        return strtoupper($level);
    }

    /**
     * Get log level color
     *
     * @param string $level
     * @return string
     */
    public function getLogLevelColor(string $level): string
    {
        return match ($level) {
            self::LEVEL_EMERGENCY => 'red',
            self::LEVEL_ALERT => 'red',
            self::LEVEL_CRITICAL => 'red',
            self::LEVEL_ERROR => 'red',
            self::LEVEL_WARNING => 'yellow',
            self::LEVEL_NOTICE => 'yellow',
            self::LEVEL_INFO => 'blue',
            self::LEVEL_DEBUG => 'gray',
            default => 'gray'
        };
    }

    /**
     * Get log level icon
     *
     * @param string $level
     * @return string
     */
    public function getLogLevelIcon(string $level): string
    {
        return match ($level) {
            self::LEVEL_EMERGENCY => '🚨',
            self::LEVEL_ALERT => '⚠️',
            self::LEVEL_CRITICAL => '💥',
            self::LEVEL_ERROR => '❌',
            self::LEVEL_WARNING => '⚠️',
            self::LEVEL_NOTICE => 'ℹ️',
            self::LEVEL_INFO => 'ℹ️',
            self::LEVEL_DEBUG => '🔍',
            default => 'ℹ️'
        };
    }
} 