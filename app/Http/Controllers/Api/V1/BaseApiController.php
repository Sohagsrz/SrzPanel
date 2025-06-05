<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BaseApiController extends Controller
{
    protected $model;
    protected $resource;
    protected $collection;
    protected $validationRules = [];
    protected $searchableFields = [];
    protected $sortableFields = [];
    protected $defaultSort = 'created_at';
    protected $defaultOrder = 'desc';
    protected $perPage = 15;
    protected $maxPerPage = 100;

    public function index(Request $request): JsonResponse
    {
        try {
            $query = $this->model::query();

            // Apply search filters
            if ($request->has('search') && !empty($this->searchableFields)) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    foreach ($this->searchableFields as $field) {
                        $q->orWhere($field, 'LIKE', "%{$search}%");
                    }
                });
            }

            // Apply sorting
            if ($request->has('sort_by') && in_array($request->get('sort_by'), $this->sortableFields)) {
                $sortField = $request->get('sort_by');
                $sortOrder = $request->get('sort_order', $this->defaultOrder);
                $query->orderBy($sortField, $sortOrder);
            } else {
                $query->orderBy($this->defaultSort, $this->defaultOrder);
            }

            // Apply pagination
            $perPage = min($request->get('per_page', $this->perPage), $this->maxPerPage);
            $items = $query->paginate($perPage);

            return response()->json([
                'data' => new $this->collection($items),
                'meta' => [
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: ' . $e->getMessage());
            return $this->errorResponse('Failed to fetch records', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate($this->validationRules);
            $item = $this->model::create($validated);
            
            return response()->json([
                'message' => 'Record created successfully',
                'data' => new $this->resource($item)
            ], 201);
        } catch (\Exception $e) {
            Log::error('API Error: ' . $e->getMessage());
            return $this->errorResponse('Failed to create record', 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $item = $this->model::findOrFail($id);
            return response()->json(new $this->resource($item));
        } catch (\Exception $e) {
            Log::error('API Error: ' . $e->getMessage());
            return $this->errorResponse('Record not found', 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $item = $this->model::findOrFail($id);
            $validated = $request->validate($this->validationRules);
            $item->update($validated);
            
            return response()->json([
                'message' => 'Record updated successfully',
                'data' => new $this->resource($item)
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: ' . $e->getMessage());
            return $this->errorResponse('Failed to update record', 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $item = $this->model::findOrFail($id);
            $item->delete();
            
            return response()->json([
                'message' => 'Record deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete record', 500);
        }
    }

    protected function successResponse($data, $message = null, $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    protected function errorResponse($message, $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $code);
    }
} 