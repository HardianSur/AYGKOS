# Proposal

## Why

Halaman Keamanan Akun (`/settings/security`) memaksa pengguna melewati layar konfirmasi password Fortify (`/user/confirm-password`) sebelum form ubah password tampil, padahal form itu sendiri sudah mewajibkan `current_password`. Pengguna mengetik password lama dua kali dan melewati dua round-trip untuk proteksi yang sama. Middleware `RequirePassword` juga hanya terpasang pada GET halaman, bukan pada endpoint tulis `user-password.update`, sehingga gate itu tidak menambah pengamanan nyata.

## What Changes

- Hapus middleware `RequirePassword` dari route GET `settings/security` sehingga tab Security langsung menampilkan form ubah password (current, new, confirm).
- Pertahankan validasi `current_password` di `PasswordUpdateRequest` dan throttle `6,1` pada route `user-password.update`.
- Sesuaikan `tests/Feature/Settings/SecurityTest.php`: buang asumsi gate konfirmasi dan prop 2FA yang tidak lagi dikirim controller, tambahkan test bahwa halaman tampil tanpa `auth.password_confirmed_at`.
- Bersihkan `SecurityController::edit` dari parameter request dan import yang tidak terpakai.

Tidak ada perubahan perilaku yang dijanjikan dokumen: FR-PRF-02 tetap "mengubah password dengan memasukkan password lama dan password baru". Karena itu change ini ditandai `skip_specs: true`; capability pengaturan profil belum punya spec sendiri dan akan dibuat saat kelompok FR-PRF dikerjakan.

## Capabilities

### New Capabilities

Tidak ada. Gate konfirmasi password bukan requirement yang pernah dispesifikasikan, dan FR-PRF-02 tidak berubah.

### Modified Capabilities

Tidak ada.

## Impact

- `routes/settings.php` — hapus `RequirePassword` dan importnya dari route `security.edit`.
- `app/Http/Controllers/Settings/SecurityController.php` — buang `TwoFactorAuthenticationRequest` dan import `Password` yang tidak dipakai.
- `tests/Feature/Settings/SecurityTest.php` — sesuaikan test halaman security.
- Route `password.confirm` milik Fortify tetap terdaftar dan tidak dihapus; halaman ini saja yang berhenti memakainya.
- Tidak menyentuh `docs/ERD/erd.md` maupun `docs/FunctionalRequirment/functional_requirment.md`, jadi tidak ada versi dokumen baru. Baris "Keamanan Akun" di `docs/ARCHITECTURE.md` §5.2 tetap akurat.
