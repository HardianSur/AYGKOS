# Tasks

## 1. Skema Basis Data

- [ ] 1.1 Buat `database/migrations/2026_11_24_000001_restructure_kost_catalog.php`: buang tabel `rooms` dan `kost_photos`, buang kolom `kosts.rules` dan `kosts.verification_document_path`; verifikasi dengan `php artisan migrate:fresh --env=testing` berjalan tanpa galat dan `down()` mengembalikan bentuk V004.
- [ ] 1.2 Buat tabel `room_types` (`kost_id`, `name`, `price_per_month`, `total_rooms`, `available_rooms`, `notes`, `status`, timestamp, `deleted_at`) dengan unique `(kost_id, name)` dan check `price_per_month > 0`, `total_rooms > 0`, `available_rooms between 0 and total_rooms`; verifikasi dengan tes insert yang ditolak pada `tests/Feature/Schema/DomainSchemaTest.php`.
- [ ] 1.3 Buat tabel master `rules` (`name` unique, `scope`), `specifications` (`name` unique, `input_type`, `unit`), dan tambahkan `scope` serta `icon` pada `facilities`; verifikasi constraint `scope` dan `input_type` menolak nilai di luar enumerasi.
- [ ] 1.4 Buat tabel pivot `kost_rules`, `room_type_facilities`, `room_type_rules`, `room_type_specifications` (`value`) dengan primary key komposit dan FK `cascade` ke induk; ubah FK master pada `kost_facilities` yang sudah ada dari `cascade` menjadi `restrict`, dan pakai `restrict` pada seluruh pivot untuk sisi master; verifikasi penghapusan master yang masih dipakai ditolak.
- [ ] 1.5 Buat tabel `photos` (`kost_id`, `room_type_id` nullable, `category`, `photo_path`, `sort_order`, `is_primary`) dengan CHECK keterkaitan kategori dan indeks unik parsial `is_primary` per kost dan per tipe kamar; verifikasi dua foto utama ditolak dan kategori `bangunan` dengan `room_type_id` terisi ditolak.
- [ ] 1.6 Buat tabel `kost_documents` (`kost_id`, `type`, `file_path`) dengan unique `(kost_id, type)` dan CHECK lima jenis; verifikasi jenis ganda pada satu kost ditolak.

## 2. Model dan Relasi

- [ ] 2.1 Hapus `app/Models/Room.php` dan `app/Models/KostPhoto.php`; buat `RoomType`, `Photo`, `KostDocument`, `Rule`, `Specification` dengan `HasUuids`, `#[Fillable]`, dan `SoftDeletes` hanya pada `RoomType`; verifikasi nama tabel lewat tes model.
- [ ] 2.2 Sesuaikan `app/Models/Kost.php`: buang `rules` dan `verification_document_path` dari fillable dan docblock, ganti `rooms()` menjadi `roomTypes()`, tambah `photos()`, `documents()`, `kostRules()`, `facilities()`; verifikasi tes "menghapus kost menghapus tipe kamar dan fotonya" lulus.
- [ ] 2.3 Tulis ulang `tests/Feature/Schema/DomainSchemaTest.php` dan `DomainModelTest.php` untuk tabel baru (daftar tabel, kolom timestamp, aksi `on delete`, constraint check, keunikan); verifikasi `php artisan test --filter=Domain` lulus.
- [ ] 2.4 Buat `app/Support/FacilityIcons.php` sebagai satu-satunya sumber daftar nama ikon fasilitas, dipakai validasi fasilitas dan `FacilitySeeder`; verifikasi tes menolak nama ikon di luar daftar.
- [ ] 2.5 Tambah seeder master data `FacilitySeeder`, `RuleSeeder`, `SpecificationSeeder` memakai nama ikon dari `FacilityIcons`; verifikasi `php artisan db:seed --class=FacilitySeeder` menghasilkan baris tanpa galat dan `composer test` tetap hijau.

## 3. Konfigurasi dan Dukungan

