<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('auth/login'));
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors([
        'email' => trans('auth.failed'),
    ]);
});

test('an unregistered email is rejected with the generic message', function () {
    $response = $this->post(route('login.store'), [
        'email' => 'tidak-terdaftar@example.com',
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email' => trans('auth.failed')]);
});

test('inactive accounts are rejected and stay guests', function () {
    $user = User::factory()->create();
    $user->forceFill(['is_active' => false])->save();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors([
        'email' => trans('auth.inactive'),
    ]);
});

test('customers with an unverified email are rejected', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors([
        'email' => trans('auth.unverified', ['method' => 'email']),
    ]);
});

test('admins with an unverified email are rejected', function () {
    $user = User::factory()->unverified()->create([
        'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
    ]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors([
        'email' => trans('auth.unverified', ['method' => 'email']),
    ]);
});

test('kost owners without a verified whatsapp number are rejected', function () {
    $user = User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => 'pemilik_kost'])->id,
        'email_verified_at' => now(),
        'whatsapp_number' => '628123456789',
        'whatsapp_verified_at' => null,
    ]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors([
        'email' => trans('auth.unverified', ['method' => 'WhatsApp']),
    ]);
});

test('kost owners with a verified whatsapp number can authenticate', function () {
    $user = User::factory()->unverified()->create([
        'role_id' => Role::firstOrCreate(['name' => 'pemilik_kost'])->id,
        'whatsapp_number' => '628123456789',
        'whatsapp_verified_at' => now(),
    ]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
