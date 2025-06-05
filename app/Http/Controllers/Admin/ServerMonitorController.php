<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ServerMonitorService;
use Illuminate\Http\Request;

class ServerMonitorController extends Controller
{
    protected $monitorService;

    public function __construct(ServerMonitorService $monitorService)
    {
        $this->monitorService = $monitorService;
    }

    public function index()
    {
        return view('admin.monitor.index');
    }

    public function getStats()
    {
        return response()->json([
            'cpu' => $this->monitorService->getCpuUsage(),
            'ram' => $this->monitorService->getRamUsage(),
            'disk' => $this->monitorService->getDiskUsage(),
            'bandwidth' => $this->monitorService->getBandwidthUsage()
        ]);
    }
} 