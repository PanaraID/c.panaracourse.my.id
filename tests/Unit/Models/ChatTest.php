<?php

use App\Models\Chat;
use App\Models\User;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Run migrations and seed roles/permissions
    Artisan::call('migrate');
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
});

test('it can create a chat', function () {
    $user = User::factory()->create();
    
    $chat = Chat::create([
        'title' => 'Test Chat',
        'description' => 'A test chat room',
        'created_by' => $user->id,
    ]);

    expect($chat)->toBeInstanceOf(Chat::class);
    expect($chat->title)->toBe('Test Chat');
    expect($chat->description)->toBe('A test chat room');
    expect($chat->created_by)->toBe($user->id);
    expect($chat->is_active)->toBe(true);
    expect($chat->slug)->not()->toBeNull();
    expect($chat->slug)->toBe('test-chat');
});

test('it generates unique slug automatically', function () {
    $user = User::factory()->create();
    
    // Create first chat
    $chat1 = Chat::create([
        'title' => 'Test Chat',
        'created_by' => $user->id,
    ]);
    
    // Create second chat with same title
    $chat2 = Chat::create([
        'title' => 'Test Chat',
        'created_by' => $user->id,
    ]);

    expect($chat1->slug)->toBe('test-chat');
    expect($chat2->slug)->toBe('test-chat-1');
});

test('it can accept custom slug', function () {
    $user = User::factory()->create();
    
    $chat = Chat::create([
        'title' => 'Test Chat',
        'slug' => 'custom-chat-slug',
        'created_by' => $user->id,
    ]);

    expect($chat->slug)->toBe('custom-chat-slug');
});

test('it belongs to creator', function () {
    $user = User::factory()->create();
    
    $chat = Chat::create([
        'title' => 'Test Chat',
        'created_by' => $user->id,
    ]);

    expect($chat->creator)->toBeInstanceOf(User::class);
    expect($chat->creator->id)->toBe($user->id);
    expect($chat->creator->name)->toBe($user->name);
});

test('it can have many members', function () {
    $creator = User::factory()->create();
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    
    $chat = Chat::create([
        'title' => 'Test Chat',
        'created_by' => $creator->id,
    ]);

    $chat->members()->attach([$member1->id, $member2->id]);

    expect($chat->members)->toHaveCount(2);
    expect($chat->members->contains($member1))->toBe(true);
    expect($chat->members->contains($member2))->toBe(true);
});

test('it can have many messages', function () {
    $creator = User::factory()->create();
    $member = User::factory()->create();
    
    $chat = Chat::create([
        'title' => 'Test Chat',
        'created_by' => $creator->id,
    ]);

    $message1 = Message::create([
        'chat_id' => $chat->id,
        'user_id' => $creator->id,
        'content' => 'First message'
    ]);

    $message2 = Message::create([
        'chat_id' => $chat->id,
        'user_id' => $member->id,
        'content' => 'Second message'
    ]);

    expect($chat->messages)->toHaveCount(2);
    expect($chat->messages->contains($message1))->toBe(true);
    expect($chat->messages->contains($message2))->toBe(true);
});

test('it orders messages by creation date', function () {
    $creator = User::factory()->create();
    
    $chat = Chat::create([
        'title' => 'Test Chat',
        'created_by' => $creator->id,
    ]);

    // Create messages with specific timestamps
    $oldMessage = Message::create([
        'chat_id' => $chat->id,
        'user_id' => $creator->id,
        'content' => 'Old message',
        'created_at' => now()->subHours(2),
    ]);

    $newMessage = Message::create([
        'chat_id' => $chat->id,
        'user_id' => $creator->id,
        'content' => 'New message',
        'created_at' => now(),
    ]);

    $messages = $chat->messages;
    expect($messages->first()->id)->toBe($oldMessage->id);
    expect($messages->last()->id)->toBe($newMessage->id);

    $latestMessages = $chat->latestMessages;
    expect($latestMessages->first()->id)->toBe($newMessage->id);
    expect($latestMessages->last()->id)->toBe($oldMessage->id);
});

test('it uses slug for route key', function () {
    $user = User::factory()->create();
    
    $chat = Chat::create([
        'title' => 'Test Chat',
        'created_by' => $user->id,
    ]);

    expect($chat->getRouteKeyName())->toBe('slug');
    expect($chat->getRouteKey())->toBe($chat->slug);
});

test('it can be deactivated', function () {
    $user = User::factory()->create();
    
    $chat = Chat::create([
        'title' => 'Test Chat',
        'created_by' => $user->id,
    ]);

    expect($chat->is_active)->toBe(true);

    $chat->update(['is_active' => false]);

    expect($chat->is_active)->toBe(false);
    expect($chat->fresh()->is_active)->toBe(false);
});

test('it casts is_active to boolean', function () {
    $user = User::factory()->create();
    
    $chat = Chat::create([
        'title' => 'Test Chat',
        'created_by' => $user->id,
        'is_active' => 1, // Integer
    ]);

    expect($chat->is_active)->toBeBool();
    expect($chat->is_active)->toBe(true);

    $chat->update(['is_active' => 0]);
    
    expect($chat->fresh()->is_active)->toBeBool();
    expect($chat->fresh()->is_active)->toBe(false);
});

test('it has fillable attributes', function () {
    $fillable = ['title', 'description', 'slug', 'created_by', 'is_active'];
    
    $chat = new Chat();
    
    expect($chat->getFillable())->toBe($fillable);
});

test('it handles long titles for slug generation', function () {
    $user = User::factory()->create();
    $longTitle = str_repeat('Very Long Title With Many Words ', 10);
    
    $chat = Chat::create([
        'title' => $longTitle,
        'created_by' => $user->id,
    ]);

    expect($chat->slug)->not()->toBeNull();
    // The slug might be truncated by the database or the slug generation
    expect($chat->slug)->toStartWith('very-long-title');
});

test('it handles special characters in title for slug', function () {
    $user = User::factory()->create();
    
    $chat = Chat::create([
        'title' => 'Chat with Special Characters! @#$%^&*()',
        'created_by' => $user->id,
    ]);

    expect($chat->slug)->toContain('chat-with-special-characters');
});
