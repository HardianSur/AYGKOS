<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FacilityController;
use App\Http\Controllers\Auth\WhatsappVerificationController;
use App\Http\Controllers\Owner\KostController;
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

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('facilities', [FacilityController::class, 'index'])->name('facilities.index');
        Route::post('facilities', [FacilityController::class, 'store'])->name('facilities.store');
        Route::put('facilities/{facility}', [FacilityController::class, 'update'])->name('facilities.update');
        Route::delete('facilities/{facility}', [FacilityController::class, 'destroy'])->name('facilities.destroy');
    });

Route::middleware(['auth', 'verified', 'role:pemilik_kost'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {
        Route::get('kosts', [KostController::class, 'index'])->name('kosts.index');
        Route::get('kosts/create', [KostController::class, 'create'])->name('kosts.create');
        Route::post('kosts', [KostController::class, 'store'])->name('kosts.store');
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
