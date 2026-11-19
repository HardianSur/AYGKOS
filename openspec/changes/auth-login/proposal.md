# Proposal — auth-login

## Why

Halaman login saat ini masih scaffold bawaan: memakai `auth-simple-layout` yang sama dengan registrasi dan lupa password, bertulisan Inggris, tanpa token warna maupun huruf dari `docs/DESIGN.md`. Di sisi alur, FR-AUT-08 baru terpenuhi sebagian (pesan galat generik dari framework berbahasa Inggris) dan FR-AUT-09 belum dijalankan sama sekali — akun `is_active = false` dan akun belum terverifikasi masih bisa masuk. Login adalah pintu masuk semua peran, jadi alur ini perlu dibenahi sekaligus tampilannya diberi arah desain.

## What Changes

- Halaman `/login` ditulis ulang mengikuti referensi dua panel: panel kiri navy malam berisi merek dan nilai jual, panel kanan berisi formulir. Panel kiri diciutkan menjadi strip merek pada layar kecil.
- Layout login dipisahkan dari `auth-simple-layout` supaya registrasi, lupa password, reset password, verifikasi email, dan konfirmasi password tidak ikut berubah.
- Token warna dan huruf dari `docs/DESIGN.md` ditambahkan sebagai token brand baru (additive) tanpa menimpa `:root`/`.dark` shadcn; huruf Plus Jakarta Sans dimuat berdampingan dengan Instrument Sans.
- FR-AUT-09 dijalankan: akun `is_active = false` ditolak dengan pesan akun nonaktif, akun belum terverifikasi ditolak dengan arahan verifikasi. Bukti verifikasi mengikuti peran — Customer dan Admin lewat `email_verified_at`, Pemilik Kost lewat `whatsapp_verified_at`.
- FR-AUT-08 dipertegas: kegagalan autentikasi selalu memakai satu pesan generik yang tidak menyebut data mana yang salah.
- Pesan galat login dan validasi dasar berbahasa Indonesia lewat `lang/id/`; `APP_LOCALE=id` pada `.env.example` dan `phpunit.xml`.
- **Di luar cakupan**: pengarahan per peran (FR-AUT-10) masih memakai `home = /dashboard` untuk semua peran; lupa password (FR-AUT-11), verifikasi email/WhatsApp (FR-AUT-05, FR-AUT-06), kirim ulang verifikasi (FR-AUT-07), dan registrasi (FR-AUT-01 s.d. FR-AUT-04) tidak disentuh. Social login tidak ditambahkan karena tidak ada di FR.

## Capabilities

### New Capabilities

- `auth/login`: perilaku halaman masuk — formulir email dan kata sandi, penolakan kredensial salah dengan pesan generik, penolakan akun nonaktif, penolakan akun belum terverifikasi sesuai peran, serta pengarahan setelah login berhasil (untuk saat ini satu tujuan `/dashboard` untuk semua peran).

### Modified Capabilities

- Tidak ada. `domain/identity-accounts` hanya mengatur penyimpanan dan bukti verifikasi akun, bukan perilaku login, sehingga tidak ada requirement di sana yang berubah.

## Impact

- **Kode**: `resources/js/pages/auth/login.tsx`, `resources/js/layouts/auth-layout.tsx`, `resources/js/app.tsx` (resolusi layout per halaman), `resources/css/app.css`, `vite.config.ts`, `app/Models/User.php`, `app/Providers/FortifyServiceProvider.php`, `lang/id/auth.php`, `lang/id/validation.php`, `.env.example`, `phpunit.xml`, `tests/Feature/Auth/AuthenticationTest.php`.
- **API**: tidak ada route baru. `POST /login` tetap ditangani Fortify; yang berubah hanya pipeline autentikasinya (kredensial, status aktif, status verifikasi).
- **Dependensi**: tidak ada paket baru. Plus Jakarta Sans dimuat lewat `bunny()` dari `laravel-vite-plugin/fonts` yang sudah dipakai.
- **Dokumen**: `docs/FunctionalRequirment/functional_requirment.md` tidak diubah, sehingga tidak ada versi FR baru. FR yang disentuh: FR-AUT-08 dan FR-AUT-09 terpenuhi; FR-AUT-10 tetap tertunda dengan alasan route tujuan per peran belum ada (dicatat di `docs/ARCHITECTURE.md` §7.4 dan §12 sebagai pekerjaan M2).
- **Dampak lintas halaman**: `APP_LOCALE=id` berlaku global, sehingga halaman lain yang belum diterjemahkan akan menampilkan campuran Indonesia dan Inggris melalui fallback `en`. Tidak ada perubahan struktur atau perilaku di halaman tersebut.