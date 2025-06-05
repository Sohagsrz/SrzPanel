<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\CronJob;
use App\Models\CronLog;

class CronService
{
    protected $isWindows;
    protected $cronPath;
    protected $backupPath;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->cronPath = $this->isWindows ? 'C:\\laragon\\bin\\cron' : '/etc/cron.d';
        $this->backupPath = storage_path('backups/cron');

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    public function createCronJob(array $data): array
    {
        try {
            // Create cron job
            $this->createCronJobFile($data);

            // Create cron job record
            $cronJob = CronJob::create($data);

            // Reload cron service
            $this->reloadCronService();

            return [
                'success' => true,
                'message' => 'Cron job created successfully',
                'job' => $cronJob
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create cron job: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create cron job: ' . $e->getMessage()
            ];
        }
    }

    public function updateCronJob(CronJob $cronJob, array $data): array
    {
        try {
            // Update cron job
            $this->updateCronJobFile($cronJob, $data);

            // Update cron job record
            $cronJob->update($data);

            // Reload cron service
            $this->reloadCronService();

            return [
                'success' => true,
                'message' => 'Cron job updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update cron job: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update cron job: ' . $e->getMessage()
            ];
        }
    }

    public function deleteCronJob(CronJob $cronJob): array
    {
        try {
            // Delete cron job
            $this->deleteCronJobFile($cronJob);

            // Delete cron job record
            $cronJob->delete();

            // Reload cron service
            $this->reloadCronService();

            return [
                'success' => true,
                'message' => 'Cron job deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete cron job: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete cron job: ' . $e->getMessage()
            ];
        }
    }

    public function enableCronJob(CronJob $cronJob): array
    {
        try {
            $this->enableCronJobFile($cronJob);
            $cronJob->update(['status' => 'active']);

            // Reload cron service
            $this->reloadCronService();

            return [
                'success' => true,
                'message' => 'Cron job enabled successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to enable cron job: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to enable cron job: ' . $e->getMessage()
            ];
        }
    }

    public function disableCronJob(CronJob $cronJob): array
    {
        try {
            $this->disableCronJobFile($cronJob);
            $cronJob->update(['status' => 'inactive']);

            // Reload cron service
            $this->reloadCronService();

            return [
                'success' => true,
                'message' => 'Cron job disabled successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to disable cron job: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to disable cron job: ' . $e->getMessage()
            ];
        }
    }

    public function runCronJob(CronJob $cronJob): array
    {
        try {
            $this->executeCronJob($cronJob);

            return [
                'success' => true,
                'message' => 'Cron job executed successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to run cron job: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to run cron job: ' . $e->getMessage()
            ];
        }
    }

    public function backupCronJob(CronJob $cronJob): array
    {
        try {
            $backupFile = $this->backupPath . '/' . $cronJob->name . '_' . date('Y-m-d_H-i-s') . '.txt';
            $cronFile = $this->getCronJobPath($cronJob);

            if (File::exists($cronFile)) {
                File::copy($cronFile, $backupFile);

                return [
                    'success' => true,
                    'message' => 'Cron job backed up successfully',
                    'file' => $backupFile
                ];
            } else {
                throw new \Exception('Cron job file does not exist');
            }
        } catch (\Exception $e) {
            Log::error('Failed to backup cron job: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to backup cron job: ' . $e->getMessage()
            ];
        }
    }

    public function restoreCronJob(string $name, string $backupFile): array
    {
        try {
            if (!File::exists($backupFile)) {
                throw new \Exception('Backup file does not exist');
            }

            $cronFile = $this->getCronJobPath($name);
            File::copy($backupFile, $cronFile);

            // Reload cron service
            $this->reloadCronService();

            return [
                'success' => true,
                'message' => 'Cron job restored successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to restore cron job: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore cron job: ' . $e->getMessage()
            ];
        }
    }

    public function getCronJobLogs(CronJob $cronJob, array $filters = []): array
    {
        try {
            $query = CronLog::where('cron_job_id', $cronJob->id);

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
            Log::error('Failed to get cron job logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get cron job logs: ' . $e->getMessage()
            ];
        }
    }

    protected function createCronJobFile(array $data): void
    {
        if ($this->isWindows) {
            $this->createWindowsCronJob($data);
        } else {
            $this->createLinuxCronJob($data);
        }
    }

    protected function updateCronJobFile(CronJob $cronJob, array $data): void
    {
        if ($this->isWindows) {
            $this->updateWindowsCronJob($cronJob, $data);
        } else {
            $this->updateLinuxCronJob($cronJob, $data);
        }
    }

    protected function deleteCronJobFile(CronJob $cronJob): void
    {
        if ($this->isWindows) {
            $this->deleteWindowsCronJob($cronJob);
        } else {
            $this->deleteLinuxCronJob($cronJob);
        }
    }

