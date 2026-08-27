<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use App\Services\ShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    public function __construct(private readonly ShareService $share) {}

    public function send(Request $request, Post $post): JsonResponse
    {
        $data = $request->validate(['user_id' => 'required|integer|exists:users,id']);

        if ((int) $data['user_id'] === $request->user()->id) {
            return response()->json(['message' => 'Você não pode enviar para si mesmo.'], 422);
        }

        $recipient = User::findOrFail($data['user_id']);

        $this->share->send($post, $recipient, $request->user());

        return response()->json(['sent' => true]);
    }
}
