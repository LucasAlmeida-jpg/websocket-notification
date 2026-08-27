<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $followingIds = $user->following()->pluck('following_id');

        $posts = Post::with('user')
            ->whereNull('parent_id')
            ->where(fn($q) => $q->whereIn('user_id', $followingIds)->orWhere('user_id', $user->id))
            ->latest()
            ->paginate(20);

        $items = collect($posts->items())->map(
            fn($p) => PostController::formatPost($p, $user)
        );

        return response()->json([
            'data'         => $items,
            'current_page' => $posts->currentPage(),
            'last_page'    => $posts->lastPage(),
        ]);
    }
}
