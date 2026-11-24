<?php

use App\Actions\StoreAvatar;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email is stored in lowercase so the user can still log in', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'Test@Example.com',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->email)->toBe('test@example.com');

    // Mengubah email mengosongkan email_verified_at; tandai terverifikasi lagi
    // supaya tes ini tetap menguji huruf besar-kecil, bukan gerbang verifikasi.
    $user->forceFill(['email_verified_at' => now()])->save();

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => 'Test@Example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
});

test('email must be unique regardless of case', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'Taken@Example.com',
        ]);

    $response
        ->assertSessionHasErrors('email')
        ->assertRedirect(route('profile.edit'));
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});

test('an uploaded photo is stored as a 400x400 webp file', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('foto.jpg', 1200, 800),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();
    $size = getimagesize(Storage::disk('public')->path((string) $user->avatar_path));

    expect($user->avatar_path)->toEndWith('.webp')
        ->and($size)->not->toBeFalse()
        ->and($size[0])->toBe(400)
        ->and($size[1])->toBe(400)
        ->and($size['mime'])->toBe('image/webp');
});

test('an uploaded photo becomes the avatar url', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('foto.png', 600, 900),
        ])
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->avatar_path)->not->toBeNull()
        ->and($user->avatar)->toBe(Storage::disk('public')->url($user->avatar_path));
});

test('replacing the photo deletes the previous file', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    Storage::disk('public')->put('avatars/lama.webp', 'old');
    $user->forceFill(['avatar_path' => 'avatars/lama.webp'])->save();

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('baru.jpg', 500, 500),
        ])
        ->assertSessionHasNoErrors();

    $user->refresh();

    Storage::disk('public')->assertMissing('avatars/lama.webp');
    Storage::disk('public')->assertExists($user->avatar_path);
});

test('a failed photo replacement keeps the previous file', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    Storage::disk('public')->put('avatars/lama.webp', 'old');
    $user->forceFill(['avatar_path' => 'avatars/lama.webp'])->save();

    $this->mock(StoreAvatar::class)
        ->shouldReceive('handle')
        ->andThrow(new RuntimeException('disk penuh'));

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('baru.jpg', 500, 500),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    Storage::disk('public')->assertExists('avatars/lama.webp');
    expect($user->refresh()->avatar_path)->toBe('avatars/lama.webp');
});

test('a profile update without a photo keeps the existing photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    Storage::disk('public')->put('avatars/lama.webp', 'old');
    $user->forceFill(['avatar_path' => 'avatars/lama.webp'])->save();

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->avatar_path)->toBe('avatars/lama.webp');
    Storage::disk('public')->assertExists('avatars/lama.webp');
});

test('a photo larger than 3 MB is rejected', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->create('besar.png', 4096, 'image/png'),
        ])
        ->assertSessionHasErrors('avatar');

    expect($user->refresh()->avatar_path)->toBeNull();
});

test('a photo that is not an allowed image type is rejected', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('avatar');

    expect($user->refresh()->avatar_path)->toBeNull();
});

test('a whatsapp number sent with the profile form is ignored', function () {
    $user = User::factory()->create([
        'role_id' => Role::where('name', 'pemilik_kost')->value('id'),
        'whatsapp_number' => '628123456789',
        'whatsapp_verified_at' => now(),
    ]);

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp_number' => '628999999999',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->whatsapp_number)->toBe('628123456789');
});

test('deleting the account removes its avatar file', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    Storage::disk('public')->put('avatars/hapus.webp', 'foto');
    $user->forceFill(['avatar_path' => 'avatars/hapus.webp'])->save();

    $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing('avatars/hapus.webp');
    expect(User::find($user->id))->toBeNull();
});

test('the avatar action returns a relative path on the public disk', function () {
    Storage::fake('public');

    $path = app(StoreAvatar::class)->handle(
        UploadedFile::fake()->image('foto.jpg', 300, 600),
    );

    expect($path)->toStartWith('avatars/')->toEndWith('.webp');
    Storage::disk('public')->assertExists($path);
});
