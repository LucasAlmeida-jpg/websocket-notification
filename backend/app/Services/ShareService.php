<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Post;
use App\Models\User;

class ShareService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function send(Post $post, User $recipient, User $sender): void
    {
        $this->notifications->notify(
            recipient:    $recipient,
            actor:        $sender,
            type:         NotificationType::Share,
            message:      'enviou um post para você.',
            resourceType: 'post',
            resourceId:   $post->id,
        );
    }
}
