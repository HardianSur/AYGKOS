<?php

use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $attributes
 */
function userWithRole(string $roleName, array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role_id' => Role::where('name', $roleName)->value('id'),
    ], $attributes));
}

test('verified admin can open the admin dashboard', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('dashboard'));
});

test('customers and kost owners cannot open the admin dashboard', function () {
    $customer = userWithRole('customer');
    $owner = userWithRole('pemilik_kost', [
        'whatsapp_number' => '6281234567890',
        'whatsapp_verified_at' => now(),
    ]);

    $this->actingAs($customer)
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('guests are redirected to the login page', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

test('admin without verified email is redirected to verification notice', function () {
    $admin = userWithRole('admin', ['email_verified_at' => null]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('verification.notice'));
});

test('a user whose role is not admin is forbidden', function () {
    $user = userWithRole(Role::create(['name' => 'staff'])->name);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('a user without a role is forbidden', function () {
    $user = userWithRole('admin');
    $user->setRelation('role', null);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('denial carries no page props for json requests', function () {
    $user = userWithRole('customer');

    $this->actingAs($user)
        ->getJson(route('admin.dashboard'))
        ->assertForbidden()
        ->assertJsonMissingPath('props');
});

test('role name is shared with the frontend on the dashboard', function () {
    $admin = userWithRole('admin');
    $owner = userWithRole('pemilik_kost', [
        'whatsapp_number' => '6281234567890',
        'whatsapp_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('auth.user.role', 'admin'));

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('auth.user.role', 'pemilik_kost'));
});
