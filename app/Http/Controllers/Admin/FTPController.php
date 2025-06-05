<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FTPService;
use Illuminate\Http\Request;

class FTPController extends Controller
{
    protected $ftpService;

    public function __construct(FTPService $ftpService)
    {
        $this->ftpService = $ftpService;
    }

    public function index()
    {
        $accounts = $this->ftpService->listAccounts();
        $status = $this->ftpService->getStatus();
        return view('admin.ftp.index', compact('accounts', 'status'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|min:3|max:32',
            'password' => 'required|string|min:6',
            'directory' => 'required|string'
        ]);

        $this->ftpService->createAccount(
            $request->username,
            $request->password,
            $request->directory
        );

        return redirect()->route('admin.ftp.index')
            ->with('success', 'FTP account created successfully.');
    }

    public function destroy($username)
    {
        $this->ftpService->deleteAccount($username);
        return redirect()->route('admin.ftp.index')
            ->with('success', 'FTP account deleted successfully.');
    }

    public function changePassword(Request $request, $username)
    {
        $request->validate([
            'password' => 'required|string|min:6'
        ]);

        $this->ftpService->changePassword($username, $request->password);
        return redirect()->route('admin.ftp.index')
            ->with('success', 'Password changed successfully.');
    }

    public function toggleService()
    {
        if ($this->ftpService->getStatus()) {
            $this->ftpService->stopService();
            $message = 'FTP service stopped.';
        } else {
            $this->ftpService->startService();
            $message = 'FTP service started.';
        }

        return redirect()->route('admin.ftp.index')
            ->with('success', $message);
    }

    public function restartService()
    {
        $this->ftpService->restartService();
        return redirect()->route('admin.ftp.index')
            ->with('success', 'FTP service restarted.');
    }
} 