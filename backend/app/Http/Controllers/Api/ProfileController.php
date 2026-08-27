<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profiles) {}

    public function show(Request $request, User $user): JsonResponse
    {
        return response()->json(
            $this->profiles->getProfile($user, $request->user(), (int) $request->query('page', 1))
        );
    }

    public function search(Request $request): JsonResponse
    {
        $q = $request->validate(['q' => 'required|string|min:1'])['q'];

        return response()->json($this->profiles->search($q, $request->user()));
    }
}
