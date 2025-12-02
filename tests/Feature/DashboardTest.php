<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('migrate');
    Artisan::call('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
});

test('guests are redirected to the chat index', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('chat.index'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('member');
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('chat.index'));
});