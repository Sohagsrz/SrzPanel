<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Account;
use App\Http\Resources\Api\V1\AccountResource;
use App\Http\Resources\Api\V1\AccountCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountController extends BaseApiController
{
    protected $model = Account::class;
    protected $resource = AccountResource::class;
    protected $collection = AccountCollection::class;
    
    protected $validationRules = [
        'username' => ['required', 'string', 'min:3', 'max:255', 'unique:accounts'],
        'email' => ['required', 'email', 'unique:accounts'],
        'password' => ['required', 'string', 'min:8'],
        'package_id' => ['required', 'exists:packages,id'],
        'domain' => ['required', 'string', 'unique:accounts'],
        'status' => ['required', 'in:active,suspended,terminated'],
        'disk_limit' => ['required', 'integer', 'min:0'],
        'bandwidth_limit' => ['required', 'integer', 'min:0'],
        'max_ftp_accounts' => ['required', 'integer', 'min:0'],
        'max_email_accounts' => ['required', 'integer', 'min:0'],
        'max_databases' => ['required', 'integer', 'min:0'],
        'max_subdomains' => ['required', 'integer', 'min:0'],
    ];

    protected $searchableFields = ['username', 'email', 'domain'];
    protected $sortableFields = ['username', 'email', 'domain', 'status', 'created_at'];

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules);
        $validated['password'] = Hash::make($validated['password']);
        
        $account = $this->model::create($validated);
        
        // Create necessary directories and configurations
        $this->setupAccount($account);
        
        return response()->json([
            'message' => 'Account created successfully',
            'data' => new $this->resource($account)
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $account = $this->model::findOrFail($id);
        
        $rules = $this->validationRules;
        $rules['username'] = ['required', 'string', 'min:3', 'max:255', Rule::unique('accounts')->ignore($id)];
        $rules['email'] = ['required', 'email', Rule::unique('accounts')->ignore($id)];
        $rules['domain'] = ['required', 'string', Rule::unique('accounts')->ignore($id)];
        $rules['password'] = ['nullable', 'string', 'min:8'];
        
        $validated = $request->validate($rules);
        
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        
        $account->update($validated);
        
        // Update configurations if needed
        $this->updateAccount($account);
        
        return response()->json([
            'message' => 'Account updated successfully',
            'data' => new $this->resource($account)
        ]);
    }

    public function destroy($id)
    {
        $account = $this->model::findOrFail($id);
        
        // Delete account files and configurations
        $this->deleteAccount($account);
        
        $account->delete();
        
        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }

    protected function setupAccount(Account $account)
    {
        // Create home directory
        $homeDir = storage_path('accounts/' . $account->username);
        if (!file_exists($homeDir)) {
            mkdir($homeDir, 0755, true);
        }

        // Create web root
        $webRoot = $homeDir . '/public_html';
        if (!file_exists($webRoot)) {
            mkdir($webRoot, 0755, true);
        }

        // Create log directory
        $logDir = $homeDir . '/logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Create Apache/Nginx configuration
        $this->createWebServerConfig($account);

        // Set up DNS records
        $this->setupDNS($account);
    }

    protected function updateAccount(Account $account)
    {
        // Update web server configuration
        $this->updateWebServerConfig($account);

        // Update DNS records if domain changed
        $this->updateDNS($account);
    }

    protected function deleteAccount(Account $account)
    {
        // Remove web server configuration
        $this->removeWebServerConfig($account);

        // Remove DNS records
        $this->removeDNS($account);

        // Delete account files
        $homeDir = storage_path('accounts/' . $account->username);
        if (file_exists($homeDir)) {
            $this->recursiveDelete($homeDir);
        }
    }

    protected function createWebServerConfig(Account $account)
    {
        // Implementation depends on your web server (Apache/Nginx)
        // This is a placeholder for the actual implementation
    }

    protected function updateWebServerConfig(Account $account)
    {
        // Implementation depends on your web server (Apache/Nginx)
        // This is a placeholder for the actual implementation
    }

    protected function removeWebServerConfig(Account $account)
    {
        // Implementation depends on your web server (Apache/Nginx)
        // This is a placeholder for the actual implementation
    }

    protected function setupDNS(Account $account)
    {
        // Implementation depends on your DNS provider
        // This is a placeholder for the actual implementation
    }

    protected function updateDNS(Account $account)
    {
        // Implementation depends on your DNS provider
        // This is a placeholder for the actual implementation
    }

    protected function removeDNS(Account $account)
    {
        // Implementation depends on your DNS provider
        // This is a placeholder for the actual implementation
    }

    protected function recursiveDelete($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->recursiveDelete($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
} 