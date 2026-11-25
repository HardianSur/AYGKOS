<?php

use App\Http\Requests\Uploads\ComplaintAttachmentUploadRequest;
use App\Http\Requests\Uploads\KostDocumentUploadRequest;
use App\Http\Requests\Uploads\KostPhotoUploadRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::post('/__test/uploads/kost-photo', fn (KostPhotoUploadRequest $request) => response()->noContent())
        ->middleware('web');
    Route::post('/__test/uploads/kost-document', fn (KostDocumentUploadRequest $request) => response()->noContent())
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

it('menerima dokumen izin berupa pdf yang valid', function () {
    $this->post('/__test/uploads/kost-document', [
        'type' => 'nib',
        'document' => UploadedFile::fake()->create('nib.pdf', 100, 'application/pdf'),
    ])->assertNoContent();
});

it('menolak jenis dokumen izin di luar daftar pada kolom type', function () {
    $this->post('/__test/uploads/kost-document', [
        'type' => 'ktp',
        'document' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('type');
});

it('menolak dokumen izin selain pdf pada kolom document', function () {
    $this->post('/__test/uploads/kost-document', [
        'type' => 'nib',
        'document' => UploadedFile::fake()->create('nib.jpg', 100, 'image/jpeg'),
    ])->assertSessionHasErrors('document');
});

it('menolak dokumen izin yang melebihi batas ukuran', function () {
    $this->post('/__test/uploads/kost-document', [
        'type' => 'nib',
        'document' => UploadedFile::fake()->create('nib.pdf', 6000, 'application/pdf'),
    ])->assertSessionHasErrors('document');
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
