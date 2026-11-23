# Tasks

## 1. Dependensi dan batas unggahan

- [ ] 1.1 Jalankan `composer require intervention/image`, lalu verifikasi `composer show intervention/image` melaporkan versi terpasang dan `php artisan tinker --execute="echo class_exists(\Intervention\Image\ImageManager::class) ? 'ok' : 'missing';"` mencetak `ok`.
- [ ] 1.2 Tambahkan entri `profile_photo` (`max_kb` 3 × 1024, `mimes` `jpg,jpeg,png,webp`) di `config/uploads.php` mengikuti pola `kost_photo`, lalu verifikasi dengan `php artisan tinker --execute="print_r(config('uploads.profile_photo'));"`.

## 2. Skema data dan model

- [ ] 2.1 Buat migrasi `add_avatar_path_to_users_table` yang menambah `avatar_path varchar(500) nullable` pada `users`, lalu verifikasi `php artisan migrate` berhasil dan `php artisan tinker --execute="echo Schema::hasColumn('users','avatar_path') ? 'ok' : 'missing';"` mencetak `ok`.
- [ ] 2.2 Perbarui `app/Models/User.php`: docblock `@property string|null $avatar_path`, tambahkan `avatar_path` ke `#[Hidden]`, tambahkan `#[Appends(['avatar'])]`, dan tulis accessor `avatar()` yang mengembalikan `Storage::disk('public')->url($this->avatar_path)` atau `null`. Verifikasi dengan `php artisan tinker --execute="echo (new App\Models\User)->avatar === null ? 'ok' : 'url';"`.
- [ ] 2.3 Daftarkan listener `deleting` di `User::booted()` yang menghapus berkas avatar dari disk `public` bila `avatar_path` terisi; verifikasi dengan tes `tests/Feature/Settings/ProfileUpdateTest.php` yang membuat akun ber-avatar, menghapusnya, dan meng-assert `Storage::disk('public')->exists(...)` bernilai `false`.

## 3. Penyimpanan foto

- [ ] 3.1 Buat `app/Actions/StoreAvatar.php` yang mengubah `UploadedFile` menjadi WebP 400×400 (`orient()` → `cover(400, 400)` → `toWebp()` → `quality(85)`) lalu menyimpannya ke disk `public` di direktori `avatars` dan mengembalikan path relatif. Verifikasi dengan tes feature yang mengunggah `Storage::fake`-independent file sungguhan (`UploadedFile::fake()->image('foto.jpg', 1200, 800)`) dan meng-assert berkas tersimpan berekstensi `.webp` serta dapat dibaca `getimagesize` berukuran 400×400.
- [ ] 3.2 Tambahkan aturan `avatar` (`nullable`, `image`, `mimes` dan `max` dari `config('uploads.profile_photo')`) di `ProfileUpdateRequest::rules()` tanpa menyentuh `app/Concerns/ProfileValidationRules.php`; verifikasi dengan tes yang mengirim gambar 4 MB dan PDF, keduanya menghasilkan galat pada kunci `avatar`.

## 4. Simpan profil

- [ ] 4.1 Ubah `ProfileController::update()` agar mengambil `$request->safe()->except('avatar')` untuk `fill()`, menyimpan akun lebih dulu, lalu — bila ada berkas — menyimpan foto baru, memperbarui `avatar_path`, dan menghapus berkas lama; kegagalan penyimpanan berkas dilaporkan lewat `Inertia::flash('toast', ['type' => 'error', ...])`. Verifikasi dengan tes: unggah pertama mengisi `avatar_path`, unggah kedua menggantinya, dan berkas lama tidak ada lagi di `Storage::disk('public')`.
- [ ] 4.2 Pastikan pengiriman `whatsapp_number` bersama formulir tidak mengubah `users.whatsapp_number`; verifikasi dengan tes yang mengirim `whatsapp_number` baru lalu meng-assert nilai kolomnya tidak berubah.

## 5. Halaman profil

- [ ] 5.1 Tambahkan `whatsapp_number: string | null` dan `whatsapp_verified_at: string | null` pada `resources/js/types/auth.ts`, lalu verifikasi dengan `npm run types:check`.
- [ ] 5.2 Tambahkan ke `resources/js/pages/settings/profile.tsx`: pratinjau avatar lingkaran (`size-20`, `object-cover`, fallback inisial via `AvatarFallback`), pemilih berkas `avatar` (`accept` sesuai mime, petunjuk "JPG, PNG, atau WebP maksimal 3 MB", cincin fokus `focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-surface`), dan tampilan read-only nomor WhatsApp beserta status verifikasinya yang hanya dirender saat nomornya terisi. Verifikasi dengan `npm run types:check`, `npm run check`, dan pemeriksaan manual di kedua mode warna.

## 6. Dashboard per peran

- [ ] 6.1 Ganti `Route::inertia('dashboard', 'dashboard')` di `routes/web.php` dengan closure yang merender `home` untuk peran `customer` dan `dashboard` untuk peran lain, tetap di dalam middleware `auth` + `verified`. Verifikasi dengan tes `tests/Feature/DashboardTest.php` yang meng-assert nama komponen Inertia untuk masing-masing peran.
- [ ] 6.2 Buat `resources/js/pages/home.tsx` berisi `<h1>` sapaan dengan nama pengguna (memakai `auth.user.name`) tanpa panel placeholder; verifikasi dengan tes feature yang meng-assert `home` memuat `auth.user.name` dan dengan `npm run types:check`.

## 7. Dokumen versi

- [ ] 7.1 Salin `docs/ERD/erd.md` ke `docs/ERD/logs/V003-erd.md`, ubah `docs/ERD/erd.md` menjadi V004 (tambah baris `avatar_path` pada tabel `users` beserta baris matriks di bagian atas), dan tambahkan baris V004 di `docs/ERD/release.md` dengan waktu `dd-mm-yyyy:HH:MM WIB`. Verifikasi dengan membaca ketiga berkas: versi di header V004, kolom `avatar_path` tercatat, dan baris release menyebut V004.
- [ ] 7.2 Tambahkan header `## Versi V001` beserta catatan waktu pada `docs/FunctionalRequirment/functional_requirment.md`, sisipkan baris `FR-PRF-04` (foto profil) di kelompok FR-PRF, dan catat versi awal itu di `docs/FunctionalRequirment/release.md`. Verifikasi dengan membaca kedua berkas: `FR-PRF-04` ada dan tercatat di release.

## 8. Integrasi

- [ ] 8.1 Jalankan `composer test` (pint, phpstan level 7, pest) dan pastikan seluruh gate hijau, termasuk tes profil, dashboard, dan penghapusan akun yang baru.
- [ ] 8.2 Jalankan `npm run check`, `npm run types:check`, dan `npm run build`; perbaiki setiap temuan sampai ketiganya lolos.
- [ ] 8.3 Lakukan pemeriksaan manual di browser: unggah foto JPEG potret, pastikan foto tampil lingkaran di halaman profil, header, dan menu pengguna; masuk sebagai Customer dan pastikan `/dashboard` menampilkan sapaan; masuk sebagai Pemilik Kost dan pastikan dashboard lama serta nomor WhatsApp read-only tampil.

## Workflow follow-up

- Arsipkan change setelah tinjauan terpenuhi dan gate hijau.
- Verifikasi hasil arsip: spec delta masuk ke `openspec/specs/` dan change berpindah ke `openspec/changes/archive/`.