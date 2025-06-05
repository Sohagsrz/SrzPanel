<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    BackupController,
    UpdateController,
    SecurityController,
    NotificationController,
    TaskController,
    ServerController,
    DomainController,
    DatabaseController,
    EmailController,
    FTPController,
    SSLController,
    SettingController,
    CronController,
    FirewallController,
    LogController,
    PackageController,
    UserController,
    ProfileController
};
use App\Http\Controllers\Api\V1\{
    AccountController,
    EmailController as ApiEmailController,
    DomainController as ApiDomainController,
    DnsController,
    CronJobController,
    StatisticsController,
    CustomizationController,
    WebhookController
};

// API Routes with authentication
Route::middleware(['auth:sanctum'])->group(function () {
    // Backup Management
    Route::prefix('backups')->group(function () {
        Route::get('/', [BackupController::class, 'index']);
        Route::post('/', [BackupController::class, 'create']);
        Route::post('/batch', [BackupController::class, 'createBatch']);
        Route::post('/{id}/restore', [BackupController::class, 'restore']);
        Route::delete('/{id}', [BackupController::class, 'delete']);
        Route::get('/{id}/status', [BackupController::class, 'getStatus']);
    });

    // System Updates
    Route::prefix('updates')->group(function () {
        Route::get('/', [UpdateController::class, 'index']);
        Route::get('/check', [UpdateController::class, 'checkForUpdates']);
        Route::post('/install', [UpdateController::class, 'installUpdate']);
        Route::post('/batch', [UpdateController::class, 'installBatch']);
        Route::post('/rollback', [UpdateController::class, 'rollback']);
        Route::get('/{id}/status', [UpdateController::class, 'getStatus']);
    });

    // Security Management
    Route::prefix('security')->group(function () {
        Route::get('/', [SecurityController::class, 'index']);
        Route::post('/scan', [SecurityController::class, 'scan']);
        Route::post('/scan/batch', [SecurityController::class, 'scanBatch']);
        Route::get('/history', [SecurityController::class, 'getHistory']);
        Route::get('/threats/{id}', [SecurityController::class, 'getThreatDetails']);
        Route::get('/stats', [SecurityController::class, 'getStats']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/send', [NotificationController::class, 'send']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'delete']);
        Route::get('/stats', [NotificationController::class, 'getStats']);
    });

    // Task Management
    Route::prefix('tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'store']);
        Route::put('/{id}', [TaskController::class, 'update']);
        Route::delete('/{id}', [TaskController::class, 'delete']);
        Route::post('/{id}/progress', [TaskController::class, 'updateProgress']);
        Route::post('/{id}/complete', [TaskController::class, 'complete']);
    });

    // Server Management
    Route::prefix('servers')->group(function () {
        Route::get('/', [ServerController::class, 'index']);
        Route::post('/', [ServerController::class, 'store']);
        Route::put('/{id}', [ServerController::class, 'update']);
        Route::delete('/{id}', [ServerController::class, 'delete']);
        Route::get('/{id}/stats', [ServerController::class, 'getStats']);
        Route::get('/{id}/status', [ServerController::class, 'checkStatus']);
    });

    // Domain Management
    Route::prefix('domains')->group(function () {
        Route::get('/', [DomainController::class, 'index']);
        Route::post('/', [DomainController::class, 'store']);
        Route::put('/{id}', [DomainController::class, 'update']);
        Route::delete('/{id}', [DomainController::class, 'delete']);
        Route::post('/{id}/enable', [DomainController::class, 'enable']);
        Route::post('/{id}/disable', [DomainController::class, 'disable']);
        Route::post('/{id}/backup', [DomainController::class, 'backup']);
        Route::post('/{id}/restore', [DomainController::class, 'restore']);
    });

    // Database Management
    Route::prefix('databases')->group(function () {
        Route::get('/', [DatabaseController::class, 'index']);
        Route::post('/', [DatabaseController::class, 'store']);
        Route::put('/{id}', [DatabaseController::class, 'update']);
        Route::delete('/{id}', [DatabaseController::class, 'delete']);
        Route::post('/{id}/backup', [DatabaseController::class, 'backup']);
        Route::post('/{id}/restore', [DatabaseController::class, 'restore']);
    });

    // Email Management
    Route::prefix('emails')->group(function () {
        Route::get('/', [EmailController::class, 'index']);
        Route::post('/send', [EmailController::class, 'send']);
        Route::post('/template', [EmailController::class, 'sendTemplate']);
        Route::get('/templates', [EmailController::class, 'getTemplates']);
        Route::post('/templates', [EmailController::class, 'createTemplate']);
        Route::put('/templates/{id}', [EmailController::class, 'updateTemplate']);
        Route::delete('/templates/{id}', [EmailController::class, 'deleteTemplate']);
    });

    // FTP Management
    Route::prefix('ftp')->group(function () {
        Route::get('/', [FTPController::class, 'index']);
        Route::post('/', [FTPController::class, 'store']);
        Route::put('/{id}', [FTPController::class, 'update']);
        Route::delete('/{id}', [FTPController::class, 'delete']);
        Route::post('/{id}/enable', [FTPController::class, 'enable']);
        Route::post('/{id}/disable', [FTPController::class, 'disable']);
    });

    // SSL Management
    Route::prefix('ssl')->group(function () {
        Route::get('/', [SSLController::class, 'index']);
        Route::post('/', [SSLController::class, 'store']);
        Route::put('/{id}', [SSLController::class, 'update']);
        Route::delete('/{id}', [SSLController::class, 'delete']);
        Route::post('/{id}/renew', [SSLController::class, 'renew']);
        Route::post('/{id}/verify', [SSLController::class, 'verify']);
    });

    // Settings Management
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index']);
        Route::post('/', [SettingController::class, 'store']);
        Route::put('/{id}', [SettingController::class, 'update']);
        Route::delete('/{id}', [SettingController::class, 'delete']);
        Route::get('/groups', [SettingController::class, 'getGroups']);
    });

    // Cron Management
    Route::prefix('cron')->group(function () {
        Route::get('/', [CronController::class, 'index']);
        Route::post('/', [CronController::class, 'store']);
        Route::put('/{id}', [CronController::class, 'update']);
        Route::delete('/{id}', [CronController::class, 'delete']);
        Route::post('/{id}/enable', [CronController::class, 'enable']);
        Route::post('/{id}/disable', [CronController::class, 'disable']);
        Route::post('/{id}/run', [CronController::class, 'run']);
    });

    // Firewall Management
    Route::prefix('firewall')->group(function () {
        Route::get('/', [FirewallController::class, 'index']);
        Route::post('/', [FirewallController::class, 'store']);
        Route::put('/{id}', [FirewallController::class, 'update']);
        Route::delete('/{id}', [FirewallController::class, 'delete']);
        Route::post('/{id}/enable', [FirewallController::class, 'enable']);
        Route::post('/{id}/disable', [FirewallController::class, 'disable']);
    });

    // Log Management
    Route::prefix('logs')->group(function () {
        Route::get('/', [LogController::class, 'index']);
        Route::get('/{type}', [LogController::class, 'getLogs']);
        Route::get('/{type}/{file}', [LogController::class, 'getLogContent']);
        Route::delete('/{type}', [LogController::class, 'clearLogs']);
        Route::post('/{type}/backup', [LogController::class, 'backupLogs']);
        Route::post('/{type}/restore', [LogController::class, 'restoreLogs']);
    });

    // Package Management
    Route::prefix('packages')->group(function () {
        Route::get('/', [PackageController::class, 'index']);
        Route::post('/', [PackageController::class, 'store']);
        Route::put('/{id}', [PackageController::class, 'update']);
        Route::delete('/{id}', [PackageController::class, 'delete']);
        Route::post('/{id}/install', [PackageController::class, 'install']);
        Route::post('/{id}/uninstall', [PackageController::class, 'uninstall']);
    });

    // User Management
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'delete']);
        Route::post('/{id}/suspend', [UserController::class, 'suspend']);
        Route::post('/{id}/unsuspend', [UserController::class, 'unsuspend']);
    });

    // Profile Management
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('/password', [ProfileController::class, 'updatePassword']);
        Route::put('/settings', [ProfileController::class, 'updateSettings']);
    });
});

