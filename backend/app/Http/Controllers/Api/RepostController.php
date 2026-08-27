<?php

namespace App\Http\Controllers\Api;

use App\Events\NotificationCreated;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use App\Notifications\SocialNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepostController extends Controller
{
    public function toggle(Request $request, Post $post): JsonResponse
    {
        $user     = $request->user();
        $existing = $post->reposts()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
            $post->decrement('reposts_count');
            $post->refresh();

            return response()->json([
                'reposted'      => false,
                'reposts_count' => (int) $post->reposts_count,
            ]);
        }

        $post->reposts()->create(['user_id' => $user->id]);
        $post->increment('reposts_count');
        $post->refresh();

        if ($post->user_id !== $user->id) {
            $this->notify($post->user, $user, 'repost', 'repostou seu post.', $post->id);
        }

        return response()->json([
            'reposted'      => true,
            'reposts_count' => (int) $post->reposts_count,
        ]);
    }

    private function notify(User $recipient, User $actor, string $type, string $message, int $resourceId): void
    {
        $recipient->notify(new SocialNotification(
            actor:        $actor,
            type:         $type,
            message:      $message,
            resourceType: 'post',
            resourceId:   $resourceId,
        ));

        try {
            broadcast(new NotificationCreated($recipient, [
                'type'          => $type,
                'message'       => $message,
                'actor_id'      => $actor->id,
                'actor_name'    => $actor->name,
                'resource_type' => 'post',
                'resource_id'   => $resourceId,
                'created_at'    => now()->toIso8601String(),
            ]))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('WebSocket broadcast failed: ' . $e->getMessage());
        }
    }
}
