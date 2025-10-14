<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Notification::where('user_id', $user->id)
            ->with('user')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->has('read') && $request->read !== 'all') {
            $query->where('read', $request->read === 'true');
        }

        // Pagination
        $perPage = min($request->get('per_page', 20), 50);
        $notifications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications->getCollection(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
                'from' => $notifications->firstItem(),
                'to' => $notifications->lastItem(),
            ],
            'filters' => [
                'type' => $request->type ?? 'all',
                'priority' => $request->priority ?? 'all',
                'category' => $request->category ?? 'all',
                'read' => $request->read ?? 'all',
            ],
        ]);
    }

    /**
     * Get unread notifications count.
     */
    public function unreadCount(): JsonResponse
    {
        $user = Auth::user();

        $count = Notification::where('user_id', $user->id)
            ->where('read', false)
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Get recent notifications (for real-time updates).
     */
    public function recent(Request $request): JsonResponse
    {
        $user = Auth::user();

        $since = $request->get('since');
        $query = Notification::where('user_id', $user->id)
            ->where('created_at', '>', $since)
            ->orderBy('created_at', 'desc')
            ->limit(10);

        $notifications = $query->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        // Ensure user can only mark their own notifications as read
        if ($notification->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $marked = $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => $marked ? 'Notification marked as read' : 'Notification was already read',
            'data' => $notification->fresh(),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();

        $count = Notification::where('user_id', $user->id)
            ->where('read', false)
            ->update([
                'read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "Marked {$count} notifications as read",
            'count' => $count,
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Notification $notification): JsonResponse
    {
        // Ensure user can only delete their own notifications
        if ($notification->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * Delete all notifications for the user.
     */
    public function destroyAll(): JsonResponse
    {
        $user = Auth::user();

        $count = Notification::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted {$count} notifications",
            'count' => $count,
        ]);
    }

    /**
     * Delete all read notifications.
     */
    public function destroyRead(): JsonResponse
    {
        $user = Auth::user();

        $count = Notification::where('user_id', $user->id)
            ->where('read', true)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted {$count} read notifications",
            'count' => $count,
        ]);
    }

    /**
     * Create a test notification (for development).
     */
    public function createTest(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:reservation_reminder,lab_available,reservation_confirmed,system_alert',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'required|string|in:low,medium,high',
            'category' => 'nullable|string|in:lab,system,reservation',
            'action_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'title' => $request->title,
            'message' => $request->message,
            'priority' => $request->priority,
            'category' => $request->category,
            'action_url' => $request->action_url,
            'read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test notification created',
            'data' => $notification,
        ]);
    }

    /**
     * Get notification statistics.
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();

        $stats = Notification::where('user_id', $user->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN read = 0 THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN priority = "high" THEN 1 ELSE 0 END) as high_priority,
                SUM(CASE WHEN priority = "medium" THEN 1 ELSE 0 END) as medium_priority,
                SUM(CASE WHEN priority = "low" THEN 1 ELSE 0 END) as low_priority,
                SUM(CASE WHEN type = "lab_available" THEN 1 ELSE 0 END) as lab_notifications,
                SUM(CASE WHEN type = "system_alert" THEN 1 ELSE 0 END) as system_notifications,
                SUM(CASE WHEN type = "reservation_reminder" THEN 1 ELSE 0 END) as reservation_notifications
            ')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
