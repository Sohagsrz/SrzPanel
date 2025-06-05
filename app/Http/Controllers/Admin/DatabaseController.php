<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Database;
use App\Models\Domain;
use Illuminate\Http\Request;
use App\Services\DatabaseService;
use App\Services\BackupService;
use App\Services\CacheService;
use App\Services\PostgresService;
use App\Services\MySQLService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DatabaseController extends Controller
{
    protected $databaseService;
    protected $backupService;
    protected $cacheService;
    protected $postgresService;
    protected $mysqlService;

    public function __construct(
        DatabaseService $databaseService,
        BackupService $backupService,
        CacheService $cacheService,
        PostgresService $postgresService,
        MySQLService $mysqlService
    ) {
        $this->databaseService = $databaseService;
        $this->backupService = $backupService;
        $this->cacheService = $cacheService;
        $this->postgresService = $postgresService;
        $this->mysqlService = $mysqlService;
    }

    public function index()
    {
        $databases = $this->cacheService->remember('database.list', 300, function () {
            $mysqlDbs = $this->mysqlService->listDatabases();
            $postgresDbs = $this->postgresService->listDatabases();
            
            return [
                'mysql' => $mysqlDbs,
                'postgres' => $postgresDbs
            ];
        });

        return view('admin.databases.index', compact('databases'));
    }

    public function create()
    {
        $domains = $this->cacheService->remember(
            CacheService::KEY_DOMAIN_LIST,
            fn() => Domain::all(),
            3600 // Cache for 1 hour
        );
        
        return view('admin.databases.create', compact('domains'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'type' => 'required|in:mysql,postgres',
        ]);

        try {
            if ($request->type === 'mysql') {
                $success = $this->mysqlService->createDatabase(
                    $request->name,
                    $request->username,
                    $request->password
                );
            } else {
                $success = $this->postgresService->createDatabase(
                    $request->name,
                    $request->username,
                    $request->password
                );
            }

            if ($success) {
                $this->cacheService->forget('database.list');
                return redirect()->route('admin.databases.index')
                    ->with('success', 'Database created successfully.');
            }

            return back()->with('error', 'Failed to create database.');
        } catch (\Exception $e) {
            Log::error('Database creation failed: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while creating the database.');
        }
    }

    public function show(Database $database)
    {
        $database->load('domain');
        
        $backups = $this->cacheService->remember(
            CacheService::KEY_BACKUP_STATS . ".{$database->id}",
            fn() => $this->backupService->getDatabaseBackups($database),
            300 // Cache for 5 minutes
        );
        
        $stats = $this->cacheService->remember(
            CacheService::KEY_DATABASE_STATS . ".{$database->id}",
            fn() => $this->databaseService->getStats($database),
            300 // Cache for 5 minutes
        );

        return view('admin.databases.show', compact('database', 'backups', 'stats'));
    }

    public function edit(Database $database)
    {
        $domains = $this->cacheService->remember(
            CacheService::KEY_DOMAIN_LIST,
            fn() => Domain::all(),
            3600 // Cache for 1 hour
        );
        
        return view('admin.databases.edit', compact('database', 'domains'));
    }

    public function update(Request $request, Database $database)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'domain_id' => 'required|exists:domains,id',
            'username' => 'required|string',
            'password' => 'nullable|string|min:8',
            'status' => 'required|string',
        ]);

        $this->databaseService->updateDatabase($database, $validated);
        
        // Clear database list cache
        $this->cacheService->forget(CacheService::KEY_DATABASE_LIST);
        // Clear database stats cache
        $this->cacheService->forget(CacheService::KEY_DATABASE_STATS . ".{$database->id}");

        return redirect()->route('databases.show', $database)
            ->with('success', 'Database updated successfully.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:mysql,postgres',
        ]);

        try {
            if ($request->type === 'mysql') {
                $success = $this->mysqlService->deleteDatabase($request->name);
            } else {
                $success = $this->postgresService->deleteDatabase($request->name);
            }

            if ($success) {
                $this->cacheService->forget('database.list');
                return redirect()->route('admin.databases.index')
                    ->with('success', 'Database deleted successfully.');
            }

            return back()->with('error', 'Failed to delete database.');
        } catch (\Exception $e) {
            Log::error('Database deletion failed: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while deleting the database.');
        }
    }

    public function backup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:mysql,postgres',
        ]);

        try {
            if ($request->type === 'mysql') {
                $success = $this->mysqlService->backupDatabase($request->name);
            } else {
                $success = $this->postgresService->backupDatabase($request->name);
            }

            if ($success) {
                return redirect()->route('admin.databases.index')
                    ->with('success', 'Database backup created successfully.');
            }

            return back()->with('error', 'Failed to create database backup.');
        } catch (\Exception $e) {
            Log::error('Database backup failed: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while backing up the database.');
        }
    }

    public function restore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:mysql,postgres',
            'backup' => 'required|file',
        ]);

        try {
            if ($request->type === 'mysql') {
                $success = $this->mysqlService->restoreDatabase($request->name, $request->file('backup'));
            } else {
                $success = $this->postgresService->restoreDatabase($request->name, $request->file('backup'));
            }

            if ($success) {
                return redirect()->route('admin.databases.index')
                    ->with('success', 'Database restored successfully.');
            }

            return back()->with('error', 'Failed to restore database.');
        } catch (\Exception $e) {
            Log::error('Database restore failed: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while restoring the database.');
        }
    }

    public function optimize(Database $database)
    {
        $this->databaseService->optimizeDatabase($database);
        
        // Clear database stats cache
        $this->cacheService->forget(CacheService::KEY_DATABASE_STATS . ".{$database->id}");

        return redirect()->route('databases.show', $database)
            ->with('success', 'Database optimized successfully.');
    }

    public function repair(Database $database)
    {
        $this->databaseService->repairDatabase($database);
        
        // Clear database stats cache
        $this->cacheService->forget(CacheService::KEY_DATABASE_STATS . ".{$database->id}");

        return redirect()->route('databases.show', $database)
            ->with('success', 'Database repaired successfully.');
    }
} 