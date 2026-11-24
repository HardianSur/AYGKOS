<?php

use App\Http\Controllers\Auth\WhatsappVerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        return Inertia::render(
            $request->user()?->role?->name === 'customer' ? 'home' : 'dashboard'
        );
    })->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('verify-whatsapp', [WhatsappVerificationController::class, 'show'])
        ->name('verification.whatsapp.notice');

    Route::post('verify-whatsapp', [WhatsappVerificationController::class, 'store'])
        ->name('verification.whatsapp.store');

    Route::post('verify-whatsapp/resend', [WhatsappVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.whatsapp.send');
});

require __DIR__.'/settings.php';
