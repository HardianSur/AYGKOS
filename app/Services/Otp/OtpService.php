<?php

namespace App\Services\Otp;

use App\Contracts\OtpSender;
use App\Models\User;
use App\Models\WhatsappVerification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Penerbitan dan pemeriksaan kode OTP WhatsApp.
 *
 * Satu akun hanya memiliki satu kode aktif. Setiap penerbitan baru membatalkan
 * kode sebelumnya, dan setiap kegagalan pemeriksaan menaikkan penghitung
 * percobaan tanpa menandai kode sebagai terpakai.
 */
final class OtpService
{
    public function __construct(private readonly OtpSender $sender) {}

    /**
     * Terbitkan kode baru dan kirimkan ke nomor tujuan.
     *
     * Permintaan yang masih berada di jeda kirim ulang atau sudah mencapai
     * batas pengiriman per jam diabaikan tanpa mengubah kode aktif.
     */
    public function issue(User $user, string $number): void
    {
        if (! $this->canResend($user)) {
            return;
        }

        $now = now();

        $user->whatsappVerifications()
            ->whereNull('consumed_at')
            ->update(['consumed_at' => $now]);

        $code = Str::padLeft((string) random_int(0, 999999), 6, '0');

        $user->whatsappVerifications()->create([
            'target_number' => $number,
            'code_hash' => Hash::make($code),
            'expires_at' => $now->addMinutes((int) config('otp.expiry_minutes')),
            'resend_count' => $this->sendsInLastHour($user),
            'last_sent_at' => $now,
        ]);

        $this->sender->send($number, $code);
    }

    /**
     * Periksa kode yang dimasukkan pengguna.
     */
    public function verify(User $user, string $code): OtpResult
    {
        if ($user->whatsapp_verified_at !== null) {
            return OtpResult::AlreadyVerified;
        }

        $verification = $this->activeVerification($user);

        if (! $verification instanceof WhatsappVerification) {
            return OtpResult::Invalid;
        }

        if ($verification->attempt_count >= (int) config('otp.max_attempts')) {
            return OtpResult::TooManyAttempts;
        }

        if ($verification->expires_at->isPast()) {
            $verification->increment('attempt_count');

            return OtpResult::Expired;
        }

        if (! Hash::check($code, $verification->code_hash)) {
            $verification->increment('attempt_count');

            return OtpResult::Invalid;
        }

        $verification->forceFill(['consumed_at' => now()])->save();
        $user->forceFill(['whatsapp_verified_at' => now()])->save();

        return OtpResult::Verified;
    }

    /**
     * Apakah pengguna boleh meminta kode baru sekarang.
     */
    public function canResend(User $user): bool
    {
        return $this->resendWaitSeconds($user) === 0;
    }

    /**
     * Sisa detik sebelum kirim ulang diizinkan (0 bila sudah boleh).
     */
    public function resendWaitSeconds(User $user): int
    {
        if ($this->sendsInLastHour($user) >= (int) config('otp.max_sends_per_hour')) {
            $oldest = $user->whatsappVerifications()
                ->where('last_sent_at', '>=', now()->subHour())
                ->oldest('last_sent_at')
                ->value('last_sent_at');

            return $oldest === null
                ? 0
                : $this->secondsUntil(Carbon::parse((string) $oldest)->addHour());
        }

        $latest = $user->whatsappVerifications()->latest('last_sent_at')->value('last_sent_at');

        if ($latest === null) {
            return 0;
        }

        return $this->secondsUntil(
            Carbon::parse((string) $latest)->addSeconds((int) config('otp.resend_cooldown_seconds')),
        );
    }

    /**
     * Jumlah pengiriman kode dalam satu jam terakhir.
     */
    public function sendsInLastHour(User $user): int
    {
        return $user->whatsappVerifications()
            ->where('last_sent_at', '>=', now()->subHour())
            ->count();
    }

    /**
     * Sisa detik menuju waktu tertentu (0 bila sudah lewat).
     */
    private function secondsUntil(Carbon $moment): int
    {
        $now = now();

        return $moment->isFuture() ? (int) $now->diffInSeconds($moment) : 0;
    }

    /**
     * Kode yang masih aktif untuk akun ini.
     */
    private function activeVerification(User $user): ?WhatsappVerification
    {
        return $user->whatsappVerifications()
            ->whereNull('consumed_at')
            ->latest('created_at')
            ->first();
    }
}
