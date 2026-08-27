<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request, User $user): JsonResponse
    {
        $viewer    = $request->user();
        $posts     = $user->posts()->with('user')->paginate(20);
        $following = $viewer ? $user->isFollowedBy($viewer) : false;

        $items = collect($posts->items())->map(
            fn($p) => PostController::formatPost($p, $viewer)
        );

        return response()->json([
            'user' => [
                'id'               => $user->id,
                'name'             => $user->name,
                'bio'              => $user->bio,
                'avatar'           => $user->avatar,
                'followers_count'  => $user->followers()->count(),
                'following_count'  => $user->following()->count(),
                'is_following'     => $following,
            ],
            'posts' => [
                'data'         => $items,
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $q      = $request->validate(['q' => 'required|string|min:1'])['q'];
        $viewer = $request->user();

        $followingIds = $viewer->following()->pluck('following_id')->toArray();

        $users = User::where(fn($query) =>
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
            )
            ->where('id', '!=', $viewer->id)
            ->limit(10)
            ->get(['id', 'name', 'avatar', 'bio'])
            ->map(fn($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'avatar'       => $u->avatar,
                'bio'          => $u->bio,
                'is_following' => in_array($u->id, $followingIds),
            ]);

        return response()->json($users);
    }
}
