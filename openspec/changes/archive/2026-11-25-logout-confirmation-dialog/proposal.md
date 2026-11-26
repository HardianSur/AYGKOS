# Proposal

## Why

Tombol "Log out" di menu pengguna langsung mengakhiri sesi tanpa konfirmasi (FR-AUT-12), sehingga satu klik yang tidak sengaja mengusir pengguna dari halaman yang sedang dibuka. Di sisi lain, komponen `Dialog` yang sudah ada belum memakai token warna `docs/DESIGN.md`, jadi modal baru berisiko tampil sebagai elemen asing di aplikasi.

## What Changes

- Klik item "Log out" pada menu pengguna membuka dialog konfirmasi, bukan langsung mengirim permintaan logout.
- Dialog memakai kosakata bahasa Indonesia ("Keluar dari akun?", "Batal", "Keluar") dan gaya visual sesuai `docs/DESIGN.md`: latar `surface`, tepi `hairline`, radius `xl`, bayangan satu tingkat, overlay `night`, dan merah status Ditolak/Ditangguhkan untuk tombol konfirmasi.
- Membatalkan menutup dialog tanpa mengakhiri sesi; tombol Escape dan klik overlay berperilaku sama seperti "Batal".
- Mengonfirmasi mengirim permintaan POST logout lewat route Wayfinder, mengakhiri sesi, lalu mengarahkan ke halaman masuk seperti perilaku yang sudah ada.
- Tidak mengubah endpoint, controller, middleware, redirect, atau pengujian backend logout.
- Tidak menambah konfirmasi pada tautan "Keluar" di halaman verifikasi email; halaman itu sedang ditangani oleh change `redesign-verify-email-layout`.

## Capabilities

### New Capabilities

- `auth/logout`: mengatur konfirmasi sebelum sesi pengguna diakhiri dan hasil dari tindakan konfirmasi maupun pembatalan.

### Modified Capabilities

_(tidak ada)_

## Impact

- `resources/js/components/user-menu-content.tsx` — item menu memicu permintaan buka dialog lewat prop baru.
- `resources/js/components/nav-user.tsx`, `resources/js/components/app-header.tsx` — pemilik state dialog; keduanya wajib diubah karena prop baru bersifat wajib.
- `resources/js/components/logout-dialog.tsx` (baru) — dialog konfirmasi.
- `resources/js/components/ui/dialog.tsx` — prop opsional `showCloseButton` agar tombol tutup bisa disembunyikan; default tetap `true`.
- `resources/css/app.css` — token warna status merah `--brand-danger` dan `--brand-danger-ink` sesuai §3 `docs/DESIGN.md`.
- Tidak menyentuh `docs/ERD/erd.md` maupun `docs/FunctionalRequirment/functional_requirment.md`, sehingga tidak ada versi dokumen baru dan tidak ada tugas pensiunan versi di `logs/`.
- Tidak ada dependensi, route, migrasi, atau perubahan API baru.
- Belum ada runner tes frontend di repo; dialog diverifikasi manual di browser dan melalui gate `npm run types:check`, `npm run check`, `npm run build`, serta `composer test`.
