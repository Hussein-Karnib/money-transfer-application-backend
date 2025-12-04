<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data'    => $user->notifications()->orderBy('created_at', 'desc')->paginate(20),
        ]);
    }

    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data'    => $user->unreadNotifications()->orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $user = $request->user();

        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        // Check if this is a web request
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
            ]);
        }

        // Web request - redirect back
        return redirect()->route('app.notifications.index')->with('success', 'Notification marked as read');
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        // Check if this is a web request
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
            ]);
        }

        // Web request - redirect back
        return redirect()->route('app.notifications.index')->with('success', 'All notifications marked as read');
    }
}
