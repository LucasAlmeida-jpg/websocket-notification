<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\RepostController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    Route::get('/feed', FeedController::class);

    Route::post('/posts',             [PostController::class, 'store']);
    Route::get('/posts/{post}',       [PostController::class, 'show']);
    Route::delete('/posts/{post}',    [PostController::class, 'destroy']);
    Route::post('/posts/{post}/like',   [LikeController::class,   'toggle']);
    Route::post('/posts/{post}/repost', [RepostController::class, 'toggle']);

    Route::post('/users/{user}/follow', [FollowController::class, 'toggle']);
    Route::get('/users/{user}',         [ProfileController::class, 'show']);
    Route::get('/users',                [ProfileController::class, 'search']);

    Route::prefix('notifications')->group(function () {
        Route::get('/',             [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('/{id}/read',  [NotificationController::class, 'markAsRead']);
        Route::patch('/read-all',   [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}',      [NotificationController::class, 'destroy']);
    });

    Route::prefix('push')->group(function () {
        Route::get('/vapid-key', [PushSubscriptionController::class, 'vapidKey']);
        Route::post('/',         [PushSubscriptionController::class, 'store']);
        Route::delete('/',       [PushSubscriptionController::class, 'destroy']);
    });
});
