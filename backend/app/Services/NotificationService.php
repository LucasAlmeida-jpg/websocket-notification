<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\User;
use App\Notifications\SocialNotification;

class NotificationService
{
    public function notify(
        User $recipient,
        User $actor,
        string $type,
        string $message,
        string $resourceType,
        int $resourceId
    ): void {
        $recipient->notify(new SocialNotification(
            actor:        $actor,
            type:         $type,
            message:      $message,
            resourceType: $resourceType,
            resourceId:   $resourceId,
        ));

        try {
            broadcast(new NotificationCreated($recipient, [
                'type'          => $type,
                'message'       => $message,
                'actor_id'      => $actor->id,
                'actor_name'    => $actor->name,
                'resource_type' => $resourceType,
                'resource_id'   => $resourceId,
                'created_at'    => now()->toIso8601String(),
            ]))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('WebSocket broadcast failed: ' . $e->getMessage());
        }
    }
}
