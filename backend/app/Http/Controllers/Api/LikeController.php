<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\LikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function __construct(private readonly LikeService $likes) {}

    public function toggle(Request $request, Post $post): JsonResponse
    {
        return response()->json($this->likes->toggle($post, $request->user()));
    }
}
