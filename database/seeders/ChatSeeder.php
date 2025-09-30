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
        Chat::create([
            'title' => 'Guru All Wilayah',
            'description' => 'Forum diskusi untuk semua guru di seluruh wilayah',
            'created_by' => $admin->id,
        ]);

        Chat::create([
            'title' => 'Tentor All Wilayah',
            'description' => 'Komunikasi dan koordinasi tentor di seluruh wilayah',
            'created_by' => $admin->id,
        ]);

        Chat::create([
            'title' => 'PIC Sekolah All Wilayah',
            'description' => 'Koordinasi Person In Charge sekolah di seluruh wilayah',
            'created_by' => $admin->id,
        ]);

        Chat::create([
            'title' => 'Training of Tentor Batch 1',
            'description' => 'Pelatihan dan pengembangan tentor batch pertama',
            'created_by' => $admin->id,
        ]);

        Chat::create([
            'title' => 'Casis TNI - Polri',
            'description' => 'Pembahasan dan persiapan calon siswa TNI dan Polri',
            'created_by' => $admin->id,
        ]);

        Chat::create([
            'title' => 'Casis Kedinasan',
            'description' => 'Diskusi persiapan calon siswa sekolah kedinasan',
            'created_by' => $admin->id,
        ]);

        Chat::create([
            'title' => 'Training of Sales Team',
            'description' => 'Pelatihan dan koordinasi tim sales',
            'created_by' => $admin->id,
        ]);
        $this->command->info('Demo chats and messages created successfully!');
    }
}
