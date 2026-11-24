<?php

use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('customers see the greeting home page', function () {
    $user = User::factory()->create([
        'role_id' => Role::where('name', 'customer')->value('id'),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('home')
            ->where('auth.user.name', $user->name));
});

test('kost owners see the dashboard', function () {
    $user = User::factory()->create([
        'role_id' => Role::where('name', 'pemilik_kost')->value('id'),
        'whatsapp_number' => '628123456789',
        'whatsapp_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('dashboard'));
});

test('admins see the dashboard', function () {
    $user = User::factory()->create([
        'role_id' => Role::where('name', 'admin')->value('id'),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('dashboard'));
});
