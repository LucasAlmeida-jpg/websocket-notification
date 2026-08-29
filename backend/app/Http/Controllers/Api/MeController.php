<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __construct(private readonly MeService $me) {}

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'   => ['sometimes', 'string', 'max:100'],
            'bio'    => ['sometimes', 'nullable', 'string', 'max:300'],
            'avatar' => ['sometimes', 'nullable', 'url', 'max:2048'],
        ]);

        return response()->json($this->me->update($request->user(), $data));
    }
}
