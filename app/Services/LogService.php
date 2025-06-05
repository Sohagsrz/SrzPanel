<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class LogService
{
    protected $logPaths;
    protected $maxLogSize;
    protected $maxLogFiles;
    protected $isWindows;
    protected $logPath;
    protected $backupPath;
    protected $maxBackups;
    protected $logTypes;

    public function __construct()
    {
        $this->logPaths = [
            'apache' => '/var/log/apache2',
            'nginx' => '/var/log/nginx',
            'php' => '/var/log/php',
            'mysql' => '/var/log/mysql',
            'system' => '/var/log',
            'application' => storage_path('logs')
        ];

        $this->maxLogSize = 10 * 1024 * 1024; // 10MB
        $this->maxLogFiles = 5;
        $this->maxBackups = 5;

        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->logPath = $this->isWindows ? 'C:\\laragon\\logs' : '/var/log';
        $this->backupPath = storage_path('backups/logs');

        $this->logTypes = [
            'apache' => $this->isWindows ? 'C:\\laragon\\logs\\apache' : '/var/log/apache2',
            'nginx' => $this->isWindows ? 'C:\\laragon\\logs\\nginx' : '/var/log/nginx',
            'php' => $this->isWindows ? 'C:\\laragon\\logs\\php' : '/var/log/php',
            'mysql' => $this->isWindows ? 'C:\\laragon\\logs\\mysql' : '/var/log/mysql',
            'system' => $this->isWindows ? 'C:\\laragon\\logs\\system' : '/var/log/syslog',
            'application' => storage_path('logs')
        ];

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    public function getLogFiles(string $type): array
    {
        $path = $this->logPaths[$type] ?? null;
        if (!$path || !File::exists($path)) {
            return [];
        }

        $files = File::files($path);
        $logFiles = [];

        foreach ($files as $file) {
            if ($this->isLogFile($file->getFilename())) {
                $logFiles[] = [
                    'name' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                    'type' => $type
                ];
            }
        }

        return $logFiles;
    }

    public function getLogContent(string $path, int $lines = 100, int $offset = 0): array
    {
        if (!File::exists($path)) {
            return [];
        }

        $content = File::get($path);
        $lines = explode("\n", $content);
        $totalLines = count($lines);

        $start = max(0, $totalLines - $lines - $offset);
        $length = min($lines, $totalLines - $start);

        return [
            'content' => array_slice($lines, $start, $length),
            'total_lines' => $totalLines,
            'start_line' => $start + 1,
            'end_line' => $start + $length
        ];
    }

    public function searchLogs(string $query, string $type = null, int $limit = 100): array
    {
        $results = [];
        $types = $type ? [$type] : array_keys($this->logPaths);

        foreach ($types as $logType) {
            $files = $this->getLogFiles($logType);
            foreach ($files as $file) {
                $content = File::get($file['path']);
                $lines = explode("\n", $content);
                
                foreach ($lines as $lineNumber => $line) {
                    if (stripos($line, $query) !== false) {
                        $results[] = [
                            'file' => $file['name'],
                            'type' => $logType,
                            'line_number' => $lineNumber + 1,
                            'content' => $line
                        ];

                        if (count($results) >= $limit) {
                            break 2;
                        }
                    }
                }
            }
        }

        return $results;
    }

    public function rotateLogs(string $type): void
    {
        $path = $this->logPaths[$type] ?? null;
        if (!$path || !File::exists($path)) {
            return;
        }

        $files = $this->getLogFiles($type);
        foreach ($files as $file) {
            if ($file['size'] >= $this->maxLogSize) {
                $this->rotateLogFile($file['path']);
            }
        }
    }

    public function clearLog(string $path): void
    {
        if (File::exists($path)) {
            File::put($path, '');
        }
    }

    public function downloadLog(string $path): ?string
    {
        if (!File::exists($path)) {
            return null;
        }

        return File::get($path);
    }

    public function getLogStats(): array
    {
        $stats = [];
        foreach ($this->logPaths as $type => $path) {
            if (File::exists($path)) {
                $files = $this->getLogFiles($type);
                $totalSize = 0;
                $fileCount = count($files);

                foreach ($files as $file) {
                    $totalSize += $file['size'];
                }

                $stats[$type] = [
                    'file_count' => $fileCount,
                    'total_size' => $totalSize,
                    'largest_file' => $fileCount > 0 ? max(array_column($files, 'size')) : 0,
                    'last_modified' => $fileCount > 0 ? max(array_column($files, 'modified')) : 0
                ];
            }
        }

        return $stats;
    }

    public function setLogRotation(int $maxSize, int $maxFiles): void
    {
        $this->maxLogSize = $maxSize;
        $this->maxLogFiles = $maxFiles;
    }

    protected function isLogFile(string $filename): bool
    {
        $extensions = ['.log', '.err', '.access', '.error'];
        foreach ($extensions as $ext) {
            if (str_ends_with($filename, $ext)) {
                return true;
            }
        }
        return false;
    }

    protected function rotateLogFile(string $path): void
    {
        // Remove oldest log file if we've reached the maximum
        $oldestLog = "{$path}.{$this->maxLogFiles}";
        if (File::exists($oldestLog)) {
            File::delete($oldestLog);
        }

        // Rotate existing log files
        for ($i = $this->maxLogFiles - 1; $i > 0; $i--) {
            $oldLog = "{$path}.{$i}";
            $newLog = "{$path}." . ($i + 1);
            if (File::exists($oldLog)) {
                File::move($oldLog, $newLog);
            }
        }

        // Rename current log file
        if (File::exists($path)) {
            File::move($path, "{$path}.1");
        }

        // Create new empty log file
        File::put($path, '');
    }

    public function getLogTypes(): array
    {
        return array_keys($this->logPaths);
    }

    public function addLogPath(string $type, string $path): void
    {
        $this->logPaths[$type] = $path;
    }

    public function removeLogPath(string $type): void
    {
        unset($this->logPaths[$type]);
    }

    public function getLogPath(string $type): ?string
    {
        return $this->logPaths[$type] ?? null;
    }

    public function getLogFiles(): array
    {
        try {
            if ($this->isWindows) {
                return $this->getWindowsLogFiles();
            } else {
                return $this->getLinuxLogFiles();
            }
        } catch (\Exception $e) {
            Log::error('Failed to get log files: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get log files: ' . $e->getMessage()
            ];
        }
    }

    public function getLogContent(string $filename, int $lines = 100): array
    {
        try {
            if ($this->isWindows) {
                return $this->getWindowsLogContent($filename, $lines);
            } else {
                return $this->getLinuxLogContent($filename, $lines);
            }
        } catch (\Exception $e) {
            Log::error('Failed to get log content: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get log content: ' . $e->getMessage()
            ];
        }
    }

    public function searchLogs(string $query, string $filename = null): array
    {
        try {
            if ($this->isWindows) {
                return $this->searchWindowsLogs($query, $filename);
            } else {
                return $this->searchLinuxLogs($query, $filename);
            }
        } catch (\Exception $e) {
            Log::error('Failed to search logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to search logs: ' . $e->getMessage()
            ];
        }
    }

    public function clearLog(string $filename): array
    {
        try {
            if ($this->isWindows) {
                return $this->clearWindowsLog($filename);
            } else {
                return $this->clearLinuxLog($filename);
            }
        } catch (\Exception $e) {
            Log::error('Failed to clear log: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to clear log: ' . $e->getMessage()
            ];
        }
    }

    public function backupLog(string $filename): array
    {
        try {
            if ($this->isWindows) {
                return $this->backupWindowsLog($filename);
            } else {
                return $this->backupLinuxLog($filename);
            }
        } catch (\Exception $e) {
            Log::error('Failed to backup log: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to backup log: ' . $e->getMessage()
            ];
        }
    }

    public function restoreLog(string $filename, string $backupFile): array
    {
        try {
            if (!File::exists($backupFile)) {
                throw new \Exception('Backup file does not exist');
            }

            if ($this->isWindows) {
                return $this->restoreWindowsLog($filename, $backupFile);
            } else {
                return $this->restoreLinuxLog($filename, $backupFile);
            }
        } catch (\Exception $e) {
            Log::error('Failed to restore log: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore log: ' . $e->getMessage()
            ];
        }
    }

    public function getLogStats(string $filename): array
    {
        try {
            if ($this->isWindows) {
                return $this->getWindowsLogStats($filename);
            } else {
                return $this->getLinuxLogStats($filename);
            }
        } catch (\Exception $e) {
            Log::error('Failed to get log stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get log stats: ' . $e->getMessage()
            ];
        }
    }

    public function rotateLogs(): array
    {
        try {
            if ($this->isWindows) {
                return $this->rotateWindowsLogs();
            } else {
                return $this->rotateLinuxLogs();
            }
        } catch (\Exception $e) {
            Log::error('Failed to rotate logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to rotate logs: ' . $e->getMessage()
            ];
        }
    }

    protected function getWindowsLogFiles(): array
    {
        $logFiles = [];
        $files = File::files($this->logPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'log') {
                $logFiles[] = [
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime()
                ];
            }
        }

        return [
            'success' => true,
            'files' => $logFiles
        ];
    }

    protected function getLinuxLogFiles(): array
    {
        $logFiles = [];
        $files = File::files($this->logPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'log') {
                $logFiles[] = [
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime()
                ];
            }
        }

        return [
            'success' => true,
            'files' => $logFiles
        ];
    }

    protected function getWindowsLogContent(string $filename, int $lines): array
    {
        $file = $this->logPath . '\\' . $filename;
        if (!File::exists($file)) {
            throw new \Exception('Log file does not exist');
        }

        $command = sprintf('powershell Get-Content -Path "%s" -Tail %d', $file, $lines);
        $output = Process::run($command)->output();

        return [
            'success' => true,
            'content' => $output
        ];
    }

    protected function getLinuxLogContent(string $filename, int $lines): array
    {
        $file = $this->logPath . '/' . $filename;
        if (!File::exists($file)) {
            throw new \Exception('Log file does not exist');
        }

        $command = sprintf('tail -n %d %s', $lines, $file);
        $output = Process::run($command)->output();

        return [
            'success' => true,
            'content' => $output
        ];
    }

    protected function searchWindowsLogs(string $query, ?string $filename): array
    {
        if ($filename) {
            $file = $this->logPath . '\\' . $filename;
            if (!File::exists($file)) {
                throw new \Exception('Log file does not exist');
            }

            $command = sprintf('powershell Select-String -Path "%s" -Pattern "%s"', $file, $query);
        } else {
            $command = sprintf('powershell Get-ChildItem -Path "%s" -Filter *.log | Select-String -Pattern "%s"', $this->logPath, $query);
        }

        $output = Process::run($command)->output();

        return [
            'success' => true,
            'results' => $output
        ];
    }

    protected function searchLinuxLogs(string $query, ?string $filename): array
    {
        if ($filename) {
            $file = $this->logPath . '/' . $filename;
            if (!File::exists($file)) {
                throw new \Exception('Log file does not exist');
            }

            $command = sprintf('grep "%s" %s', $query, $file);
        } else {
            $command = sprintf('grep -r "%s" %s/*.log', $query, $this->logPath);
        }

        $output = Process::run($command)->output();

        return [
            'success' => true,
            'results' => $output
        ];
    }

    protected function clearWindowsLog(string $filename): array
    {
        $file = $this->logPath . '\\' . $filename;
        if (!File::exists($file)) {
            throw new \Exception('Log file does not exist');
        }

        File::put($file, '');

        return [
            'success' => true,
            'message' => 'Log cleared successfully'
        ];
    }

    protected function clearLinuxLog(string $filename): array
    {
        $file = $this->logPath . '/' . $filename;
        if (!File::exists($file)) {
            throw new \Exception('Log file does not exist');
        }

        File::put($file, '');

        return [
            'success' => true,
            'message' => 'Log cleared successfully'
        ];
    }

    protected function backupWindowsLog(string $filename): array
    {
        $file = $this->logPath . '\\' . $filename;
        if (!File::exists($file)) {
            throw new \Exception('Log file does not exist');
        }

        $backupFile = $this->backupPath . '\\' . $filename . '_' . date('Y-m-d_H-i-s') . '.log';
        File::copy($file, $backupFile);

        return [
            'success' => true,
            'message' => 'Log backed up successfully',
            'file' => $backupFile
        ];
    }

    protected function backupLinuxLog(string $filename): array
    {
        $file = $this->logPath . '/' . $filename;
        if (!File::exists($file)) {
            throw new \Exception('Log file does not exist');
        }

        $backupFile = $this->backupPath . '/' . $filename . '_' . date('Y-m-d_H-i-s') . '.log';
        File::copy($file, $backupFile);

        return [
            'success' => true,
            'message' => 'Log backed up successfully',
            'file' => $backupFile
        ];
    }

    protected function restoreWindowsLog(string $filename, string $backupFile): array
    {
        $file = $this->logPath . '\\' . $filename;
        File::copy($backupFile, $file);

        return [
            'success' => true,
            'message' => 'Log restored successfully'
        ];
    }

    protected function restoreLinuxLog(string $filename, string $backupFile): array
    {
        $file = $this->logPath . '/' . $filename;
        File::copy($backupFile, $file);

        return [
            'success' => true,
            'message' => 'Log restored successfully'
        ];
    }

    protected function getWindowsLogStats(string $filename): array
    {
        $file = $this->logPath . '\\' . $filename;
        if (!File::exists($file)) {
            throw new \Exception('Log file does not exist');
        }

        $size = File::size($file);
        $modified = File::lastModified($file);
        $lines = count(File::lines($file));

        return [
            'success' => true,
            'stats' => [
                'size' => $size,
                'modified' => $modified,
                'lines' => $lines
            ]
        ];
    }

    protected function getLinuxLogStats(string $filename): array
    {
        $file = $this->logPath . '/' . $filename;
        if (!File::exists($file)) {
            throw new \Exception('Log file does not exist');
        }

        $size = File::size($file);
        $modified = File::lastModified($file);
        $lines = count(File::lines($file));

        return [
            'success' => true,
            'stats' => [
                'size' => $size,
                'modified' => $modified,
                'lines' => $lines
            ]
        ];
    }

    protected function rotateWindowsLogs(): array
    {
        $files = File::files($this->logPath);
        $rotated = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'log') {
                $backupFile = $this->backupPath . '\\' . $file->getFilename() . '_' . date('Y-m-d_H-i-s') . '.log';
                File::copy($file->getPathname(), $backupFile);
                File::put($file->getPathname(), '');
                $rotated[] = $file->getFilename();
            }
        }

        return [
            'success' => true,
            'message' => 'Logs rotated successfully',
            'rotated' => $rotated
        ];
    }

    protected function rotateLinuxLogs(): array
    {
        $files = File::files($this->logPath);
        $rotated = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'log') {
                $backupFile = $this->backupPath . '/' . $file->getFilename() . '_' . date('Y-m-d_H-i-s') . '.log';
                File::copy($file->getPathname(), $backupFile);
                File::put($file->getPathname(), '');
                $rotated[] = $file->getFilename();
            }
        }

        return [
            'success' => true,
            'message' => 'Logs rotated successfully',
            'rotated' => $rotated
        ];
    }

    public function getLogs(string $type, array $filters = []): array
    {
        try {
            if (!isset($this->logTypes[$type])) {
                throw new \Exception("Invalid log type: {$type}");
            }

            $logPath = $this->logTypes[$type];
            if (!File::exists($logPath)) {
                throw new \Exception("Log path does not exist: {$logPath}");
            }

            $logs = [];
            $files = File::files($logPath);

            foreach ($files as $file) {
                if ($this->shouldIncludeFile($file, $filters)) {
                    $logs[] = $this->getLogFileInfo($file);
                }
            }

            return [
                'success' => true,
                'logs' => $logs
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get logs: ' . $e->getMessage()
            ];
        }
    }

    public function getLogContent(string $type, string $file, int $lines = 100): array
    {
        try {
            if (!isset($this->logTypes[$type])) {
                throw new \Exception("Invalid log type: {$type}");
            }

            $logPath = $this->logTypes[$type] . '/' . $file;
            if (!File::exists($logPath)) {
                throw new \Exception("Log file does not exist: {$logPath}");
            }

            $content = $this->getLastLines($logPath, $lines);

            return [
                'success' => true,
                'content' => $content
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get log content: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get log content: ' . $e->getMessage()
            ];
        }
    }

    public function clearLogs(string $type): array
    {
        try {
            if (!isset($this->logTypes[$type])) {
                throw new \Exception("Invalid log type: {$type}");
            }

            $logPath = $this->logTypes[$type];
            if (!File::exists($logPath)) {
                throw new \Exception("Log path does not exist: {$logPath}");
            }

            $files = File::files($logPath);
            foreach ($files as $file) {
                File::put($file, '');
            }

            return [
                'success' => true,
                'message' => 'Logs cleared successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to clear logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to clear logs: ' . $e->getMessage()
            ];
        }
    }

    public function backupLogs(string $type): array
    {
        try {
            if (!isset($this->logTypes[$type])) {
                throw new \Exception("Invalid log type: {$type}");
            }

            $logPath = $this->logTypes[$type];
            if (!File::exists($logPath)) {
                throw new \Exception("Log path does not exist: {$logPath}");
            }

            $backupDir = $this->backupPath . '/' . $type . '_' . date('Y-m-d_H-i-s');
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            $files = File::files($logPath);
            foreach ($files as $file) {
                File::copy($file, $backupDir . '/' . $file->getFilename());
            }

            return [
                'success' => true,
                'message' => 'Logs backed up successfully',
                'backup_dir' => $backupDir
            ];
        } catch (\Exception $e) {
            Log::error('Failed to backup logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to backup logs: ' . $e->getMessage()
            ];
        }
    }

    public function restoreLogs(string $type, string $backupDir): array
    {
        try {
            if (!isset($this->logTypes[$type])) {
                throw new \Exception("Invalid log type: {$type}");
            }

            if (!File::exists($backupDir)) {
                throw new \Exception("Backup directory does not exist: {$backupDir}");
            }

            $logPath = $this->logTypes[$type];
            if (!File::exists($logPath)) {
                File::makeDirectory($logPath, 0755, true);
            }

            $files = File::files($backupDir);
            foreach ($files as $file) {
                File::copy($file, $logPath . '/' . $file->getFilename());
            }

            return [
                'success' => true,
                'message' => 'Logs restored successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to restore logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore logs: ' . $e->getMessage()
            ];
        }
    }

    public function getLogStats(string $type): array
    {
        try {
            if (!isset($this->logTypes[$type])) {
                throw new \Exception("Invalid log type: {$type}");
            }

            $logPath = $this->logTypes[$type];
            if (!File::exists($logPath)) {
                throw new \Exception("Log path does not exist: {$logPath}");
            }

            $stats = [
                'total_size' => 0,
                'file_count' => 0,
                'last_modified' => null,
                'files' => []
            ];

            $files = File::files($logPath);
            foreach ($files as $file) {
                $stats['total_size'] += $file->getSize();
                $stats['file_count']++;
                $stats['files'][] = $this->getLogFileInfo($file);

                $modified = Carbon::createFromTimestamp($file->getMTime());
                if (!$stats['last_modified'] || $modified->gt($stats['last_modified'])) {
                    $stats['last_modified'] = $modified;
                }
            }

            return [
                'success' => true,
                'stats' => $stats
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get log stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get log stats: ' . $e->getMessage()
            ];
        }
    }

    public function rotateLogs(string $type): array
    {
        try {
            if (!isset($this->logTypes[$type])) {
                throw new \Exception("Invalid log type: {$type}");
            }

            $logPath = $this->logTypes[$type];
            if (!File::exists($logPath)) {
                throw new \Exception("Log path does not exist: {$logPath}");
            }

            $files = File::files($logPath);
            foreach ($files as $file) {
                if ($file->getSize() >= $this->maxLogSize) {
                    $this->rotateLogFile($file);
                }
            }

            return [
                'success' => true,
                'message' => 'Logs rotated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to rotate logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to rotate logs: ' . $e->getMessage()
            ];
        }
    }

    protected function shouldIncludeFile($file, array $filters): bool
    {
        if (empty($filters)) {
            return true;
        }

        if (isset($filters['extension'])) {
            if ($file->getExtension() !== $filters['extension']) {
                return false;
            }
        }

        if (isset($filters['min_size'])) {
            if ($file->getSize() < $filters['min_size']) {
                return false;
            }
        }

        if (isset($filters['max_size'])) {
            if ($file->getSize() > $filters['max_size']) {
                return false;
            }
        }

        if (isset($filters['min_date'])) {
            $modified = Carbon::createFromTimestamp($file->getMTime());
            if ($modified->lt($filters['min_date'])) {
                return false;
            }
        }

        if (isset($filters['max_date'])) {
            $modified = Carbon::createFromTimestamp($file->getMTime());
            if ($modified->gt($filters['max_date'])) {
                return false;
            }
        }

        return true;
    }

    protected function getLogFileInfo($file): array
    {
        return [
            'name' => $file->getFilename(),
            'size' => $file->getSize(),
            'modified' => Carbon::createFromTimestamp($file->getMTime())->toIso8601String(),
            'extension' => $file->getExtension()
        ];
    }

    protected function getLastLines(string $file, int $lines): array
    {
        $content = [];
        $handle = fopen($file, 'r');
        if ($handle) {
            $position = filesize($file);
            $chunk = 4096;
            $data = '';
            $count = 0;

            while ($position > 0 && $count < $lines) {
                $size = min($chunk, $position);
                $position -= $size;
                fseek($handle, $position);
                $data = fread($handle, $size) . $data;
                $count = substr_count($data, "\n");
            }

            fclose($handle);
            $content = array_slice(explode("\n", $data), -$lines);
        }

        return $content;
    }

    protected function rotateLogFile($file): void
    {
        $path = $file->getPath();
        $name = $file->getBasename('.' . $file->getExtension());
        $extension = $file->getExtension();

        // Delete oldest log file if max files reached
        $oldestFile = $path . '/' . $name . '.' . $this->maxLogFiles . '.' . $extension;
        if (File::exists($oldestFile)) {
            File::delete($oldestFile);
        }

        // Rotate existing log files
        for ($i = $this->maxLogFiles - 1; $i > 0; $i--) {
            $oldFile = $path . '/' . $name . '.' . $i . '.' . $extension;
            $newFile = $path . '/' . $name . '.' . ($i + 1) . '.' . $extension;
            if (File::exists($oldFile)) {
                File::move($oldFile, $newFile);
            }
        }

        // Rename current log file
        $newFile = $path . '/' . $name . '.1.' . $extension;
        File::move($file->getPathname(), $newFile);

        // Create new empty log file
        File::put($file->getPathname(), '');
    }
} 
} 