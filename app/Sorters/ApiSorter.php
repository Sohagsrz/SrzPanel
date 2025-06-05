<?php

namespace App\Sorters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiSorter
{
    /**
     * Request
     *
     * @var Request
     */
    protected Request $request;

    /**
     * Sort rules
     *
     * @var array
     */
    protected array $rules = [];

    /**
     * Default sort field
     *
     * @var string
     */
    protected string $defaultField = 'created_at';

    /**
     * Default sort direction
     *
     * @var string
     */
    protected string $defaultDirection = 'desc';

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
     * Apply sorting
     *
     * @param Builder $query
     * @return Builder
     */
    public function apply(Builder $query): Builder
    {
        try {
            $field = $this->getSortField();
            $direction = $this->getSortDirection();

            if (isset($this->rules[$field])) {
                $this->rules[$field]($query, $direction);
            } else {
                $query->orderBy($field, $direction);
            }

            return $query;
        } catch (\Exception $e) {
            Log::error('API sorter application failed', [
                'rules' => $this->rules,
                'error' => $e->getMessage()
            ]);

            return $query;
        }
    }

    /**
     * Get sort field from request
     *
     * @return string
     */
    protected function getSortField(): string
    {
        return $this->request->input('sort_by', $this->defaultField);
    }

    /**
     * Get sort direction from request
     *
     * @return string
     */
    protected function getSortDirection(): string
    {
        $direction = strtolower($this->request->input('sort_direction', $this->defaultDirection));
        return in_array($direction, ['asc', 'desc']) ? $direction : $this->defaultDirection;
    }

    /**
     * Add sort rule
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
     * Remove sort rule
     *
     * @param string $field
     * @return void
     */
    public function removeRule(string $field): void
    {
        unset($this->rules[$field]);
    }

    /**
     * Get sort rules
     *
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Set sort rules
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
     * Get default sort field
     *
     * @return string
     */
    public function getDefaultField(): string
    {
        return $this->defaultField;
    }

    /**
     * Set default sort field
     *
     * @param string $field
     * @return void
     */
    public function setDefaultField(string $field): void
    {
        $this->defaultField = $field;
    }

    /**
     * Get default sort direction
     *
     * @return string
     */
    public function getDefaultDirection(): string
    {
        return $this->defaultDirection;
    }

    /**
     * Set default sort direction
     *
     * @param string $direction
     * @return void
     */
    public function setDefaultDirection(string $direction): void
    {
        $this->defaultDirection = $direction;
    }

    /**
     * Sort by multiple fields
     *
     * @param Builder $query
     * @param array $fields
     * @param string $direction
     * @return Builder
     */
    protected function sortByMultiple(Builder $query, array $fields, string $direction): Builder
    {
        foreach ($fields as $field) {
            $query->orderBy($field, $direction);
        }

        return $query;
    }

    /**
     * Sort by relationship
     *
     * @param Builder $query
     * @param string $relation
     * @param string $field
     * @param string $direction
     * @return Builder
     */
    protected function sortByRelation(Builder $query, string $relation, string $field, string $direction): Builder
    {
        return $query->join($relation, $relation . '.id', '=', $query->getModel()->getTable() . '.' . $relation . '_id')
            ->orderBy($relation . '.' . $field, $direction)
            ->select($query->getModel()->getTable() . '.*');
    }

    /**
     * Sort by raw SQL
     *
     * @param Builder $query
     * @param string $sql
     * @param string $direction
     * @return Builder
     */
    protected function sortByRaw(Builder $query, string $sql, string $direction): Builder
    {
        return $query->orderByRaw($sql . ' ' . $direction);
    }
} 