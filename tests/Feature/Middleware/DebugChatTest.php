<?php

use App\Models\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('migrate');
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
});

test('debug chat creation and database state', function () {
    $owner = User::factory()->create();
    $owner->assignRole('admin');

    // Create chat and debug
    $chat = Chat::create([
        'title' => 'Debug Chat',
        'created_by' => $owner->id,
    ]);
    
    // Check chat was saved
    expect($chat->exists)->toBeTrue();
    expect($chat->id)->not->toBeNull();
    expect($chat->slug)->toBe('debug-chat');
    
    // Check chat is in database
    $foundChat = Chat::where('slug', 'debug-chat')->first();
    expect($foundChat)->not->toBeNull();
    expect($foundChat->id)->toBe($chat->id);
    
    // Check total chats in database
    $chatCount = Chat::count();
    expect($chatCount)->toBeGreaterThan(0);
    
    // List all chats
    $allChats = Chat::all();
    dump("Total chats: " . $allChats->count());
    foreach ($allChats as $c) {
        dump("Chat ID: {$c->id}, Title: {$c->title}, Slug: {$c->slug}");
    }
    
    // Try manual route model binding
    $routeChat = Chat::where('slug', $chat->slug)->first();
    expect($routeChat)->not->toBeNull();
    expect($routeChat->id)->toBe($chat->id);
});
