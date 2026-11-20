<?php

namespace App\Services\Otp;

/**
 * Hasil pemeriksaan kode OTP.
 */
enum OtpResult
{
    case Verified;
    case Invalid;
    case Expired;
    case TooManyAttempts;
    case AlreadyVerified;
}
