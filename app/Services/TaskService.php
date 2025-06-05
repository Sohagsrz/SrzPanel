<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Models\TaskLog;

class TaskService
{
    protected $isWindows;
    protected $taskPath;
    protected $backupPath;
    protected $tempPath;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->taskPath = $this->isWindows ? 'C:\\laragon\\tasks' : '/var/tasks';
        $this->backupPath = storage_path('backups/tasks');
        $this->tempPath = storage_path('temp/tasks');

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }

        if (!File::exists($this->tempPath)) {
            File::makeDirectory($this->tempPath, 0755, true);
        }
    }

    public function createTask(array $data): array
    {
        try {
            $task = Task::create($data);

            if ($this->isWindows) {
                $this->createWindowsTask($task);
            } else {
                $this->createLinuxTask($task);
            }

            return [
                'success' => true,
                'message' => 'Task created successfully',
                'task' => $task
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create task: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create task: ' . $e->getMessage()
            ];
        }
    }

    public function updateTask(Task $task, array $data): array
    {
        try {
            $task->update($data);

            if ($this->isWindows) {
                $this->updateWindowsTask($task);
            } else {
                $this->updateLinuxTask($task);
            }

            return [
                'success' => true,
                'message' => 'Task updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update task: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update task: ' . $e->getMessage()
            ];
        }
    }

    public function deleteTask(Task $task): array
    {
        try {
            if ($this->isWindows) {
                $this->deleteWindowsTask($task);
            } else {
                $this->deleteLinuxTask($task);
            }

            $task->delete();

            return [
                'success' => true,
                'message' => 'Task deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete task: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete task: ' . $e->getMessage()
            ];
        }
    }

    public function enableTask(Task $task): array
    {
        try {
            if ($this->isWindows) {
                $this->enableWindowsTask($task);
            } else {
                $this->enableLinuxTask($task);
            }

            $task->update(['status' => 'active']);

            return [
                'success' => true,
                'message' => 'Task enabled successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to enable task: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to enable task: ' . $e->getMessage()
            ];
        }
    }

    public function disableTask(Task $task): array
    {
        try {
            if ($this->isWindows) {
                $this->disableWindowsTask($task);
            } else {
                $this->disableLinuxTask($task);
            }

            $task->update(['status' => 'inactive']);

            return [
                'success' => true,
                'message' => 'Task disabled successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to disable task: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to disable task: ' . $e->getMessage()
            ];
        }
    }

    public function runTask(Task $task): array
    {
        try {
            if ($this->isWindows) {
                $result = $this->runWindowsTask($task);
            } else {
                $result = $this->runLinuxTask($task);
            }

            TaskLog::create([
                'task_id' => $task->id,
                'status' => $result['success'] ? 'success' : 'failed',
                'output' => $result['output'] ?? '',
                'error' => $result['error'] ?? ''
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to run task: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to run task: ' . $e->getMessage()
            ];
        }
    }

    public function getTaskLogs(Task $task, array $filters = []): array
    {
        try {
            $query = TaskLog::where('task_id', $task->id);

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }

            $logs = $query->orderBy('created_at', 'desc')->paginate(20);

            return [
                'success' => true,
                'logs' => $logs
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get task logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get task logs: ' . $e->getMessage()
            ];
        }
    }

    public function getTaskStats(): array
    {
        try {
            return [
                'success' => true,
                'stats' => [
                    'total' => Task::count(),
                    'active' => Task::where('status', 'active')->count(),
                    'inactive' => Task::where('status', 'inactive')->count(),
                    'by_type' => Task::selectRaw('type, count(*) as count')
                        ->groupBy('type')
                        ->get()
                        ->pluck('count', 'type')
                        ->toArray(),
                    'by_status' => TaskLog::selectRaw('status, count(*) as count')
                        ->groupBy('status')
                        ->get()
                        ->pluck('count', 'status')
                        ->toArray()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get task stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get task stats: ' . $e->getMessage()
            ];
        }
    }

    protected function createWindowsTask(Task $task): void
    {
        $xml = $this->generateWindowsTaskXml($task);
        $xmlFile = $this->tempPath . '/task_' . $task->id . '.xml';
        File::put($xmlFile, $xml);

        Process::run("schtasks /create /tn \"{$task->name}\" /xml \"$xmlFile\"");
        File::delete($xmlFile);
    }

    protected function createLinuxTask(Task $task): void
    {
        $cronLine = $this->generateLinuxCronLine($task);
        $cronFile = $this->taskPath . '/' . $task->name;

        File::put($cronFile, $cronLine);
        Process::run('chmod +x ' . $cronFile);
    }

    protected function updateWindowsTask(Task $task): void
    {
        $this->deleteWindowsTask($task);
        $this->createWindowsTask($task);
    }

    protected function updateLinuxTask(Task $task): void
    {
        $this->deleteLinuxTask($task);
        $this->createLinuxTask($task);
    }

    protected function deleteWindowsTask(Task $task): void
    {
        Process::run("schtasks /delete /tn \"{$task->name}\" /f");
    }

    protected function deleteLinuxTask(Task $task): void
    {
        $cronFile = $this->taskPath . '/' . $task->name;
        if (File::exists($cronFile)) {
            File::delete($cronFile);
        }
    }

    protected function enableWindowsTask(Task $task): void
    {
        Process::run("schtasks /change /tn \"{$task->name}\" /enable");
    }

    protected function enableLinuxTask(Task $task): void
    {
        $cronFile = $this->taskPath . '/' . $task->name;
        if (File::exists($cronFile)) {
            Process::run('chmod +x ' . $cronFile);
        }
    }

    protected function disableWindowsTask(Task $task): void
    {
        Process::run("schtasks /change /tn \"{$task->name}\" /disable");
    }

    protected function disableLinuxTask(Task $task): void
    {
        $cronFile = $this->taskPath . '/' . $task->name;
        if (File::exists($cronFile)) {
            Process::run('chmod -x ' . $cronFile);
        }
    }

    protected function runWindowsTask(Task $task): array
    {
        $result = Process::run("schtasks /run /tn \"{$task->name}\"");

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput()
        ];
    }

    protected function runLinuxTask(Task $task): array
    {
        $cronFile = $this->taskPath . '/' . $task->name;
        $result = Process::run($cronFile);

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput()
        ];
    }

    protected function generateWindowsTaskXml(Task $task): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-16"?>
