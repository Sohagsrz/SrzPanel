<?php

namespace App\Services;

use App\Models\Email;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Mail\Message;
use App\Models\User;
use App\Models\EmailTemplate;
use App\Models\EmailLog;
use Carbon\Carbon;

class EmailService
{
    protected $isWindows;
    protected $postfixPath;
    protected $dovecotPath;
    protected $backupPath;
    protected $config;
    protected $defaultFrom;
    protected $defaultFromName;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->postfixPath = $this->isWindows ? 'C:\\laragon\\bin\\postfix' : '/etc/postfix';
        $this->dovecotPath = $this->isWindows ? 'C:\\laragon\\bin\\dovecot' : '/etc/dovecot';
        $this->backupPath = storage_path('backups/emails');
        $this->config = Config::get('mail');
        $this->defaultFrom = $this->config['from']['address'] ?? 'noreply@example.com';
        $this->defaultFromName = $this->config['from']['name'] ?? 'System';

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    public function createEmail(array $data): Email
    {
        // Create email account
        $this->createEmailAccount($data['username'], $data['password']);

        // Create email record
        return Email::create($data);
    }

    public function updateEmail(Email $email, array $data): void
    {
        // Update password if provided
        if (isset($data['password'])) {
            $this->updateEmailPassword($email->username, $data['password']);
        }

        // Update quota if provided
        if (isset($data['quota'])) {
            $this->updateEmailQuota($email->username, $data['quota']);
        }

        $email->update($data);
    }

    public function deleteEmail(Email $email): void
    {
        // Delete email account
        $this->deleteEmailAccount($email->username);

        $email->delete();
    }

    public function enableEmail(Email $email): void
    {
        $this->enableEmailAccount($email->username);
        $email->update(['status' => 'active']);
    }

    public function disableEmail(Email $email): void
    {
        $this->disableEmailAccount($email->username);
        $email->update(['status' => 'inactive']);
    }

    public function backupEmail(Email $email): array
    {
        try {
            $backupFile = $this->backupPath . '/' . $email->username . '_' . date('Y-m-d_H-i-s') . '.tar.gz';
            $emailPath = $this->getEmailPath($email->username);

            $command = sprintf(
                'tar -czf %s -C %s .',
                $backupFile,
                $emailPath
            );

            Process::run($command);

            if (File::exists($backupFile)) {
                return [
                    'success' => true,
                    'message' => 'Email backed up successfully',
                    'file' => $backupFile
                ];
            } else {
                throw new \Exception('Backup file was not created');
            }
        } catch (\Exception $e) {
            Log::error('Failed to backup email: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to backup email: ' . $e->getMessage()
            ];
        }
    }

    public function restoreEmail(string $username, string $backupFile): array
    {
        try {
            if (!File::exists($backupFile)) {
                throw new \Exception('Backup file does not exist');
            }

            $emailPath = $this->getEmailPath($username);
            if (!File::exists($emailPath)) {
                File::makeDirectory($emailPath, 0755, true);
            }

            $command = sprintf(
                'tar -xzf %s -C %s',
                $backupFile,
                $emailPath
            );

            Process::run($command);

            return [
                'success' => true,
                'message' => 'Email restored successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to restore email: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore email: ' . $e->getMessage()
            ];
        }
    }

    public function getEmailStats(Email $email): array
    {
        try {
            $emailPath = $this->getEmailPath($email->username);
            $size = $this->getDirectorySize($emailPath);
            $messages = $this->countMessages($emailPath);

            return [
                'success' => true,
                'size' => $size,
                'messages' => $messages,
                'last_modified' => File::lastModified($emailPath)
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get email stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get email stats: ' . $e->getMessage()
            ];
        }
    }

    public function getEmailQuota(Email $email): array
    {
        try {
            $quota = $this->getQuota($email->username);
            return [
                'success' => true,
                'quota' => $quota
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get email quota: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get email quota: ' . $e->getMessage()
            ];
        }
    }

