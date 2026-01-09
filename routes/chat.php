<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Chat routes
Route::middleware(['auth', 'role:admin|member', 'log.access:chat_access'])->group(function () {
    Volt::route('/chat', 'chat.index')->name('chat.index');
    Volt::route('/chat/{chat:slug}', 'chat.show')
        ->middleware(['permission:view-chat', 'chat.access:member', 'log.access:chat_room_access'])
        ->name('chat.show');

    // Admin or chat owner only routes
    Route::middleware(['chat.access:owner-or-admin', 'permission:manage-chat-members', 'log.access:chat_management'])->group(function () {
        Volt::route('/chat/{chat:slug}/manage', 'chat.manage')->name('chat.manage');
    });

    // Chat creation route (admin only)
    Route::middleware(['role:admin', 'permission:create-chat'])->group(function () {
        // Chat creation handled via Livewire component in chat.index
    });
});
