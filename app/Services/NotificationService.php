<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Events\NotificationSent;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class NotificationService
{
    protected $channels = ['database', 'email', 'sms'];
    protected $isWindows;
    protected $notificationPath;
    protected $backupPath;
    protected $tempPath;
    protected $maxNotifications;
    protected $notificationTypes;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->notificationPath = $this->isWindows ? 'C:\\laragon\\notifications' : '/var/notifications';
        $this->backupPath = storage_path('backups/notifications');
        $this->tempPath = storage_path('temp/notifications');
        $this->maxNotifications = config('notification.max_notifications', 100);
        $this->notificationTypes = [
            'system' => [
                'name' => 'System Notification',
                'description' => 'System-wide notifications'
            ],
            'security' => [
                'name' => 'Security Notification',
                'description' => 'Security-related notifications'
            ],
            'update' => [
                'name' => 'Update Notification',
                'description' => 'System update notifications'
            ],
            'backup' => [
                'name' => 'Backup Notification',
                'description' => 'Backup-related notifications'
            ],
            'user' => [
                'name' => 'User Notification',
                'description' => 'User-specific notifications'
            ]
        ];
        $this->channels = [
            'email' => [
                'name' => 'Email',
                'description' => 'Send notifications via email'
            ],
            'sms' => [
                'name' => 'SMS',
                'description' => 'Send notifications via SMS'
            ],
            'push' => [
                'name' => 'Push Notification',
                'description' => 'Send push notifications'
            ],
            'in_app' => [
                'name' => 'In-App Notification',
                'description' => 'Show notifications in the application'
            ]
        ];

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }

        if (!File::exists($this->tempPath)) {
            File::makeDirectory($this->tempPath, 0755, true);
        }
    }

    public function send(User $user, string $title, string $message, string $type = 'info', array $data = []): Notification
    {
        // Create notification record
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data,
            'read_at' => null
        ]);

        // Send through configured channels
        $this->sendThroughChannels($notification);

        return $notification;
    }

    public function sendToAll(string $title, string $message, string $type = 'info', array $data = []): void
    {
        User::chunk(100, function ($users) use ($title, $message, $type, $data) {
            foreach ($users as $user) {
                $this->send($user, $title, $message, $type, $data);
            }
        });
    }

    public function sendToRole(string $role, string $title, string $message, string $type = 'info', array $data = []): void
    {
        User::role($role)->chunk(100, function ($users) use ($title, $message, $type, $data) {
            foreach ($users as $user) {
                $this->send($user, $title, $message, $type, $data);
            }
        });
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->update(['read_at' => now()]);
    }

    public function markAllAsRead(User $user): void
    {
        $user->notifications()->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function deleteNotification(Notification $notification): void
    {
        $notification->delete();
    }

    public function deleteAllNotifications(User $user): void
    {
        $user->notifications()->delete();
    }

    public function getUnreadCount(User $user): int
    {
        return $user->notifications()->whereNull('read_at')->count();
    }

    public function getNotifications(User $user, int $limit = 10, int $offset = 0): array
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->toArray();
    }

    public function getNotificationTypes(): array
    {
        return [
            'info' => 'Information',
            'success' => 'Success',
            'warning' => 'Warning',
            'error' => 'Error',
            'system' => 'System',
            'security' => 'Security',
            'update' => 'Update'
        ];
    }

    protected function sendThroughChannels(Notification $notification): void
    {
        $user = $notification->user;
        $channels = $this->getUserChannels($user);

        foreach ($channels as $channel) {
            try {
                switch ($channel) {
                    case 'email':
                        $this->sendEmail($notification);
                        break;
                    case 'sms':
                        $this->sendSMS($notification);
                        break;
                }
            } catch (\Exception $e) {
                Log::error("Failed to send notification through {$channel}: " . $e->getMessage());
            }
        }
    }

    protected function getUserChannels(User $user): array
    {
        // This is a placeholder. In a real application, you would:
        // 1. Get user's notification preferences
        // 2. Return enabled channels
        return ['database', 'email'];
    }

    protected function sendEmail(Notification $notification): void
    {
        $user = $notification->user;

        Mail::send('emails.notification', [
            'notification' => $notification
        ], function ($message) use ($user, $notification) {
            $message->to($user->email)
                ->subject($notification->title);
        });
    }

    protected function sendSMS(Notification $notification): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Get user's phone number
        // 2. Send SMS using a service like Twilio
    }

    public function getNotificationStats(): array
    {
        return [
            'total' => Notification::count(),
            'unread' => Notification::whereNull('read_at')->count(),
            'by_type' => Notification::selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type')
                ->toArray(),
            'by_channel' => [
                'database' => Notification::count(),
                'email' => Notification::whereNotNull('email_sent_at')->count(),
                'sms' => Notification::whereNotNull('sms_sent_at')->count()
            ]
        ];
    }

    public function getNotificationPreferences(User $user): array
    {
        // This is a placeholder. In a real application, you would:
        // 1. Get user's notification preferences from database
        // 2. Return enabled channels and types
        return [
            'channels' => ['database', 'email'],
            'types' => array_keys($this->getNotificationTypes())
        ];
    }

    public function updateNotificationPreferences(User $user, array $preferences): void
    {
        // This is a placeholder. In a real application, you would:
        // 1. Validate preferences
        // 2. Update user's notification preferences in database
    }

    public function getNotificationHistory(User $user, string $type = null, int $limit = 10): array
    {
        $query = $user->notifications();

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->toArray();
    }

    public function createNotification(array $data): array
    {
        try {
            $notification = Notification::create($data);

            if (isset($data['user_id'])) {
                event(new NotificationSent($notification));
            }

            return [
                'success' => true,
                'message' => 'Notification created successfully',
                'notification' => $notification
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create notification: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create notification: ' . $e->getMessage()
            ];
        }
    }

    public function updateNotification(Notification $notification, array $data): array
    {
        try {
            $notification->update($data);

            return [
                'success' => true,
                'message' => 'Notification updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update notification: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update notification: ' . $e->getMessage()
            ];
        }
    }

    public function deleteNotification(Notification $notification): array
    {
        try {
            $notification->delete();

            return [
                'success' => true,
                'message' => 'Notification deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete notification: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete notification: ' . $e->getMessage()
            ];
        }
    }

    public function markAsRead(Notification $notification): array
    {
        try {
            $notification->update(['read_at' => now()]);

            return [
                'success' => true,
                'message' => 'Notification marked as read'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to mark notification as read: ' . $e->getMessage()
            ];
        }
    }

    public function markAllAsRead(User $user): array
    {
        try {
            Notification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return [
                'success' => true,
                'message' => 'All notifications marked as read'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to mark all notifications as read: ' . $e->getMessage()
            ];
        }
    }

    public function getUserNotifications(User $user, array $filters = []): array
    {
        try {
            $query = Notification::where('user_id', $user->id);

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['read'])) {
                if ($filters['read']) {
                    $query->whereNotNull('read_at');
                } else {
                    $query->whereNull('read_at');
                }
            }

            if (isset($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }

            $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

            return [
                'success' => true,
                'notifications' => $notifications
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get user notifications: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get user notifications: ' . $e->getMessage()
            ];
        }
    }

    public function getUnreadCount(User $user): array
    {
        try {
            $count = Notification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count();

            return [
                'success' => true,
                'count' => $count
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get unread notification count: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get unread notification count: ' . $e->getMessage()
            ];
        }
    }

    public function clearNotifications(User $user): array
    {
        try {
            Notification::where('user_id', $user->id)->delete();

            return [
                'success' => true,
                'message' => 'All notifications cleared'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to clear notifications: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to clear notifications: ' . $e->getMessage()
            ];
        }
    }

    public function getNotificationStats(User $user): array
    {
        try {
            $total = Notification::where('user_id', $user->id)->count();
            $unread = Notification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count();
            $read = $total - $unread;

            $types = Notification::where('user_id', $user->id)
                ->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->get();

            return [
                'success' => true,
                'stats' => [
                    'total' => $total,
                    'unread' => $unread,
                    'read' => $read,
                    'types' => $types
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get notification stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get notification stats: ' . $e->getMessage()
            ];
        }
    }

    public function backupNotifications(): array
    {
        try {
            $backupFile = $this->backupPath . '/notifications_' . date('Y-m-d_H-i-s') . '.json';
            $notifications = Notification::all()->toArray();
            File::put($backupFile, json_encode($notifications, JSON_PRETTY_PRINT));

            if (File::exists($backupFile)) {
                return [
                    'success' => true,
                    'message' => 'Notifications backed up successfully',
                    'file' => $backupFile
                ];
            } else {
                throw new \Exception('Backup file was not created');
            }
        } catch (\Exception $e) {
            Log::error('Failed to backup notifications: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to backup notifications: ' . $e->getMessage()
            ];
        }
    }

    public function restoreNotifications(string $backupFile): array
    {
        try {
            if (!File::exists($backupFile)) {
                throw new \Exception('Backup file does not exist');
            }

            $notifications = json_decode(File::get($backupFile), true);
            if (!$notifications) {
                throw new \Exception('Invalid backup file format');
            }

            foreach ($notifications as $notification) {
                Notification::create($notification);
            }

            return [
                'success' => true,
                'message' => 'Notifications restored successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to restore notifications: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore notifications: ' . $e->getMessage()
            ];
        }
    }

    public function sendNotification(string $type, string $message, array $data = [], array $channels = ['in_app']): array
    {
        try {
            if (!isset($this->notificationTypes[$type])) {
                throw new \Exception("Invalid notification type: {$type}");
            }

            $notification = Notification::create([
                'type' => $type,
                'message' => $message,
                'data' => $data,
                'channels' => $channels,
                'status' => 'pending'
            ]);

            foreach ($channels as $channel) {
                if (!isset($this->channels[$channel])) {
                    continue;
                }

                switch ($channel) {
                    case 'email':
                        $this->sendEmailNotification($notification);
                        break;
                    case 'sms':
                        $this->sendSMSNotification($notification);
                        break;
                    case 'push':
                        $this->sendPushNotification($notification);
                        break;
                    case 'in_app':
                        $this->sendInAppNotification($notification);
                        break;
                }
            }

            $notification->update(['status' => 'sent']);

            return [
                'success' => true,
                'message' => 'Notification sent successfully',
                'notification' => $notification
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send notification: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ];
        }
    }

    public function getNotifications(array $filters = []): array
    {
        try {
            $query = Notification::query();

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['channel'])) {
                $query->whereJsonContains('channels', $filters['channel']);
            }

            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }

            $notifications = $query->orderBy('created_at', 'desc')
                ->paginate($filters['per_page'] ?? 15);

            return [
                'success' => true,
                'notifications' => $notifications
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get notifications: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get notifications: ' . $e->getMessage()
            ];
        }
    }

    public function getNotificationStats(): array
    {
        try {
            $stats = [
                'total_notifications' => 0,
                'notifications_by_type' => [],
                'notifications_by_channel' => [],
                'notifications_by_status' => [],
                'last_notification' => null
            ];

            foreach ($this->notificationTypes as $type => $info) {
                $stats['notifications_by_type'][$type] = 0;
            }

            foreach ($this->channels as $channel => $info) {
                $stats['notifications_by_channel'][$channel] = 0;
            }

            $notifications = Notification::all();
            foreach ($notifications as $notification) {
                $stats['total_notifications']++;
                $stats['notifications_by_type'][$notification->type]++;
                foreach ($notification->channels as $channel) {
                    $stats['notifications_by_channel'][$channel]++;
                }
                $stats['notifications_by_status'][$notification->status] = ($stats['notifications_by_status'][$notification->status] ?? 0) + 1;

                if (!$stats['last_notification'] || $notification->created_at > $stats['last_notification']) {
                    $stats['last_notification'] = $notification->created_at;
                }
            }

            return [
                'success' => true,
                'stats' => $stats
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get notification stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get notification stats: ' . $e->getMessage()
            ];
        }
    }

    public function markAsRead(int $notificationId): array
    {
        try {
            $notification = Notification::findOrFail($notificationId);
            $notification->update(['read_at' => now()]);

            return [
                'success' => true,
                'message' => 'Notification marked as read'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to mark notification as read: ' . $e->getMessage()
            ];
        }
    }

    public function deleteNotification(int $notificationId): array
    {
        try {
            $notification = Notification::findOrFail($notificationId);
            $notification->delete();

            return [
                'success' => true,
                'message' => 'Notification deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete notification: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete notification: ' . $e->getMessage()
            ];
        }
    }

    protected function sendEmailNotification(Notification $notification): void
    {
        $users = User::where('email_notifications', true)->get();
        foreach ($users as $user) {
            Mail::to($user->email)->send(new \App\Mail\Notification($notification));
        }
    }

    protected function sendSMSNotification(Notification $notification): void
    {
        $users = User::where('sms_notifications', true)->get();
        foreach ($users as $user) {
            // Implement SMS sending logic here
            // This could be using a service like Twilio, Nexmo, etc.
        }
    }

    protected function sendPushNotification(Notification $notification): void
    {
        $users = User::where('push_notifications', true)->get();
        foreach ($users as $user) {
            // Implement push notification logic here
            // This could be using Firebase Cloud Messaging, OneSignal, etc.
        }
    }

    protected function sendInAppNotification(Notification $notification): void
    {
        $users = User::where('in_app_notifications', true)->get();
        foreach ($users as $user) {
            // Store notification in database for in-app display
            $user->notifications()->create([
                'type' => $notification->type,
                'message' => $notification->message,
                'data' => $notification->data
            ]);
        }
    }
} 