    protected function enableCronJobFile(CronJob $cronJob): void
    {
        if ($this->isWindows) {
            $this->enableWindowsCronJob($cronJob);
        } else {
            $this->enableLinuxCronJob($cronJob);
        }
    }

    protected function disableCronJobFile(CronJob $cronJob): void
    {
        if ($this->isWindows) {
            $this->disableWindowsCronJob($cronJob);
        } else {
            $this->disableLinuxCronJob($cronJob);
        }
    }

    protected function executeCronJob(CronJob $cronJob): void
    {
        if ($this->isWindows) {
            $this->executeWindowsCronJob($cronJob);
        } else {
            $this->executeLinuxCronJob($cronJob);
        }
    }

    protected function getCronJobPath(CronJob|string $cronJob): string
    {
        $name = $cronJob instanceof CronJob ? $cronJob->name : $cronJob;
        return $this->isWindows
            ? $this->cronPath . '\\' . $name . '.bat'
            : $this->cronPath . '/' . $name;
    }

    protected function createWindowsCronJob(array $data): void
    {
        $content = sprintf(
            '@echo off
%s
',
            $data['command']
        );

        $file = $this->getCronJobPath($data['name']);
        File::put($file, $content);
    }

    protected function createLinuxCronJob(array $data): void
    {
        $content = sprintf(
            '%s %s %s %s %s %s
',
            $data['minute'],
            $data['hour'],
            $data['day_of_month'],
            $data['month'],
            $data['day_of_week'],
            $data['command']
        );

        $file = $this->getCronJobPath($data['name']);
        File::put($file, $content);
    }

    protected function updateWindowsCronJob(CronJob $cronJob, array $data): void
    {
        $content = sprintf(
            '@echo off
%s
',
            $data['command'] ?? $cronJob->command
        );

        $file = $this->getCronJobPath($cronJob);
        File::put($file, $content);
    }

    protected function updateLinuxCronJob(CronJob $cronJob, array $data): void
    {
        $content = sprintf(
            '%s %s %s %s %s %s
',
            $data['minute'] ?? $cronJob->minute,
            $data['hour'] ?? $cronJob->hour,
            $data['day_of_month'] ?? $cronJob->day_of_month,
            $data['month'] ?? $cronJob->month,
            $data['day_of_week'] ?? $cronJob->day_of_week,
            $data['command'] ?? $cronJob->command
        );

        $file = $this->getCronJobPath($cronJob);
        File::put($file, $content);
    }

    protected function deleteWindowsCronJob(CronJob $cronJob): void
    {
        $file = $this->getCronJobPath($cronJob);
        if (File::exists($file)) {
            File::delete($file);
        }
    }

    protected function deleteLinuxCronJob(CronJob $cronJob): void
    {
        $file = $this->getCronJobPath($cronJob);
        if (File::exists($file)) {
            File::delete($file);
        }
    }

    protected function enableWindowsCronJob(CronJob $cronJob): void
    {
        $file = $this->getCronJobPath($cronJob);
        if (File::exists($file . '.disabled')) {
            File::move($file . '.disabled', $file);
        }
    }

    protected function enableLinuxCronJob(CronJob $cronJob): void
    {
        $file = $this->getCronJobPath($cronJob);
        if (File::exists($file . '.disabled')) {
            File::move($file . '.disabled', $file);
        }
    }

    protected function disableWindowsCronJob(CronJob $cronJob): void
    {
        $file = $this->getCronJobPath($cronJob);
        if (File::exists($file)) {
            File::move($file, $file . '.disabled');
        }
    }

    protected function disableLinuxCronJob(CronJob $cronJob): void
    {
        $file = $this->getCronJobPath($cronJob);
        if (File::exists($file)) {
            File::move($file, $file . '.disabled');
        }
    }

    protected function executeWindowsCronJob(CronJob $cronJob): void
    {
        $file = $this->getCronJobPath($cronJob);
        if (File::exists($file)) {
            Process::run($file);
        }
    }

    protected function executeLinuxCronJob(CronJob $cronJob): void
    {
        $file = $this->getCronJobPath($cronJob);
        if (File::exists($file)) {
            Process::run('bash ' . $file);
        }
    }

    protected function reloadCronService(): void
    {
        if ($this->isWindows) {
            $this->reloadWindowsCronService();
        } else {
            $this->reloadLinuxCronService();
        }
    }

    protected function reloadWindowsCronService(): void
    {
        Process::run('net stop cron');
        Process::run('net start cron');
    }

    protected function reloadLinuxCronService(): void
    {
        Process::run('systemctl restart cron');
    }
} 