<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FollowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function __construct(private readonly FollowService $follows) {}

    public function following(Request $request): JsonResponse
    {
        return response()->json($this->follows->getFollowing($request->user()));
    }

    public function toggle(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Você não pode seguir a si mesmo.'], 422);
        }

        return response()->json($this->follows->toggle($user, $request->user()));
    }
}
