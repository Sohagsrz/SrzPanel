<?php

namespace App\Paginators;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class ApiPaginator
{
    /**
     * Request
     *
     * @var Request
     */
    protected Request $request;

    /**
     * Default per page
     *
     * @var int
     */
    protected int $defaultPerPage = 15;

    /**
     * Maximum per page
     *
     * @var int
     */
    protected int $maxPerPage = 100;

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
     * Apply pagination
     *
     * @param Builder $query
     * @return LengthAwarePaginator
     */
    public function apply(Builder $query): LengthAwarePaginator
    {
        try {
            $page = $this->getPage();
            $perPage = $this->getPerPage();

            return $query->paginate($perPage, ['*'], 'page', $page);
        } catch (\Exception $e) {
            Log::error('API paginator application failed', [
                'error' => $e->getMessage()
            ]);

            return $query->paginate($this->defaultPerPage);
        }
    }

    /**
     * Get page from request
     *
     * @return int
     */
    protected function getPage(): int
    {
        $page = (int) $this->request->input('page', 1);
        return max(1, $page);
    }

    /**
     * Get per page from request
     *
     * @return int
     */
    protected function getPerPage(): int
    {
        $perPage = (int) $this->request->input('per_page', $this->defaultPerPage);
        return min($this->maxPerPage, max(1, $perPage));
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
     * Get default per page
     *
     * @return int
     */
    public function getDefaultPerPage(): int
    {
        return $this->defaultPerPage;
    }

    /**
     * Set default per page
     *
     * @param int $perPage
     * @return void
     */
    public function setDefaultPerPage(int $perPage): void
    {
        $this->defaultPerPage = $perPage;
    }

    /**
     * Get maximum per page
     *
     * @return int
     */
    public function getMaxPerPage(): int
    {
        return $this->maxPerPage;
    }

    /**
     * Set maximum per page
     *
     * @param int $perPage
     * @return void
     */
    public function setMaxPerPage(int $perPage): void
    {
        $this->maxPerPage = $perPage;
    }

    /**
     * Get pagination metadata
     *
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    public function getMetadata(LengthAwarePaginator $paginator): array
    {
        return [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'has_more_pages' => $paginator->hasMorePages(),
            'has_pages' => $paginator->hasPages(),
            'on_first_page' => $paginator->onFirstPage(),
            'on_last_page' => $paginator->onLastPage(),
            'previous_page_url' => $paginator->previousPageUrl(),
            'next_page_url' => $paginator->nextPageUrl(),
            'first_page_url' => $paginator->url(1),
            'last_page_url' => $paginator->url($paginator->lastPage()),
            'path' => $paginator->path(),
            'query' => $paginator->query()
        ];
    }

    /**
     * Get pagination links
     *
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    public function getLinks(LengthAwarePaginator $paginator): array
    {
        return [
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl()
        ];
    }

    /**
     * Get pagination data
     *
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    public function getData(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $paginator->items(),
            'meta' => $this->getMetadata($paginator),
            'links' => $this->getLinks($paginator)
        ];
    }
} 