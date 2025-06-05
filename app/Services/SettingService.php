<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class SettingService
{
    protected $isWindows;
    protected $settingsPath;
    protected $backupPath;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->settingsPath = $this->isWindows ? 'C:\\laragon\\settings' : '/etc/settings';
        $this->backupPath = storage_path('backups/settings');

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    public function getSetting(string $key, $default = null)
    {
        try {
            $setting = Cache::rememberForever('setting.' . $key, function () use ($key) {
                return Setting::where('key', $key)->first();
            });

            return $setting ? $setting->value : $default;
        } catch (\Exception $e) {
            Log::error('Failed to get setting: ' . $e->getMessage());
            return $default;
        }
    }

    public function setSetting(string $key, $value): array
    {
        try {
            $setting = Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );

            Cache::forget('setting.' . $key);

            return [
                'success' => true,
                'message' => 'Setting updated successfully',
                'setting' => $setting
            ];
        } catch (\Exception $e) {
            Log::error('Failed to set setting: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to set setting: ' . $e->getMessage()
            ];
        }
    }

    public function deleteSetting(string $key): array
    {
        try {
            $setting = Setting::where('key', $key)->first();

            if ($setting) {
                $setting->delete();
                Cache::forget('setting.' . $key);
            }

            return [
                'success' => true,
                'message' => 'Setting deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete setting: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete setting: ' . $e->getMessage()
            ];
        }
    }

    public function getAllSettings(): array
    {
        try {
            $settings = Cache::rememberForever('settings.all', function () {
                return Setting::all()->pluck('value', 'key')->toArray();
            });

            return [
                'success' => true,
                'settings' => $settings
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get all settings: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get all settings: ' . $e->getMessage()
            ];
        }
    }

    public function updateSettings(array $settings): array
    {
        try {
            foreach ($settings as $key => $value) {
                $this->setSetting($key, $value);
            }

            Cache::forget('settings.all');

            return [
                'success' => true,
                'message' => 'Settings updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update settings: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ];
        }
    }

    public function backupSettings(): array
    {
        try {
            $settings = Setting::all()->toArray();
            $backupFile = $this->backupPath . '/settings_' . date('Y-m-d_H-i-s') . '.json';

            File::put($backupFile, json_encode($settings, JSON_PRETTY_PRINT));

            return [
                'success' => true,
                'message' => 'Settings backed up successfully',
                'file' => $backupFile
            ];
        } catch (\Exception $e) {
            Log::error('Failed to backup settings: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to backup settings: ' . $e->getMessage()
            ];
        }
    }

    public function restoreSettings(string $backupFile): array
    {
        try {
            if (!File::exists($backupFile)) {
                throw new \Exception('Backup file does not exist');
            }

            $settings = json_decode(File::get($backupFile), true);

            foreach ($settings as $setting) {
                Setting::updateOrCreate(
                    ['key' => $setting['key']],
                    ['value' => $setting['value']]
                );
            }

            Cache::flush();

            return [
                'success' => true,
                'message' => 'Settings restored successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to restore settings: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore settings: ' . $e->getMessage()
            ];
        }
    }

    public function getSettingGroups(): array
    {
        return [
            'general' => 'General Settings',
            'email' => 'Email Settings',
            'security' => 'Security Settings',
            'backup' => 'Backup Settings',
            'notification' => 'Notification Settings',
            'system' => 'System Settings'
        ];
    }

    public function getSettingsByGroup(string $group): array
    {
        try {
            $settings = Setting::where('group', $group)->get();

            return [
                'success' => true,
                'settings' => $settings
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get settings by group: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get settings by group: ' . $e->getMessage()
            ];
        }
    }

    public function validateSetting(string $key, $value): array
    {
        try {
            $setting = Setting::where('key', $key)->first();

            if (!$setting) {
                return [
                    'success' => true,
                    'valid' => true
                ];
            }

            $rules = $this->getValidationRules($setting->type);
            $validator = validator(['value' => $value], ['value' => $rules]);

            return [
                'success' => true,
                'valid' => !$validator->fails(),
                'errors' => $validator->errors()->all()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to validate setting: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to validate setting: ' . $e->getMessage()
            ];
        }
    }

    protected function getValidationRules(string $type): string
    {
        switch ($type) {
            case 'string':
                return 'string|max:255';
            case 'integer':
                return 'integer';
            case 'float':
                return 'numeric';
            case 'boolean':
                return 'boolean';
            case 'array':
                return 'array';
            case 'json':
                return 'json';
            case 'email':
                return 'email';
            case 'url':
                return 'url';
            case 'ip':
                return 'ip';
            case 'date':
                return 'date';
            default:
                return 'string';
        }
    }
} 