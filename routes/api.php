<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FrontendLogController;

Route::get('/ping', function () {
    return response()->json(['message' => 'API is working']);
});

// Push Subscription API Routes
use App\Http\Controllers\Api\PushSubscriptionController;

Route::get('/push/public-key', [PushSubscriptionController::class, 'publicKey'])->name('push.public-key');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});
