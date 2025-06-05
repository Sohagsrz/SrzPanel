<?php

namespace App\Transformers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class ApiTransformer
{
    /**
     * Transform a model
     *
     * @param Model $model
     * @return array
     */
    abstract public function transform(Model $model): array;

    /**
     * Transform a collection of models
     *
     * @param Collection $collection
     * @return array
     */
    public function transformCollection(Collection $collection): array
    {
        return $collection->map(function ($model) {
            return $this->transform($model);
        })->toArray();
    }

    /**
     * Transform a paginated collection
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @return array
     */
    public function transformPaginated(\Illuminate\Pagination\LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $this->transformCollection($paginator->items()),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem()
            ]
        ];
    }

    /**
     * Format date
     *
     * @param mixed $date
     * @return string|null
     */
    protected function formatDate($date): ?string
    {
        if (!$date) {
            return null;
        }

        return $date->toISOString();
    }

    /**
     * Format boolean
     *
     * @param mixed $value
     * @return bool
     */
    protected function formatBoolean($value): bool
    {
        return (bool) $value;
    }

    /**
     * Format number
     *
     * @param mixed $value
     * @param int $decimals
     * @return float
     */
    protected function formatNumber($value, int $decimals = 2): float
    {
        return round((float) $value, $decimals);
    }

    /**
     * Format currency
     *
     * @param mixed $value
     * @param string $currency
     * @return string
     */
    protected function formatCurrency($value, string $currency = 'USD'): string
    {
        return number_format((float) $value, 2) . ' ' . $currency;
    }

    /**
     * Format percentage
     *
     * @param mixed $value
     * @param int $decimals
     * @return string
     */
    protected function formatPercentage($value, int $decimals = 2): string
    {
        return number_format((float) $value, $decimals) . '%';
    }

    /**
     * Format file size
     *
     * @param mixed $bytes
     * @return string
     */
    protected function formatFileSize($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Format duration
     *
     * @param mixed $seconds
     * @return string
     */
    protected function formatDuration($seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * Format phone number
     *
     * @param mixed $number
     * @return string
     */
    protected function formatPhoneNumber($number): string
    {
        return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $number);
    }

    /**
     * Format credit card number
     *
     * @param mixed $number
     * @return string
     */
    protected function formatCreditCardNumber($number): string
    {
        return substr($number, 0, 4) . ' **** **** ' . substr($number, -4);
    }

    /**
     * Format IP address
     *
     * @param mixed $ip
     * @return string
     */
    protected function formatIpAddress($ip): string
    {
        return inet_ntop(inet_pton($ip));
    }

    /**
     * Format MAC address
     *
     * @param mixed $mac
     * @return string
     */
    protected function formatMacAddress($mac): string
    {
        return strtoupper(preg_replace('/([0-9a-f]{2})/i', '$1:', $mac));
    }
} 