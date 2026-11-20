<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Otp\OtpResult;
use App\Services\Otp\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WhatsappVerificationController extends Controller
{
    public function __construct(private readonly OtpService $otpService) {}

    /**
     * Tampilkan halaman verifikasi WhatsApp.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $this->currentUser($request);

        if ($user->whatsapp_verified_at !== null) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('auth/verify-whatsapp', [
            'status' => $request->session()->get('status'),
            'whatsappNumber' => $this->maskNumber($user->whatsapp_number),
            'canResend' => $this->otpService->canResend($user),
            'resendIn' => $this->otpService->resendWaitSeconds($user),
        ]);
    }

    /**
     * Periksa kode yang dikirim pengguna.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $result = $this->otpService->verify(
            $this->currentUser($request),
            $validated['code'],
        );

        if ($result === OtpResult::Verified || $result === OtpResult::AlreadyVerified) {
            return redirect()->route('dashboard')->with('status', trans('verification.verified'));
        }

        return back()->withErrors([
            'code' => trans(match ($result) {
                OtpResult::Expired => 'verification.expired_code',
                OtpResult::TooManyAttempts => 'verification.too_many_attempts',
                default => 'verification.invalid_code',
            }),
        ]);
    }

    /**
     * Terbitkan dan kirim kode baru.
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $this->currentUser($request);

        if (! $this->otpService->canResend($user)) {
            $reachedLimit = $this->otpService->sendsInLastHour($user) >= (int) config('otp.max_sends_per_hour');

            return back()->withErrors([
                'resend' => $reachedLimit
                    ? trans('verification.resend_limit')
                    : trans('verification.resend_cooldown', [
                        'seconds' => $this->otpService->resendWaitSeconds($user),
                    ]),
            ]);
        }

        if ($user->whatsapp_number === null) {
            return back()->withErrors(['resend' => trans('verification.missing_number')]);
        }

        $this->otpService->issue($user, $user->whatsapp_number);

        return back()->with('status', trans('verification.code_sent'));
    }

    private function currentUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    /**
     * Samarkan nomor agar halaman tidak membocorkan nomor penuh.
     */
    private function maskNumber(?string $number): ?string
    {
        if ($number === null || strlen($number) < 6) {
            return $number;
        }

        return substr($number, 0, 4).'****'.substr($number, -3);
    }
}