// API Version 1
Route::prefix('v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    // Accounts API
    Route::apiResource('accounts', AccountController::class);
    
    // Email API
    Route::apiResource('email', ApiEmailController::class);
    
    // Domain API
    Route::apiResource('domains', ApiDomainController::class);
    Route::post('subdomains', [ApiDomainController::class, 'createSubdomain']);
    
    // DNS API
    Route::apiResource('dnszones', DnsController::class);
    Route::apiResource('dnsrecords', DnsController::class);
    
    // Database API
    Route::apiResource('databases', DatabaseController::class);
    
    // Cron Jobs API
    Route::apiResource('cronjobs', CronJobController::class);
    
    // Packages API
    Route::apiResource('packages', PackageController::class);
    
    // Statistics API
    Route::prefix('stats')->group(function () {
        Route::get('account/{id}', [StatisticsController::class, 'accountStats']);
        Route::get('server', [StatisticsController::class, 'serverStats']);
    });
    
    // Security API
    Route::prefix('security')->group(function () {
        Route::apiResource('firewall', SecurityController::class);
    });
    
    // Customization API (Reseller/Root only)
    Route::middleware(['role:reseller|root'])->prefix('customization')->group(function () {
        Route::post('logo', [CustomizationController::class, 'uploadLogo']);
        Route::post('theme', [CustomizationController::class, 'setTheme']);
        Route::get('themes', [CustomizationController::class, 'listThemes']);
    });
    
    // Webhooks API
    Route::prefix('webhooks')->group(function () {
        Route::get('/', [WebhookController::class, 'index']);
        Route::post('/', [WebhookController::class, 'store']);
        Route::get('/{id}', [WebhookController::class, 'show']);
        Route::put('/{id}', [WebhookController::class, 'update']);
        Route::delete('/{id}', [WebhookController::class, 'destroy']);
    });
}); 