<?php

use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\CronController;
use App\Http\Controllers\Admin\DatabaseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\FileManagerController;
use App\Http\Controllers\Admin\PackagesController;
use App\Http\Controllers\Admin\PHPController;
use App\Http\Controllers\Admin\SSLController;
use App\Http\Controllers\Admin\TerminalController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\DnsController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\InstallerController;
use App\Http\Controllers\Admin\OSDetectionController;
use App\Http\Controllers\Admin\VirtualHostController;
use App\Http\Controllers\Admin\ResourceMonitorController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\FirewallController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\FTPController;
use App\Http\Controllers\Admin\DNSTemplateController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\ServerController;
use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    //logout
    
    // Domains
    Route::resource('domains', DomainController::class);
    Route::post('domains/{domain}/dns', [DomainController::class, 'updateDNS'])->name('domains.dns.update');
    Route::post('domains/{domain}/ssl', [DomainController::class, 'updateSSL'])->name('domains.ssl.update');

    // Databases
    Route::resource('databases', DatabaseController::class);
    Route::post('databases/{database}/backup', [DatabaseController::class, 'backup'])->name('databases.backup');
    Route::post('databases/{database}/restore', [DatabaseController::class, 'restore'])->name('databases.restore');
    Route::post('databases/{database}/optimize', [DatabaseController::class, 'optimize'])->name('databases.optimize');
    Route::post('databases/{database}/repair', [DatabaseController::class, 'repair'])->name('databases.repair');

    // Email
    Route::resource('emails', EmailController::class);
    Route::post('emails/{email}/forward', [EmailController::class, 'updateForward'])->name('emails.forward.update');
    Route::post('emails/{email}/autoresponder', [EmailController::class, 'updateAutoresponder'])->name('emails.autoresponder.update');
    Route::post('emails/{email}/password', [EmailController::class, 'changePassword'])->name('emails.password.change');

    // File Manager
    Route::get('/files', [FileManagerController::class, 'index'])->name('admin.files.index');
    Route::get('/files/{path}', [FileManagerController::class, 'show'])->where('path', '.*')->name('admin.files.show');
    Route::post('/files/upload', [FileManagerController::class, 'upload'])->name('admin.files.upload');
    Route::post('/files', [FileManagerController::class, 'create'])->name('admin.files.store');
    Route::put('/files/{path}', [FileManagerController::class, 'update'])->where('path', '.*')->name('admin.files.update');
    Route::delete('/files/{path}', [FileManagerController::class, 'destroy'])->where('path', '.*')->name('admin.files.destroy');
    Route::post('/files/extract', [FileManagerController::class, 'extract'])->name('admin.files.extract');
    Route::post('/files/compress', [FileManagerController::class, 'compress'])->name('admin.files.compress');
    Route::post('/files/permissions', [FileManagerController::class, 'permissions'])->name('admin.files.permissions');

    // Backups
    Route::resource('backups', BackupController::class);
    Route::post('backups/{backup}/restore', [BackupController::class, 'restore'])->name('backups.restore');

    // SSL
    Route::resource('ssl', SSLController::class);
    Route::post('ssl/{ssl}/renew', [SSLController::class, 'renew'])->name('ssl.renew');

    // SSL Management
    Route::get('/ssl', [SSLController::class, 'index'])->name('ssl.index');
    Route::post('/ssl/lets-encrypt', [SSLController::class, 'requestLetsEncrypt'])->name('ssl.request-lets-encrypt');
    Route::post('/ssl/renew-all', [SSLController::class, 'renewAll'])->name('ssl.renew-all');
    Route::delete('/ssl/{id}', [SSLController::class, 'destroy'])->name('ssl.destroy');

    // Cron
    Route::resource('cron', CronController::class);
    Route::get('cron/{cron}/logs', [CronController::class, 'logs'])->name('cron.logs');

    // PHP
    Route::resource('php', PHPController::class);
    Route::post('php/{php}/ini', [PHPController::class, 'updateIni'])->name('php.ini.update');

    // Packages
    Route::get('packages', [PackagesController::class, 'index'])->name('admin.packages.index');
    Route::post('packages/install', [PackagesController::class, 'install'])->name('admin.packages.install');
    Route::post('packages/remove', [PackagesController::class, 'remove'])->name('admin.packages.remove');

    // Terminal
    Route::get('terminal', [TerminalController::class, 'index'])->name('admin.terminal.index');
    Route::post('terminal/execute', [TerminalController::class, 'execute'])->name('admin.terminal.execute');

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('admin.settings.index');
    Route::put('settings', [SettingsController::class, 'update'])->name('admin.settings.update');

    // Profile
    Route::get('profile', [ProfileController::class, 'index'])->name('admin.profile.index');
    Route::put('profile', [ProfileController::class, 'update'])->name('admin.profile.update');

    // DNS Manager Routes
    Route::get('/dns', [DnsController::class, 'index'])->name('dns.index');
    Route::post('/dns', [DnsController::class, 'store'])->name('dns.store');
    Route::post('/dns/record', [DnsController::class, 'addRecord'])->name('dns.add-record');
    Route::delete('/dns', [DnsController::class, 'destroy'])->name('dns.destroy');

    // Announcements
    Route::resource('announcements', AnnouncementController::class);
    Route::post('announcements/{announcement}/toggle', [AnnouncementController::class, 'toggle'])
        ->name('admin.announcements.toggle');

    // Virtual Hosts
    Route::get('/vhosts', [VirtualHostController::class, 'index'])->name('vhosts.index');
    Route::get('/vhosts/create', [VirtualHostController::class, 'create'])->name('vhosts.create');
    Route::post('/vhosts', [VirtualHostController::class, 'store'])->name('vhosts.store');
    Route::delete('/vhosts/{domain}', [VirtualHostController::class, 'destroy'])->name('vhosts.destroy');
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Servers
    Route::resource('servers', ServerController::class);
    Route::post('servers/{server}/restart', [ServerController::class, 'restart'])->name('servers.restart');
    Route::post('servers/{server}/update', [ServerController::class, 'update'])->name('servers.update');
    Route::get('servers/{server}/stats', [ServerController::class, 'stats'])->name('servers.stats');
    Route::get('servers/{server}/status', [ServerController::class, 'status'])->name('servers.status');

    // Domains
    Route::resource('domains', DomainController::class);
    Route::post('domains/{domain}/dns', [DomainController::class, 'updateDNS'])->name('domains.dns.update');
    Route::post('domains/{domain}/ssl', [DomainController::class, 'updateSSL'])->name('domains.ssl.update');
    Route::post('domains/{domain}/check-dns', [DomainController::class, 'checkDns'])->name('domains.check-dns');

    // Databases
    Route::resource('databases', DatabaseController::class);
    Route::post('databases/{database}/backup', [DatabaseController::class, 'backup'])->name('databases.backup');
    Route::post('databases/{database}/restore', [DatabaseController::class, 'restore'])->name('databases.restore');
    Route::post('databases/{database}/optimize', [DatabaseController::class, 'optimize'])->name('databases.optimize');
    Route::post('databases/{database}/repair', [DatabaseController::class, 'repair'])->name('databases.repair');

    // Email
    Route::resource('emails', EmailController::class);
    Route::post('emails/{email}/forward', [EmailController::class, 'updateForward'])->name('emails.forward.update');
    Route::post('emails/{email}/autoresponder', [EmailController::class, 'updateAutoresponder'])->name('emails.autoresponder.update');
    Route::post('emails/{email}/password', [EmailController::class, 'changePassword'])->name('emails.password.change');
    Route::post('emails/{email}/test', [EmailController::class, 'test'])->name('emails.test');

    // File Manager
    Route::get('/files', [FileManagerController::class, 'index'])->name('files.index');
    Route::get('/files/{path}', [FileManagerController::class, 'show'])->where('path', '.*')->name('files.show');
    Route::post('/files/upload', [FileManagerController::class, 'upload'])->name('files.upload');
    Route::post('/files', [FileManagerController::class, 'create'])->name('files.store');
    Route::put('/files/{path}', [FileManagerController::class, 'update'])->where('path', '.*')->name('files.update');
    Route::delete('/files/{path}', [FileManagerController::class, 'destroy'])->where('path', '.*')->name('files.destroy');
    Route::post('/files/extract', [FileManagerController::class, 'extract'])->name('files.extract');
    Route::post('/files/compress', [FileManagerController::class, 'compress'])->name('files.compress');
    Route::post('/files/permissions', [FileManagerController::class, 'permissions'])->name('files.permissions');
    Route::get('filemanager', [FileManagerController::class, 'index'])->name('filemanager.index');
    Route::get('filemanager/edit', [FileManagerController::class, 'editFile'])->name('filemanager.edit');
    Route::post('filemanager/save', [FileManagerController::class, 'saveFile'])->name('filemanager.save');
    Route::post('filemanager/upload', [FileManagerController::class, 'uploadFile'])->name('filemanager.upload');
    Route::post('filemanager/create-directory', [FileManagerController::class, 'createDirectory'])->name('filemanager.create-directory');
    Route::post('filemanager/delete-directory', [FileManagerController::class, 'deleteDirectory'])->name('filemanager.delete-directory');
    Route::post('filemanager/delete-file', [FileManagerController::class, 'deleteFile'])->name('filemanager.delete-file');
    Route::post('filemanager/move', [FileManagerController::class, 'moveFile'])->name('filemanager.move');
    Route::post('filemanager/copy', [FileManagerController::class, 'copyFile'])->name('filemanager.copy');
    Route::post('filemanager/permissions', [FileManagerController::class, 'changePermissions'])->name('filemanager.permissions');

    // Backups
    Route::resource('backups', BackupController::class);
    Route::post('backups/{backup}/restore', [BackupController::class, 'restore'])->name('backups.restore');

    // SSL
    Route::resource('ssl', SSLController::class);
    Route::post('ssl/{ssl}/renew', [SSLController::class, 'renew'])->name('ssl.renew');
    Route::post('/ssl/lets-encrypt', [SSLController::class, 'requestLetsEncrypt'])->name('ssl.request-lets-encrypt');
    Route::post('/ssl/renew-all', [SSLController::class, 'renewAll'])->name('ssl.renew-all');

    // Cron
    Route::resource('cron', CronController::class);
    Route::get('cron/{cron}/logs', [CronController::class, 'logs'])->name('cron.logs');

    // PHP
    Route::resource('php', PHPController::class);
    Route::post('php/{php}/ini', [PHPController::class, 'updateIni'])->name('php.ini.update');

    // Packages
    Route::get('packages', [PackagesController::class, 'index'])->name('packages.index');
    Route::post('packages/install', [PackagesController::class, 'install'])->name('packages.install');
    Route::post('packages/remove', [PackagesController::class, 'remove'])->name('packages.remove');

    // Terminal
    Route::get('terminal', [TerminalController::class, 'index'])->name('terminal.index');
    Route::post('terminal/execute', [TerminalController::class, 'execute'])->name('terminal.execute');

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

    // Profile
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // DNS Manager
    Route::get('/dns', [DnsController::class, 'index'])->name('dns.index');
    Route::post('/dns', [DnsController::class, 'store'])->name('dns.store');
    Route::post('/dns/record', [DnsController::class, 'addRecord'])->name('dns.add-record');
    Route::delete('/dns', [DnsController::class, 'destroy'])->name('dns.destroy');

    // DNS Templates
    Route::resource('dns-templates', DNSTemplateController::class);
    Route::post('dns-templates/{template}/apply', [DNSTemplateController::class, 'apply'])->name('dns-templates.apply');

    // Announcements
    Route::resource('announcements', AnnouncementController::class);
    Route::post('announcements/{announcement}/toggle', [AnnouncementController::class, 'toggle'])
        ->name('announcements.toggle');

    // Virtual Hosts
    Route::get('/vhosts', [VirtualHostController::class, 'index'])->name('vhosts.index');
    Route::get('/vhosts/create', [VirtualHostController::class, 'create'])->name('vhosts.create');
    Route::post('/vhosts', [VirtualHostController::class, 'store'])->name('vhosts.store');
    Route::delete('/vhosts/{domain}', [VirtualHostController::class, 'destroy'])->name('vhosts.destroy');

    // Resource Monitor
    Route::get('/monitor', [ResourceMonitorController::class, 'index'])->name('monitor.index');
    Route::get('/monitor/cpu', [ResourceMonitorController::class, 'cpu'])->name('monitor.cpu');
    Route::get('/monitor/memory', [ResourceMonitorController::class, 'memory'])->name('monitor.memory');
    Route::get('/monitor/disk', [ResourceMonitorController::class, 'disk'])->name('monitor.disk');
    Route::get('/monitor/network', [ResourceMonitorController::class, 'network'])->name('monitor.network');

    // Users
    Route::resource('users', UserController::class);
    Route::post('users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
    Route::post('users/{user}/unsuspend', [UserController::class, 'unsuspend'])->name('users.unsuspend');

    // Firewall
    Route::get('/firewall', [FirewallController::class, 'index'])->name('firewall.index');
    Route::post('/firewall/block', [FirewallController::class, 'block'])->name('firewall.block');
    Route::post('/firewall/unblock', [FirewallController::class, 'unblock'])->name('firewall.unblock');

    // Logs
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('/logs/{type}', [LogController::class, 'show'])->name('logs.show');
    Route::delete('/logs/{type}', [LogController::class, 'clear'])->name('logs.clear');

    // FTP
    Route::resource('ftp', FTPController::class);
    Route::post('ftp/{ftp}/suspend', [FTPController::class, 'suspend'])->name('ftp.suspend');
    Route::post('ftp/{ftp}/unsuspend', [FTPController::class, 'unsuspend'])->name('ftp.unsuspend');

    // Email Templates
    Route::resource('email-templates', EmailTemplateController::class);
});

