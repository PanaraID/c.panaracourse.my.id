<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Chat;
use App\Models\User;
use App\Models\Message;

class ChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user
        $admin = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->first();

        // Get member users  
        $members = User::whereHas('roles', function ($query) {
            $query->where('name', 'member');
        })->get();

        if (!$admin || $members->count() < 2) {
            $this->command->info('Skipping chat seeder - not enough users with roles');
            return;
        }

        // Create demo chats
        $generalChat = Chat::create([
            'title' => 'General Discussion',
            'description' => 'Tempat diskusi umum untuk semua anggota',
            'created_by' => $admin->id,
        ]);

        $techChat = Chat::create([
            'title' => 'Tech Talk',
            'description' => 'Diskusi seputar teknologi dan programming',
            'created_by' => $admin->id,
        ]);

        $randomChat = Chat::create([
            'title' => 'Random Chat',
            'description' => 'Obrolan santai dan random',
            'created_by' => $admin->id,
        ]);

        // Add members to chats
        $generalChat->members()->attach([$admin->id, $members[0]->id, $members[1]->id]);
        $techChat->members()->attach([$admin->id, $members[0]->id]);
        $randomChat->members()->attach([$admin->id, $members[1]->id]);

        // Create some demo messages
        Message::create([
            'chat_id' => $generalChat->id,
            'user_id' => $admin->id,
            'content' => 'Selamat datang di chat umum! Mari kita diskusi dengan santun.',
        ]);

        Message::create([
            'chat_id' => $generalChat->id,
            'user_id' => $members[0]->id,
            'content' => 'Terima kasih! Senang bisa bergabung di sini.',
        ]);

        Message::create([
            'chat_id' => $techChat->id,
            'user_id' => $admin->id,
            'content' => 'Ada yang mau diskusi tentang Laravel 11?',
        ]);

        Message::create([
            'chat_id' => $techChat->id,
            'user_id' => $members[0]->id,
            'content' => 'Saya tertarik dengan Livewire Volt yang baru!',
        ]);

        $this->command->info('Demo chats and messages created successfully!');
    }
}
