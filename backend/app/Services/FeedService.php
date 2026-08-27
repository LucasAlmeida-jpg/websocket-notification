<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class FeedService
{
    public function __construct(
        private readonly PostService $posts,
    ) {}

    public function getFeed(User $user, int $page = 1): array
    {
        $followingIds = $user->following()->pluck('following_id');

        $paginator = Post::with('user')
            ->whereNull('parent_id')
            ->where(function ($q) use ($user, $followingIds) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('user_id', $followingIds);
            })
            ->latest()
            ->paginate(15, ['*'], 'page', $page);

        return [
            'data'         => collect($paginator->items())
                ->map(fn($post) => $this->posts->formatPost($post, $user))
                ->values()
                ->all(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
        ];
    }
}
