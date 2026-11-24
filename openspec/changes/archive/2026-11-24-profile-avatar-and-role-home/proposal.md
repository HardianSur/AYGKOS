# Proposal

## Why

Halaman `settings/profile` hanya menyunting nama dan email, padahal `auth.user.avatar` sudah dirender `app-header.tsx` dan `user-info.tsx` tanpa pernah diisi server sehingga setiap pengguna tampil sebagai inisial selamanya. Setelah masuk, seluruh peran juga diarahkan ke `/dashboard` berisi placeholder kost yang belum bermakna bagi Customer (FR-AUT-10 meminta Customer diarahkan ke halaman pencarian), dan Pemilik Kost tidak bisa melihat nomor WhatsApp yang terdaftar padahal nomor itu bukti verifikasi akunnya.

## What Changes

- Profil menyimpan foto: unggahan `jpg`, `jpeg`, `png`, atau `webp` maksimal 3 MB dikonversi ke WebP 400×400 (crop tengah, orientasi EXIF dipatuhi) sebelum disimpan sekali, bukan saat disajikan.
- Foto profil tampil sebagai lingkaran di halaman profil, header, dan menu pengguna; pengguna tanpa foto tetap melihat inisial.
- Foto lama terhapus dari penyimpanan setelah foto baru berhasil ditulis, dan foto terhapus bersama akun.
- Nomor WhatsApp tampil read-only pada halaman profil untuk akun yang memilikinya (Pemilik Kost) beserta status verifikasi; Customer dan Admin tidak melihat kolom itu karena nomornya memang kosong. Perubahan nomor tetap lewat alur OTP FR-PRF-03 di halaman terpisah, di luar change ini.
- `/dashboard` menampilkan konten menurut peran: Customer mendapat halaman sapaan sederhana `<h1>hi {nama}</h1>`, sedangkan Pemilik Kost dan Admin tetap melihat dashboard yang ada sampai FR-DSH-01/02 dibangun. Pengarahan setelah masuk tidak berubah, tetap ke `/dashboard`.
- `docs/FunctionalRequirment/functional_requirment.md` mendapat header versi dan baris `FR-PRF-04` (foto profil); `docs/ERD/erd.md` naik ke V004 dengan kolom `users.avatar_path`.

## Capabilities

### New Capabilities

- `profile/account-profile`: isi halaman profil yang dapat dilihat dan diubah pengguna — nama, email, foto profil beserta aturan unggah dan tampilannya, serta nomor WhatsApp read-only.
- `dashboard/role-home`: konten yang disajikan pada `/dashboard` menurut peran, dan perlakuan terhadap peran yang dashboard-nya belum dibangun.

### Modified Capabilities

- `domain/identity-accounts`: akun menyimpan path foto profil yang boleh kosong, tanpa mengubah aturan peran maupun verifikasi.
- `platform/file-storage`: foto profil termasuk berkas publik, diganti secara atomik, dan dibersihkan saat akun dihapus.

## Impact

- Migrasi baru menambah `users.avatar_path varchar(500) nullable`; `docs/ERD/erd.md` naik dari **V003 ke V004** (V003 dibekukan ke `docs/ERD/logs/V003-erd.md`, baris baru di `docs/ERD/release.md`).
- `docs/FunctionalRequirment/functional_requirment.md` mendapat header `## Versi V001` dan baris `FR-PRF-04`, dicatat sebagai versi awal di `docs/FunctionalRequirment/release.md` (aturan 3 `openspec/config.yaml`: tidak ada versi yang dipensiunkan sebelum V002).
- Dependensi baru: `intervention/image ^4.2` (dipakai Laravel `Illuminate\Image`; tanpa paket ini facade `Image` melempar `ImageException`). `ext-gd` dan `ext-exif` sudah tersedia di container PHP 8.5.
- `app/Models/User.php`, `app/Http/Requests/Settings/ProfileUpdateRequest.php`, `app/Http/Controllers/Settings/ProfileController.php`, satu Action baru, `config/uploads.php`.
- `routes/web.php`, `resources/js/pages/dashboard.tsx`, `resources/js/pages/settings/profile.tsx`, `resources/js/types/auth.ts`.
- Tidak menambah route unggah terpisah: `PATCH settings/profile` menerima berkas karena Inertia memaksa `multipart/form-data` saat mendeteksi `File`.
- Alur OTP perubahan nomor WhatsApp (FR-PRF-03) dan dashboard Pemilik Kost/Admin (FR-DSH-01/02) tetap di luar change ini.