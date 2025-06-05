<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class TerminalService
{
    protected $isWindows;
    protected $shell;
    protected $workingDirectory;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->shell = $this->isWindows ? 'powershell.exe' : 'bash';
        $this->workingDirectory = base_path();
    }

    public function execute($command)
    {
        try {
            if ($this->isWindows) {
                $command = "powershell.exe -Command \"{$command}\"";
            }

            $process = Process::path($this->workingDirectory)
                ->timeout(60)
                ->run($command);

            return [
                'success' => $process->successful(),
                'output' => $process->output(),
                'error' => $process->errorOutput()
            ];
        } catch (\Exception $e) {
            Log::error('Terminal error: ' . $e->getMessage());
            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage()
            ];
        }
    }

    public function changeDirectory($path)
    {
        if (is_dir($path)) {
            $this->workingDirectory = $path;
            return true;
        }
        return false;
    }

    public function getCurrentDirectory()
    {
        return $this->workingDirectory;
    }

    public function getShell()
    {
        return $this->shell;
    }

    public function isWindows()
    {
        return $this->isWindows;
    }
} 