<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FrontendLogController;

Route::get('/ping', function () {
    return response()->json(['message' => 'API is working']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Get or create a token for the current authenticated user
Route::middleware(['auth:web'])->get('/user/token', function (Request $request) {
    $user = $request->user();
    
    // Delete existing tokens for this user to create fresh one
    $user->tokens()->delete();
    
    // Create new token
    $tokenName = 'api_token_' . now()->timestamp;
    $token = $user->createToken($tokenName, ['*'], now()->addDays(30))->plainTextToken;
    
    return response()->json([
        'token' => $token,
        'user' => $user->only(['id', 'name', 'email']),
        'expires_at' => now()->addDays(30)->toDateTimeString()
    ]);
});

// Mengambil data notifikasi untuk user yang terautentikasi pada chat yang ia ikuti
Route::middleware(['auth:sanctum'])->get('/notifications', function (Request $request) {
    logger()->info('Fetching notifications for user ID ' . $request->user()->id);

    // Ambil notifikasi terbaru yang belum dibaca dan belum dipush
    $notification = $request->user()->notifications()->where(['pushed_at' => null])->first();
    logger()->info('Fetched notification: ' . ($notification ? $notification->id : 'none'));
    
    if ($notification) {
        $notification->pushed_at = now();
        $notification->save();
    }
    return response()->json([
        'notification' => $notification,
        'user' => $request->user()->only(['id', 'name', 'email'])
    ]);
});

// Frontend Logging API Routes
Route::post('/frontend-logs', [FrontendLogController::class, 'store'])->name('frontend-logs.store');
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/frontend-logs', [FrontendLogController::class, 'index'])->name('frontend-logs.index');
    Route::get('/frontend-logs/stats', [FrontendLogController::class, 'stats'])->name('frontend-logs.stats');
    Route::delete('/frontend-logs/cleanup', [FrontendLogController::class, 'cleanup'])->name('frontend-logs.cleanup');
});

// Push Subscription API Routes
use App\Http\Controllers\Api\PushSubscriptionController;

Route::get('/push/public-key', [PushSubscriptionController::class, 'publicKey'])->name('push.public-key');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});
