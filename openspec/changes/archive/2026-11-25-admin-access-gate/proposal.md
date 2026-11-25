# Proposal

## Why

FR-AUT-13 mewajibkan akses halaman dan aksi dibatasi peran, dan `docs/ARCHITECTURE.md` §7.3 menetapkan middleware `EnsureRole` pada grup route `/admin/*` serta `/owner/*`. Di kode keduanya belum ada: `bootstrap/app.php` tidak mendaftarkan alias middleware apa pun dan `routes/web.php` hanya memuat rute autentikasi serta `/dashboard`. Akibatnya perubahan `admin-manage-facilities` tidak punya gerbang untuk mendaftarkan route-nya, dan siapa pun yang sudah masuk dapat membuka URL Admin bila route-nya kelak ditambahkan. Admin pun belum punya titik masuk sama sekali: tidak ada halaman `/admin/dashboard` maupun kerangka navigasi Admin.

## What Changes

- Middleware `App\Http\Middleware\EnsureRole` menerima nama peran sebagai parameter (`role:admin`, `role:pemilik_kost`), membaca peran pengguna yang sedang masuk, dan menolak permintaan dengan 403 bila peran tidak cocok atau pengguna tidak punya peran.
- Alias middleware `role` didaftarkan di `bootstrap/app.php` sehingga dapat dipakai per grup route.
- Grup route `/admin` dengan middleware `auth`, `verified`, dan `role:admin`, berisi `GET /admin/dashboard` yang menyajikan halaman dashboard yang sudah ada.
- Halaman Admin memakai dashboard aplikasi yang sama dengan Pemilik Kost: tidak ada `AdminLayout` baru dan tidak ada halaman dashboard Admin terpisah, sehingga satu komponen dashboard melayani kedua peran sampai dashboard per peran dibangun.
- Prop bersama Inertia memuat `auth.user.role` berupa nama peran (string), agar sidebar dapat menyusun menu sesuai peran tanpa query tambahan di sisi klien.
- Sidebar menampilkan menu Admin hanya untuk peran Admin; menu lain tetap seperti sekarang.
- Perilaku akses `/dashboard` tidak berubah: gerbang ini hanya menambah grup `/admin`, bukan memindahkan dashboard peran.

## Capabilities

### New Capabilities

- `auth/access-control`: batas akses per peran pada level route, termasuk penolakan peran yang tidak berwenang dan penolakan pengguna yang tidak punya peran.

### Modified Capabilities

- `dashboard/role-home`: halaman `/admin/dashboard` untuk Admin tersedia dan menyajikan dashboard aplikasi yang sama dengan Pemilik Kost, tanpa halaman dashboard Admin terpisah.

## Impact

- **Kode baru**: `app/Http/Middleware/EnsureRole.php`, `app/Http/Controllers/Admin/DashboardController.php`, rute grup `/admin` di `routes/web.php`, alias `role` di `bootstrap/app.php`.
- **Kode yang diubah**: `app/Http/Middleware/HandleInertiaRequests.php` (menambah `auth.user.role`), `resources/js/types/auth.ts` (menambah `role` pada tipe `User`), `resources/js/components/app-sidebar.tsx` (menu bersyarat peran).
- **Prasyarat**: change `admin-manage-facilities` bergantung pada change ini; artefaknya sudah ada dan sengaja menunggu gerbang ini.
- **Dokumen**: tidak ada perubahan pada `docs/ERD/erd.md` (tetap V005) maupun `docs/FunctionalRequirment/functional_requirment.md` (tetap V002). FR-AUT-13 sudah mencakup perilaku ini; yang kurang hanya implementasinya.
- **Di luar cakupan**: dashboard Admin tersendiri (isi statistik FR-ADM-DSB), grup route `/owner` dan `role:pemilik_kost` (dibangun bersama area Pemilik Kost di M2), `EnsureAccountVerified` (middleware `verified` sudah memakai `User::hasVerifiedEmail()` yang menghormati verifikasi per peran), serta Policy per model.

## Catatan Status Dokumen

`docs/ROADMAP.md` M1.2 dan `docs/ARCHITECTURE.md` §12 sudah menandai sebagian pekerjaan ini selesai atau belum, tetapi isinya tidak konsisten dengan kode. Perubahan status di dokumen tidak termasuk change ini; `docs/ARCHITECTURE.md` §12 baris "Middleware `EnsureRole`, `EnsureAccountVerified`: Belum ada" akan tetap benar untuk `EnsureAccountVerified`.
