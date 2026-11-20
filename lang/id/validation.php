<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pesan Validasi
    |--------------------------------------------------------------------------
    |
    | Hanya kunci yang dipakai formulir masuk yang diterjemahkan di sini. Kunci
    | lain jatuh ke bahasa cadangan (APP_FALLBACK_LOCALE) sehingga berkas ini
    | tidak perlu menyalin seluruh kamus bawaan.
    |
    */

    'confirmed' => ':attribute dan konfirmasinya tidak sama.',
    'email' => ':attribute harus berupa alamat email yang sah.',
    'min' => [
        'string' => ':attribute minimal :min karakter.',
    ],
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah terdaftar.',

    /*
    |--------------------------------------------------------------------------
    | Nama Atribut
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'email' => 'email',
        'name' => 'nama',
        'password' => 'kata sandi',
    ],

];
