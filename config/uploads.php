<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batas Unggahan Berkas
    |--------------------------------------------------------------------------
    |
    | Satu sumber kebenaran untuk batas ukuran (dalam kilobyte) dan ekstensi
    | yang diizinkan per jenis berkas. Form Request membaca nilai di sini
    | sehingga pesan galat otomatis menempel pada kolom yang benar.
    |
    | Catatan: aturan `max` Laravel untuk berkas dinyatakan dalam kilobyte.
    | Pastikan upload_max_filesize dan post_max_size PHP lebih besar dari
    | nilai terbesar di bawah (5 MB = 5120 KB).
    |
    */

    'kost_photo' => [
        'max_kb' => 3 * 1024,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    'verification_document' => [
        'max_kb' => 5 * 1024,
        'mimes' => ['jpg', 'jpeg', 'png', 'pdf'],
    ],

    'complaint_attachment' => [
        'max_kb' => 5 * 1024,
        'max_files' => 3,
        'mimes' => ['jpg', 'jpeg', 'png', 'pdf'],
    ],

];
