<?php

namespace App\Services;

use App\Models\Follow;
use App\Models\User;

class FollowService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function toggle(User $target, User $actor): array
    {
        $existing = Follow::where('follower_id', $actor->id)
            ->where('following_id', $target->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $following = false;
        } else {
            Follow::create(['follower_id' => $actor->id, 'following_id' => $target->id]);
            $following = true;

            $this->notifications->notify(
                recipient:    $target,
                actor:        $actor,
                type:         'follow',
                message:      'começou a te seguir.',
                resourceType: 'user',
                resourceId:   $actor->id,
            );
        }

        return ['following' => $following];
    }

    public function getFollowing(User $user): array
    {
        return $user->following()
            ->with('following')
            ->get()
            ->map(fn($f) => [
                'id'     => $f->following->id,
                'name'   => $f->following->name,
                'avatar' => $f->following->avatar,
            ])
            ->values()
            ->all();
    }
}
