# Design

## Context

Lihat `proposal.md` untuk motivasi. Keadaan kode yang membentuk rancangan ini:

- `bootstrap/app.php` sama sekali tidak mendaftarkan alias middleware. `web` grup hanya mendapat `HandleAppearance`, `HandleInertiaRequests`, `AddLinkHeadersForPreloadedAssets`.
- `routes/web.php` memuat `/`, `/dashboard` (middleware `auth`, `verified`), rute verifikasi WhatsApp, lalu `require routes/settings.php`.
- `User` punya `role()` (`belongsTo`) dan `isPemilikKost()`; tidak ada pembaca peran umum. `users.role_id` wajib terisi, jadi pengguna tanpa peran hanya mungkin bila baris peran dihapus secara paksa di luar aplikasi.
- `User::hasVerifiedEmail()` di-override memanggil `hasVerifiedIdentity()`, jadi middleware `verified` bawaan sudah menegakkan bukti verifikasi per peran tanpa middleware tambahan.
- `HandleInertiaRequests::share()` mengirim `auth.user` sebagai model `User` mentah; `role` tidak di-eager-load sehingga properti relasi tidak ikut terserialisasi.
- `resources/js/app.tsx` meresolusi layout dari nama halaman; `admin/*` belum punya cabang dan jatuh ke `AppLayout`. `docs/ARCHITECTURE.md` §4.2 menyebut `AdminLayout` sebagai target, tetapi target itu tidak menjadi syarat change ini.
- `resources/js/components/app-sidebar.tsx` memakai daftar menu statis `mainNavItems`.

## Goals / Non-Goals

**Goals:**

- Satu middleware peran yang menolak dengan 403, bukan mengalihkan, supaya perbedaan antara "belum masuk" dan "peran salah" tetap terlihat di kode dan di tes.
- Grup `/admin` yang nyata dengan satu halaman nyata, sehingga gerbang dapat diuji end-to-end dan change berikutnya hanya menambah route.
- Nama peran tersedia di antarmuka sebagai teks, sesuai konvensi `docs/ARCHITECTURE.md` §7.4 dan `Global.d.ts` yang sudah memberi tempat pada prop bersama.

**Non-Goals:**

- Dashboard Admin tersendiri dengan statistik FR-ADM-DSB.
- `AdminLayout` terpisah dan cabang layout `admin/*` di `app.tsx`.
- Grup `/owner` dan `role:pemilik_kost`, `EnsureAccountVerified`.
- Policy per model, redirect per peran setelah masuk (`LoginResponse`).

## Decisions

### Middleware peran berdiri sendiri, bukan memakai Gate atau Policy

`EnsureRole::handle(Request $request, Closure $next, string ...$roles)` memeriksa `$request->user()`, membiarkan permintaan tanpa pengguna diteruskan ke middleware `auth` (yang mengalihkannya ke halaman masuk), lalu membandingkan `$user->role?->name` dengan daftar peran yang diminta dan `abort(403)` bila tidak cocok.

Alternatif: `Gate::define('admin', ...)` + `can:admin` — ditolak karena Gate lebih tepat untuk keputusan atas sumber daya, dan `docs/ARCHITECTURE.md` §7.3 menyebut middleware sebagai mekanisme peran. Alternatif: `EnsureRole` yang mengalihkan ke dashboard seperti pola beberapa starter kit — ditolak karena menyembunyikan kesalahan konfigurasi route; 403 adalah jawaban yang diminta FR-AUT-13 ("menolak akses").

Parameter dibuat variadik (`role:admin,pemilik_kost`) supaya satu alias dapat melayani beberapa peran tanpa middleware kedua ketika `/owner` dibangun.

### Alias `role` didaftarkan di `bootstrap/app.php`

`$middleware->alias(['role' => EnsureRole::class])` di dalam `withMiddleware()`. Alternatif: memakai `EnsureRole::class.':admin'` langsung di grup route — ditolak karena nama pendek lebih terbaca dan sama dengan yang disebut dokumen (`role:admin`).

### Grup `/admin` memakai tiga middleware berlapis

`Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(...)`. Urutan penting: `auth` mengalihkan tamu ke halaman masuk, `verified` mengalihkan akun yang bukti verifikasinya kurang ke halaman verifikasi, dan `role` menolak peran lain. Bila `role` diletakkan sebelum `verified`, akun Pemilik Kost yang belum memverifikasi WhatsApp akan menerima 403, bukan arahan verifikasi.

