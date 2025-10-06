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

test('basic route test', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $chat = Chat::create([
        'title' => 'Test Chat',
        'created_by' => $user->id,
    ]);

    expect($chat->slug)->toBe('test-chat');
    expect(route('chat.show', $chat))->toBe('http://localhost/chat/test-chat');

    // Test basic auth redirect first
    $response = $this->get('/chat');
    $response->assertRedirect();
});

test('route with auth', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    // Test dashboard route works (should redirect to chat)
    $response = $this->actingAs($user)->get('/dashboard');
    expect($response->status())->toBe(302);
    expect($response->headers->get('Location'))->toBe('/chat');
});
