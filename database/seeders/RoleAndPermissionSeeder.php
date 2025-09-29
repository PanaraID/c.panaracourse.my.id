<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'create-chat',
            'edit-chat',
            'delete-chat',
            'manage-chat-members',
            'send-message',
            'delete-message',
            'view-chat',
            'manage-users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $memberRole = Role::firstOrCreate(['name' => 'member']);

        // Assign permissions to admin role
        $adminRole->givePermissionTo(Permission::all());

        // Assign limited permissions to member role
        $memberRole->givePermissionTo([
            'send-message',
            'view-chat',
        ]);
    }
}
