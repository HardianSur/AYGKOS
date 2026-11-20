<?php

use App\Contracts\OtpSender;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsappVerification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

/**
 * @return array<string, string>
 */
function customerRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'customer',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $overrides);
}

/**
 * @return array<string, string>
 */
function ownerRegistrationPayload(array $overrides = []): array
{
    return customerRegistrationPayload(array_merge([
        'role' => 'pemilik_kost',
        'whatsapp_number' => '081234567890',
    ], $overrides));
}

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('customers can register and are sent to email verification', function () {
    Notification::fake();

    $response = $this->post(route('register.store'), customerRegistrationPayload());

    $this->assertAuthenticated();

    $user = User::query()->where('email', 'test@example.com')->sole();

    expect($user->role->name)->toBe('customer')
        ->and($user->whatsapp_number)->toBeNull()
        ->and($user->email_verified_at)->toBeNull();

    $response->assertRedirect(route('verification.notice', absolute: false));
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('a customer supplied whatsapp number is ignored', function () {
    $this->post(route('register.store'), customerRegistrationPayload([
        'whatsapp_number' => '081234567890',
    ]));

    expect(User::query()->where('email', 'test@example.com')->sole()->whatsapp_number)->toBeNull();
});

test('owners can register, store a normalized number, and receive an otp', function () {
    $code = null;

    $this->mock(OtpSender::class, function ($mock) use (&$code): void {
        $mock->shouldReceive('send')
            ->once()
            ->with('6281234567890', Mockery::capture($code));
    });

    $response = $this->post(route('register.store'), ownerRegistrationPayload());

    $this->assertAuthenticated();

    $user = User::query()->where('email', 'test@example.com')->sole();

    expect($user->role->name)->toBe('pemilik_kost')
        ->and($user->whatsapp_number)->toBe('6281234567890')
        ->and($user->whatsapp_verified_at)->toBeNull();

    $response->assertRedirect(route('verification.whatsapp.notice', absolute: false));

    $verification = WhatsappVerification::query()->where('user_id', $user->id)->sole();

    expect($verification->code_hash)->not->toBe($code)
        ->and(Hash::check((string) $code, $verification->code_hash))->toBeTrue()
        ->and($verification->consumed_at)->toBeNull()
        ->and($verification->expires_at->lessThanOrEqualTo(now()->addMinutes(5)))->toBeTrue()
        ->and((string) $code)->toMatch('/^\d{6}$/');
});

test('registration as admin is rejected', function () {
    $response = $this->post(route('register.store'), customerRegistrationPayload([
        'role' => 'admin',
    ]));

    $this->assertGuest();
    $response->assertSessionHasErrors('role');
    expect(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();
});

test('registration without a role is rejected', function () {
    $payload = customerRegistrationPayload();
    unset($payload['role']);

    $response = $this->post(route('register.store'), $payload);

    $this->assertGuest();
    $response->assertSessionHasErrors('role');
});

test('an invalid email is rejected on the email field', function () {
    $response = $this->post(route('register.store'), customerRegistrationPayload([
        'email' => 'bukan-email',
    ]));

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('a duplicate email is rejected on the email field', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->post(route('register.store'), customerRegistrationPayload());

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('a mismatched password confirmation is rejected', function () {
    $response = $this->post(route('register.store'), customerRegistrationPayload([
        'password_confirmation' => 'berbeda',
    ]));

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('a password below the strength rule is rejected', function () {
    Password::defaults(fn (): Password => Password::min(10)->mixedCase()->numbers());

    $response = $this->post(route('register.store'), customerRegistrationPayload([
        'password' => 'pendek',
        'password_confirmation' => 'pendek',
    ]));

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('an invalid whatsapp number is rejected', function () {
    $response = $this->post(route('register.store'), ownerRegistrationPayload([
        'whatsapp_number' => '12345',
    ]));

    $response->assertSessionHasErrors('whatsapp_number');
    $this->assertGuest();
});

test('a duplicate whatsapp number is rejected in any spelling', function () {
    User::factory()->create([
        'role_id' => Role::where('name', 'pemilik_kost')->value('id'),
        'whatsapp_number' => '6281234567890',
    ]);

    $response = $this->post(route('register.store'), ownerRegistrationPayload([
        'whatsapp_number' => '0812-3456-7890',
    ]));

    $response->assertSessionHasErrors('whatsapp_number');
    $this->assertGuest();
});

test('owners must provide a whatsapp number', function () {
    $response = $this->post(route('register.store'), ownerRegistrationPayload([
        'whatsapp_number' => '',
    ]));

    $response->assertSessionHasErrors('whatsapp_number');
    $this->assertGuest();
});
