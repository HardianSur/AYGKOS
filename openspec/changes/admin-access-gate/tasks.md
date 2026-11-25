# Tasks

## 1. Middleware peran

- [ ] 1.1 Buat `app/Http/Middleware/EnsureRole.php`: `handle(Request $request, Closure $next, string ...$roles)`, teruskan bila `$request->user()` kosong, `abort(403)` bila `$request->user()->role?->name` tidak ada di `$roles`. Verifikasi: `vendor/bin/phpstan analyse app/Http/Middleware` lulus.
- [ ] 1.2 Daftarkan alias `role` di `bootstrap/app.php` dalam `withMiddleware()`: `$middleware->alias(['role' => EnsureRole::class])`. Verifikasi: `php artisan route:list` tidak mengeluh soal middleware tak dikenal.

## 2. Grup route Admin dan halaman

- [ ] 2.1 Buat `app/Http/Controllers/Admin/DashboardController.php` dengan `__invoke()` yang mengembalikan `Inertia::render('dashboard')`. Verifikasi: `vendor/bin/phpstan analyse app/Http/Controllers/Admin` lulus.
- [ ] 2.2 Daftarkan grup route di `routes/web.php`: `Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(...)` berisi `GET dashboard` bernama `admin.dashboard`. Verifikasi: `php artisan route:list --path=admin` menampilkan satu route dan `php artisan wayfinder:generate` menghasilkan `resources/js/routes/admin`.
- [ ] 2.3 Tambah `role` ke data pengguna bersama di `HandleInertiaRequests::share()`: `'role' => $request->user()?->role?->name`. Verifikasi: tes pada 4.1 lulus.

## 3. Antarmuka

- [ ] 3.1 Tambah `role: string | null` pada tipe `User` di `resources/js/types/auth.ts`. Verifikasi: `npm run types:check` lulus.
- [ ] 3.2 Saring menu sidebar di `resources/js/components/app-sidebar.tsx`: entri `Admin` menuju `admin.dashboard()` dari `@/routes/admin` hanya tampil bila `usePage().props.auth.user?.role === 'admin'`. Verifikasi: `npm run check` dan `npm run types:check` lulus.

## 4. Tes

- [ ] 4.1 Tulis `tests/Feature/Admin/AccessGateTest.php`: Admin terverifikasi mendapat 200 pada `/admin/dashboard` dengan komponen `dashboard`; Customer dan Pemilik Kost terverifikasi mendapat 403; tamu dialihkan ke `route('login')`; Admin tanpa `email_verified_at` dialihkan ke `route('verification.notice')`; pengguna dengan `role_id` yang menunjuk peran lain mendapat 403. Verifikasi: `php artisan test --filter=AccessGateTest` lulus.
- [ ] 4.2 Tambah tes prop bersama di `tests/Feature/Admin/AccessGateTest.php` (atau `tests/Feature/DashboardTest.php`): `auth.user.role` bernilai `admin` untuk Admin dan `pemilik_kost` untuk Pemilik Kost pada `/dashboard`. Verifikasi: tes tersebut lulus.

## 5. Verifikasi akhir

- [ ] 5.1 Jalankan gate penuh: `composer test` (pint, phpstan level 7, pest) dan `npm run ci:check`. Verifikasi: seluruh gate hijau; kegagalan sisa dilaporkan beserta alasannya.
- [ ] 5.2 Tinjau diff akhir: tidak ada perubahan di luar `proposal.md`, tidak ada `AdminLayout` atau `admin/dashboard.tsx` yang setengah jadi, dan `git status` hanya memuat berkas pada bagian Impact.

## Workflow follow-up

- Setelah change ini diarsipkan, lanjutkan `admin-manage-facilities`: task 1.1 di sana memverifikasi prasyarat ini, lalu route fasilitas ditambahkan ke grup `admin` yang sama.
- Jalankan `openspec validate admin-access-gate --strict` sebelum arsip.