    public function updateEmailQuota(Email $email, int $quota): array
    {
        try {
            $this->setQuota($email->username, $quota);
            return [
                'success' => true,
                'message' => 'Email quota updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update email quota: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update email quota: ' . $e->getMessage()
            ];
        }
    }

    public function sendEmail(array $data): array
    {
        try {
            $required = ['to', 'subject', 'body'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("Missing required field: {$field}");
                }
            }

            $to = $data['to'];
            $subject = $data['subject'];
            $body = $data['body'];
            $from = $data['from'] ?? $this->defaultFrom;
            $fromName = $data['from_name'] ?? $this->defaultFromName;
            $cc = $data['cc'] ?? [];
            $bcc = $data['bcc'] ?? [];
            $attachments = $data['attachments'] ?? [];

            Mail::send([], [], function ($message) use ($to, $subject, $body, $from, $fromName, $cc, $bcc, $attachments) {
                $message->from($from, $fromName)
                    ->to($to)
                    ->subject($subject)
                    ->setBody($body, 'text/html');

                if (!empty($cc)) {
                    $message->cc($cc);
                }

                if (!empty($bcc)) {
                    $message->bcc($bcc);
                }

                foreach ($attachments as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? basename($attachment['path']),
                            'mime' => $attachment['mime'] ?? null
                        ]);
                    }
                }
            });

            return [
                'success' => true,
                'message' => 'Email sent successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send email: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ];
        }
    }

    public function sendTemplateEmail(string $template, array $data): array
    {
        try {
            if (empty($data['to'])) {
                throw new \Exception('Recipient email is required');
            }

            $view = 'emails.' . $template;
            if (!view()->exists($view)) {
                throw new \Exception('Email template not found');
            }

            $to = $data['to'];
            $subject = $data['subject'] ?? Config::get('mail.templates.' . $template . '.subject');
            $from = $data['from'] ?? $this->defaultFrom;
            $fromName = $data['from_name'] ?? $this->defaultFromName;
            $cc = $data['cc'] ?? [];
            $bcc = $data['bcc'] ?? [];
            $attachments = $data['attachments'] ?? [];

            Mail::send($view, $data, function ($message) use ($to, $subject, $from, $fromName, $cc, $bcc, $attachments) {
                $message->from($from, $fromName)
                    ->to($to)
                    ->subject($subject);

                if (!empty($cc)) {
                    $message->cc($cc);
                }

                if (!empty($bcc)) {
                    $message->bcc($bcc);
                }

                foreach ($attachments as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? basename($attachment['path']),
                            'mime' => $attachment['mime'] ?? null
                        ]);
                    }
                }
            });

            return [
                'success' => true,
                'message' => 'Template email sent successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send template email: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send template email: ' . $e->getMessage()
            ];
        }
    }

    public function updateEmailConfig(array $config): array
    {
        try {
            $required = ['driver', 'host', 'port', 'username', 'password', 'encryption'];
            foreach ($required as $field) {
                if (empty($config[$field])) {
                    throw new \Exception("Missing required field: {$field}");
                }
            }

            Config::set('mail', array_merge($this->config, $config));

            return [
                'success' => true,
                'message' => 'Email configuration updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update email config: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update email config: ' . $e->getMessage()
            ];
        }
    }

    public function getEmailConfig(): array
    {
        try {
            return [
                'success' => true,
                'config' => [
                    'driver' => $this->config['driver'],
                    'host' => $this->config['host'],
                    'port' => $this->config['port'],
                    'username' => $this->config['username'],
                    'encryption' => $this->config['encryption'],
                    'from' => [
                        'address' => $this->defaultFrom,
                        'name' => $this->defaultFromName
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get email config: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get email config: ' . $e->getMessage()
            ];
        }
    }

    public function testEmailConfig(): array
    {
        try {
            $testData = [
                'to' => $this->config['username'],
                'subject' => 'Test Email',
                'body' => 'This is a test email to verify your email configuration.',
                'from' => $this->defaultFrom,
                'from_name' => $this->defaultFromName
            ];

            return $this->sendEmail($testData);
        } catch (\Exception $e) {
            Log::error('Failed to test email config: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to test email config: ' . $e->getMessage()
            ];
        }
    }

    public function validateEmailConfig(array $config): array
    {
        try {
            $errors = [];

            if (empty($config['driver'])) {
                $errors[] = 'Mail driver is required';
            }

            if (empty($config['host'])) {
                $errors[] = 'Mail host is required';
            }

            if (empty($config['port'])) {
                $errors[] = 'Mail port is required';
            }

            if (empty($config['username'])) {
                $errors[] = 'Mail username is required';
            }

            if (empty($config['password'])) {
                $errors[] = 'Mail password is required';
            }

            if (empty($config['encryption'])) {
                $errors[] = 'Mail encryption is required';
            }

            if (empty($errors)) {
                return [
                    'success' => true
                ];
            }

            return [
                'success' => false,
                'errors' => $errors
            ];
        } catch (\Exception $e) {
            Log::error('Failed to validate email config: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to validate email config: ' . $e->getMessage()
            ];
        }
    }

    public function createTemplate(string $name, string $subject, string $content): array
    {
        try {
            EmailTemplate::create([
                'name' => $name,
                'subject' => $subject,
                'content' => $content
            ]);

            return [
                'success' => true,
                'message' => 'Email template created successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create email template: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create email template: ' . $e->getMessage()
            ];
        }
    }

    public function updateTemplate(string $name, array $data): array
    {
        try {
            $template = EmailTemplate::where('name', $name)->first();
            if (!$template) {
                throw new \Exception('Email template not found');
            }

            $template->update($data);

            return [
                'success' => true,
                'message' => 'Email template updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update email template: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update email template: ' . $e->getMessage()
            ];
        }
    }

    public function deleteTemplate(string $name): array
    {
        try {
            $template = EmailTemplate::where('name', $name)->first();
            if (!$template) {
                throw new \Exception('Email template not found');
            }

            $template->delete();

            return [
                'success' => true,
                'message' => 'Email template deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete email template: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete email template: ' . $e->getMessage()
            ];
        }
    }

    public function getTemplates(): array
    {
        try {
            $templates = EmailTemplate::all();
            return [
                'success' => true,
                'templates' => $templates
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get email templates: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get email templates: ' . $e->getMessage()
            ];
        }
    }

    public function getEmailLogs(array $filters = []): array
    {
        try {
            $query = EmailLog::query();

            if (isset($filters['recipient'])) {
                $query->where('recipient', 'like', '%' . $filters['recipient'] . '%');
            }

            if (isset($filters['template'])) {
                $query->where('template', $filters['template']);
            }

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
            Log::error('Failed to get email logs: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get email logs: ' . $e->getMessage()
            ];
        }
    }

    public function getEmailStats(): array
    {
        try {
            $total = EmailLog::count();
            $successful = EmailLog::where('status', true)->count();
            $failed = EmailLog::where('status', false)->count();

            $templates = EmailLog::select('template')
                ->selectRaw('count(*) as count')
                ->groupBy('template')
                ->get();

            $dailyStats = EmailLog::selectRaw('DATE(created_at) as date')
                ->selectRaw('count(*) as total')
                ->selectRaw('sum(case when status = 1 then 1 else 0 end) as successful')
                ->selectRaw('sum(case when status = 0 then 1 else 0 end) as failed')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get();

            return [
                'success' => true,
                'stats' => [
                    'total' => $total,
                    'successful' => $successful,
                    'failed' => $failed,
                    'success_rate' => $total > 0 ? ($successful / $total) * 100 : 0,
                    'templates' => $templates,
                    'daily_stats' => $dailyStats
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get email stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get email stats: ' . $e->getMessage()
            ];
        }
    }

    protected function createEmailAccount(string $username, string $password): void
    {
        if ($this->isWindows) {
            $this->createWindowsEmailAccount($username, $password);
        } else {
            $this->createLinuxEmailAccount($username, $password);
        }
    }

    protected function updateEmailPassword(string $username, string $password): void
    {
        if ($this->isWindows) {
            $this->updateWindowsEmailPassword($username, $password);
        } else {
            $this->updateLinuxEmailPassword($username, $password);
        }
    }

    protected function deleteEmailAccount(string $username): void
    {
        if ($this->isWindows) {
            $this->deleteWindowsEmailAccount($username);
        } else {
            $this->deleteLinuxEmailAccount($username);
        }
    }

    protected function enableEmailAccount(string $username): void
    {
        if ($this->isWindows) {
            $this->enableWindowsEmailAccount($username);
        } else {
            $this->enableLinuxEmailAccount($username);
        }
    }

    protected function disableEmailAccount(string $username): void
    {
        if ($this->isWindows) {
            $this->disableWindowsEmailAccount($username);
        } else {
            $this->disableLinuxEmailAccount($username);
        }
    }

    protected function createWindowsEmailAccount(string $username, string $password): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Create the email account in the Windows mail server
        // 2. Set up the necessary directories and permissions
    }

    protected function createLinuxEmailAccount(string $username, string $password): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Create the email account in Postfix/Dovecot
        // 2. Set up the necessary directories and permissions
    }

    protected function updateWindowsEmailPassword(string $username, string $password): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Update the email account password in the Windows mail server
    }

    protected function updateLinuxEmailPassword(string $username, string $password): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Update the email account password in Postfix/Dovecot
    }

    protected function deleteWindowsEmailAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Delete the email account from the Windows mail server
        // 2. Remove the account directories and files
    }

    protected function deleteLinuxEmailAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Delete the email account from Postfix/Dovecot
        // 2. Remove the account directories and files
    }

    protected function enableWindowsEmailAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Enable the email account in the Windows mail server
    }

    protected function enableLinuxEmailAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Enable the email account in Postfix/Dovecot
    }

    protected function disableWindowsEmailAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Disable the email account in the Windows mail server
    }

    protected function disableLinuxEmailAccount(string $username): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Disable the email account in Postfix/Dovecot
    }

    protected function getEmailPath(string $username): string
    {
        if ($this->isWindows) {
            return 'C:\\laragon\\mail\\' . $username;
        } else {
            return '/var/mail/' . $username;
        }
    }

    protected function getDirectorySize(string $path): int
    {
        $size = 0;
        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    protected function countMessages(string $path): int
    {
        return count(File::allFiles($path));
    }

    protected function getQuota(string $username): array
    {
        if ($this->isWindows) {
            return $this->getWindowsQuota($username);
        } else {
            return $this->getLinuxQuota($username);
        }
    }

    protected function setQuota(string $username, int $quota): void
    {
        if ($this->isWindows) {
            $this->setWindowsQuota($username, $quota);
        } else {
            $this->setLinuxQuota($username, $quota);
        }
    }

    protected function getWindowsQuota(string $username): array
    {
        // This is a placeholder. In a real application, you would:
        // 1. Get the email quota from the Windows mail server
        return [
            'used' => 0,
            'total' => 0
        ];
    }

    protected function getLinuxQuota(string $username): array
    {
        // This is a placeholder. In a real application, you would:
        // 1. Get the email quota from Postfix/Dovecot
        return [
            'used' => 0,
            'total' => 0
        ];
    }

    protected function setWindowsQuota(string $username, int $quota): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Set the email quota in the Windows mail server
    }

    protected function setLinuxQuota(string $username, int $quota): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Set the email quota in Postfix/Dovecot
    }

    protected function parseTemplate(string $template, array $data): string
    {
        $placeholders = [];
        $values = [];

        foreach ($data as $key => $value) {
            $placeholders[] = '{' . $key . '}';
            $values[] = $value;
        }

        return str_replace($placeholders, $values, $template);
    }

    protected function logEmail(string $recipient, string $subject, string $template, bool $status, string $error = null): void
    {
        EmailLog::create([
            'recipient' => $recipient,
            'subject' => $subject,
            'template' => $template,
            'status' => $status,
            'error' => $error
        ]);
    }
} 