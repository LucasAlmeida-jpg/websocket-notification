<?php

namespace App\Http\Controllers\Api;

use App\Events\NotificationCreated;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use App\Notifications\SocialNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    public function send(Request $request, Post $post): JsonResponse
    {
        $data   = $request->validate(['user_id' => 'required|integer|exists:users,id']);
        $sender = $request->user();

        if ((int) $data['user_id'] === $sender->id) {
            return response()->json(['message' => 'Você não pode enviar para si mesmo.'], 422);
        }

        $recipient = User::findOrFail($data['user_id']);

        $recipient->notify(new SocialNotification(
            actor:        $sender,
            type:         'share',
            message:      'enviou um post para você.',
            resourceType: 'post',
            resourceId:   $post->id,
        ));

        try {
            broadcast(new NotificationCreated($recipient, [
                'type'          => 'share',
                'message'       => 'enviou um post para você.',
                'actor_id'      => $sender->id,
                'actor_name'    => $sender->name,
                'resource_type' => 'post',
                'resource_id'   => $post->id,
                'created_at'    => now()->toIso8601String(),
            ]))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('WebSocket broadcast failed: ' . $e->getMessage());
        }

        return response()->json(['sent' => true]);
    }
}
