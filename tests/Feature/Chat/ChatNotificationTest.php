<?php

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('migrate');
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
});

it('memastikan setiap user itu memiliki token dari sanctum', function () {
    $user = User::factory()->create();
    expect($user->createToken('test-token')->plainTextToken)->not->toBeNull();
});

it('memastikan route notification dapat diakses', function () {
    $user = User::factory()->create();
    $user->assignRole('member');
    $this->actingAs($user);

    $response = $this->get('/notifications');
    $response->assertOk();
});

it('memastikan route notification hanya bisa diakses oleh user yang terautentikasi', function () {
    $response = $this->get('/notifications');
    $response->assertRedirect();
});

it('memastikan notifikasi dapat diambil oleh user yang ada di dalam chat', function () {
    // Create users with different roles
    $admin = User::factory()->create();
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $nonMember = User::factory()->create();
    
    $admin->assignRole('admin');
    $member1->assignRole('member');
    $member2->assignRole('member');
    $nonMember->assignRole('member');

    // Admin creates a chat
    $chat = Chat::create([
        'title' => 'Integration Test Chat',
        'description' => 'A chat for integration testing',
        'created_by' => $admin->id,
    ]);

    // Add members to chat
    $chat->members()->attach([$member1->id, $member2->id]);
    expect($chat->members()->count())->toBe(2); // Admin tidak termasuk member loh yaa

    // Member1 sends a message
    $message = Message::create([
        'chat_id' => $chat->id,
        'user_id' => $member1->id,
        'content' => 'Hello from member1',
    ]);
    expect($message->id)->not->toBeNull();

    // Member2 should receive a notification
    $this->actingAs($member2);
    $response = $this->get('/notifications');
    $response->assertOk();
})->only();