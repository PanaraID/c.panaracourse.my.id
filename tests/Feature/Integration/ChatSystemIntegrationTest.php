<?php

namespace Tests\Feature\Integration;

use App\Models\Chat;
use App\Models\User;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ChatSystemIntegrationTest extends TestCase
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
    public function complete_chat_workflow_works_end_to_end()
    {
        // Create users with roles
        $admin = User::factory()->create(['name' => 'Admin User']);
        $member1 = User::factory()->create(['name' => 'Member One']);
        $member2 = User::factory()->create(['name' => 'Member Two']);
        
        $admin->assignRole('admin');
        $member1->assignRole('member');
        $member2->assignRole('member');

        // Admin creates a chat
        $chat = Chat::create([
            'title' => 'Project Discussion',
            'description' => 'Discussion about the new project',
            'created_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('chats', [
            'title' => 'Project Discussion',
            'slug' => 'project-discussion',
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        // Admin adds members to the chat
        $chat->members()->attach([$member1->id, $member2->id]);

        // Verify members can access chat
        $response1 = $this->actingAs($member1)->get(route('chat.show', $chat));
        $response1->assertOk();

        $response2 = $this->actingAs($member2)->get(route('chat.show', $chat));
        $response2->assertOk();

        // Members send messages
        $message1 = Message::create([
            'chat_id' => $chat->id,
            'user_id' => $member1->id,
            'content' => 'Hello everyone! Excited about this project.',
        ]);

        $message2 = Message::create([
            'chat_id' => $chat->id,
            'user_id' => $member2->id,
            'content' => 'Hi! Looking forward to working together.',
        ]);

        // Verify notifications are created
        $this->assertDatabaseHas('notifications', [
            'chat_id' => $chat->id,
            'message_id' => $message1->id,
        ]);

        // Admin manages chat (access management page)
        $adminResponse = $this->actingAs($admin)->get(route('chat.manage', $chat));
        $adminResponse->assertOk();

        // Verify chat shows messages in correct order
        $chatResponse = $this->actingAs($admin)->get(route('chat.show', $chat));
        $chatResponse->assertSee('Hello everyone! Excited about this project.');
        $chatResponse->assertSee('Hi! Looking forward to working together.');
        $chatResponse->assertSee('Member One');
        $chatResponse->assertSee('Member Two');

        // Test message editing
        $message1->update(['content' => 'Hello everyone! Updated message about the project.']);
        $message1->markAsEdited();

        $this->assertTrue($message1->fresh()->is_edited);
        $this->assertNotNull($message1->fresh()->edited_at);

        // Verify rate limiting works
        Cache::put("chat_rate_limit:user_{$member1->id}", 60, now()->addMinute());
        
        $rateLimitResponse = $this->actingAs($member1)->get(route('chat.show', $chat));
        $rateLimitResponse->assertStatus(429);
    }

    /** @test */
    public function chat_permissions_work_across_different_scenarios()
    {
        $admin = User::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $outsider = User::factory()->create();

        $admin->assignRole('admin');
        $owner->assignRole('member');
        $member->assignRole('member');
        $outsider->assignRole('member');

        $chat = Chat::create([
            'title' => 'Private Chat',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach($member->id);

        // Test various access scenarios
        $scenarios = [
            // [user, route, expected_status, description]
            [$admin, 'chat.show', 200, 'Admin can view any chat'],
            [$admin, 'chat.manage', 200, 'Admin can manage any chat'],
            [$owner, 'chat.show', 200, 'Owner can view own chat even if not member'],
            [$owner, 'chat.manage', 200, 'Owner can manage own chat'],
            [$member, 'chat.show', 200, 'Member can view chat they belong to'],
            [$member, 'chat.manage', 403, 'Member cannot manage chat they dont own'],
            [$outsider, 'chat.show', 403, 'Outsider cannot view chat'],
            [$outsider, 'chat.manage', 403, 'Outsider cannot manage chat'],
        ];

        foreach ($scenarios as [$user, $route, $expectedStatus, $description]) {
            $response = $this->actingAs($user)->get(route($route, $chat));
            $this->assertEquals($expectedStatus, $response->getStatusCode(), $description);
        }
    }

    /** @test */
    public function notification_system_works_correctly()
    {
        $owner = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        
        $owner->assignRole('admin');
        $member1->assignRole('member');
        $member2->assignRole('member');

        $chat = Chat::create([
            'title' => 'Notification Test Chat',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach([$member1->id, $member2->id]);

        $initialNotificationCount = Notification::count();

        // Member1 sends a message
        $message = Message::create([
            'chat_id' => $chat->id,
            'user_id' => $member1->id,
            'content' => 'Test notification message',
        ]);

        // Create notifications for the message
        Notification::createForNewMessage($message);

        $finalNotificationCount = Notification::count();

        // Should create notifications for other members (not the sender)
        $this->assertGreaterThan($initialNotificationCount, $finalNotificationCount);

        // Verify notifications exist for other members but not sender
        $member1Notifications = Notification::where('user_id', $member1->id)->count();
        $member2Notifications = Notification::where('user_id', $member2->id)->count();
        $ownerNotifications = Notification::where('user_id', $owner->id)->count();

        $this->assertEquals(0, $member1Notifications, 'Sender should not get notification');
        $this->assertGreaterThan(0, $member2Notifications, 'Other members should get notifications');
        $this->assertGreaterThan(0, $ownerNotifications, 'Chat creator should get notifications');
    }

    /** @test */
    public function rate_limiting_works_per_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $user1->assignRole('member');
        $user2->assignRole('member');

        $chat = Chat::create([
            'title' => 'Rate Limit Test',
            'created_by' => $user1->id,
        ]);

        $chat->members()->attach([$user1->id, $user2->id]);

        // Set rate limit for user1 to be exceeded
        Cache::put("chat_rate_limit:user_{$user1->id}", 100, now()->addMinute());

        // User1 should be rate limited
        $user1Response = $this->actingAs($user1)->get(route('chat.show', $chat));
        $user1Response->assertStatus(429);

        // User2 should not be affected by user1's rate limit
        $user2Response = $this->actingAs($user2)->get(route('chat.show', $chat));
        $user2Response->assertOk();
    }

    /** @test */
    public function middleware_stack_works_correctly()
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $chat = Chat::create([
            'title' => 'Middleware Test',
            'created_by' => $user->id,
        ]);

        $chat->members()->attach($user->id);

        // This request should pass through multiple middleware:
        // - role:admin|member
        // - permission:view-chat
        // - chat.access:member
        // - chat.rate_limit
        // - log.access:chat_room_access

        $response = $this->actingAs($user)->get(route('chat.show', $chat));
        $response->assertOk();

        // Test that removing any required permission/role breaks access
        $user->revokePermissionTo('view-chat');
        $responseWithoutPermission = $this->actingAs($user)->get(route('chat.show', $chat));
        $responseWithoutPermission->assertStatus(403);
    }

    /** @test */
    public function chat_system_handles_edge_cases()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Test empty chat title
        $chat = Chat::create([
            'title' => '',
            'created_by' => $user->id,
        ]);
        
        $this->assertEquals('', $chat->slug);

        // Test very long chat title
        $longTitle = str_repeat('Very Long Chat Title ', 20);
        $chatWithLongTitle = Chat::create([
            'title' => $longTitle,
            'created_by' => $user->id,
        ]);
        
        $this->assertNotNull($chatWithLongTitle->slug);

        // Test special characters in title
        $specialTitle = 'Chat with Special Characters! @#$%^&*()';
        $chatWithSpecialChars = Chat::create([
            'title' => $specialTitle,
            'created_by' => $user->id,
        ]);
        
        $this->assertEquals('chat-with-special-characters', $chatWithSpecialChars->slug);

        // Test accessing non-existent chat
        $response = $this->actingAs($user)->get('/chat/non-existent-slug');
        $response->assertStatus(404);
    }

    /** @test */
    public function performance_with_large_datasets()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $chat = Chat::create([
            'title' => 'Performance Test Chat',
            'created_by' => $user->id,
        ]);

        // Create many members
        $members = User::factory()->count(20)->create();
        foreach ($members as $member) {
            $member->assignRole('member');
        }
        
        $chat->members()->attach($members->pluck('id'));

        // Create many messages
        for ($i = 0; $i < 100; $i++) {
            Message::create([
                'chat_id' => $chat->id,
                'user_id' => $members->random()->id,
                'content' => "Performance test message {$i}",
            ]);
        }

        $startTime = microtime(true);
        
        // Test chat page load with many messages and members
        $response = $this->actingAs($user)->get(route('chat.show', $chat));
        
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertOk();
        
        // Should load within reasonable time (adjust threshold as needed)
        $this->assertLessThan(5000, $loadTime, 'Chat page should load within 5 seconds');
    }
}