- [ ] 3.1 Tambah `config/uploads.php` kunci `kost_document` (5 MB, `pdf` saja) dan `kost_documents_wajib` berisi `['nib', 'surat_pernyataan']`; verifikasi `UploadValidationTest` memuat kasus baru, termasuk penolakan berkas `jpg`.
- [ ] 3.2 Buat `app/Http/Requests/Uploads/KostDocumentUploadRequest.php` mengikuti pola `KostPhotoUploadRequest`, memvalidasi `type` terhadap lima jenis dan hanya menerima berkas PDF; verifikasi tes route uji menerima PDF valid serta menolak jenis asing dan berkas `jpg`.
- [ ] 3.3 Pensiunkan `VerificationDocumentUploadRequest` beserta kunci `uploads.verification_document`: hapus berkasnya, hapus kunci konfigurasi, dan hapus kasus ujinya dari `tests/Feature/Platform/UploadValidationTest.php`; verifikasi tidak ada lagi rujukan `verification_document` pada `app/`, `config/`, dan `tests/`.

## 4. Dokumentasi Domain

- [ ] 4.1 Tulis ulang `docs/ERD/erd.md` ke V005: buang `rooms` dan `kost_photos`, tambah `room_types`, `photos`, `kost_documents`, `rules`, `specifications`, `room_type_specifications`, `kost_rules`, `room_type_rules`, `room_type_facilities`, dan kolom `facilities.scope`/`facilities.icon`; bekukan V004 ke `docs/ERD/logs/V004-erd.md` dan tambah baris baru pada `docs/ERD/release.md`. Verifikasi dengan `openspec validate` dan cek daftar entitas ERD memuat seluruh tabel baru.
- [ ] 4.2 Perbarui `docs/FunctionalRequirment/functional_requirment.md` ke V002: FR-KOS-01/05/06/07/08, FR-SRC-03/06/08, FR-KTK-03/04, FR-ADM-VER-01, FR-ADM-REP-01, FR-DSH-01, dan FR-ADM baru untuk CRUD master data; bekukan V001 ke `docs/FunctionalRequirment/logs/V001-functional_requirment.md` dan tambah baris pada `docs/FunctionalRequirment/release.md`. Verifikasi tidak ada lagi FR yang menyebut nomor kamar atau `kosts.rules`.
- [ ] 4.3 Perbarui `docs/ARCHITECTURE.md` (layout tabel, `KostCard`, alur simpan fasilitas, alur galeri, route map admin master data dan tipe kamar) serta `docs/ROADMAP.md` M2.2/M3.2 (ganti `rooms` dengan `room_types`, kriteria keluar nomor kamar diganti validasi tipe kamar). Verifikasi tidak ada lagi penyebutan nomor kamar pada kedua dokumen.

## 5. Verifikasi

- [ ] 5.1 Jalankan `php artisan migrate:fresh --seed` pada database uji, lalu `composer test`; verifikasi hijau.
- [ ] 5.2 Jalankan `grep -rn "rooms\b\|room_number\|KostPhoto\|verification_document_path" app/ database/ tests/ config/ routes/`; verifikasi hanya menyisakan nama berkas `KostPhotoUploadRequest` yang masih dipakai unggahan foto kost.
- [ ] 5.3 Jalankan `openspec validate --changes restructure-kost-catalog --strict`; verifikasi lolos.

## Deferred to later changes

Perilaku berikut sudah ditetapkan pada delta spec tetapi implementasinya ditunda ke fase M2.2 (pengelolaan kost) dan M3.2 (pencarian dan detail kost), karena change ini dibatasi pada dokumen, konfigurasi, basis data, dan model.

- Controller, FormRequest, Action, Policy, dan halaman Inertia pengelolaan master data oleh Admin.
- Action dan halaman pengelolaan tipe kamar, sinkronisasi fasilitas/aturan/spesifikasi, unggah dokumen, dan pengajuan kost oleh Pemilik Kost.
- Query daftar publik, kartu hasil pencarian, halaman detail bertipe kamar, galeri berkategori, dan draf WhatsApp "Ajukan Sewa".

## Workflow follow-up

- Arsipkan change setelah review pemilik proyek menyetujui delta spec.
- Jalankan `openspec archive restructure-kost-catalog` lalu verifikasi spec utama `openspec/specs/domain/kost-catalog/spec.md` sudah memuat requirement tipe kamar.
