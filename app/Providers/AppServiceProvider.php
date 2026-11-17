<?php

namespace App\Providers;

use App\Contracts\OtpSender;
use App\Services\Otp\FonnteOtpSender;
use App\Services\Otp\LogOtpSender;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OtpSender::class, function (): OtpSender {
            /** @var string|null $driver */
            $driver = config('otp.driver');
            /** @var string|null $token */
            $token = config('services.fonnte.token');

            if ($driver === 'fonnte' && ($token === null || $token === '')) {
                Log::warning('FONNTE_TOKEN tidak diisi; OTP memakai driver log.');

                return new LogOtpSender;
            }

            return $driver === 'fonnte'
                ? new FonnteOtpSender
                : new LogOtpSender;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
