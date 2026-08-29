<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Post;
use App\Models\Repost;
use App\Models\User;

class RepostService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function toggle(Post $post, User $user): array
    {
        $existing = Repost::where('post_id', $post->id)->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
            $post->decrement('reposts_count');
            $reposted = false;
        } else {
            Repost::create(['post_id' => $post->id, 'user_id' => $user->id]);
            $post->increment('reposts_count');
            $reposted = true;

            if ($post->user_id !== $user->id) {
                $this->notifications->notify(
                    recipient:    $post->user,
                    actor:        $user,
                    type:         NotificationType::Repost,
                    message:      'repostou seu post.',
                    resourceType: 'post',
                    resourceId:   $post->id,
                );
            }
        }

        return ['reposted' => $reposted, 'reposts_count' => $post->fresh()->reposts_count];
    }
}
