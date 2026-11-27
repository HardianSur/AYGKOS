# Design

## Context

Lihat `proposal.md` untuk motivasi dan `specs/admin/kost-verification/spec.md` untuk kontrak perilaku.

Keadaan kode yang membentuk pendekatan ini:

- `app/Models/Kost.php` sudah memuat semua relasi yang dibutuhkan detail: `owner`, `reviewer`, `photos`, `documents`, `facilities()`, `kostRules()`, dan `roomTypes()`. Tidak ada relasi atau kolom baru.
- `app/Support/PrivateFileUrl::for()` sudah ada dan menghasilkan tautan bertanda tangan 10 menit ke route `storage.local`, dengan alasan yang terdokumentasi: adapter disk `local` tidak mendukung `Storage::temporaryUrl()`.
- `config/uploads.php` menjadi satu sumber kebenaran: `kost_documents_wajib` berisi `nib` dan `surat_pernyataan`, sementara lima jenis dokumen didefinisikan di constraint `kost_documents_type_values`.
- `app/Policies/KostPolicy.php` hari ini hanya melayani jalur Pemilik Kost (`viewAny`, `create`) dengan pemeriksaan peran `pemilik_kost`.
- Pola halaman Admin sudah ada di `app/Http/Controllers/Admin/FacilityController.php` dan `resources/js/pages/admin/facilities/index.tsx`: controller tipis, prop Inertia, tabel bertema token (`surface`, `hairline`, `ink`, `ink-soft`, `ember-wash`), tombol `h-10 rounded-lg bg-ember`.
- `resources/js/pages/owner/kosts/index.tsx` sudah memakai `paginate(12)` tanpa `withQueryString()`; halaman Admin memakai pola pagination serupa (tombol Sebelumnya/Berikutnya berbasis `prev_page_url`/`next_page_url`) tetapi dengan `withQueryString()`.
- SSR Inertia aktif (`config/inertia.php`). Tidak ada komponen yang butuh `window` pada render awal, sehingga halaman ini aman di-SSR.

## Goals / Non-Goals

**Goals:**

- Dua halaman baca-saja yang datanya berasal dari satu query bersarang per halaman, tanpa N+1.
- Satu sumber kebenaran untuk daftar lima dokumen dan penanda wajib, yaitu `config/uploads.php`.
- Komponen terpisah agar halaman tetap tipis dan gaya tabel Admin yang sudah ada dipakai ulang, bukan diduplikasi.
- Otorisasi di server lewat middleware peran dan Policy, bukan dengan menyembunyikan tautan.

**Non-Goals:**

- Tidak menambah kolom, tabel, migrasi, atau model.
- Tidak membuat Action, Form Request, atau route mutasi apa pun.
- Tidak mengubah `KostPolicy` milik Pemilik Kost; ability Admin ditambahkan terpisah.
- Tidak menyentuh halaman publik, katalog, atau dashboard hitungan.
- Tidak menangani tipe kamar sebagai bagian yang dapat dikelola (M2.2 belum ada); detail hanya menampilkan namanya bila baris `room_types` sudah ada.

## Decisions

### 1. Dua route `GET` di grup `role:admin`, tanpa route mutasi

```php
Route::get('kosts', [KostController::class, 'index'])->name('kosts.index');
Route::get('kosts/{kost}', [KostController::class, 'show'])->name('kosts.show');
```

Ditempatkan di grup `role:admin` yang sudah ada sehingga `auth`, `verified`, dan `role:admin` berlaku otomatis, konsisten dengan `facilities`.

Alternatif: route `POST` setujui/tolak di tempat yang sama. Ditolak untuk change ini karena proposal membatasi cakupan ke baca-saja, dan menambahnya sekarang akan memaksa Action, Form Request, serta notifikasi ikut dikerjakan.

### 2. Ability Admin ditambahkan ke `KostPolicy`, bukan Policy baru

Dua method baru: `viewAnyAsAdmin(User $user): bool` dan `viewAsAdmin(User $user): bool`, keduanya hanya memeriksa `$user->role?->name === 'admin'` dan `$user->is_active`.

Alternatif: `AdminKostPolicy` terpisah. Ditolak karena satu model dengan satu nama Policy lebih mudah ditemukan, dan Laravel memetakan Policy per model; dua Policy untuk satu model memaksa pemanggilan `Gate::authorize` manual dengan nama ability yang mudah salah ketik.

`viewAsAdmin` sengaja tidak memeriksa `hasVerifiedIdentity()` sendiri karena middleware `verified` sudah menanganinya di route; pemeriksaan ganda membuat perilaku 403 dan redirect bercampur.

### 3. Query master memakai subquery filter, bukan `when()` berlapis