// User Webhook Routes
Route::middleware(['auth', 'role:user'])->prefix('user')->name('user.')->group(function () {
    Route::get('/webhooks', [App\Http\Controllers\User\WebhookController::class, 'index'])->name('webhooks.index');
    Route::post('/webhooks', [App\Http\Controllers\User\WebhookController::class, 'store'])->name('webhooks.store');
    Route::put('/webhooks/{webhook}', [App\Http\Controllers\User\WebhookController::class, 'update'])->name('webhooks.update');
    Route::delete('/webhooks/{webhook}', [App\Http\Controllers\User\WebhookController::class, 'destroy'])->name('webhooks.destroy');
    Route::post('/webhooks/{webhook}/regenerate-secret', [App\Http\Controllers\User\WebhookController::class, 'regenerateSecret'])->name('webhooks.regenerate-secret');
});

// Reseller API Token Routes
Route::middleware(['auth', 'role:reseller'])->prefix('reseller')->name('reseller.')->group(function () {
    Route::get('/api-tokens', [App\Http\Controllers\Reseller\ApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('/api-tokens', [App\Http\Controllers\Reseller\ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::get('/api-tokens/{token}', [App\Http\Controllers\Reseller\ApiTokenController::class, 'show'])->name('api-tokens.show');
    Route::put('/api-tokens/{token}', [App\Http\Controllers\Reseller\ApiTokenController::class, 'update'])->name('api-tokens.update');
    Route::delete('/api-tokens/{token}', [App\Http\Controllers\Reseller\ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');
});

require __DIR__.'/auth.php';
