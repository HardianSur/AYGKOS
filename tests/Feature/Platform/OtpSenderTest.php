<?php

use App\Contracts\OtpSender;
use App\Services\Otp\FonnteOtpSender;
use App\Services\Otp\LogOtpSender;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

it('mengembalikan LogOtpSender saat driver log', function () {
    config()->set('otp.driver', 'log');

    expect(app(OtpSender::class))->toBeInstanceOf(LogOtpSender::class);
});

it('jatuh ke driver log saat token Fonnte tidak tersedia', function () {
    config()->set('otp.driver', 'fonnte');
    config()->set('services.fonnte.token', null);

    Log::spy();

    expect(app(OtpSender::class))->toBeInstanceOf(LogOtpSender::class);
    Log::shouldHaveReceived('warning');
});

it('mengembalikan FonnteOtpSender saat driver fonnte dan token tersedia', function () {
    config()->set('otp.driver', 'fonnte');
    config()->set('services.fonnte.token', 'token-uji');

    expect(app(OtpSender::class))->toBeInstanceOf(FonnteOtpSender::class);
});

it('mengirim kode OTP ke endpoint Fonnte dengan token dari konfigurasi', function () {
    config()->set('otp.driver', 'fonnte');
    config()->set('services.fonnte.token', 'token-uji');
    config()->set('services.fonnte.endpoint', 'https://api.fonnte.com/send');

    Http::fake([
        'api.fonnte.com/*' => Http::response(['status' => true], 200),
    ]);

    app(OtpSender::class)->send('6281234567890', '123456');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.fonnte.com/send'
        && $request->hasHeader('Authorization', 'token-uji')
        && $request['target'] === '6281234567890'
        && str_contains((string) $request['message'], '123456'));
});

it('tidak melempar galat saat Fonnte menolak permintaan', function () {
    config()->set('otp.driver', 'fonnte');
    config()->set('services.fonnte.token', 'token-uji');
    config()->set('services.fonnte.endpoint', 'https://api.fonnte.com/send');

    Http::fake([
        'api.fonnte.com/*' => Http::response(['status' => false], 400),
    ]);

    expect(fn () => app(OtpSender::class)->send('081234567890', '123456'))
        ->not->toThrow(Throwable::class);
});

it('tidak melempar galat saat koneksi ke Fonnte gagal', function () {
    config()->set('otp.driver', 'fonnte');
    config()->set('services.fonnte.token', 'token-uji');
    config()->set('services.fonnte.endpoint', 'https://api.fonnte.com/send');

    Http::fake(fn () => throw new ConnectionException('jaringan terputus'));

    expect(fn () => app(OtpSender::class)->send('081234567890', '123456'))
        ->not->toThrow(Throwable::class);
});
