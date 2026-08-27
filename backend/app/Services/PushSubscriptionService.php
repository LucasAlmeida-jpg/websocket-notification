<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushSubscriptionService
{
    public function store(User $user, array $data): void
    {
        PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id'          => $user->id,
                'public_key'       => $data['public_key'],
                'auth_token'       => $data['auth_token'],
                'content_encoding' => $data['content_encoding'] ?? 'aesgcm',
            ],
        );
    }

    public function destroy(User $user, string $endpoint): void
    {
        $user->pushSubscriptions()->where('endpoint', $endpoint)->delete();
    }

    public function sendTest(User $user): array
    {
        $webPush = new WebPush(['VAPID' => [
            'subject'    => config('services.vapid.subject'),
            'publicKey'  => config('services.vapid.public_key'),
            'privateKey' => config('services.vapid.private_key'),
        ]]);

        $payload = json_encode([
            'title' => 'Teste de notificação push',
            'body'  => 'Olá, ' . $user->name . '! Sua integração push está funcionando.',
            'icon'  => '/icon-192.png',
        ]);

        foreach ($user->pushSubscriptions as $sub) {
            $webPush->queueNotification(Subscription::create([
                'endpoint'        => $sub->endpoint,
                'publicKey'       => $sub->public_key,
                'authToken'       => $sub->auth_token,
                'contentEncoding' => $sub->content_encoding,
            ]), $payload);
        }

        $results = [];
        foreach ($webPush->flush() as $report) {
            $results[] = [
                'endpoint' => $report->getRequest()->getUri()->__toString(),
                'success'  => $report->isSuccess(),
                'reason'   => $report->getReason(),
            ];
        }

        return $results;
    }
}
