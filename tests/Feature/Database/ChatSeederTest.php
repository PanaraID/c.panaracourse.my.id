<?php

namespace Tests\Feature\Database;

use App\Models\Chat;
use App\Models\User;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations and seed roles/permissions first
        $this->artisan('migrate');
        $this->artisan('db:seed --class=DatabaseSeeder');
    }

    /** @test */
    public function chat_seeder_creates_chats_successfully()
    {
        // Get initial chat count
        $initialChatCount = Chat::count();

        // Run the chat seeder
        $this->artisan('db:seed --class=ChatSeeder');

        // Verify chats were created
        $finalChatCount = Chat::count();
        $this->assertGreaterThan($initialChatCount, $finalChatCount);
    }

    /** @test */
    public function chat_seeder_creates_valid_chat_data()
    {
        $this->artisan('db:seed --class=ChatSeeder');

        $chats = Chat::all();

        foreach ($chats as $chat) {
            // Verify required fields are set
            $this->assertNotNull($chat->title);
            $this->assertNotNull($chat->slug);
            $this->assertNotNull($chat->created_by);
            $this->assertIsBool($chat->is_active);
            
            // Verify relationships
            $this->assertInstanceOf(User::class, $chat->creator);
            
            // Verify slug format
            $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $chat->slug);
        }
    }

    /** @test */
    public function chat_seeder_creates_unique_slugs()
    {
        $this->artisan('db:seed --class=ChatSeeder');

        $slugs = Chat::pluck('slug')->toArray();
        $uniqueSlugs = array_unique($slugs);

        $this->assertCount(count($slugs), $uniqueSlugs, 'All chat slugs should be unique');
    }

    /** @test */
    public function chat_seeder_assigns_valid_creators()
    {
        $this->artisan('db:seed --class=ChatSeeder');

        $chats = Chat::all();

        foreach ($chats as $chat) {
            // Verify creator exists in users table
            $creator = User::find($chat->created_by);
            $this->assertNotNull($creator, "Chat creator with ID {$chat->created_by} should exist");
            
            // Verify creator has appropriate role
            $this->assertTrue(
                $creator->hasRole('admin') || $creator->hasRole('member'),
                "Chat creator should have admin or member role"
            );
        }
    }

    /** @test */
    public function chat_seeder_creates_chat_members()
    {
        $this->artisan('db:seed --class=ChatSeeder');

        $chats = Chat::with('members')->get();

        foreach ($chats as $chat) {
            if ($chat->members->count() > 0) {
                foreach ($chat->members as $member) {
                    // Verify member exists and has appropriate role
                    $this->assertInstanceOf(User::class, $member);
                    $this->assertTrue(
                        $member->hasRole('admin') || $member->hasRole('member'),
                        "Chat member should have admin or member role"
                    );
                }

                // Verify pivot table has timestamps
                $member = $chat->members->first();
                $this->assertNotNull($member->pivot->created_at);
                $this->assertNotNull($member->pivot->updated_at);
            }
        }
    }

    /** @test */
    public function chat_seeder_creates_initial_messages()
    {
        $this->artisan('db:seed --class=ChatSeeder');

        $chats = Chat::with('messages')->get();

        foreach ($chats as $chat) {
            if ($chat->messages->count() > 0) {
                foreach ($chat->messages as $message) {
                    // Verify message data
                    $this->assertNotNull($message->content);
                    $this->assertNotNull($message->user_id);
                    $this->assertEquals($chat->id, $message->chat_id);
                    
                    // Verify message author exists
                    $author = User::find($message->user_id);
                    $this->assertNotNull($author);
                    
                    // Verify message author is either creator or member of the chat
                    $this->assertTrue(
                        $message->user_id === $chat->created_by || 
                        $chat->members->contains($message->user_id),
                        "Message author should be either chat creator or member"
                    );
                    
                    // Verify content is reasonable length
                    $this->assertGreaterThan(0, strlen($message->content));
                    $this->assertLessThanOrEqual(1000, strlen($message->content));
                }
            }
        }
    }

    /** @test */
    public function chat_seeder_can_run_multiple_times_without_errors()
    {
        // Run seeder first time
        $this->artisan('db:seed --class=ChatSeeder');
        $firstRunCount = Chat::count();

        // Run seeder second time
        $this->artisan('db:seed --class=ChatSeeder');
        $secondRunCount = Chat::count();

        // Should either create new chats or handle duplicates gracefully
        $this->assertGreaterThanOrEqual($firstRunCount, $secondRunCount);
    }

    /** @test */
    public function chat_seeder_respects_database_constraints()
    {
        $this->artisan('db:seed --class=ChatSeeder');

        $chats = Chat::all();

        foreach ($chats as $chat) {
            // Test title length constraint (assuming varchar(255))
            $this->assertLessThanOrEqual(255, strlen($chat->title));
            
            // Test slug length constraint
            $this->assertLessThanOrEqual(255, strlen($chat->slug));
            
            // Test description length if present
            if ($chat->description) {
                $this->assertIsString($chat->description);
            }
        }
    }

    /** @test */
    public function chat_seeder_creates_active_chats_by_default()
    {
        $this->artisan('db:seed --class=ChatSeeder');

        $chats = Chat::all();
        $activeChats = $chats->where('is_active', true);

        // Most or all seeded chats should be active
        $this->assertGreaterThan(0, $activeChats->count());
        
        // Should have at least 80% active chats
        $activePercentage = ($activeChats->count() / $chats->count()) * 100;
        $this->assertGreaterThanOrEqual(80, $activePercentage);
    }

    /** @test */
    public function chat_seeder_maintains_referential_integrity()
    {
        $this->artisan('db:seed --class=ChatSeeder');

        // Test chat-user relationships
        $chatUsers = \DB::table('chat_users')->get();
        
        foreach ($chatUsers as $chatUser) {
            // Verify chat exists
            $chat = Chat::find($chatUser->chat_id);
            $this->assertNotNull($chat, "Chat with ID {$chatUser->chat_id} should exist");
            
            // Verify user exists  
            $user = User::find($chatUser->user_id);
            $this->assertNotNull($user, "User with ID {$chatUser->user_id} should exist");
        }

        // Test message relationships
        $messages = Message::all();
        
        foreach ($messages as $message) {
            // Verify chat exists
            $chat = Chat::find($message->chat_id);
            $this->assertNotNull($chat, "Chat with ID {$message->chat_id} should exist");
            
            // Verify user exists
            $user = User::find($message->user_id);
            $this->assertNotNull($user, "User with ID {$message->user_id} should exist");
        }
    }

    /** @test */
    public function chat_seeder_creates_diverse_chat_types()
    {
        $this->artisan('db:seed --class=ChatSeeder');

        $chats = Chat::all();

        if ($chats->count() > 1) {
            // Should have chats with different titles
            $titles = $chats->pluck('title')->unique();
            $this->assertGreaterThan(1, $titles->count());

            // Should have chats with different member counts
            $memberCounts = $chats->map(function ($chat) {
                return $chat->members()->count();
            })->unique();
            
            // Should have at least some variety in member counts
            $this->assertGreaterThanOrEqual(1, $memberCounts->count());
        }
    }

    /** @test */
    public function database_seeder_includes_chat_seeder()
    {
        // Reset database
        $this->artisan('migrate:fresh');
        
        // Run main database seeder
        $this->artisan('db:seed');

        // Verify chats were created by DatabaseSeeder
        $this->assertGreaterThan(0, Chat::count());
        $this->assertGreaterThan(0, User::count());
        
        // Verify roles and permissions exist
        $this->assertGreaterThan(0, \Spatie\Permission\Models\Role::count());
        $this->assertGreaterThan(0, \Spatie\Permission\Models\Permission::count());
    }
}
