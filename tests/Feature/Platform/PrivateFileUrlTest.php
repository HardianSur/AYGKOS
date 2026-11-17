<?php

use App\Support\PrivateFileUrl;
use Illuminate\Support\Facades\Storage;

it('membentuk URL bertanda tangan dengan masa berlaku', function () {
    $url = PrivateFileUrl::for('documents/ktp.pdf');

    expect($url)
        ->toContain('/storage/documents/ktp.pdf')
        ->toContain('signature=')
        ->toContain('expires=');
});

it('menolak permintaan berkas privat tanpa tanda tangan', function () {
    Storage::fake('local');
    Storage::disk('local')->put('documents/ktp.pdf', 'isi dokumen');

    $this->get('/storage/documents/ktp.pdf')->assertForbidden();
});

it('menyajikan berkas privat melalui URL bertanda tangan', function () {
    Storage::fake('local');
    Storage::disk('local')->put('documents/ktp.pdf', 'isi dokumen');

    $this->get(PrivateFileUrl::for('documents/ktp.pdf'))
        ->assertOk()
        ->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox");
});