Nama route memakai prefiks `admin.` (`admin.dashboard`) sehingga Wayfinder menghasilkan helper `@/routes/admin`. Alternatif: tanpa prefiks nama — ditolak karena `dashboard` sudah dipakai rute `/dashboard` dan dua route bernama sama tidak dapat didaftarkan.

### Halaman Admin memakai dashboard aplikasi yang ada

`Admin\DashboardController@__invoke` hanya mengembalikan `Inertia::render('dashboard')`. Tidak ada komponen `admin/dashboard.tsx`, tidak ada cabang layout `admin/*` di `app.tsx`, tidak ada `AdminLayout`. Konsekuensi: Admin dan Pemilik Kost melihat halaman yang identik, dan `admin/` tetap diresolusi ke `AppLayout`; itu sesuai instruksi yang dipilih.

Alternatif: `AdminLayout` + halaman `admin/dashboard.tsx` yang membungkus `dashboard.tsx` — ditolak untuk sekarang karena menambah dua berkas dan satu cabang layout yang harus dihapus saat dashboard Admin sesungguhnya dibangun, tanpa perubahan perilaku yang terlihat.

### Nama peran diekspos lewat `auth.user.role` bertipe teks

`EnsureRole` membaca `$user->role?->name`. Agar antarmuka dapat melakukan hal yang sama tanpa endpoint baru, `HandleInertiaRequests::share()` menambahkan `role` pada array `auth.user` yang sudah dikirim. Implementasi: `$request->user()?->role?->name`.

Alternatif: mengirim `$user->toArray()` dengan `load('role')` — ditolak karena membocorkan seluruh kolom relasi peran dan membuat bentuk prop bergantung pada model. Alternatif: prop bersama baru `auth.role` di luar `auth.user` — ditolak karena memecah data satu pengguna ke dua tempat; tipe `User` di antarmuka sudah longgar (`[key: string]: unknown`) sehingga penambahan `role: string | null` aman.

### Menu sidebar disaring dari daftar statis, bukan dari server

`app-sidebar.tsx` membaca `usePage().props.auth.user.role` dan menyusun `mainNavItems` dengan entri Admin (`/admin/dashboard`) hanya bila perannya `admin`. Tidak ada nav baru yang dikirim dari server.

Alternatif: mengirim daftar menu dari `HandleInertiaRequests` — ditolak karena navigasi adalah urusan tampilan, dan server tidak boleh menjadi sumber kebenaran tampilan sidebar. Penyaringan di klien bukan batas keamanan; gerbang sebenarnya tetap middleware.

## Risks / Trade-offs

- **Menu peran di klien bisa melenceng dari middleware** → keduanya membaca nilai yang sama (`roles.name`) dan tes memeriksa keduanya; menambah peran baru tetap memerlukan suntingan di `EnsureRole` pemanggil route dan menu.
- **`auth.user` berisi model mentah sehingga `role` bergantung pada eager load** → pembacaan dilakukan eksplisit (`$user->role?->name`) alih-alih `toArray()`, jadi satu query per permintaan. Dapat dihilangkan nanti dengan `$request->user()->loadMissing('role')` bila ternyata terukur; belum dilakukan karena satu query kecil pada setiap permintaan Inertia tidak terbukti menjadi masalah.
- **`/admin` ada tetapi isinya hanya dashboard** → diterima; tujuannya membuka gerbang, bukan mengisi area Admin. Change berikutnya menambah route di grup yang sama.
- **Pengguna tanpa peran** → ditolak 403 di route berperan dan mendapat `role: null` di antarmuka; tidak ada data yang rusak, karena `role_id` wajib di level basis data.
- **`docs/ARCHITECTURE.md` §12 dan `docs/ROADMAP.md` M1.2 menyatakan status yang tidak sesuai kode** → tidak diubah oleh change ini; selisih itu dicatat di `proposal.md` agar tidak dianggap bukti bahwa pekerjaan ini sudah ada.

## Migration Plan

1. Deploy middleware, alias, grup route, controller. Tidak ada perubahan skema dan tidak ada backfill.
2. Tidak ada data yang perlu disiapkan; `RoleSeeder` sudah menyediakan `admin`.
3. Rollback cukup mengembalikan kode. Bila `role` dihapus tetapi grup route tertinggal, aplikasi gagal saat boot dengan galat middleware tak dikenal — itulah alasan menghapus keduanya bersamaan.

## Open Questions

Tidak ada.
