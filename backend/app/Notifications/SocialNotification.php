<?php

namespace App\Notifications;

use App\Events\NotificationCreated;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SocialNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // Supported types: like, comment, follow, mention
    public function __construct(
        public readonly User $actor,
        public readonly string $type,
        public readonly string $message,
        public readonly ?string $resourceType = null,
        public readonly ?int $resourceId = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => $this->type,
            'message'       => $this->message,
            'actor_id'      => $this->actor->id,
            'actor_name'    => $this->actor->name,
            'resource_type' => $this->resourceType,
            'resource_id'   => $this->resourceId,
        ];
    }

    public function afterCommit(): bool
    {
        return true;
    }
}
