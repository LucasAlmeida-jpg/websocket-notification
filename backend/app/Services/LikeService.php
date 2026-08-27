<?php

namespace App\Services;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;

class LikeService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function toggle(Post $post, User $user): array
    {
        $existing = Like::where('post_id', $post->id)->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
            $post->decrement('likes_count');
            $liked = false;
        } else {
            Like::create(['post_id' => $post->id, 'user_id' => $user->id]);
            $post->increment('likes_count');
            $liked = true;

            if ($post->user_id !== $user->id) {
                $this->notifications->notify(
                    recipient:    $post->user,
                    actor:        $user,
                    type:         'like',
                    message:      'curtiu seu post.',
                    resourceType: 'post',
                    resourceId:   $post->id,
                );
            }
        }

        return ['liked' => $liked, 'likes_count' => $post->fresh()->likes_count];
    }
}
