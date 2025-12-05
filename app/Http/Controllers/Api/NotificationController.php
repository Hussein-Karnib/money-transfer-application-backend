<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Support\NotificationHelper;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get notifications for authenticated user
     */
    public function index(Request $request)
    {
        $limit = $request->query('limit', 20);
        $offset = $request->query('offset', 0);
        $unreadOnly = $request->query('unread_only', false);

        $query = NotificationLog::where('notifiable_type', get_class($request->user()))
            ->where('notifiable_id', $request->user()->id)
            ->orderByDesc('sent_at');

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        $total = $query->count();
        $notifications = $query->skip($offset)->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
            'unread_count' => NotificationHelper::getUnreadCount($request->user()),
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(Request $request)
    {
        return response()->json([
            'success' => true,
            'unread_count' => NotificationHelper::getUnreadCount($request->user()),
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $notificationId)
    {
        $notification = NotificationLog::findOrFail($notificationId);

        if ($notification->notifiable_id !== $request->user()->id || 
            $notification->notifiable_type !== get_class($request->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        NotificationHelper::markAsRead($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        NotificationHelper::markAllAsRead($request->user());

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Get notification preferences
     */
    public function getPreferences(Request $request)
    {
        $preferences = $request->user()->getOrCreateNotificationPreference();

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'transfer_initiated' => 'boolean',
            'transfer_status_update' => 'boolean',
            'beneficiary_action' => 'boolean',
            'verification_required' => 'boolean',
            'promotion_alert' => 'boolean',
            'security_alert' => 'boolean',
            'commission_earned' => 'boolean',
            'settlement_alert' => 'boolean',
            'admin_message' => 'boolean',
            'system_alert' => 'boolean',
            'channel_email' => 'boolean',
            'channel_sms' => 'boolean',
            'channel_push' => 'boolean',
            'channel_in_app' => 'boolean',
            'channel_dashboard' => 'boolean',
            'email_frequency' => 'in:instant,daily,weekly,never',
            'sms_frequency' => 'in:instant,daily,weekly,never',
            'digest_send_time' => 'date_format:H:i',
            'mute_all' => 'boolean',
            'mute_until' => 'nullable|date',
        ]);

        $success = NotificationHelper::updatePreferences($request->user(), $request->validated());

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Preferences updated' : 'Failed to update preferences',
        ]);
    }

    /**
     * Delete notification
     */
    public function delete(Request $request, $notificationId)
    {
        $notification = NotificationLog::findOrFail($notificationId);

        if ($notification->notifiable_id !== $request->user()->id || 
            $notification->notifiable_type !== get_class($request->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted',
        ]);
    }

    /**
     * Mute notifications temporarily
     */
    public function mute(Request $request)
    {
        $request->validate([
            'until' => 'nullable|date|after:now',
            'mute_all' => 'boolean|required',
        ]);

        $preferences = $request->user()->getOrCreateNotificationPreference();
        $preferences->update([
            'mute_all' => $request->boolean('mute_all'),
            'mute_until' => $request->input('until'),
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->boolean('mute_all') ? 'Notifications muted' : 'Notifications unmuted',
        ]);
    }
}
