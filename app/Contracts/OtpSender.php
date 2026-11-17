<?php

namespace App\Contracts;

/**
 * Transport pengiriman kode OTP.
 *
 * Implementasi ini hanya mengirim kode; pembuatan, hashing, penyimpanan,
 * dan verifikasi kode menjadi tanggung jawab pemanggil.
 */
interface OtpSender
{
    /**
     * Kirim kode OTP ke nomor tujuan.
     */
    public function send(string $number, string $code): void;
}
