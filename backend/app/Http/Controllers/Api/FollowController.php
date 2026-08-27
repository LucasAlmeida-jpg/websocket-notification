<?php

namespace App\Http\Controllers\Api;

use App\Events\NotificationCreated;
use App\Http\Controllers\Controller;
use App\Models\Follow;
use App\Models\User;
use App\Notifications\SocialNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function toggle(Request $request, User $user): JsonResponse
    {
        $viewer = $request->user();

        if ($viewer->id === $user->id) {
            return response()->json(['message' => 'Você não pode seguir a si mesmo.'], 422);
        }

        $follow = Follow::where('follower_id', $viewer->id)->where('following_id', $user->id)->first();

        if ($follow) {
            $follow->delete();

            return response()->json(['following' => false]);
        }

        Follow::create(['follower_id' => $viewer->id, 'following_id' => $user->id]);

        $user->notify(new SocialNotification(
            actor:        $viewer,
            type:         'follow',
            message:      'começou a te seguir.',
            resourceType: null,
            resourceId:   null,
        ));

        try {
            broadcast(new NotificationCreated($user, [
                'type'          => 'follow',
                'message'       => 'começou a te seguir.',
                'actor_id'      => $viewer->id,
                'actor_name'    => $viewer->name,
                'resource_type' => null,
                'resource_id'   => null,
                'created_at'    => now()->toIso8601String(),
            ]))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('WebSocket broadcast failed: ' . $e->getMessage());
        }

        return response()->json(['following' => true]);
    }
}
