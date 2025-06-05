<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TerminalService;
use Illuminate\Http\Request;

class TerminalController extends Controller
{
    protected $terminalService;

    public function __construct(TerminalService $terminalService)
    {
        $this->terminalService = $terminalService;
    }

    public function index()
    {
        return view('admin.terminal.index', [
            'shell' => $this->terminalService->getShell(),
            'currentDirectory' => $this->terminalService->getCurrentDirectory(),
            'isWindows' => $this->terminalService->isWindows()
        ]);
    }

    public function execute(Request $request)
    {
        $request->validate([
            'command' => 'required|string'
        ]);

        $result = $this->terminalService->execute($request->command);

        return response()->json($result);
    }

    public function changeDirectory(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        $success = $this->terminalService->changeDirectory($request->path);

        return response()->json([
            'success' => $success,
            'currentDirectory' => $this->terminalService->getCurrentDirectory()
        ]);
    }
} 