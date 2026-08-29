<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Post;
use App\Models\User;

class PostService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function create(User $author, string $body, ?int $parentId): Post
    {
        $post = Post::create([
            'user_id'   => $author->id,
            'body'      => $body,
            'parent_id' => $parentId,
        ]);

        if ($parentId) {
            $parent = Post::find($parentId);
            $parent->increment('replies_count');

            if ($parent->user_id !== $author->id) {
                $this->notifications->notify(
                    recipient:    $parent->user,
                    actor:        $author,
                    type:         NotificationType::Comment,
                    message:      'respondeu ao seu post.',
                    resourceType: 'post',
                    resourceId:   $post->id,
                );
            }
        }

        $this->notifyMentions($body, $author, $post->id);

        $post->load('user');

        return $post;
    }

    public function delete(Post $post): void
    {
        if ($post->parent_id) {
            Post::find($post->parent_id)?->decrement('replies_count');
        }

        $post->delete();
    }

    public function formatPost(Post $post, ?User $viewer): array
    {
        return [
            'id'            => $post->id,
            'body'          => $post->body,
            'likes_count'   => (int) ($post->likes_count ?? 0),
            'replies_count' => (int) ($post->replies_count ?? 0),
            'reposts_count' => (int) ($post->reposts_count ?? 0),
            'liked'         => $viewer ? $post->isLikedBy($viewer) : false,
            'reposted'      => $viewer ? $post->isRepostedBy($viewer) : false,
            'created_at'    => $post->created_at->toIso8601String(),
            'user'          => [
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
            $this->notifications->notify(
                recipient:    $user,
                actor:        $actor,
                type:         NotificationType::Mention,
                message:      'mencionou você em um post.',
                resourceType: 'post',
                resourceId:   $postId,
            );
        }
    }
}
