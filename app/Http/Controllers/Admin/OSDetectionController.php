<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OSDetectionController extends Controller
{
    public function index()
    {
        $osType = PHP_OS;
        return view('admin.os-detection.index', compact('osType'));
    }
} 