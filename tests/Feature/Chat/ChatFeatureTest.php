<?php

namespace Tests\Feature\Chat;

use App\Models\Chat;
use App\Models\User;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ChatFeatureTest extends TestCase
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
    public function unauthenticated_users_cannot_access_chat_index()
    {
        $response = $this->get(route('chat.index'));
        
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_users_can_access_chat_index()
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get(route('chat.index'));
        
        $response->assertOk();
        $response->assertSeeLivewire('chat.index');
    }

    /** @test */
    public function users_without_proper_role_cannot_access_chat_index()
    {
        $user = User::factory()->create();
        // Don't assign any role

        $response = $this->actingAs($user)->get(route('chat.index'));
        
        $response->assertStatus(403);
    }

    /** @test */
    public function chat_members_can_view_chat_room()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $owner->assignRole('admin');
        $member->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'description' => 'A test chat room',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach($member->id);

        $response = $this->actingAs($member)->get(route('chat.show', $chat));
        
        $response->assertOk();
        $response->assertSeeLivewire('chat.show');
    }

    /** @test */
    public function non_members_cannot_view_chat_room()
    {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $owner->assignRole('admin');
        $nonMember->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'description' => 'A test chat room',
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($nonMember)->get(route('chat.show', $chat));
        
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_any_chat_room()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $owner->assignRole('member');
        $admin->assignRole('admin');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'description' => 'A test chat room',
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($admin)->get(route('chat.show', $chat));
        
        $response->assertOk();
        $response->assertSeeLivewire('chat.show');
    }

    /** @test */
    public function chat_owner_can_manage_chat()
    {
        $owner = User::factory()->create();
        $owner->assignRole('admin');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'description' => 'A test chat room',
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->get(route('chat.manage', $chat));
        
        $response->assertOk();
        $response->assertSeeLivewire('chat.manage');
    }

    /** @test */
    public function non_owner_cannot_manage_chat()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $owner->assignRole('admin');
        $member->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'description' => 'A test chat room',
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($member)->get(route('chat.manage', $chat));
        
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_manage_any_chat()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $owner->assignRole('member');
        $admin->assignRole('admin');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'description' => 'A test chat room',
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($admin)->get(route('chat.manage', $chat));
        
        $response->assertOk();
        $response->assertSeeLivewire('chat.manage');
    }

    /** @test */
    public function chat_shows_correct_information()
    {
        $owner = User::factory()->create(['name' => 'Chat Owner']);
        $member = User::factory()->create(['name' => 'Chat Member']);
        $owner->assignRole('admin');
        $member->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat Room',
            'description' => 'This is a test chat room description',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach($member->id);

        $response = $this->actingAs($member)->get(route('chat.show', $chat));
        
        $response->assertOk();
        $response->assertSeeText('Test Chat Room');
        $response->assertSeeText('This is a test chat room description');
    }

    /** @test */
    public function chat_shows_messages_from_members()
    {
        $owner = User::factory()->create(['name' => 'Chat Owner']);
        $member = User::factory()->create(['name' => 'Chat Member']);
        $owner->assignRole('admin');
        $member->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat Room',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach($member->id);

        $message1 = Message::create([
            'chat_id' => $chat->id,
            'user_id' => $owner->id,
            'content' => 'Hello from owner!',
        ]);

        $message2 = Message::create([
            'chat_id' => $chat->id,
            'user_id' => $member->id,
            'content' => 'Hello from member!',
        ]);

        $response = $this->actingAs($member)->get(route('chat.show', $chat));
        
        $response->assertOk();
        $response->assertSeeText('Hello from owner!');
        $response->assertSeeText('Hello from member!');
        $response->assertSeeText('Chat Owner');
        $response->assertSeeText('Chat Member');
    }

    /** @test */
    public function users_without_proper_permissions_cannot_view_chat()
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $owner->assignRole('admin');
        $user->assignRole('member');

        // Create chat but don't give view-chat permission
        $chat = Chat::create([
            'title' => 'Test Chat',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach($user->id);

        // Remove view-chat permission
        $user->revokePermissionTo('view-chat');

        $response = $this->actingAs($user)->get(route('chat.show', $chat));
        
        $response->assertStatus(403);
    }

    /** @test */
    public function users_without_manage_permissions_cannot_manage_chat()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $owner->assignRole('admin');
        $admin->assignRole('admin');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'created_by' => $owner->id,
        ]);

        // Remove manage-chat-members permission from admin
        $admin->revokePermissionTo('manage-chat-members');

        $response = $this->actingAs($admin)->get(route('chat.manage', $chat));
        
        $response->assertStatus(403);
    }

    /** @test */
    public function non_existent_chat_returns_404()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/chat/non-existent-chat');
        
        $response->assertStatus(404);
    }

    /** @test */
    public function chat_routes_require_authentication()
    {
        $owner = User::factory()->create();
        $owner->assignRole('admin');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'created_by' => $owner->id,
        ]);

        // Test chat show route
        $response = $this->get(route('chat.show', $chat));
        $response->assertRedirect(route('login'));

        // Test chat manage route
        $response = $this->get(route('chat.manage', $chat));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function inactive_chat_can_still_be_accessed_by_members()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $owner->assignRole('admin');
        $member->assignRole('member');

        $chat = Chat::create([
            'title' => 'Inactive Chat',
            'created_by' => $owner->id,
            'is_active' => false,
        ]);

        $chat->members()->attach($member->id);

        $response = $this->actingAs($member)->get(route('chat.show', $chat));
        
        $response->assertOk();
    }

    /** @test */
    public function chat_access_is_logged()
    {
        $owner = User::factory()->create(['name' => 'Test User']);
        $member = User::factory()->create(['name' => 'Test Member']);
        $owner->assignRole('admin');
        $member->assignRole('member');

        $chat = Chat::create([
            'title' => 'Test Chat',
            'created_by' => $owner->id,
        ]);

        $chat->members()->attach($member->id);

        // Check that log file is created/updated when accessing chat
        $logFile = storage_path('logs/laravel.log');
        $logSizeBefore = file_exists($logFile) ? filesize($logFile) : 0;

        $response = $this->actingAs($member)->get(route('chat.show', $chat));
        
        $response->assertOk();

        $logSizeAfter = file_exists($logFile) ? filesize($logFile) : 0;
        
        // Log file should have grown (new entries added)
        $this->assertGreaterThan($logSizeBefore, $logSizeAfter);
    }

    /** @test */
    public function multiple_middleware_work_together()
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

        // This request should pass through:
        // 1. role:admin|member middleware
        // 2. permission:view-chat middleware  
        // 3. chat.access:member middleware
        // 4. log.access:chat_room_access middleware
        $response = $this->actingAs($member)->get(route('chat.show', $chat));
        
        $response->assertOk();
        $response->assertSeeLivewire('chat.show');
    }
}
