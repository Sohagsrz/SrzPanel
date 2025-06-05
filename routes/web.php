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
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/installers', [InstallerController::class, 'index'])->name('installers.index');
    Route::post('/installers/install', [InstallerController::class, 'install'])->name('installers.install');
    Route::post('/installers/uninstall', [InstallerController::class, 'uninstall'])->name('installers.uninstall');
    Route::get('/os-detection', [OSDetectionController::class, 'index'])->name('os-detection.index');
    Route::get('/vhosts', [VirtualHostController::class, 'index'])->name('vhosts.index');
    Route::get('/vhosts/create', [VirtualHostController::class, 'create'])->name('vhosts.create');
    Route::post('/vhosts', [VirtualHostController::class, 'store'])->name('vhosts.store');
    Route::delete('/vhosts/{domain}', [VirtualHostController::class, 'destroy'])->name('vhosts.destroy');
    Route::get('/monitor', [ResourceMonitorController::class, 'index'])->name('admin.monitor.index');
    Route::get('/monitor/stats', [ResourceMonitorController::class, 'getStats'])->name('admin.monitor.stats');
    Route::get('/monitor/processes', [ResourceMonitorController::class, 'getProcesses'])->name('admin.monitor.processes');
    Route::post('/users/{id}/suspend', [UserController::class, 'suspend'])->name('admin.users.suspend');
    Route::post('/users/{id}/unsuspend', [UserController::class, 'unsuspend'])->name('admin.users.unsuspend');
    Route::get('/firewall', [FirewallController::class, 'index'])->name('admin.firewall.index');
    Route::post('/firewall', [FirewallController::class, 'store'])->name('admin.firewall.store');
    Route::delete('/firewall/{id}', [FirewallController::class, 'destroy'])->name('admin.firewall.destroy');
    Route::get('/logs', [LogController::class, 'index'])->name('admin.logs.index');
    Route::get('/logs/{path}', [LogController::class, 'show'])->name('admin.logs.show');
    Route::post('/logs/{path}/clear', [LogController::class, 'clear'])->name('admin.logs.clear');
    Route::get('/logs/{path}/download', [LogController::class, 'download'])->name('admin.logs.download');
    Route::get('/logs/{path}/search', [LogController::class, 'search'])->name('admin.logs.search');
    Route::get('/ftp', [FTPController::class, 'index'])->name('admin.ftp.index');
    Route::post('/ftp', [FTPController::class, 'store'])->name('admin.ftp.store');
    Route::delete('/ftp/{username}', [FTPController::class, 'destroy'])->name('admin.ftp.destroy');
    Route::post('/ftp/{username}/password', [FTPController::class, 'changePassword'])->name('admin.ftp.password');
    Route::post('/ftp/toggle', [FTPController::class, 'toggleService'])->name('admin.ftp.toggle');
    Route::post('/ftp/restart', [FTPController::class, 'restartService'])->name('admin.ftp.restart');
    Route::resource('dns-templates', DNSTemplateController::class);
    Route::post('dns-templates/{template}/apply/{domain}', [DNSTemplateController::class, 'apply'])
        ->name('admin.dns-templates.apply');
    Route::resource('email-templates', EmailTemplateController::class);
    Route::get('email-templates/{template}/preview', [EmailTemplateController::class, 'preview'])
        ->name('admin.email-templates.preview');
});

// File Manager Routes
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('filemanager', [FileManagerController::class, 'index'])->name('admin.filemanager.index');
    Route::get('filemanager/edit', [FileManagerController::class, 'editFile'])->name('admin.filemanager.edit');
    Route::post('filemanager/save', [FileManagerController::class, 'saveFile'])->name('admin.filemanager.save');
    Route::post('filemanager/upload', [FileManagerController::class, 'uploadFile'])->name('admin.filemanager.upload');
    Route::post('filemanager/create-directory', [FileManagerController::class, 'createDirectory'])->name('admin.filemanager.create-directory');
    Route::post('filemanager/delete-directory', [FileManagerController::class, 'deleteDirectory'])->name('admin.filemanager.delete-directory');
    Route::post('filemanager/delete-file', [FileManagerController::class, 'deleteFile'])->name('admin.filemanager.delete-file');
    Route::post('filemanager/move', [FileManagerController::class, 'moveFile'])->name('admin.filemanager.move');
    Route::post('filemanager/copy', [FileManagerController::class, 'copyFile'])->name('admin.filemanager.copy');
    Route::post('filemanager/permissions', [FileManagerController::class, 'changePermissions'])->name('admin.filemanager.permissions');
    Route::get('filemanager/show/{path}', [FileManagerController::class, 'show'])->name('admin.filemanager.show');
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
