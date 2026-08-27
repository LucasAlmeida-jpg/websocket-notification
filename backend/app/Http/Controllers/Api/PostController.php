<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'body'      => 'required|string|max:500',
            'parent_id' => 'nullable|integer|exists:posts,id',
        ]);

        $post = $this->posts->create($request->user(), $data['body'], $data['parent_id'] ?? null);

        return response()->json($this->posts->formatPost($post, $request->user()), 201);
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        $post->load('user');
        $replies = $post->replies()->with('user')->get();

        return response()->json([
            'post'    => $this->posts->formatPost($post, $request->user()),
            'replies' => $replies->map(fn($r) => $this->posts->formatPost($r, $request->user())),
        ]);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $this->posts->delete($post);

        return response()->json(['message' => 'Post removido.']);
    }
}
