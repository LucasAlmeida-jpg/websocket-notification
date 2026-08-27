<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\VAPID;

class PushSubscriptionController extends Controller
{
    /** Register a push subscription for the authenticated user. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'         => 'required|url',
            'public_key'       => 'required|string',
            'auth_token'       => 'required|string',
            'content_encoding' => 'nullable|string|in:aesgcm,aes128gcm',
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id'          => $request->user()->id,
                'public_key'       => $data['public_key'],
                'auth_token'       => $data['auth_token'],
                'content_encoding' => $data['content_encoding'] ?? 'aesgcm',
            ],
        );

        return response()->json(['message' => 'Inscrição registrada com sucesso.'], 201);
    }

    /** Remove a push subscription. */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => 'required|url']);

        $request->user()
            ->pushSubscriptions()
            ->where('endpoint', $data['endpoint'])
            ->delete();

        return response()->json(['message' => 'Inscrição removida com sucesso.']);
    }

    /** Return VAPID public key so the frontend can subscribe. */
    public function vapidKey(): JsonResponse
    {
        return response()->json([
            'public_key' => config('services.vapid.public_key'),
        ]);
    }

    /** Send a test push notification to all subscriptions of the authenticated user. */
    public function test(Request $request): JsonResponse
    {
        $publicKey  = config('services.vapid.public_key');
        $privateKey = config('services.vapid.private_key');
        $subject    = config('services.vapid.subject');

        if (!$publicKey || !$privateKey) {
            return response()->json([
                'message' => 'VAPID keys não configuradas. Execute php artisan vapid:generate e adicione ao .env.',
            ], 422);
        }

        $auth = [
            'VAPID' => [
                'subject'    => $subject,
                'publicKey'  => $publicKey,
                'privateKey' => $privateKey,
            ],
        ];

        $webPush = new WebPush($auth);

        $subscriptions = $request->user()->pushSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return response()->json(['message' => 'Nenhuma inscrição encontrada para este usuário.'], 404);
        }

        $payload = json_encode([
            'title'   => 'Teste de notificação push',
            'body'    => 'Olá, ' . $request->user()->name . '! Sua integração push está funcionando.',
            'icon'    => '/icon-192.png',
        ]);

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint'        => $sub->endpoint,
                'publicKey'       => $sub->public_key,
                'authToken'       => $sub->auth_token,
                'contentEncoding' => $sub->content_encoding,
            ]);

            $webPush->queueNotification($subscription, $payload);
        }

        $results = [];
        foreach ($webPush->flush() as $report) {
            $results[] = [
                'endpoint' => $report->getRequest()->getUri()->__toString(),
                'success'  => $report->isSuccess(),
                'reason'   => $report->getReason(),
            ];
        }

        return response()->json(['results' => $results]);
    }
}
