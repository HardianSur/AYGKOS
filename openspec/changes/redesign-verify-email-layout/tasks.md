# Tasks

## 1. Kirim alamat email ke halaman verifikasi

- [ ] 1.1 Tambahkan prop `email` (`$request->user()->email`) pada `Inertia::render('auth/verify-email', ...)` di `FortifyServiceProvider::verifyEmailView` dan verifikasi `php artisan test --filter=EmailVerificationTest` tetap hijau

## 2. Susun ulang halaman verifikasi email

- [ ] 2.1 Tulis ulang `resources/js/pages/auth/verify-email.tsx` memakai `AuthLoginLayout` (judul "Verifikasi email", deskripsi singkat) dengan pola isi verifikasi WhatsApp: kotak status `role="status"`, kotak keterangan `bg-canvas border-hairline` yang menyebut alamat email, tombol utama ember solid untuk kirim ulang, lalu pemisah dan tombol keluar bergaya sekunder; verifikasi dengan `npm run types:check`
- [ ] 2.2 Pastikan lint dan format lolos untuk berkas yang diubah dengan `npm run check`, lalu verifikasi tampilan di browser: buka `/verify-email` sebagai akun Customer belum terverifikasi dan pastikan kartu kanan, alamat email, pesan status kirim ulang, dan tombol keluar tampil sesuai
- [ ] 2.3 Jalankan `npm run build` dan pastikan build sukses

## 3. Verifikasi akhir

- [ ] 3.1 Jalankan `composer test` (pint, phpstan level 7, pest) dan pastikan tanpa kegagalan
- [ ] 3.2 Tinjau diff: hanya `verify-email.tsx` dan `FortifyServiceProvider.php` yang berubah, tanpa import mati atau perubahan di luar task

## Workflow follow-up

- Arsipkan change setelah review, lalu pastikan requirement baru masuk ke `openspec/specs/auth/verification/spec.md`.