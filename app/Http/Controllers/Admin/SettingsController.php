<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    protected $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index()
    {
        $settings = $this->getSettings();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'site_description' => 'required|string',
            'maintenance_mode' => 'boolean',
            'default_php_version' => 'required|string',
            'max_upload_size' => 'required|integer|min:1',
            'backup_retention_days' => 'required|integer|min:1',
            'smtp_host' => 'nullable|string',
            'smtp_port' => 'nullable|integer|min:1',
            'smtp_username' => 'nullable|string',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'nullable|string|in:tls,ssl',
            'smtp_from_address' => 'nullable|email',
            'smtp_from_name' => 'nullable|string',
        ]);

        try {
            $this->saveSettings($validated);
            $this->cacheService->forget(CacheService::KEY_SYSTEM_SETTINGS);
            
            return redirect()->route('admin.settings.index')
                ->with('success', 'Settings updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update settings: ' . $e->getMessage());
            return redirect()->route('admin.settings.index')
                ->with('error', 'Failed to update settings. Please try again.');
        }
    }

    protected function getSettings()
    {
        return $this->cacheService->remember(
            CacheService::KEY_SYSTEM_SETTINGS,
            function () {
                $defaultSettings = [
                    'site_name' => 'SrzPanel',
                    'site_description' => 'Modern Web Hosting Control Panel',
                    'maintenance_mode' => false,
                    'default_php_version' => '8.1',
                    'max_upload_size' => 64,
                    'backup_retention_days' => 30,
                    'smtp_host' => '',
                    'smtp_port' => 587,
                    'smtp_username' => '',
                    'smtp_password' => '',
                    'smtp_encryption' => 'tls',
                    'smtp_from_address' => '',
                    'smtp_from_name' => '',
                ];

                $configPath = storage_path('app/settings.json');
                
                if (file_exists($configPath)) {
                    $savedSettings = json_decode(file_get_contents($configPath), true);
                    return array_merge($defaultSettings, $savedSettings);
                }

                return $defaultSettings;
            },
            3600 // Cache for 1 hour
        );
    }

    protected function saveSettings($settings)
    {
        $configPath = storage_path('app/settings.json');
        file_put_contents($configPath, json_encode($settings, JSON_PRETTY_PRINT));
    }
} 