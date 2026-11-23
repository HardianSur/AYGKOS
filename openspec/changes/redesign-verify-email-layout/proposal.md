# Proposal

## Why

Halaman verifikasi email memakai `AuthLayout` polos (logo tengah, `max-w-sm`, teks rata tengah), sementara halaman login, registrasi, dan verifikasi WhatsApp memakai `AuthLoginLayout` (panel gelap + kartu kanan, token warna brand). Pengguna yang baru mendaftar berpindah dari halaman registrasi berkarakter ke halaman verifikasi yang terlihat seperti template bawaan, sehingga alur onboarding terasa terputus.

## What Changes

- Halaman `auth/verify-email` memakai `AuthLoginLayout` berjudul "Verifikasi email", sama seperti halaman verifikasi WhatsApp.
- Isi halaman disusun ulang mengikuti pola halaman verifikasi WhatsApp: kotak status, kotak keterangan tujuan pengiriman, tombol aksi utama, dan aksi sekunder.
- Kotak keterangan menampilkan alamat email tujuan verifikasi, diambil dari akun yang sedang login.
- Tombol "Kirim ulang email verifikasi" memakai gaya tombol utama (ember solid) dan "Keluar" menjadi aksi sekunder di bawah pemisah.
- Tidak ada perubahan pada mekanisme verifikasi: tetap tautan bertanda tangan dari Fortify, bukan kode OTP. Backend, route, dan pembatasan frekuensi kirim ulang tidak berubah.
- Tidak ada countdown kirim ulang; hasil kirim ulang disampaikan lewat pesan status yang sudah ada.

## Capabilities

### New Capabilities

_(tidak ada)_

### Modified Capabilities

- `auth/verification`: menambahkan ketentuan tampilan halaman pemberitahuan verifikasi email — tata letak kartu autentikasi yang konsisten dengan verifikasi WhatsApp, dan penyebutan alamat email tujuan.

## Impact

- `resources/js/pages/auth/verify-email.tsx` — susun ulang komponen dan layout.
- `app/Providers/FortifyServiceProvider.php` — kirim prop `email` akun ke view `auth/verify-email`.
- Tidak menyentuh `docs/ERD/erd.md` maupun `docs/FunctionalRequirment/functional_requirment.md`, sehingga tidak ada versi dokumen baru.
- Tidak ada perubahan dependensi, route, migrasi, atau tes backend yang sudah ada.