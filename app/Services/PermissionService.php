<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Permission;
use Carbon\Carbon;

class PermissionService
{
    public function createPermission(array $data): array
    {
        try {
            $permission = Permission::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'group' => $data['group'] ?? 'general',
                'status' => $data['status'] ?? 'active'
            ]);

            return [
                'success' => true,
                'message' => 'Permission created successfully',
                'permission' => $permission
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create permission: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create permission: ' . $e->getMessage()
            ];
        }
    }

    public function updatePermission(Permission $permission, array $data): array
    {
        try {
            $permission->update($data);

            return [
                'success' => true,
                'message' => 'Permission updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update permission: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update permission: ' . $e->getMessage()
            ];
        }
    }

    public function deletePermission(Permission $permission): array
    {
        try {
            if ($permission->roles()->count() > 0) {
                throw new \Exception('Cannot delete permission with associated roles');
            }

            $permission->delete();

            return [
                'success' => true,
                'message' => 'Permission deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete permission: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete permission: ' . $e->getMessage()
            ];
        }
    }

    public function enablePermission(Permission $permission): array
    {
        try {
            $permission->update(['status' => 'active']);

            return [
                'success' => true,
                'message' => 'Permission enabled successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to enable permission: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to enable permission: ' . $e->getMessage()
            ];
        }
    }

    public function disablePermission(Permission $permission): array
    {
        try {
            $permission->update(['status' => 'inactive']);

            return [
                'success' => true,
                'message' => 'Permission disabled successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to disable permission: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to disable permission: ' . $e->getMessage()
            ];
        }
    }

    public function getPermissionStats(): array
    {
        try {
            return [
                'success' => true,
                'stats' => [
                    'total' => Permission::count(),
                    'active' => Permission::where('status', 'active')->count(),
                    'inactive' => Permission::where('status', 'inactive')->count(),
                    'by_group' => Permission::selectRaw('group, count(*) as count')
                        ->groupBy('group')
                        ->get()
                        ->toArray()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get permission stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get permission stats: ' . $e->getMessage()
            ];
        }
    }

    public function searchPermissions(array $filters = []): array
    {
        try {
            $query = Permission::query();

            if (isset($filters['name'])) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            }

            if (isset($filters['group'])) {
                $query->where('group', $filters['group']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }

            $permissions = $query->orderBy('created_at', 'desc')->paginate(20);

            return [
                'success' => true,
                'permissions' => $permissions
            ];
        } catch (\Exception $e) {
            Log::error('Failed to search permissions: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to search permissions: ' . $e->getMessage()
            ];
        }
    }

    public function getPermissionRoles(Permission $permission): array
    {
        try {
            return [
                'success' => true,
                'roles' => $permission->roles
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get permission roles: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get permission roles: ' . $e->getMessage()
            ];
        }
    }

    public function validatePermissionData(array $data): array
    {
        try {
            $errors = [];

            if (empty($data['name'])) {
                $errors[] = 'Permission name is required';
            }

            if (empty($errors)) {
                return [
                    'success' => true
                ];
            }

            return [
                'success' => false,
                'errors' => $errors
            ];
        } catch (\Exception $e) {
            Log::error('Failed to validate permission data: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to validate permission data: ' . $e->getMessage()
            ];
        }
    }
} 