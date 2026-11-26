# Design

## Context

Lihat `proposal.md` — Why. Kondisi saat ini:

- `routes/settings.php` memasang `RequirePassword` pada GET `settings/security`. Middleware ini membaca `auth.password_confirmed_at` (batas `config('auth.password_timeout')`, default 10800 detik) dan mengalihkan ke route Fortify `password.confirm` bila flag belum ada atau kedaluwarsa.
- Endpoint tulis `PUT settings/password` (`user-password.update`) hanya memakai `throttle:6,1`; tidak memakai `RequirePassword`.
- `PasswordUpdateRequest` mewajibkan `current_password` (`PasswordValidationRules::currentPasswordRules()`), jadi password lama sudah diverifikasi terhadap hash saat submit.
- Fitur Fortify yang aktif hanya registrasi, reset password, dan verifikasi email (`config/fortify.php`); 2FA tidak aktif, sehingga halaman security tidak memuat aksi lain yang butuh gate "recently authenticated".

## Goals / Non-Goals

**Goals:**

- Halaman Keamanan Akun menampilkan form ubah password langsung, tanpa layar konfirmasi terpisah.
- Verifikasi password lama tetap terjadi di server saat submit.

**Non-Goals:**

- Menghapus route atau fitur `password.confirm` milik Fortify (dipakai ulang bila nanti ada aksi sensitif lain).
- Mengubah aturan kekuatan password, throttle, atau alur reset password.
- Menambah pengaturan 2FA atau passkey.

## Decisions

**1. Buang `RequirePassword` dari route GET, bukan mengubah form.**

Alternatif A (dipilih): hapus middleware dari `security.edit`. Form tetap tiga field; `current_password` menjadi satu-satunya bukti identitas, dicek tepat saat password diganti.

Alternatif B: pertahankan gate lalu buang `current_password` dari form (gaya Jetstream). Ditolak karena keamanan justru turun: penggantian password hanya bergantung pada flag sesi berumur hingga 3 jam, bukan password yang diketik saat itu. Jumlah ketikan juga tidak berkurang berarti.

Alternatif C: status quo. Ditolak karena pengguna mengetik password lama dua kali dan gate tidak menutup endpoint tulis, jadi tidak ada pengamanan tambahan.

**2. Biarkan route `password.confirm` tetap terdaftar.**

Tidak ada yang dihapus dari Fortify. Bila kelak halaman security memuat aksi tanpa field password sendiri (2FA, passkey, cabut sesi), gate dikembalikan pada route aksi itu — bukan pada GET halaman — agar tidak mengulang pola yang sekarang.

**3. Test mengikuti perilaku baru.**

Test lama yang mengharapkan redirect ke `password.confirm` dan prop `canManageTwoFactor`/`twoFactorEnabled` sudah tidak sesuai: controller tidak mengirim prop 2FA dan fitur 2FA mati, sehingga test itu hanya lolos karena `skipUnlessFortifyHas`. Diganti dengan test yang memastikan halaman tampil tanpa `auth.password_confirmed_at`.

## Risks / Trade-offs

- [Seseorang menambahkan kembali `RequirePassword` karena menganggapnya standar starter kit] → Catat keputusan ini di design dan proposal; test "halaman tampil tanpa konfirmasi" akan gagal bila gate dikembalikan.
- [2FA diaktifkan kelak tanpa memasang gate] → 2FA hanya bisa diubah dari halaman security oleh pengguna yang sudah login; pasang `RequirePassword` pada route aksi 2FA saat fitur itu ditambahkan, dan test-nya menyusul.
- [Session hijack] → Tidak berubah. Penyerang dengan cookie sesi tetap butuh `current_password` untuk mengganti password, sama seperti sebelumnya.

## Migration Plan

Tidak ada migrasi data. Deploy hanya berisi perubahan route, controller, dan test; rollback = kembalikan middleware pada route GET.
