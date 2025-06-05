<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        try {
            $notifications = Notification::latest()->paginate(10);
            return view('admin.notifications.index', compact('notifications'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch notifications: ' . $e->getMessage());
            return back()->with('error', 'Failed to fetch notifications');
        }
    }

    public function send(Request $request)
    {
        try {
            $notification = Notification::create([
                'type' => $request->type,
                'title' => $request->title,
                'message' => $request->message,
                'data' => $request->data ?? [],
                'user_id' => $request->user_id,
                'channel' => $request->channel ?? 'in-app',
                'status' => 'pending'
            ]);

            // Dispatch notification job
            SendNotificationJob::dispatch($notification, $request->channels ?? ['in-app'])
                ->onQueue('notifications');

            return response()->json([
                'success' => true,
                'message' => 'Notification job queued successfully',
                'notification' => $notification
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send notification: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to send notification'], 500);
        }
    }

    public function markAsRead($id)
    {
        try {
            $notification = Notification::findOrFail($id);
            $notification->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to mark notification as read'], 500);
        }
    }

    public function delete($id)
    {
        try {
            $notification = Notification::findOrFail($id);
            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete notification: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete notification'], 500);
        }
    }

    public function getStats()
    {
        try {
            $stats = $this->notificationService->getNotificationStats();
            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Failed to fetch notification stats: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch notification stats'], 500);
        }
    }
} 