<?php

namespace App\Http\Controllers\Api;

use App\Events\NotificationCreated;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use App\Notifications\SocialNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'body'      => 'required|string|max:500',
            'parent_id' => 'nullable|integer|exists:posts,id',
        ]);

        $post = Post::create([
            'user_id'   => $request->user()->id,
            'body'      => $data['body'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        if (!empty($data['parent_id'])) {
            $parent = Post::find($data['parent_id']);
            $parent->increment('replies_count');

            if ($parent->user_id !== $request->user()->id) {
                $this->notify($parent->user, $request->user(), 'comment', 'respondeu ao seu post.', $post->id);
            }
        }

        $this->notifyMentions($data['body'], $request->user(), $post->id);

        $post->load('user');

        return response()->json($this->formatPost($post, $request->user()), 201);
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        $post->load('user');
        $replies = $post->replies()->with('user')->get();

        return response()->json([
            'post'    => $this->formatPost($post, $request->user()),
            'replies' => $replies->map(fn($r) => $this->formatPost($r, $request->user())),
        ]);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($post->parent_id) {
            Post::find($post->parent_id)?->decrement('replies_count');
        }

        $post->delete();

        return response()->json(['message' => 'Post removido.']);
    }

    public static function formatPost(Post $post, ?User $viewer): array
    {
        return [
            'id'             => $post->id,
            'body'           => $post->body,
            'likes_count'    => (int) ($post->likes_count ?? 0),
            'replies_count'  => (int) ($post->replies_count ?? 0),
            'reposts_count'  => (int) ($post->reposts_count ?? 0),
            'liked'          => $viewer ? $post->isLikedBy($viewer) : false,
            'reposted'       => $viewer ? $post->isRepostedBy($viewer) : false,
            'created_at'     => $post->created_at->toIso8601String(),
            'user'           => [
                'id'     => $post->user->id,
                'name'   => $post->user->name,
                'avatar' => $post->user->avatar,
            ],
        ];
    }

    private function notifyMentions(string $body, User $actor, int $postId): void
    {
        preg_match_all('/@([\w]+)/', $body, $matches);
        $tokens = array_unique($matches[1] ?? []);
        if (empty($tokens)) return;

        $mentioned = User::where('id', '!=', $actor->id)
            ->where(function ($q) use ($tokens) {
                foreach ($tokens as $token) {
                    $q->orWhere('name', 'like', $token . '%');
                }
            })
            ->get();

        foreach ($mentioned as $user) {
            $this->notify($user, $actor, 'mention', 'mencionou você em um post.', $postId);
        }
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
