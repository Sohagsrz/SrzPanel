<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Role;
use App\Models\Permission;
use Carbon\Carbon;

class RoleService
{
    public function createRole(array $data): array
    {
        try {
            $role = Role::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active'
            ]);

            if (isset($data['permissions'])) {
                $role->permissions()->sync($data['permissions']);
            }

            return [
                'success' => true,
                'message' => 'Role created successfully',
                'role' => $role
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create role: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create role: ' . $e->getMessage()
            ];
        }
    }

    public function updateRole(Role $role, array $data): array
    {
        try {
            $role->update($data);

            if (isset($data['permissions'])) {
                $role->permissions()->sync($data['permissions']);
            }

            return [
                'success' => true,
                'message' => 'Role updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update role: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update role: ' . $e->getMessage()
            ];
        }
    }

    public function deleteRole(Role $role): array
    {
        try {
            if ($role->users()->count() > 0) {
                throw new \Exception('Cannot delete role with associated users');
            }

            $role->delete();

            return [
                'success' => true,
                'message' => 'Role deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete role: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete role: ' . $e->getMessage()
            ];
        }
    }

    public function enableRole(Role $role): array
    {
        try {
            $role->update(['status' => 'active']);

            return [
                'success' => true,
                'message' => 'Role enabled successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to enable role: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to enable role: ' . $e->getMessage()
            ];
        }
    }

    public function disableRole(Role $role): array
    {
        try {
            $role->update(['status' => 'inactive']);

            return [
                'success' => true,
                'message' => 'Role disabled successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to disable role: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to disable role: ' . $e->getMessage()
            ];
        }
    }

    public function getRoleStats(): array
    {
        try {
            return [
                'success' => true,
                'stats' => [
                    'total' => Role::count(),
                    'active' => Role::where('status', 'active')->count(),
                    'inactive' => Role::where('status', 'inactive')->count(),
                    'by_permission' => Role::selectRaw('permissions.name, count(*) as count')
                        ->join('permission_role', 'roles.id', '=', 'permission_role.role_id')
                        ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
                        ->groupBy('permissions.name')
                        ->get()
                        ->toArray()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get role stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get role stats: ' . $e->getMessage()
            ];
        }
    }

    public function searchRoles(array $filters = []): array
    {
        try {
            $query = Role::query();

            if (isset($filters['name'])) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['permission'])) {
                $query->whereHas('permissions', function ($q) use ($filters) {
                    $q->where('name', $filters['permission']);
                });
            }

            if (isset($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }

            $roles = $query->orderBy('created_at', 'desc')->paginate(20);

            return [
                'success' => true,
                'roles' => $roles
            ];
        } catch (\Exception $e) {
            Log::error('Failed to search roles: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to search roles: ' . $e->getMessage()
            ];
        }
    }

    public function getRolePermissions(Role $role): array
    {
        try {
            return [
                'success' => true,
                'permissions' => $role->permissions
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get role permissions: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get role permissions: ' . $e->getMessage()
            ];
        }
    }

    public function updateRolePermissions(Role $role, array $permissions): array
    {
        try {
            $role->permissions()->sync($permissions);

            return [
                'success' => true,
                'message' => 'Role permissions updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update role permissions: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update role permissions: ' . $e->getMessage()
            ];
        }
    }

    public function getRoleUsers(Role $role): array
    {
        try {
            return [
                'success' => true,
                'users' => $role->users
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get role users: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get role users: ' . $e->getMessage()
            ];
        }
    }

    public function validateRoleData(array $data): array
    {
        try {
            $errors = [];

            if (empty($data['name'])) {
                $errors[] = 'Role name is required';
            }

            if (isset($data['permissions']) && !is_array($data['permissions'])) {
                $errors[] = 'Permissions must be an array';
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
            Log::error('Failed to validate role data: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to validate role data: ' . $e->getMessage()
            ];
        }
    }
} 