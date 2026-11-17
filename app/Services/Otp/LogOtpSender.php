<?php

namespace App\Services\Otp;

use App\Contracts\OtpSender;
use Illuminate\Support\Facades\Log;

/**
 * Pengirim OTP darurat: menulis kode ke log aplikasi tanpa memanggil
 * penyedia eksternal.
 */
final class LogOtpSender implements OtpSender
{
    public function send(string $number, string $code): void
    {
        Log::info('Kode OTP WhatsApp (driver log).', [
            'number' => $number,
            'code' => $code,
        ]);
    }
}
