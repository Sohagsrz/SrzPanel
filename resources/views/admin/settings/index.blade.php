@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h2 class="text-2xl font-semibold mb-6">System Settings</h2>

                <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- General Settings -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium">General Settings</h3>
                        
                        <div>
                            <label for="site_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Site Name</label>
                            <input type="text" name="site_name" id="site_name" value="{{ old('site_name', $settings['site_name']) }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        </div>

                        <div>
                            <label for="site_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Site Description</label>
                            <textarea name="site_description" id="site_description" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">{{ old('site_description', $settings['site_description']) }}</textarea>
                        </div>

                        <div>
                            <label for="default_php_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default PHP Version</label>
                            <input type="text" name="default_php_version" id="default_php_version" value="{{ old('default_php_version', $settings['default_php_version']) }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        </div>

                        <div>
                            <label for="max_upload_size" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Upload Size (MB)</label>
                            <input type="number" name="max_upload_size" id="max_upload_size" value="{{ old('max_upload_size', $settings['max_upload_size']) }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        </div>

                        <div>
                            <label for="backup_retention_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Backup Retention (Days)</label>
                            <input type="number" name="backup_retention_days" id="backup_retention_days" value="{{ old('backup_retention_days', $settings['backup_retention_days']) }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1" {{ old('maintenance_mode', $settings['maintenance_mode']) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                            <label for="maintenance_mode" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">Maintenance Mode</label>
                        </div>
                    </div>

                    <!-- SMTP Settings -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium">SMTP Settings</h3>
                        
                        <div>
                            <label for="smtp_host" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SMTP Host</label>
                            <input type="text" name="smtp_host" id="smtp_host" value="{{ old('smtp_host', $settings['smtp_host']) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        </div>

                        <div>
                            <label for="smtp_port" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SMTP Port</label>
                            <input type="number" name="smtp_port" id="smtp_port" value="{{ old('smtp_port', $settings['smtp_port']) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        </div>

                        <div>
                            <label for="smtp_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SMTP Username</label>
                            <input type="text" name="smtp_username" id="smtp_username" value="{{ old('smtp_username', $settings['smtp_username']) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        </div>

                        <div>
                            <label for="smtp_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SMTP Password</label>
                            <input type="password" name="smtp_password" id="smtp_password" value="{{ old('smtp_password', $settings['smtp_password']) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        </div>

                        <div>
                            <label for="smtp_encryption" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SMTP Encryption</label>
                            <select name="smtp_encryption" id="smtp_encryption"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                <option value="tls" {{ old('smtp_encryption', $settings['smtp_encryption']) === 'tls' ? 'selected' : '' }}>TLS</option>
                                <option value="ssl" {{ old('smtp_encryption', $settings['smtp_encryption']) === 'ssl' ? 'selected' : '' }}>SSL</option>
                            </select>
                        </div>

                        <div>
                            <label for="smtp_from_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Address</label>
                            <input type="email" name="smtp_from_address" id="smtp_from_address" value="{{ old('smtp_from_address', $settings['smtp_from_address']) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        </div>

                        <div>
                            <label for="smtp_from_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Name</label>
                            <input type="text" name="smtp_from_name" id="smtp_from_name" value="{{ old('smtp_from_name', $settings['smtp_from_name']) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection 