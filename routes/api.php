<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    // Ambil notifikasi terbaru yang belum dibaca dan belum dipush
    $notification = $request->user()->notifications()->whereNull(['read_at', 'pushed_at'])->first();
    if ($notification) {
        $notification->pushed_at = now();
        $notification->save();
    }
    return response()->json([
        'notification' => $notification,
        'user' => $request->user()->only(['id', 'name', 'email'])
    ]);
});