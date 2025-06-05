<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class ApiFilter
{
    /**
     * Filter rules
     *
     * @var array
     */
    protected array $rules = [];

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
     * Apply filters
     *
     * @param Builder $query
     * @return Builder
     */
    public function apply(Builder $query): Builder
    {
        foreach ($this->getFilters() as $field => $value) {
            if (isset($this->rules[$field])) {
                $this->rules[$field]($query, $value);
            }
        }

        return $query;
    }

    /**
     * Get filters from request
     *
     * @return array
     */
    protected function getFilters(): array
    {
        return $this->request->only(array_keys($this->rules));
    }

    /**
     * Add filter rule
     *
     * @param string $field
     * @param callable $rule
     * @return void
     */
    public function addRule(string $field, callable $rule): void
    {
        $this->rules[$field] = $rule;
    }

    /**
     * Remove filter rule
     *
     * @param string $field
     * @return void
     */
    public function removeRule(string $field): void
    {
        unset($this->rules[$field]);
    }

    /**
     * Get filter rules
     *
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Set filter rules
     *
     * @param array $rules
     * @return void
     */
    public function setRules(array $rules): void
    {
        $this->rules = $rules;
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
     * Filter by date range
     *
     * @param Builder $query
     * @param string $field
     * @param array $value
     * @return Builder
     */
    protected function filterByDateRange(Builder $query, string $field, array $value): Builder
    {
        if (isset($value['from'])) {
            $query->where($field, '>=', $value['from']);
        }

        if (isset($value['to'])) {
            $query->where($field, '<=', $value['to']);
        }

        return $query;
    }

    /**
     * Filter by number range
     *
     * @param Builder $query
     * @param string $field
     * @param array $value
     * @return Builder
     */
    protected function filterByNumberRange(Builder $query, string $field, array $value): Builder
    {
        if (isset($value['min'])) {
            $query->where($field, '>=', $value['min']);
        }

        if (isset($value['max'])) {
            $query->where($field, '<=', $value['max']);
        }

        return $query;
    }

    /**
     * Filter by search
     *
     * @param Builder $query
     * @param string $field
     * @param string $value
     * @return Builder
     */
    protected function filterBySearch(Builder $query, string $field, string $value): Builder
    {
        return $query->where($field, 'LIKE', '%' . $value . '%');
    }

    /**
     * Filter by exact match
     *
     * @param Builder $query
     * @param string $field
     * @param mixed $value
     * @return Builder
     */
    protected function filterByExact(Builder $query, string $field, $value): Builder
    {
        return $query->where($field, $value);
    }

    /**
     * Filter by boolean
     *
     * @param Builder $query
     * @param string $field
     * @param bool $value
     * @return Builder
     */
    protected function filterByBoolean(Builder $query, string $field, bool $value): Builder
    {
        return $query->where($field, $value);
    }

    /**
     * Filter by array
     *
     * @param Builder $query
     * @param string $field
     * @param array $value
     * @return Builder
     */
    protected function filterByArray(Builder $query, string $field, array $value): Builder
    {
        return $query->whereIn($field, $value);
    }

    /**
     * Filter by null
     *
     * @param Builder $query
     * @param string $field
     * @param bool $value
     * @return Builder
     */
    protected function filterByNull(Builder $query, string $field, bool $value): Builder
    {
        return $value ? $query->whereNull($field) : $query->whereNotNull($field);
    }
} 