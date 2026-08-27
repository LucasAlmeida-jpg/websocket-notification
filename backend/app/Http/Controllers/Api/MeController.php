<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MeController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'   => ['sometimes', 'string', 'max:100'],
            'bio'    => ['sometimes', 'nullable', 'string', 'max:300'],
            'avatar' => ['sometimes', 'nullable', 'string'],
        ]);

        $user->update($data);

        return response()->json($user->fresh());
    }
}
