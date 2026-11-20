<?php

use App\Contracts\OtpSender;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsappVerification;
use App\Services\Otp\OtpResult;
use App\Services\Otp\OtpService;
use Illuminate\Support\Facades\Hash;

/**
 * @return array{0: User, 1: OtpService, 2: callable(): array<int, string>}
 */
function otpFixture(?string $number = '6281234567890'): array
{
    $user = User::factory()->unverified()->create([
        'role_id' => Role::where('name', 'pemilik_kost')->value('id'),
        'whatsapp_number' => $number,
    ]);

    $codes = [];

    test()->mock(OtpSender::class, function ($mock) use (&$codes): void {
        $mock->shouldReceive('send')
            ->andReturnUsing(function (string $number, string $code) use (&$codes): void {
                $codes[] = $code;
            });
    });

    return [
        $user,
        app(OtpService::class),
        function () use (&$codes): array {
            return $codes;
        },
    ];
}

it('menerbitkan kode enam digit, menyimpan hash, dan mengirim kode terbaca', function () {
    [$user, $service, $codes] = otpFixture();

    $service->issue($user, '6281234567890');

    $sent = $codes();
    $verification = WhatsappVerification::sole();

    expect($sent)->toHaveCount(1)
        ->and($sent[0])->toMatch('/^\d{6}$/')
        ->and($verification->target_number)->toBe('6281234567890')
        ->and($verification->code_hash)->not->toBe($sent[0])
        ->and(Hash::check($sent[0], $verification->code_hash))->toBeTrue()
        ->and($verification->expires_at->lessThanOrEqualTo(now()->addMinutes(5)))->toBeTrue()
        ->and($verification->attempt_count)->toBe(0)
        ->and($verification->consumed_at)->toBeNull();
});

it('hanya menyisakan satu kode aktif setelah penerbitan ulang', function () {
    [$user, $service, $codes] = otpFixture();

    $service->issue($user, '6281234567890');
    $this->travel(61)->seconds();
    $service->issue($user, '6281234567890');

    expect($codes())->toHaveCount(2)
        ->and(WhatsappVerification::count())->toBe(2)
        ->and(WhatsappVerification::whereNull('consumed_at')->count())->toBe(1)
        ->and(WhatsappVerification::whereNotNull('consumed_at')->count())->toBe(1);
});

it('menerima kode yang benar dan menetapkan status terverifikasi', function () {
    [$user, $service, $codes] = otpFixture();

    $service->issue($user, '6281234567890');

    $result = $service->verify($user, $codes()[0]);

    expect($result)->toBe(OtpResult::Verified)
        ->and($user->fresh()->whatsapp_verified_at)->not->toBeNull()
        ->and(WhatsappVerification::sole()->consumed_at)->not->toBeNull();
});

it('menolak kode salah dan menaikkan penghitung percobaan', function () {
    [$user, $service] = otpFixture();

    $service->issue($user, '6281234567890');

    $wrong = $service->verify($user, '000000');

    expect($wrong)->toBe(OtpResult::Invalid)
        ->and(WhatsappVerification::sole()->attempt_count)->toBe(1)
        ->and($user->fresh()->whatsapp_verified_at)->toBeNull();
});

it('menolak kode yang sudah kedaluwarsa walaupun kodenya benar', function () {
    [$user, $service, $codes] = otpFixture();

    $service->issue($user, '6281234567890');
    $this->travel(6)->minutes();

    $result = $service->verify($user, $codes()[0]);

    expect($result)->toBe(OtpResult::Expired)
        ->and($user->fresh()->whatsapp_verified_at)->toBeNull()
        ->and(WhatsappVerification::sole()->consumed_at)->toBeNull();
});

it('menolak kode setelah batas percobaan terlampaui walaupun kodenya benar', function () {
    [$user, $service, $codes] = otpFixture();

    $service->issue($user, '6281234567890');

    WhatsappVerification::sole()->forceFill(['attempt_count' => 5])->save();

    $result = $service->verify($user, $codes()[0]);

    expect($result)->toBe(OtpResult::TooManyAttempts)
        ->and($user->fresh()->whatsapp_verified_at)->toBeNull();
});

it('menolak kode yang sudah terpakai', function () {
    [$user, $service, $codes] = otpFixture();

    $service->issue($user, '6281234567890');

    WhatsappVerification::sole()->forceFill(['consumed_at' => now()])->save();

    expect($service->verify($user, $codes()[0]))->toBe(OtpResult::Invalid);
});

it('mengembalikan AlreadyVerified bila nomor sudah terverifikasi', function () {
    [$user, $service] = otpFixture();

    $user->forceFill(['whatsapp_verified_at' => now()])->save();

    expect($service->verify($user, '123456'))->toBe(OtpResult::AlreadyVerified);
});

it('menolak kirim ulang sebelum jeda terlewati tanpa menambah resend_count', function () {
    [$user, $service, $codes] = otpFixture();

    $service->issue($user, '6281234567890');
    $service->issue($user, '6281234567890');

    expect($codes())->toHaveCount(1)
        ->and(WhatsappVerification::count())->toBe(1)
        ->and(WhatsappVerification::sole()->resend_count)->toBe(0)
        ->and($service->canResend($user))->toBeFalse()
        ->and($service->resendWaitSeconds($user))->toBeGreaterThan(0);
});

it('mengirim kode baru dan membatalkan kode lama setelah jeda terlewati', function () {
    [$user, $service, $codes] = otpFixture();

    $service->issue($user, '6281234567890');
    $this->travel(61)->seconds();

    expect($service->canResend($user))->toBeTrue();

    $service->issue($user, '6281234567890');

    expect($codes())->toHaveCount(2)
        ->and(WhatsappVerification::whereNull('consumed_at')->count())->toBe(1)
        ->and(WhatsappVerification::latest('last_sent_at')->first()->resend_count)->toBe(1);
});

it('menolak kirim ulang setelah batas pengiriman per jam tercapai', function () {
    [$user, $service, $codes] = otpFixture();

    foreach (range(1, 3) as $send) {
        $service->issue($user, '6281234567890');

        if ($send < 3) {
            $this->travel(61)->seconds();
        }
    }

    expect($codes())->toHaveCount(3)
        ->and($service->sendsInLastHour($user))->toBe(3)
        ->and($service->canResend($user))->toBeFalse();

    $service->issue($user, '6281234567890');

    expect($codes())->toHaveCount(3)
        ->and(WhatsappVerification::count())->toBe(3);
});
