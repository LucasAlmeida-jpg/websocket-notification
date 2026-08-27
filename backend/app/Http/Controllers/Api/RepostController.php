<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\RepostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepostController extends Controller
{
    public function __construct(private readonly RepostService $reposts) {}

    public function toggle(Request $request, Post $post): JsonResponse
    {
        return response()->json($this->reposts->toggle($post, $request->user()));
    }
}
