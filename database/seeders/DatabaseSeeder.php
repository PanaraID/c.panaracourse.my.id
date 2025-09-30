<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the role and permission seeder first
        $this->call([
            RoleAndPermissionSeeder::class,
        ]);

        // User::factory(10)->create();

        $testUser = User::firstOrCreate([
            'email' => 'test@example.com'
        ], [
            'name' => 'Test User',
            'password' => bcrypt('password'),
        ]);

        // Assign admin role to test user if not already assigned
        if (!$testUser->hasRole('admin')) {
            $testUser->assignRole('admin');
            $this->command->info("Assigned 'admin' role to user: {$testUser->email}");
        }

        // Create 5 member users
        $newUsers = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $member = User::firstOrCreate([
            'email' => "member{$i}@example.com"
            ], [
            'name' => "Member {$i}",
            'password' => bcrypt('password'),
            ]);
            
            if (!$member->hasRole('member')) {
            $member->assignRole('member');
            }
            
            $newUsers[] = $member;
        }
        
        // Print new users to console
        foreach ($newUsers as $user) {
            $this->command->info("ID: {$user->id}, Name: {$user->name}, Email: {$user->email}");
        }

        // Seed demo chats
        $this->call([
            ChatSeeder::class,
        ]);
    }
}
