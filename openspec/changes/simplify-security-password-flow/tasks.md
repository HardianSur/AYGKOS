# Tasks

## 1. Lepas gate konfirmasi password

- [ ] 1.1 Hapus `->middleware(RequirePassword::class)` dari route GET `settings/security` dan hapus `use Illuminate\Auth\Middleware\RequirePassword;` di `routes/settings.php`; verifikasi dengan `php artisan route:list --name=security.edit -v` yang tidak lagi menampilkan middleware tersebut.
- [ ] 1.2 Bersihkan `SecurityController::edit` agar tidak lagi menerima `TwoFactorAuthenticationRequest` dan buang import `Illuminate\Validation\Rules\Password` yang tidak terpakai; verifikasi `composer lint:check` dan `./vendor/bin/phpstan analyse` lolos.

## 2. Sesuaikan test halaman security

- [ ] 2.1 Tulis test "security page is displayed without password confirmation": user login GET `route('security.edit')` tanpa `auth.password_confirmed_at` menghasilkan `assertOk` dan komponen `settings/security`; verifikasi `php artisan test --filter=SecurityTest`.
- [ ] 2.2 Hapus test "security page requires password confirmation when enabled" dan prop `canManageTwoFactor`/`twoFactorEnabled` yang tidak lagi dikirim controller; verifikasi `php artisan test --filter=SecurityTest` hijau tanpa test yang di-skip karena 2FA.
- [ ] 2.3 Pastikan test "correct password must be provided to update password" tetap ada dan lolos, membuktikan `current_password` masih ditegakkan setelah gate dilepas.

## 3. Integrasi dan gate proyek

- [ ] 3.1 Jalankan `composer test` (pint, phpstan level 7, pest) dan perbaiki kegagalan sampai hijau.
- [ ] 3.2 Jalankan `npm run check`, `npm run types:check`, dan `npm run build` sampai hijau.
- [ ] 3.3 Cek manual: buka `/settings/security` di browser sebagai user login, pastikan form tiga field langsung tampil tanpa layar konfirmasi, dan ganti password dengan password lama salah ditolak pada kolom `current_password`.

## Workflow follow-up

- Arsipkan change setelah review selesai.