```php
$filters = $request->validate([
    'status' => ['nullable', Rule::in(['menunggu_verifikasi', 'disetujui', 'ditolak', 'ditangguhkan', 'nonaktif'])],
    'dari' => ['nullable', 'date'],
    'sampai' => ['nullable', 'date'],
]);
```

Validasi memakai `$request->validate()`, bukan Form Request, karena ini `GET` tanpa mutasi dan nilai tak sah cukup diabaikan (spec: "nilai status tidak dikenal diabaikan"). Laravel `validate` akan melempar galat pada nilai tak sah, sehingga dipakai `Rule::in` dengan `nullable` dan nilai yang gagal validasi diabaikan lewat `$request->input()` terpisah:

```php
$status = in_array($request->input('status'), self::STATUSES, true) ? $request->input('status') : null;
```

Rentang tanggal memakai `whereDate('created_at', '>=', $dari)` dan `whereDate('created_at', '<=', $sampai)`. Bila `dari > sampai`, keduanya tetap diterapkan; hasilnya kosong dan halaman tetap tampil tanpa galat — sesuai spec yang meminta "tanpa galat dan tanpa hasil yang menyesatkan" (hasil kosong adalah jawaban jujur, bukan data salah). Tidak ada penukaran otomatis karena itu menyembunyikan salah isi Admin.

Alternatif: `when($status, ...)` berlapis. Ditolak karena rantai `when` dengan lima filter lebih sulit dibaca daripada satu blok penentuan nilai.

### 4. Pagination `paginate(10)->withQueryString()`

`withQueryString()` wajib agar `status`, `dari`, dan `sampai` menempel pada `prev_page_url`/`next_page_url` (spec: "Penyaring dan halaman bertahan pada URL"). Frontend memakai dua tombol Sebelumnya/Berikutnya berbasis URL itu, sama seperti `owner/kosts`, tanpa membangun ulang query di klien.

Alternatif: pagination nomor halaman dengan `onEachSide`. Ditolak karena `prev/next` sudah pola yang ada di proyek dan cukup untuk 10 baris per halaman.

### 5. Satu query detail dengan eager loading lengkap

```php
$kost->load([
    'owner:id,name,email,whatsapp_number',
    'reviewer:id,name',
    'photos',
    'documents',
    'facilities:id,name,icon',
    'kostRules:id,name,icon',
    'roomTypes:id,name,status,price_per_month',
]);
```

Kolom yang dibutuhkan dipilih eksplisit agar payload Inertia tidak membawa `password` atau kolom tak terpakai. `owner` hanya mengambil empat kolom.

`documents` diubah menjadi lima slot di controller, bukan di frontend:

```php
$uploaded = $kost->documents->keyBy('type');
$documents = collect(self::DOCUMENT_TYPES)->map(fn (string $type): array => [
    'type' => $type,
    'required' => in_array($type, config('uploads.kost_documents_wajib'), true),
    'uploaded' => $uploaded->has($type),
    'url' => $uploaded->has($type) ? PrivateFileUrl::for($uploaded[$type]->file_path) : null,
])->values();
```

Alasan: penanda wajib berasal dari config yang hanya dibaca PHP, dan pembentukan URL bertanda tangan harus terjadi saat render (bukan saat hidrasi React), karena tanda tangan memakai `now()` dan umur 10 menit.

Alternatif: kirim `documents` mentah lalu hitung slot di React. Ditolak karena itu menaruh `config('uploads')` di dua bahasa dan membuat masa berlaku tanda tangan bergantung pada jam klien.

### 6. Peta detail memakai `LeafletMap` yang sudah ada, tanpa kunci API

Komponen `resources/js/components/map/leaflet-map.tsx` sudah menerima `MapMarker`/`MapCenter`. Detail mengirim `latitude`/`longitude` sebagai angka dan merender satu marker non-interaktif.

Alternatif: peta statis Geoapify. Ditolak karena menambah permintaan jaringan pada halaman Admin dan `LeafletMap` sudah menjadi pola proyek.

### 7. Slicing komponen frontend

Halaman admin di proyek ini berisi tabel besar di satu berkas (485 baris untuk fasilitas). Untuk halaman ini slicing dilakukan sejak awal supaya tidak mengulang:

```
resources/js/pages/admin/kosts/
  index.tsx                  halaman tipis: props + susun filter/tabel/pagination
  show.tsx                   halaman tipis: props + susun panel detail

resources/js/components/admin/kost/
  status-badge.tsx           peta status -> label + kelas token (dipakai index & show)
  index-filter-bar.tsx       input status + dari + sampai, kirim lewat router.get
  index-table.tsx            tabel 5 kolom + tautan ke detail
  index-pagination.tsx       tombol Sebelumnya/Berikutnya dari URL
  detail-identity.tsx        nama, tipe, kota, alamat, tanggal, deskripsi
  detail-owner.tsx           nama, email, WhatsApp pemilik
  detail-location.tsx        bungkus LeafletMap satu marker
  detail-facilities.tsx      daftar fasilitas + peraturan (dua kelompok)
  detail-photos.tsx          grid foto bangunan dengan status kosong
  detail-documents.tsx       lima slot, tanda wajib, tautan bertanda tangan
  detail-review.tsx          peninjau, waktu, alasan (null-safe)
```

Aturan pemisahan: satu komponen tidak pernah melakukan permintaan sendiri; seluruh data masuk lewat prop dari halaman. `status-badge.tsx` sengaja dipakai bersama karena label dan warna status harus identik di master dan detail.

`index-filter-bar` mengirim nilai filter dengan `router.get(index.url(), values, { preserveState: true, replace: true })`, mereset `page` ke 1 setiap filter berubah agar tidak mendarat di halaman di luar jangkauan hasil saringan.

Alternatif: formulir `GET` biasa tanpa Inertia. Ditolak karena akan memuat ulang seluruh dokumen dan keluar dari pola navigasi SPA proyek.

### 8. Daftar status dan label dijadikan satu sumber

Label status (`Menunggu Verifikasi`, `Disetujui`, `Ditolak`, `Ditangguhkan`, `Nonaktif`) beserta kelas token-nya sudah ada di `resources/js/pages/owner/kosts/index.tsx`. Daripada menyalin, `status-badge.tsx` memuat peta itu dan halaman pemilik kelak dapat memakainya; untuk change ini halaman pemilik tidak disentuh agar diff tetap terbatas.

Alternatif: mengekstrak peta ke `resources/js/lib/kost-status.ts` lalu mengubah halaman pemilik sekaligus. Ditolak karena menyentuh berkas di luar cakupan.

## Risks / Trade-offs

- **[Daftar dokumen di dua tempat]** Lima jenis dokumen muncul di constraint migrasi, di `config/uploads.php` hanya yang wajib, dan di konstanta controller untuk urutan tampil. Perubahan jenis dokumen kelak harus menyentuh ketiganya. → Mitigasi: controller membaca `config('uploads.kost_documents_wajib')` untuk penanda wajib dan hanya menyimpan urutan tampil; tidak ada penyalinan daftar wajib.
- **[Jumlah pengajuan besar]** Tabel tanpa pencarian teks akan lambat ditelusuri saat ribuan kost. → Mitigasi: filter status dan tanggal sudah memadai untuk alur verifikasi harian; pencarian nama/pemilik ditunda ke change terpisah bila dibutuhkan.
- **[Tautan dokumen kedaluwarsa saat halaman terbuka lama]** Admin yang membiarkan tab terbuka lebih dari 10 menit akan mendapati tautan dokumen ditolak. → Mitigasi: halaman detail menyebut masa berlaku tautan pada daftar dokumen ("Tautan berlaku 10 menit; muat ulang halaman bila kedaluwarsa"); perilaku penolakan sendiri sudah ada dan diuji di `platform/file-storage`.
- **[Policy dobel peran]** `KostPolicy` kini melayani dua jalur peran. → Mitigasi: method Admin dinamai eksplisit (`viewAnyAsAdmin`, `viewAsAdmin`) dengan docblock yang menyebut jalurnya, dan tes memastikan Customer/Pemilik Kost tetap 403 pada route Admin sekaligus tetap bisa membuka halaman miliknya.
- **[Tabel `room_types` kosong]** Data tipe kamar hampir selalu kosong karena M2.2 belum dibangun. → Mitigasi: detail menampilkan bagian tipe kamar hanya bila ada baris, tanpa keadaan kosong yang membingungkan; bagian ini tidak dijanjikan di spec.

## Migration Plan

1. Tambah route dan controller; halaman belum tertaut di sidebar sehingga aman dirilis lebih dulu.
2. Tambah ability Policy dan tes otorisasi.
3. Tambah halaman dan komponen frontend, lalu tautan sidebar.
4. Jalankan `php artisan wayfinder:generate` agar helper route `admin/kosts` tersedia di frontend.

Rollback: hapus dua route dan tautan sidebar. Tidak ada migrasi maupun data yang perlu dibatalkan, jadi rollback bersifat menghapus berkas saja.

## Open Questions

Tidak ada. Nama route, bentuk filter, dan isi detail sudah diputuskan; perubahan lebih lanjut (pencarian teks, aksi verifikasi) berada di change terpisah.