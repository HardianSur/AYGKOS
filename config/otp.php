<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver Pengirim OTP
    |--------------------------------------------------------------------------
    |
    | Nilai: `log` (tulis kode ke log aplikasi) atau `fonnte` (WhatsApp).
    | Bila `fonnte` dipilih tanpa token, aplikasi jatuh ke `log` dengan
    | peringatan pada log agar tetap dapat dijalankan.
    |
    */

    'driver' => env('OTP_DRIVER', 'log'),

    /*
    | Masa berlaku kode, batas percobaan, jeda kirim ulang, dan batas
    | pengiriman per jam per akun. Nilai ini mengikuti catatan ERD.
    */

    'expiry_minutes' => 5,

    'max_attempts' => 5,

    'resend_cooldown_seconds' => 60,

    'max_sends_per_hour' => 3,

];
