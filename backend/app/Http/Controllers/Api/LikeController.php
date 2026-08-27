<?php

namespace App\Http\Controllers\Api;

use App\Events\NotificationCreated;
use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Post;
use App\Notifications\SocialNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function toggle(Request $request, Post $post): JsonResponse
    {
        $user  = $request->user();
        $liked = Like::where('user_id', $user->id)->where('post_id', $post->id)->first();

        if ($liked) {
            $liked->delete();
            $post->decrement('likes_count');

            return response()->json(['liked' => false, 'likes_count' => $post->likes_count - 1]);
        }

        Like::create(['user_id' => $user->id, 'post_id' => $post->id]);
        $post->increment('likes_count');

        if ($post->user_id !== $user->id) {
            $post->user->notify(new SocialNotification(
                actor:        $user,
                type:         'like',
                message:      'curtiu seu post.',
                resourceType: 'post',
                resourceId:   $post->id,
            ));

            try {
                broadcast(new NotificationCreated($post->user, [
                    'type'          => 'like',
                    'message'       => 'curtiu seu post.',
                    'actor_id'      => $user->id,
                    'actor_name'    => $user->name,
                    'resource_type' => 'post',
                    'resource_id'   => $post->id,
                    'created_at'    => now()->toIso8601String(),
                ]))->toOthers();
            } catch (\Throwable $e) {
                logger()->warning('WebSocket broadcast failed: ' . $e->getMessage());
            }
        }

        return response()->json(['liked' => true, 'likes_count' => $post->likes_count + 1]);
    }
}