<Task version="1.2" xmlns="http://schemas.microsoft.com/windows/2004/02/mit/task">
    <RegistrationInfo>
        <Date>{$task->created_at}</Date>
        <Author>System</Author>
        <Description>{$task->description}</Description>
    </RegistrationInfo>
    <Triggers>
        <TimeTrigger>
            <StartBoundary>{$task->start_time}</StartBoundary>
            <Enabled>true</Enabled>
            <Repetition>
                <Interval>{$task->interval}</Interval>
                <StopAtDurationEnd>false</StopAtDurationEnd>
            </Repetition>
        </TimeTrigger>
    </Triggers>
    <Principals>
        <Principal id="Author">
            <RunLevel>HighestAvailable</RunLevel>
        </Principal>
    </Principals>
    <Settings>
        <MultipleInstancesPolicy>IgnoreNew</MultipleInstancesPolicy>
        <DisallowStartIfOnBatteries>false</DisallowStartIfOnBatteries>
        <StopIfGoingOnBatteries>false</StopIfGoingOnBatteries>
        <AllowHardTerminate>true</AllowHardTerminate>
        <StartWhenAvailable>true</StartWhenAvailable>
        <RunOnlyIfNetworkAvailable>false</RunOnlyIfNetworkAvailable>
        <IdleSettings>
            <StopOnIdleEnd>false</StopOnIdleEnd>
            <RestartOnIdle>false</RestartOnIdle>
        </IdleSettings>
        <AllowStartOnDemand>true</AllowStartOnDemand>
        <Enabled>true</Enabled>
        <Hidden>false</Hidden>
        <RunOnlyIfIdle>false</RunOnlyIfIdle>
        <WakeToRun>false</WakeToRun>
        <ExecutionTimeLimit>PT72H</ExecutionTimeLimit>
        <Priority>7</Priority>
    </Settings>
    <Actions Context="Author">
        <Exec>
            <Command>{$task->command}</Command>
            <Arguments>{$task->arguments}</Arguments>
            <WorkingDirectory>{$task->working_directory}</WorkingDirectory>
        </Exec>
    </Actions>
</Task>
XML;
    }

    protected function generateLinuxCronLine(Task $task): string
    {
        return sprintf(
            "%s %s %s %s %s %s\n",
            $task->minute,
            $task->hour,
            $task->day_of_month,
            $task->month,
            $task->day_of_week,
            $task->command
        );
    }
} 