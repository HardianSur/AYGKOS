<?php

use App\Contracts\OtpSender;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsappVerification;
use App\Services\Otp\OtpService;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

function whatsappOwner(array $attributes = []): User
{
    return User::factory()->unverified()->create(array_merge([
        'role_id' => Role::where('name', 'pemilik_kost')->value('id'),
        'whatsapp_number' => '6281234567890',
    ], $attributes));
}

/**
 * @return array{0: User, 1: string}
 */
function whatsappOwnerWithCode(): array
{
    $user = whatsappOwner();
    $code = null;

    test()->mock(OtpSender::class, function ($mock) use (&$code): void {
        $mock->shouldReceive('send')
            ->andReturnUsing(function (string $number, string $sentCode) use (&$code): void {
                $code = $sentCode;
            });
    });

    app(OtpService::class)->issue($user, (string) $user->whatsapp_number);

    return [$user, (string) $code];
}

test('halaman verifikasi whatsapp tampil untuk pemilik kost', function () {
    $user = whatsappOwner();

    $this->actingAs($user)
        ->get(route('verification.whatsapp.notice'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/verify-whatsapp')
            ->where('whatsappNumber', '6281****890')
            ->where('resendIn', 0));
});

test('pemilik kost yang sudah terverifikasi dialihkan ke dashboard', function () {
    $user = whatsappOwner(['whatsapp_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('verification.whatsapp.notice'))
        ->assertRedirect(route('dashboard', absolute: false));
});

test('kode yang benar menetapkan whatsapp_verified_at dan mengarahkan ke dashboard', function () {
    [$user, $code] = whatsappOwnerWithCode();

    $response = $this->actingAs($user)
        ->post(route('verification.whatsapp.store'), ['code' => $code]);

    $response->assertRedirect(route('dashboard', absolute: false));

    expect($user->fresh()->whatsapp_verified_at)->not->toBeNull()
        ->and(WhatsappVerification::sole()->consumed_at)->not->toBeNull()
        ->and(Hash::check($code, WhatsappVerification::sole()->code_hash))->toBeTrue();
});

test('kode yang salah menampilkan galat dan tidak mengubah status', function () {
    [$user, $code] = whatsappOwnerWithCode();
    $wrong = $code === '000000' ? '111111' : '000000';

    $response = $this->actingAs($user)
        ->post(route('verification.whatsapp.store'), ['code' => $wrong]);

    $response->assertSessionHasErrors([
        'code' => trans('verification.invalid_code'),
    ]);

    expect($user->fresh()->whatsapp_verified_at)->toBeNull()
        ->and(WhatsappVerification::sole()->attempt_count)->toBe(1);
});

test('kode yang kedaluwarsa ditolak', function () {
    [$user, $code] = whatsappOwnerWithCode();
    $this->travel(6)->minutes();

    $response = $this->actingAs($user)
        ->post(route('verification.whatsapp.store'), ['code' => $code]);

    $response->assertSessionHasErrors([
        'code' => trans('verification.expired_code'),
    ]);

    expect($user->fresh()->whatsapp_verified_at)->toBeNull();
});

test('kode yang melebihi batas percobaan ditolak', function () {
    [$user, $code] = whatsappOwnerWithCode();

    WhatsappVerification::sole()->forceFill(['attempt_count' => 5])->save();

    $response = $this->actingAs($user)
        ->post(route('verification.whatsapp.store'), ['code' => $code]);

    $response->assertSessionHasErrors([
        'code' => trans('verification.too_many_attempts'),
    ]);

    expect($user->fresh()->whatsapp_verified_at)->toBeNull();
});

test('kode yang sudah terpakai ditolak', function () {
    [$user, $code] = whatsappOwnerWithCode();

    WhatsappVerification::sole()->forceFill(['consumed_at' => now()])->save();

    $response = $this->actingAs($user)
        ->post(route('verification.whatsapp.store'), ['code' => $code]);

    $response->assertSessionHasErrors([
        'code' => trans('verification.invalid_code'),
    ]);
});

test('kirim ulang sebelum jeda ditolak dengan sisa waktu tunggu', function () {
    [$user] = whatsappOwnerWithCode();

    $response = $this->actingAs($user)
        ->post(route('verification.whatsapp.send'));

    $response->assertSessionHasErrors('resend');

    expect(session('errors')->first('resend'))->toContain('Tunggu');
});

test('kirim ulang setelah jeda menerbitkan kode baru', function () {
    [$user] = whatsappOwnerWithCode();
    $this->travel(61)->seconds();

    $response = $this->actingAs($user)
        ->post(route('verification.whatsapp.send'));

    $response->assertSessionHas('status', trans('verification.code_sent'));

    expect(WhatsappVerification::count())->toBe(2)
        ->and(WhatsappVerification::whereNull('consumed_at')->count())->toBe(1);
});

test('tamu tidak dapat membuka halaman verifikasi whatsapp', function () {
    $this->get(route('verification.whatsapp.notice'))
        ->assertRedirect(route('login'));
});
