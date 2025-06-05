<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CronService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CronController extends Controller
{
    protected $cronService;
    protected $os;

    public function __construct(CronService $cronService)
    {
        $this->cronService = $cronService;
        $this->os = strtoupper(substr(PHP_OS, 0, 3));
    }

    public function index()
    {
        $jobs = $this->cronService->getJobs(auth()->user()->name);
        return view('admin.cron.index', compact('jobs'));
    }

    public function create()
    {
        return view('admin.cron.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule' => 'required|string',
            'command' => 'required|string'
        ]);

        if (!$this->cronService->validateSchedule($validated['schedule'])) {
            return redirect()->route('admin.cron.index')
                ->with('error', 'Invalid cron schedule format.');
        }

        $this->cronService->addJob(
            auth()->user()->name,
            $validated['schedule'],
            $validated['command']
        );

        return redirect()->route('admin.cron.index')
            ->with('success', 'Cron job added successfully.');
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'schedule' => 'required|string',
            'command' => 'required|string'
        ]);

        $this->cronService->removeJob(
            auth()->user()->name,
            $validated['schedule'],
            $validated['command']
        );

        return redirect()->route('admin.cron.index')
            ->with('success', 'Cron job removed successfully.');
    }

    public function logs()
    {
        $logs = $this->cronService->getLogs(auth()->user()->name);
        return view('admin.cron.logs', compact('logs'));
    }

    public function getStatus(Request $request)
    {
        $validated = $request->validate([
            'schedule' => 'required|string',
            'command' => 'required|string'
        ]);

        $status = $this->cronService->getJobStatus(
            auth()->user()->name,
            $validated['schedule'],
            $validated['command']
        );

        return response()->json($status);
    }

    protected function getTasks()
    {
        if ($this->os === 'WIN') {
            return $this->getWindowsTasks();
        }
        return $this->getCronTasks();
    }

    protected function getWindowsTasks()
    {
        $output = Process::run('schtasks /query /fo csv /nh')->output();
        $tasks = [];
        foreach (explode("\n", $output) as $line) {
            if (empty(trim($line))) continue;
            $parts = str_getcsv($line);
            if (count($parts) >= 2) {
                $tasks[] = [
                    'name' => $parts[0],
                    'schedule' => $parts[1],
                    'status' => $parts[2] ?? 'Unknown',
                ];
            }
        }
        return $tasks;
    }

    protected function getCronTasks()
    {
        $crontab = $this->getCrontab();
        $tasks = [];
        foreach ($crontab as $line) {
            if (preg_match('/^([^#].*?)\s+(.*?)$/', $line, $matches)) {
                $tasks[] = [
                    'schedule' => $matches[1],
                    'command' => $matches[2],
                ];
            }
        }
        return $tasks;
    }

    protected function createWindowsTask($task)
    {
        $xml = $this->generateWindowsTaskXml($task);
        $xmlPath = storage_path("app/temp/{$task['name']}.xml");
        file_put_contents($xmlPath, $xml);

        Process::run("schtasks /create /tn \"{$task['name']}\" /xml \"{$xmlPath}\" /ru \"{$task['user']}\"");
        unlink($xmlPath);
    }

    protected function createCronTask($task)
    {
        $crontab = $this->getCrontab();
        $crontab[] = "{$task['schedule']} {$task['command']} >> " . storage_path("logs/cron/{$task['name']}.log") . " 2>&1";
        $this->saveCrontab($crontab);
    }

    protected function getCrontab()
    {
        $output = Process::run('crontab -l')->output();
        return array_filter(explode("\n", $output));
    }

    protected function saveCrontab($crontab)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'cron');
        file_put_contents($tempFile, implode("\n", $crontab) . "\n");
        Process::run("crontab {$tempFile}");
        unlink($tempFile);
    }

    protected function generateWindowsTaskXml($task)
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-16"?>
<Task version="1.2" xmlns="http://schemas.microsoft.com/windows/2004/02/mit/task">
  <RegistrationInfo>
    <Date>{$this->formatWindowsDate()}</Date>
    <Author>{$task['user']}</Author>
  </RegistrationInfo>
  <Triggers>
    <TimeTrigger>
      <Repetition>
        <Interval>PT1H</Interval>
        <StopAtDurationEnd>false</StopAtDurationEnd>
      </Repetition>
      <StartBoundary>{$this->formatWindowsDate()}</StartBoundary>
      <Enabled>true</Enabled>
    </TimeTrigger>
  </Triggers>
  <Principals>
    <Principal id="Author">
      <UserId>{$task['user']}</UserId>
      <LogonType>InteractiveToken</LogonType>
      <RunLevel>LeastPrivilege</RunLevel>
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
    <ExecutionTimeLimit>PT1H</ExecutionTimeLimit>
    <Priority>7</Priority>
  </Settings>
  <Actions Context="Author">
    <Exec>
      <Command>cmd.exe</Command>
      <Arguments>/c {$task['command']}</Arguments>
    </Exec>
  </Actions>
</Task>
XML;
    }

    protected function formatWindowsDate()
    {
        return date('Y-m-d\TH:i:s');
    }
} 