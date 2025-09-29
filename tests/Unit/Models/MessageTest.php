<?php

use App\Models\Chat;
use App\Models\User;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Run migrations and seed roles/permissions
    Artisan::call('migrate');
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
});

test('it can create a message', function () {
    $user = User::factory()->create();
    $chat = Chat::create([
        'title' => 'Test Chat',
        'created_by' => $user->id,
    ]);

    $message = Message::create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'content' => 'Hello world!',
    ]);

    expect($message)->toBeInstanceOf(Message::class);
    expect($message->chat_id)->toBe($chat->id);
    expect($message->user_id)->toBe($user->id);
    expect($message->content)->toBe('Hello world!');
    expect($message->is_edited)->toBe(false);
    expect($message->edited_at)->toBeNull();
});

test('it belongs to a chat', function () {
    $user = User::factory()->create();
    $chat = Chat::create([
        'title' => 'Test Chat',
        'created_by' => $user->id,
    ]);

    $message = Message::create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'content' => 'Hello world!',
    ]);

    expect($message->chat)->toBeInstanceOf(Chat::class);
    expect($message->chat->id)->toBe($chat->id);
    expect($message->chat->title)->toBe($chat->title);
});

test('it belongs to a user', function () {
    $user = User::factory()->create();
    $chat = Chat::create([
        'title' => 'Test Chat',
        'created_by' => $user->id,
    ]);

    $message = Message::create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'content' => 'Hello world!',
    ]);

    expect($message->user)->toBeInstanceOf(User::class);
    expect($message->user->id)->toBe($user->id);
    expect($message->user->name)->toBe($user->name);
});

test('it can be marked as edited', function () {
    $user = User::factory()->create();
    $chat = Chat::create([
        'title' => 'Test Chat',
        'created_by' => $user->id,
    ]);

    $message = Message::create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'content' => 'Original content',
    ]);

    expect($message->is_edited)->toBe(false);
    expect($message->edited_at)->toBeNull();

    $message->markAsEdited();

    expect($message->fresh()->is_edited)->toBe(true);
    expect($message->fresh()->edited_at)->not()->toBeNull();
    expect($message->fresh()->edited_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('it has correct fillable attributes', function () {
    $fillable = ['chat_id', 'user_id', 'content', 'is_edited', 'edited_at'];
    
    $message = new Message();
    
    expect($message->getFillable())->toBe($fillable);
});
