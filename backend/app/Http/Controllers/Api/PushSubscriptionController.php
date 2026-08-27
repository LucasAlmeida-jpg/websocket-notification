<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PushSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function __construct(private readonly PushSubscriptionService $push) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'         => 'required|url',
            'public_key'       => 'required|string',
            'auth_token'       => 'required|string',
            'content_encoding' => 'nullable|string|in:aesgcm,aes128gcm',
        ]);

        $this->push->store($request->user(), $data);

        return response()->json(['message' => 'Inscrição registrada com sucesso.'], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => 'required|url']);

        $this->push->destroy($request->user(), $data['endpoint']);

        return response()->json(['message' => 'Inscrição removida com sucesso.']);
    }

    public function vapidKey(): JsonResponse
    {
        return response()->json(['public_key' => config('services.vapid.public_key')]);
    }

    public function test(Request $request): JsonResponse
    {
        if (!config('services.vapid.public_key') || !config('services.vapid.private_key')) {
            return response()->json([
                'message' => 'VAPID keys não configuradas. Execute php artisan vapid:generate.',
            ], 422);
        }

        if ($request->user()->pushSubscriptions()->doesntExist()) {
            return response()->json(['message' => 'Nenhuma inscrição encontrada para este usuário.'], 404);
        }

        return response()->json(['results' => $this->push->sendTest($request->user())]);
    }
}
