<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LogService;
use Illuminate\Http\Request;

class LogController extends Controller
{
    protected $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    public function index()
    {
        $logs = $this->logService->getLogFiles();
        return view('admin.logs.index', compact('logs'));
    }

    public function show(Request $request, $path)
    {
        $lines = $request->get('lines', 100);
        $content = $this->logService->getLogContent($path, $lines);
        return view('admin.logs.show', compact('content', 'path'));
    }

    public function clear($path)
    {
        $this->logService->clearLog($path);
        return redirect()->back()->with('success', 'Log cleared successfully.');
    }

    public function download($path)
    {
        return $this->logService->downloadLog($path);
    }

    public function search(Request $request, $path)
    {
        $query = $request->get('query');
        $results = $this->logService->searchLog($path, $query);
        return view('admin.logs.search', compact('results', 'path', 'query'));
    }
} 