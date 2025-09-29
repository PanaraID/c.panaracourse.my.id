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
        }

        // Create some member users
        $member1 = User::firstOrCreate([
            'email' => 'member1@example.com'
        ], [
            'name' => 'Member One',
            'password' => bcrypt('password'),
        ]);
        if (!$member1->hasRole('member')) {
            $member1->assignRole('member');
        }

        $member2 = User::firstOrCreate([
            'email' => 'member2@example.com'
        ], [
            'name' => 'Member Two',
            'password' => bcrypt('password'),
        ]);
        if (!$member2->hasRole('member')) {
            $member2->assignRole('member');
        }

        // Seed demo chats
        $this->call([
            ChatSeeder::class,
        ]);
    }
}
