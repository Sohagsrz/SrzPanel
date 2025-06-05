<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ResourceMonitorService;
use Illuminate\Http\Request;

class ResourceMonitorController extends Controller
{
    protected $monitorService;

    public function __construct(ResourceMonitorService $monitorService)
    {
        $this->monitorService = $monitorService;
    }

    public function index()
    {
        $data = [
            'cpu' => $this->monitorService->getCpuUsage(),
            'ram' => $this->monitorService->getRamUsage(),
            'disk' => $this->monitorService->getDiskUsage(),
            'bandwidth' => $this->monitorService->getBandwidthUsage(),
            'processes' => $this->monitorService->getProcessList()
        ];

        return view('admin.monitor.index', $data);
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

    public function getProcesses()
    {
        return response()->json([
            'processes' => $this->monitorService->getProcessList()
        ]);
    }
} 