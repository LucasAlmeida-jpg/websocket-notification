<?php

namespace App\Services;

use App\Models\User;

class ProfileService
{
    public function __construct(
        private readonly PostService $posts,
    ) {}

    public function getProfile(User $target, User $viewer, int $page = 1): array
    {
        $paginator = $target->posts()->with('user')->paginate(10, ['*'], 'page', $page);

        return [
            'user' => [
                'id'              => $target->id,
                'name'            => $target->name,
                'avatar'          => $target->avatar,
                'bio'             => $target->bio,
                'followers_count' => $target->followers()->count(),
                'following_count' => $target->following()->count(),
                'is_following'    => $target->isFollowedBy($viewer),
            ],
            'posts' => [
                'data'         => collect($paginator->items())
                    ->map(fn($post) => $this->posts->formatPost($post, $viewer))
                    ->values()
                    ->all(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ];
    }

    public function search(string $query, User $viewer): array
    {
        return User::where('id', '!=', $viewer->id)
            ->where('name', 'like', "%{$query}%")
            ->limit(10)
            ->get()
            ->map(fn($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'avatar'       => $u->avatar,
                'bio'          => $u->bio,
                'is_following' => $u->isFollowedBy($viewer),
            ])
            ->values()
            ->all();
    }
}
