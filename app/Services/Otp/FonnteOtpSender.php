<?php

namespace App\Services\Otp;

use App\Contracts\OtpSender;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pengirim OTP melalui gateway WhatsApp Fonnte.
 *
 * Kegagalan penyedia dicatat dan ditelan agar alur verifikasi pengguna
 * tidak menampilkan galat internal.
 */
final class FonnteOtpSender implements OtpSender
{
    public function send(string $number, string $code): void
    {
        $token = config('services.fonnte.token');
        $endpoint = (string) config('services.fonnte.endpoint');

        if (! is_string($token) || $token === '') {
            Log::warning('FONNTE_TOKEN tidak tersedia; OTP tidak dikirim.');

            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->timeout(10)->post($endpoint, [
                'target' => $number,
                'message' => "Kode OTP Anda: {$code}. Berlaku selama 5 menit.",
                'countryCode' => '62',
            ]);

            if ($response->failed() || ! $response->json('status')) {
                Log::warning('Fonnte menolak pengiriman OTP.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return;
            }

            Log::info('Fonnte menerima OTP untuk dikirim.', [
                'target' => $number,
                'requestid' => $response->json('requestid'),
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('Gagal terhubung ke Fonnte.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
