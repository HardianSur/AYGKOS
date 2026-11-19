# Proposal — auth-registration

## Why

FR-AUT-01 s.d. FR-AUT-07 belum terpenuhi. `CreateNewUser` mengunci `role_id` ke `customer` sehingga akun Pemilik Kost tidak dapat dibuat lewat aplikasi, formulir `/register` tidak punya pilihan peran maupun isian nomor WhatsApp, dan halaman `verify-whatsapp` belum ada. Di sisi transport, `OtpSender` hanya mengirim kode: pembuatan kode, penyimpanan hash, pemeriksaan kedaluwarsa/percobaan, dan penetapan `whatsapp_verified_at` belum ditulis sama sekali. Tanpa perubahan ini, satu dari dua peran aplikasi tidak dapat mendaftar, dan tiga dari empat belas FR autentikasi tetap kosong.

## What Changes

- Formulir `/register` memakai layout merek yang sama dengan halaman masuk dan menempatkan pilihan peran sebagai isian paling atas: `pencari kos` (Customer) atau `pemilik kos` (Pemilik Kost). Admin tidak tersedia; permintaan dengan `role=admin` ditolak validasi.
- Registrasi Pemilik Kost menambah isian nomor WhatsApp wajib (unik, format nomor Indonesia) yang hanya tampil ketika peran Pemilik Kost dipilih; `CreateNewUser` menyimpan `role_id` dan `whatsapp_number` sesuai peran.
- Registrasi Customer mengirim tautan verifikasi email (sudah otomatis lewat listener `Registered`) dan mengarahkan ke halaman pemberitahuan verifikasi email.
- Registrasi Pemilik Kost menerbitkan kode OTP 6 digit, menyimpan hash beserta masa berlaku 5 menit ke `whatsapp_verifications`, mengirimnya lewat `OtpSender`, lalu mengarahkan ke halaman `/verify-whatsapp` baru.
- Halaman `/verify-whatsapp` menerima kode, menolak kode salah/kedaluwarsa/terpakai/melebihi batas percobaan, dan menetapkan `whatsapp_verified_at` saat kode benar.
- Kirim ulang tersedia di kedua halaman verifikasi dengan jeda 60 detik dan batas 3 pengiriman per jam per akun; sisa waktu tunggu ditampilkan.
- `User::hasVerifiedEmail()` diarahkan ke aturan bukti verifikasi per peran (`hasVerifiedIdentity()`), sehingga middleware `verified` dan penolakan login tidak lagi memaksa Pemilik Kost memverifikasi email.
- Pesan formulir registrasi, halaman verifikasi, dan galat OTP berbahasa Indonesia lewat `lang/id/`.
- **Di luar cakupan**: pengarahan per peran setelah login (FR-AUT-10), middleware `EnsureRole`/`EnsureAccountVerified` dan grup route per peran (FR-AUT-13), lupa password (FR-AUT-11), serta pengubahan nomor WhatsApp dari halaman profil (FR-PRF-03).

## Capabilities

### New Capabilities

- `auth/registration`: pendaftaran mandiri untuk dua peran — pilihan peran, isian yang berbeda per peran, validasi kolom (format email, keunikan email, kekuatan dan kesesuaian konfirmasi password, format dan keunikan nomor WhatsApp), penolakan peran Admin, dan pengarahan setelah pendaftaran berhasil.
- `auth/verification`: penyelesaian verifikasi akun dua jalur — tautan email untuk Customer, OTP WhatsApp untuk Pemilik Kost, aturan masa berlaku/percobaan/konsumsi kode, kirim ulang dengan pembatasan frekuensi, serta penetapan status terverifikasi yang dipakai middleware.

### Modified Capabilities

- Tidak ada. `domain/identity-accounts` sudah mengatur bentuk penyimpanan OTP dan aturan status terverifikasi; `platform/outbound-messaging` sudah mengatur pengiriman dan pembatasan frekuensi. Change ini memenuhi keduanya tanpa mengubah teks requirement-nya.

## Impact

- **Kode**: `app/Actions/Fortify/CreateNewUser.php`, `app/Services/Otp/` (layanan penerbitan dan pemeriksaan kode baru), `app/Models/User.php` (`hasVerifiedEmail()`), `app/Providers/FortifyServiceProvider.php` (view, `registerResponse`), `app/Http/Requests/` (permintaan verifikasi WhatsApp), `app/Http/Controllers/Auth/` (controller verifikasi), `routes/web.php`, `resources/js/pages/auth/register.tsx`, `resources/js/pages/auth/verify-email.tsx`, `resources/js/pages/auth/verify-whatsapp.tsx` (baru), `resources/js/layouts/auth/auth-login-layout.tsx` (dipakai ulang), `lang/id/`, `tests/Feature/Auth/*`, `tests/Feature/Platform/*`.
- **API**: route baru `GET /verify-whatsapp` (`verification.whatsapp.notice`), `POST /verify-whatsapp` (`verification.whatsapp.store`), `POST /verify-whatsapp/resend` (`verification.whatsapp.send`). `POST /register` tetap ditangani Fortify dengan input tambahan `role` dan `whatsapp_number`.
- **Dependensi**: tidak ada paket baru. Pengiriman OTP memakai `OtpSender` yang sudah ada; tidak ada dependensi frontend baru (pilihan peran memakai `input[type=radio]` dan token brand dari change `auth-login`).
- **Dokumen**: `docs/ERD/erd.md` (V003) dan `docs/FunctionalRequirment/functional_requirment.md` tidak diubah, sehingga tidak ada versi dokumen baru. FR yang dipenuhi: FR-AUT-01, FR-AUT-02, FR-AUT-03, FR-AUT-04, FR-AUT-05, FR-AUT-06, FR-AUT-07. FR-AUT-10 dan FR-AUT-13 tetap tertunda.
- **Bergantung pada change `auth-login`**: token brand (`--color-night`, `--color-ember`, `--font-brand`), `lang/id/auth.php`, `lang/id/validation.php`, dan `APP_LOCALE=id` berasal dari change tersebut. Bila `auth-login` belum diterapkan, change ini harus menambahkannya sendiri.