<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthService
{
    public function login(array $credentials): array
    {
        try {
            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $token = $user->createToken('auth_token')->plainTextToken;

                return [
                    'success' => true,
                    'user' => $user,
                    'token' => $token
                ];
            }

            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to login: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to login: ' . $e->getMessage()
            ];
        }
    }

    public function logout(): array
    {
        try {
            $user = Auth::user();
            if ($user) {
                $user->tokens()->delete();
            }

            Auth::logout();

            return [
                'success' => true,
                'message' => 'Logged out successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to logout: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to logout: ' . $e->getMessage()
            ];
        }
    }

    public function register(array $data): array
    {
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'user',
                'status' => 'active'
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'success' => true,
                'user' => $user,
                'token' => $token
            ];
        } catch (\Exception $e) {
            Log::error('Failed to register: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to register: ' . $e->getMessage()
            ];
        }
    }

    public function forgotPassword(string $email): array
    {
        try {
            $user = User::where('email', $email)->first();
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            $token = Str::random(60);
            $user->update([
                'password_reset_token' => $token,
                'password_reset_expires_at' => now()->addHours(24)
            ]);

            // Send password reset email
            // This is a placeholder. In a real application, you would:
            // 1. Create a password reset email template
            // 2. Send the email with the reset token

            return [
                'success' => true,
                'message' => 'Password reset email sent'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send password reset email: ' . $e->getMessage()
            ];
        }
    }

    public function resetPassword(string $token, string $newPassword): array
    {
        try {
            $user = User::where('password_reset_token', $token)
                ->where('password_reset_expires_at', '>', now())
                ->first();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired token'
                ];
            }

            $user->update([
                'password' => Hash::make($newPassword),
                'password_reset_token' => null,
                'password_reset_expires_at' => null
            ]);

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
                return [
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ];
            }

            $user->update(['password' => Hash::make($newPassword)]);

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

    public function validateToken(string $token): array
    {
        try {
            $user = User::whereHas('tokens', function ($query) use ($token) {
                $query->where('token', $token);
            })->first();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Invalid token'
                ];
            }

            return [
                'success' => true,
                'user' => $user
            ];
        } catch (\Exception $e) {
            Log::error('Failed to validate token: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to validate token: ' . $e->getMessage()
            ];
        }
    }

    public function refreshToken(User $user): array
    {
        try {
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'success' => true,
                'token' => $token
            ];
        } catch (\Exception $e) {
            Log::error('Failed to refresh token: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to refresh token: ' . $e->getMessage()
            ];
        }
    }

    public function validateCredentials(array $credentials): array
    {
        $errors = [];

        if (empty($credentials['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($credentials['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($credentials['password'])) {
            $errors['password'] = 'Password is required';
        }

        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }

    public function validateRegistrationData(array $data): array
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

        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        }

        if (empty($data['password_confirmation'])) {
            $errors['password_confirmation'] = 'Password confirmation is required';
        } elseif ($data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'] = 'Password confirmation does not match';
        }

        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }

    public function validatePasswordResetData(array $data): array
    {
        $errors = [];

        if (empty($data['token'])) {
            $errors['token'] = 'Token is required';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        }

        if (empty($data['password_confirmation'])) {
            $errors['password_confirmation'] = 'Password confirmation is required';
        } elseif ($data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'] = 'Password confirmation does not match';
        }

        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }
} 