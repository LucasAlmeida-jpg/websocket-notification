<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $request->user()->notifications()->latest()->paginate(20);

        return response()->json([
            'data'         => collect($paginator->items())->map(fn($n) => [
                'id'         => $n->id,
                'data'       => [
                    'type'          => $n->data['type'] ?? null,
                    'message'       => $n->data['message'] ?? null,
                    'actor_id'      => $n->data['actor_id'] ?? null,
                    'actor_name'    => $n->data['actor_name'] ?? null,
                    'resource_type' => $n->data['resource_type'] ?? null,
                    'resource_id'   => $n->data['resource_id'] ?? null,
                ],
                'read_at'    => $n->read_at,
                'created_at' => $n->created_at,
            ]),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'total'        => $paginator->total(),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return response()->json(['message' => 'Notificação marcada como lida.']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'Todas as notificações foram marcadas como lidas.']);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->findOrFail($id)->delete();

        return response()->json(['message' => 'Notificação removida.']);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipient_id'  => 'required|integer|exists:users,id|different:' . $request->user()->id,
            'type'          => ['required', 'string', Rule::in(array_column(NotificationType::cases(), 'value'))],
            'message'       => 'required|string|max:255',
            'resource_type' => 'nullable|string|max:50',
            'resource_id'   => 'nullable|integer',
        ]);

        $this->notifications->notify(
            recipient:    User::findOrFail($data['recipient_id']),
            actor:        $request->user(),
            type:         NotificationType::from($data['type']),
            message:      $data['message'],
            resourceType: $data['resource_type'] ?? '',
            resourceId:   $data['resource_id'] ?? 0,
        );

        return response()->json(['message' => 'Notificação enviada com sucesso.'], 201);
    }
}
