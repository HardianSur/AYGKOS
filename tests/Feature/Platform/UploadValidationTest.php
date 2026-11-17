<?php

use App\Http\Requests\Uploads\ComplaintAttachmentUploadRequest;
use App\Http\Requests\Uploads\KostPhotoUploadRequest;
use App\Http\Requests\Uploads\VerificationDocumentUploadRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::post('/__test/uploads/kost-photo', fn (KostPhotoUploadRequest $request) => response()->noContent())
        ->middleware('web');
    Route::post('/__test/uploads/verification-document', fn (VerificationDocumentUploadRequest $request) => response()->noContent())
        ->middleware('web');
    Route::post('/__test/uploads/complaint-attachment', fn (ComplaintAttachmentUploadRequest $request) => response()->noContent())
        ->middleware('web');

    $this->actingAs(User::factory()->create());
});

it('menolak foto kost yang melebihi batas ukuran pada kolom photo', function () {
    $this->post('/__test/uploads/kost-photo', [
        'photo' => UploadedFile::fake()->create('foto.png', 4000, 'image/png'),
    ])->assertSessionHasErrors('photo');
});

it('menolak foto kost bertipe tidak diizinkan pada kolom photo', function () {
    $this->post('/__test/uploads/kost-photo', [
        'photo' => UploadedFile::fake()->create('foto.txt', 100, 'text/plain'),
    ])->assertSessionHasErrors('photo');
});

it('menerima foto kost yang valid', function () {
    $this->post('/__test/uploads/kost-photo', [
        'photo' => UploadedFile::fake()->create('foto.webp', 100, 'image/webp'),
    ])->assertNoContent();
});

it('menolak dokumen verifikasi bertipe tidak diizinkan pada kolom yang benar', function () {
    $this->post('/__test/uploads/verification-document', [
        'verification_document' => UploadedFile::fake()->create('ktp.exe', 100, 'application/octet-stream'),
    ])->assertSessionHasErrors('verification_document');
});

it('menolak dokumen verifikasi yang melebihi batas ukuran', function () {
    $this->post('/__test/uploads/verification-document', [
        'verification_document' => UploadedFile::fake()->create('ktp.pdf', 6000, 'application/pdf'),
    ])->assertSessionHasErrors('verification_document');
});

it('menerima dokumen verifikasi yang valid', function () {
    $this->post('/__test/uploads/verification-document', [
        'verification_document' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
    ])->assertNoContent();
});

it('menolak lampiran aduan melebihi jumlah maksimum', function () {
    $this->post('/__test/uploads/complaint-attachment', [
        'attachments' => [
            UploadedFile::fake()->create('a.jpg', 100, 'image/jpeg'),
            UploadedFile::fake()->create('b.jpg', 100, 'image/jpeg'),
            UploadedFile::fake()->create('c.jpg', 100, 'image/jpeg'),
            UploadedFile::fake()->create('d.jpg', 100, 'image/jpeg'),
        ],
    ])->assertSessionHasErrors('attachments');
});

it('menolak lampiran aduan bertipe tidak diizinkan', function () {
    $this->post('/__test/uploads/complaint-attachment', [
        'attachments' => [UploadedFile::fake()->create('a.txt', 100, 'text/plain')],
    ])->assertSessionHasErrors('attachments.0');
});
