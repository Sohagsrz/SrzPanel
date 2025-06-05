<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\Role;
use App\Models\Permission;
use Carbon\Carbon;

class UserService
{
    protected $maxLoginAttempts;
    protected $lockoutTime;
    protected $passwordExpiryDays;

    public function __construct()
    {
        $this->maxLoginAttempts = config('auth.max_login_attempts', 5);
        $this->lockoutTime = config('auth.lockout_time', 30);
        $this->passwordExpiryDays = config('auth.password_expiry_days', 90);
    }

    public function createUser(array $data): array
    {
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => $data['status'] ?? 'active',
                'role_id' => $data['role_id'] ?? null,
                'last_password_change' => Carbon::now()
            ]);

            if (isset($data['permissions'])) {
                $user->permissions()->sync($data['permissions']);
            }

            return [
                'success' => true,
                'message' => 'User created successfully',
                'user' => $user
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create user: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage()
            ];
        }
    }

    public function updateUser(User $user, array $data): array
    {
        try {
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
                $data['last_password_change'] = Carbon::now();
            }

            $user->update($data);

            if (isset($data['permissions'])) {
                $user->permissions()->sync($data['permissions']);
            }

            return [
                'success' => true,
                'message' => 'User updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update user: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update user: ' . $e->getMessage()
            ];
        }
    }

    public function deleteUser(User $user): array
    {
        try {
            $user->delete();

            return [
                'success' => true,
                'message' => 'User deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete user: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ];
        }
    }

    public function enableUser(User $user): array
    {
        try {
            $user->update(['status' => 'active']);

            return [
                'success' => true,
                'message' => 'User enabled successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to enable user: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to enable user: ' . $e->getMessage()
            ];
        }
    }

    public function disableUser(User $user): array
    {
        try {
            $user->update(['status' => 'inactive']);

            return [
                'success' => true,
                'message' => 'User disabled successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to disable user: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to disable user: ' . $e->getMessage()
            ];
        }
    }

    public function resetPassword(User $user): array
    {
        try {
            $password = Str::random(12);
            $user->update([
                'password' => Hash::make($password),
                'last_password_change' => Carbon::now()
            ]);

            Mail::to($user->email)->send(new \App\Mail\PasswordReset($user, $password));

            return [
                'success' => true,
                'message' => 'Password reset successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to reset password: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to reset password: ' . $e->getMessage()
            ];
        }
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): array
    {
        try {
            if (!Hash::check($currentPassword, $user->password)) {
                throw new \Exception('Current password is incorrect');
            }

            $user->update([
                'password' => Hash::make($newPassword),
                'last_password_change' => Carbon::now()
            ]);

            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to change password: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to change password: ' . $e->getMessage()
            ];
        }
    }

    public function getUserStats(): array
    {
        try {
            return [
                'success' => true,
                'stats' => [
                    'total' => User::count(),
                    'active' => User::where('status', 'active')->count(),
                    'inactive' => User::where('status', 'inactive')->count(),
                    'by_role' => User::selectRaw('role_id, count(*) as count')
                        ->groupBy('role_id')
                        ->get()
                        ->toArray(),
                    'password_expired' => User::where('last_password_change', '<', Carbon::now()->subDays($this->passwordExpiryDays))->count()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get user stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get user stats: ' . $e->getMessage()
            ];
        }
    }

    public function getUserHistory(User $user): array
    {
        try {
            return $user->history()
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get user history: ' . $e->getMessage());
            return [];
        }
    }

    public function validateUserData(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (isset($data['password']) && strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        }

        if (isset($data['role_id']) && !Role::where('id', $data['role_id'])->exists()) {
            $errors['role_id'] = 'Invalid role';
        }

        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }

    public function searchUsers(array $filters = []): array
    {
        try {
            $query = User::query();

            if (isset($filters['name'])) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            }

            if (isset($filters['email'])) {
                $query->where('email', 'like', '%' . $filters['email'] . '%');
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['role_id'])) {
                $query->where('role_id', $filters['role_id']);
            }

            if (isset($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }

            $users = $query->orderBy('created_at', 'desc')->paginate(20);

            return [
                'success' => true,
                'users' => $users
            ];
        } catch (\Exception $e) {
            Log::error('Failed to search users: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to search users: ' . $e->getMessage()
            ];
        }
    }

    public function getUserPermissions(User $user): array
    {
        try {
            $permissions = [
                'admin' => [
                    'manage_users' => true,
                    'manage_domains' => true,
                    'manage_databases' => true,
                    'manage_emails' => true,
                    'manage_ftp' => true,
                    'manage_ssh' => true,
                    'manage_dns' => true,
                    'manage_backups' => true,
                    'manage_tasks' => true,
                    'manage_settings' => true
                ],
                'user' => [
                    'manage_users' => false,
                    'manage_domains' => true,
                    'manage_databases' => true,
                    'manage_emails' => true,
                    'manage_ftp' => true,
                    'manage_ssh' => false,
                    'manage_dns' => true,
                    'manage_backups' => true,
                    'manage_tasks' => true,
                    'manage_settings' => false
                ]
            ];

            return [
                'success' => true,
                'permissions' => $permissions[$user->role] ?? []
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get user permissions: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get user permissions: ' . $e->getMessage()
            ];
        }
    }

    public function updateUserPermissions(User $user, array $permissions): void
    {
        try {
            $user->update(['permissions' => $permissions]);
        } catch (\Exception $e) {
            Log::error('Failed to update user permissions: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getUserActivity(User $user): array
    {
        try {
            return $user->activity()
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get user activity: ' . $e->getMessage());
            return [];
        }
    }

    public function getUserNotifications(User $user): array
    {
        try {
            return $user->notifications()
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get user notifications: ' . $e->getMessage());
            return [];
        }
    }

    public function markNotificationsAsRead(User $user): void
    {
        try {
            $user->notifications()->update(['read_at' => now()]);
        } catch (\Exception $e) {
            Log::error('Failed to mark notifications as read: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getUserSettings(User $user): array
    {
        try {
            return $user->settings()->get()->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get user settings: ' . $e->getMessage());
            return [];
        }
    }

    public function updateUserSettings(User $user, array $settings): void
    {
        try {
            foreach ($settings as $key => $value) {
                $user->settings()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to update user settings: ' . $e->getMessage());
            throw $e;
        }
    }

    public function checkPasswordExpiry(User $user): bool
    {
        return $user->last_password_change->addDays($this->passwordExpiryDays)->isPast();
    }

    public function checkLoginAttempts(User $user): bool
    {
        return $user->login_attempts >= $this->maxLoginAttempts;
    }

    public function incrementLoginAttempts(User $user): void
    {
        $user->increment('login_attempts');
    }

    public function resetLoginAttempts(User $user): void
    {
        $user->update(['login_attempts' => 0]);
    }

    public function isLockedOut(User $user): bool
    {
        if ($user->locked_until && $user->locked_until->isFuture()) {
            return true;
        }

        if ($user->locked_until && $user->locked_until->isPast()) {
            $user->update([
                'locked_until' => null,
                'login_attempts' => 0
            ]);
        }

        return false;
    }

    public function lockUser(User $user): void
    {
        $user->update([
            'locked_until' => Carbon::now()->addMinutes($this->lockoutTime)
        ]);
    }

    public function unlockUser(User $user): void
    {
        $user->update([
            'locked_until' => null,
            'login_attempts' => 0
        ]);
    }
} 
} 