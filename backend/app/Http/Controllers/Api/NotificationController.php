<?php

namespace App\Http\Controllers\Api;

use App\Events\NotificationCreated;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\SocialNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** List notifications for the authenticated user. */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    /** Count unread notifications. */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /** Mark a single notification as read. */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Notificação marcada como lida.']);
    }

    /** Mark all notifications as read. */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'Todas as notificações foram marcadas como lidas.']);
    }

    /** Delete a notification. */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->findOrFail($id)->delete();

        return response()->json(['message' => 'Notificação removida.']);
    }

    /**
     * Send a social notification to a target user.
     * This simulates actions like: like, comment, follow, mention.
     */
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipient_id'  => 'required|integer|exists:users,id|different:' . $request->user()->id,
            'type'          => 'required|string|in:like,comment,follow,mention',
            'message'       => 'required|string|max:255',
            'resource_type' => 'nullable|string|max:50',
            'resource_id'   => 'nullable|integer',
        ]);

        $recipient = User::findOrFail($data['recipient_id']);
        $actor     = $request->user();

        $notification = new SocialNotification(
            actor:        $actor,
            type:         $data['type'],
            message:      $data['message'],
            resourceType: $data['resource_type'] ?? null,
            resourceId:   $data['resource_id'] ?? null,
        );

        $recipient->notify($notification);

        // Broadcast via WebSocket (requires Soketi/Pusher running)
        $notificationData = [
            'type'          => $data['type'],
            'message'       => $data['message'],
            'actor_id'      => $actor->id,
            'actor_name'    => $actor->name,
            'resource_type' => $data['resource_type'] ?? null,
            'resource_id'   => $data['resource_id'] ?? null,
            'created_at'    => now()->toIso8601String(),
        ];

        try {
            broadcast(new NotificationCreated($recipient, $notificationData))->toOthers();
        } catch (\Throwable $e) {
            // WebSocket server unavailable — notification still persisted in DB
            logger()->warning('WebSocket broadcast failed: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Notificação enviada com sucesso.'], 201);
    }
}
