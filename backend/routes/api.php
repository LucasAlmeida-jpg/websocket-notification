<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PushSubscriptionController;
use Illuminate\Support\Facades\Route;

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/',             [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/send',        [NotificationController::class, 'send']);
        Route::patch('/{id}/read',  [NotificationController::class, 'markAsRead']);
        Route::patch('/read-all',   [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}',      [NotificationController::class, 'destroy']);
    });

    // Web Push subscriptions
    Route::prefix('push')->group(function () {
        Route::get('/vapid-key', [PushSubscriptionController::class, 'vapidKey']);
        Route::post('/',         [PushSubscriptionController::class, 'store']);
        Route::delete('/',       [PushSubscriptionController::class, 'destroy']);
        Route::post('/test',     [PushSubscriptionController::class, 'test']);
    });
});
