<?php

namespace Tests\Feature\Livewire;

use App\Models\Chat;
use App\Models\User;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatShowComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations and seed roles/permissions
        $this->artisan('migrate');
        $this->artisan('db:seed --class=DatabaseSeeder');
    }

    /** @test */
    public function it_mounts_with_chat_data()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $owner->assignRole('admin');
        $member->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'description' => 'Test Description',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach($member->id);

        Livewire::actingAs($member)
            ->test('chat.show', ['chat' => $chat])
            ->assertSet('chat.id', $chat->id)
            ->assertSet('chat.title', 'Test Chat')
            ->assertSee('Test Chat');
    }

    /** @test */
    public function it_denies_access_to_non_members()
    {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $owner->assignRole('admin');
        $nonMember->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'created_by' => $owner->id,
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Anda tidak memiliki akses ke chat ini.');

        Livewire::actingAs($nonMember)
            ->test('chat.show', ['chat' => $chat]);
    }

    /** @test */
    public function it_allows_access_to_admin()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $owner->assignRole('member');
        $admin->assignRole('admin');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'created_by' => $owner->id,
        ]);

        Livewire::actingAs($admin)
            ->test('chat.show', ['chat' => $chat])
            ->assertSet('chat.id', $chat->id)
            ->assertSee('Test Chat');
    }

    /** @test */
    public function it_loads_messages_on_mount()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $owner->assignRole('admin');
        $member->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach($member->id);

        $message1 = Message::create([
            'chat_id' => $chat->id,
            'user_id' => $owner->id,
            'content' => 'First message',
        ]);

        $message2 = Message::create([
            'chat_id' => $chat->id,
            'user_id' => $member->id,
            'content' => 'Second message',
        ]);

        Livewire::actingAs($member)
            ->test('chat.show', ['chat' => $chat])
            ->assertSee('First message')
            ->assertSee('Second message')
            ->call('loadMessages')
            ->assertCount('messages', 2);
    }

    /** @test */
    public function it_can_send_message()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $owner->assignRole('admin');
        $member->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach($member->id);

        Livewire::actingAs($member)
            ->test('chat.show', ['chat' => $chat])
            ->set('newMessage', 'Hello world!')
            ->call('sendMessage')
            ->assertSet('newMessage', '') // Should clear after sending
            ->assertSee('Hello world!');

        // Verify message was saved to database
        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'user_id' => $member->id,
            'content' => 'Hello world!',
        ]);
    }

    /** @test */
    public function it_validates_message_content()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $owner->assignRole('admin');
        $member->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach($member->id);

        // Test empty message
        Livewire::actingAs($member)
            ->test('chat.show', ['chat' => $chat])
            ->set('newMessage', '')
            ->call('sendMessage')
            ->assertHasErrors(['newMessage' => 'required']);

        // Test message too long
        $longMessage = str_repeat('a', 1001);
        Livewire::actingAs($member)
            ->test('chat.show', ['chat' => $chat])
            ->set('newMessage', $longMessage)
            ->call('sendMessage')
            ->assertHasErrors(['newMessage' => 'max']);
    }

    /** @test */
    public function it_creates_notifications_for_new_messages()
    {
        $owner = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        $owner->assignRole('admin');
        $member1->assignRole('member');
        $member2->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach([$member1->id, $member2->id]);

        // Initial notification count
        $initialNotifications = \App\Models\Notification::count();

        Livewire::actingAs($member1)
            ->test('chat.show', ['chat' => $chat])
            ->set('newMessage', 'New message!')
            ->call('sendMessage');

        // Should create notifications for other members (not the sender)
        $finalNotifications = \App\Models\Notification::count();
        $this->assertGreaterThan($initialNotifications, $finalNotifications);
    }

    /** @test */
    public function it_shows_user_names_with_messages()
    {
        $owner = User::factory()->create(['name' => 'Chat Owner']);
        $member = User::factory()->create(['name' => 'Chat Member']);
        $owner->assignRole('admin');
        $member->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach($member->id);

        Message::create([
            'chat_id' => $chat->id,
            'user_id' => $owner->id,
            'content' => 'Message from owner',
        ]);

        Message::create([
            'chat_id' => $chat->id,
            'user_id' => $member->id,
            'content' => 'Message from member',
        ]);

        Livewire::actingAs($member)
            ->test('chat.show', ['chat' => $chat])
            ->assertSee('Chat Owner')
            ->assertSee('Chat Member')
            ->assertSee('Message from owner')
            ->assertSee('Message from member');
    }

    /** @test */
    public function it_orders_messages_correctly()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $owner->assignRole('admin');
        $member->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach($member->id);

        // Create messages with specific timestamps
        $oldMessage = Message::create([
            'chat_id' => $chat->id,
            'user_id' => $owner->id,
            'content' => 'Old message',
            'created_at' => now()->subHours(2),
        ]);

        $newMessage = Message::create([
            'chat_id' => $chat->id,
            'user_id' => $member->id,
            'content' => 'New message',
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($member)
            ->test('chat.show', ['chat' => $chat]);

        // Get the messages from component state
        $messages = $component->get('messages');
        
        // Should be ordered oldest first (reverse of latest)
        $this->assertEquals('Old message', $messages->first()->content);
        $this->assertEquals('New message', $messages->last()->content);
    }

    /** @test */
    public function it_limits_messages_to_50()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $owner->assignRole('admin');
        $member->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach($member->id);

        // Create 60 messages
        for ($i = 1; $i <= 60; $i++) {
            Message::create([
                'chat_id' => $chat->id,
                'user_id' => $member->id,
                'content' => "Message {$i}",
                'created_at' => now()->addMinutes($i),
            ]);
        }

        $component = Livewire::actingAs($member)
            ->test('chat.show', ['chat' => $chat]);

        // Should only load the latest 50 messages
        $messages = $component->get('messages');
        $this->assertCount(50, $messages);
        
        // Should have the 50 most recent messages (11-60)
        $this->assertEquals('Message 11', $messages->first()->content);
        $this->assertEquals('Message 60', $messages->last()->content);
    }

    /** @test */
    public function it_logs_chat_access()
    {
        $owner = User::factory()->create(['name' => 'Test Owner']);
        $member = User::factory()->create(['name' => 'Test Member']);
        $owner->assignRole('admin');
        $member->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat Room',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach($member->id);

        // Mock Log to capture the logging
        \Log::spy();

        Livewire::actingAs($member)
            ->test('chat.show', ['chat' => $chat]);

        // Verify that access was logged
        \Log::shouldHaveReceived('info')
            ->with('User accessed chat', \Mockery::on(function ($data) use ($chat, $member) {
                return $data['chat_id'] === $chat->id
                    && $data['chat_title'] === 'Test Chat Room'
                    && $data['user_name'] === 'Test Member'
                    && $data['user_id'] === $member->id;
            }))
            ->once();
    }

    /** @test */
    public function it_handles_special_characters_in_messages()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $owner->assignRole('admin');
        $member->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach($member->id);

        $specialMessage = 'Message with émojis 😀🎉 and special chars: <script>alert("xss")</script>';

        Livewire::actingAs($member)
            ->test('chat.show', ['chat' => $chat])
            ->set('newMessage', $specialMessage)
            ->call('sendMessage')
            ->assertSee('Message with émojis 😀🎉 and special chars:');

        // Verify message was saved correctly
        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'user_id' => $member->id,
            'content' => $specialMessage,
        ]);
    }
}
