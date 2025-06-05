<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InstallerController extends Controller
{
    public function index()
    {
        // List available installers (e.g., WordPress, Laravel, etc.)
        $installers = [
            ['name' => 'WordPress', 'version' => '6.0', 'status' => 'available'],
            ['name' => 'Laravel', 'version' => '10.0', 'status' => 'available'],
            // Add more installers as needed
        ];
        return view('admin.installers.index', compact('installers'));
    }

    public function install(Request $request)
    {
        $installer = $request->input('installer');
        // Logic to install the selected installer
        return redirect()->route('admin.installers.index')->with('success', $installer . ' installed successfully.');
    }

    public function uninstall(Request $request)
    {
        $installer = $request->input('installer');
        // Logic to uninstall the selected installer
        return redirect()->route('admin.installers.index')->with('success', $installer . ' uninstalled successfully.');
    }
} 