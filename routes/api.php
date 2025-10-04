<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['message' => 'API is working']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Mengambil data notifikasi untuk user yang terautentikasi pada chat yang ia ikuti
Route::middleware(['auth:sanctum'])->get('/notifications', function (Request $request) {
    return $request->user()->notifications;
});