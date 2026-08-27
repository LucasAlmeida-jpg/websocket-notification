<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function __construct(private readonly FeedService $feed) {}

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(
            $this->feed->getFeed($request->user(), (int) $request->query('page', 1))
        );
    }
}
