# Design

## Context

Lihat `proposal.md` untuk motivasi. Kondisi yang membentuk rancangan:

- Tabel `notifications` (`id` UUID, `user_id`, `type` 50, `message` text, `reference_type`/`reference_id` polimorfik tanpa FK, `read_at`, `created_at` saja tanpa `updated_at`) dan model `App\Models\Notification` dengan indeks `(user_id, read_at)` sudah ada. Model menetapkan `public const UPDATED_AT = null`.
- Belum ada penulis baris notifikasi yang aktif: `ApproveKost`/`RejectKost`/`SuspendKost` masih in-progress di change `admin-kost-verification-actions`, jadi halaman dan lonceng harus tetap benar saat tabel kosong.
- `HandleInertiaRequests::share` hanya membagikan `name`, `auth.user` (beserta `role`), dan `sidebarOpen`. Tidak ada data notifikasi yang dibagikan global.
- Layout default untuk Owner dan Admin adalah `app-sidebar-layout` → `AppSidebarHeader` (header tipis: `SidebarTrigger` + `Breadcrumbs`, tanpa slot kanan terpakai). `AppHeader` (yang punya `name` user) hanya dipakai `app-header-layout`, yang saat ini tidak dirujuk layout mana pun, tetapi tetap dipasangi lonceng agar tidak ada varian header yang tertinggal.
- Dashboard Customer memakai `home.tsx` tanpa layout, sehingga tidak punya header. Sesuai keputusan scope, Customer tidak disasar change ini.
- Belum ada halaman detail kost untuk Pemilik Kost (`routes/web.php` grup owner hanya `kosts.index` dan `kosts.create`), sehingga tautan notifikasi kost harus menuju daftar.
- `sonner`'s `Toaster` sudah dipasang di `resources/js/app.tsx`; `Inertia::flash('toast', ...)` sudah dipakai controller lain. Tidak ada Reverb/Echo/WebSocket di proyek.
- Tidak ada `NotificationFactory`; tes perlu menulis baris lewat model atau factory baru.
- `resources/js/{actions,routes,wayfinder}/` hasil generate Wayfinder (gitignored).

## Goals / Non-Goals

**Goals:**

- Satu jalur baca notifikasi untuk Owner dan Admin yang tidak menambah tabel, kolom, dependensi, maupun layanan baru.
- Lonceng dan badge yang benar walau tabel masih kosong dan walau dashboard dibiarkan terbuka lama.
- Penandaan baca yang tidak pernah menyentuh baris milik pengguna lain di tingkat kueri.
- Menjaga biaya query tetap datar: halaman `/notifications` tetap berpaginasi, endpoint lonceng hanya menghitung dan mengambil lima baris.

**Non-Goals:**

- Realtime push (WebSocket/Reverb/SSE) dan notifikasi lintas tab yang instan.
- Notifikasi aduan (M4.1) dan pemicunya.
- Mengubah `domain/complaint-and-notification`; kontrak data sudah memadai.
- Lonceng pada dashboard Customer dan halaman detail kost sebagai tujuan tautan.
- Menghapus notifikasi, preferensi notifikasi, dan pengaturan per jenis.

## Decisions

### Polling JSON ringan untuk lonceng, bukan `usePoll` Inertia

Lonceng memanggil `GET /notifications/unread` dengan `fetch` tiap 30 detik, bukan `usePoll` dari `@inertiajs/react`.

Alasan: `usePoll` memicu kunjungan Inertia penuh, sehingga controller halaman yang sedang dibuka ikut dijalankan ulang tiap tick — dashboard Admin dan daftar kost Owner punya query agregat yang mahal, dan itu berlipat setiap 30 detik per tab. Endpoint JSON khusus hanya menjalankan dua kueri kecil (`count` dan `limit 5`) dengan indeks yang ada.

Alternatif yang ditolak: `usePoll` (murah untuk ditulis, mahal untuk dijalankan), Reverb/Echo (butuh container, channel auth, dependensi JS, dan worker untuk fitur berprioritas Rendah), SSE manual (route long-running yang tidak nyaman di PHP-FPM).

### Dua endpoint baca: halaman Inertia dan ringkasan JSON

- `GET /notifications` → `NotificationController@index`, Inertia `notifications/index`, props `notifications` (paginator dengan `id`, `type`, `message`, `reference_type`, `read_at`, `created_at`) dan `unread_count`.
- `GET /notifications/unread` → `NotificationController@unread`, JSON `{ count, latest: [...] }` (maksimal 5, terbaru lebih dulu, hanya `read_at` kosong).

`index` memakai `latest()` tidak memadai karena `notifications` tidak punya `updated_at`; urutan eksplisit `orderByDesc('created_at')->orderByDesc('id')` dipakai agar stabil saat dua baris punya waktu sama.

`unread` memakai `whereNull('read_at')` dan `limit(5)`; `count` dihitung terpisah agar daftar singkat tidak membatasi hitungan.

### Route di grup `auth` + `verified`, bukan per peran

`docs/ARCHITECTURE.md` §5.2 menempatkan Notifikasi untuk semua peran. Route didaftarkan di grup `['auth', 'verified']` yang sudah ada di `routes/web.php`, di luar grup `role:*`, sehingga Owner dan Admin memakai jalur yang sama dan Customer otomatis bisa memakainya begitu layout-nya punya header.

Empat route:

| Method | Path | Aksi |
| --- | --- | --- |
| GET | `/notifications` | `index` |
| GET | `/notifications/unread` | `unread` |
| POST | `/notifications/read` | `read` (satu `notification` id) |
| POST | `/notifications/read-all` | `readAll` |

`POST /notifications/read` mengikuti `docs/ARCHITECTURE.md` §5.5. `read-all` dipisah agar klien tidak perlu mengirim daftar id.

### Penandaan baca dibatasi di kueri, bukan pemeriksaan setelah muat

Action `App\Actions\Notification\MarkNotificationRead` menjalankan:

```php
$notification = $request->user()->notifications()->findOrFail($id); // 404 bila bukan miliknya
$notification->read_at ??= now();
$notification->save();
```

Untuk `read-all`, satu `UPDATE`:

```php
$request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);
```

Rute notifikasi pengguna lain menghasilkan 404 (bukan 403) agar tidak membocorkan keberadaan baris. Karena model menetapkan `UPDATED_AT = null`, `save()` tidak menyentuh kolom yang tidak ada.

`read` mengembalikan `RedirectResponse` ke rujukan: `reference_type === 'kost'` → `to_route('owner.kosts.index')`; selain itu → `to_route('notifications.index')`. Redirect dipakai, bukan JSON, agar baris bisa berupa `<Link method="post">` biasa dan riwayat peramban tetap wajar. `reference_type` yang tidak dikenal diperlakukan sama seperti tanpa rujukan.

Notifikasi yang sudah dibaca: `read_at` dipertahankan (`??=`), redirect tetap dilakukan — ini memenuhi skenario "Notifikasi yang sudah dibaca tidak berubah waktunya".

### Sumber data lonceng: satu komponen, dua titik pasang

`resources/js/components/notification-bell.tsx` memegang seluruh tanggung jawab klien:

- state `count`, `latest`, `lastSeenId` (disimpan lewat `sessionStorage` dengan kunci berisi id pengguna agar tidak bocor antar akun di peramban yang sama);
- `fetch` ke `/notifications/unread` saat mount, tiap 30 detik, dan sekali saat `visibilitychange` kembali ke `visible`;
- menghentikan interval saat `document.hidden`;
- memanggil `toast` dari `sonner` untuk setiap baris dengan `created_at`/`id` yang lebih baru dari `lastSeenId`, lalu memperbarui `lastSeenId`. Pada pemuatan pertama, `lastSeenId` diisi dari hasil fetch tanpa toast, supaya membuka aplikasi tidak membanjiri toast notifikasi lama. Hanya notifikasi yang tiba setelah sesi dibuka yang memunculkan toast.

Komponen yang sama dirender di `AppSidebarHeader` (dengan `ml-auto` pada wrapper kanannya) dan di `AppHeader` (sebelah tombol `Search`), sehingga varian layout mana pun tetap konsisten dan tidak ada logika ganda.

Alternatif yang ditolak: membagikan notifikasi lewat `HandleInertiaRequests::share` (setiap kunjungan Inertia membayar dua kueri, termasuk halaman yang tidak menampilkan lonceng), dan menaruh logika di masing-masing header (duplikasi interval dan `lastSeenId`).

### Wayfinder untuk URL

Frontend mengimpor helper dari `@/routes/notifications` setelah `php artisan wayfinder:generate`, sesuai konvensi proyek — tidak ada URL yang ditulis manual. Karena direktori hasil generate gitignored, langkah generate masuk tasks sebelum `npm run types:check`.

### Tes fitur, bukan tes komponen

Perilaku polling/toast bersifat klien dan tidak diuji lewat Pest; yang diuji adalah kontrak server: daftar hanya memuat milik sendiri, paginasi, penandaan per baris, penolakan baris orang lain (404 dan `read_at` tetap kosong), `read-all` tidak menyentuh baris lain, `unread` menghitung dan membatasi lima, dan `read` mengarah ke tujuan yang benar. Karena belum ada `NotificationFactory`, tes menulis baris lewat `Notification::create([...])`; factory hanya ditambahkan bila tes berulang membutuhkannya.

## Risks / Trade-offs

- **Toast terlambat hingga 30 detik** → Mitigasi: itu batas yang diterima untuk fitur berprioritas Rendah; interval menjadi satu konstanta di satu tempat sehingga mudah diturunkan tanpa menyentuh server.
- **`fetch` tiap 30 detik tetap membebani server** → Mitigasi: dua kueri terindeks, berhenti total saat tab tersembunyi, dan tidak ada kunjungan Inertia penuh.
- **`sessionStorage` bisa hilang (tab baru, mode privat)** → Mitigasi: hilangnya `lastSeenId` hanya menyebabkan toast lama muncul sekali; tidak ada data yang rusak. Kunci per pengguna mencegah toast milik akun lain muncul setelah logout/login di peramban yang sama.
- **Toast ganda saat dua tab terbuka** → Mitigasi: diterima dan dicatat sebagai batasan; penyelesaiannya butuh mekanisme lintas tab (BroadcastChannel) yang tidak sepadan sekarang.
- **Tautan notifikasi kost menuju daftar, bukan kost tertentu** → Mitigasi: perilaku ini ditetapkan di spec; tautan dipersempit ke detail kost setelah halaman detail Owner dibangun.
- **404 untuk notifikasi milik orang lain bisa membingungkan saat debug** → Mitigasi: disengaja untuk mencegah enumerasi; tes menegaskan `read_at` tidak berubah.
- **`read-all` pada tabel besar** → Mitigasi: `whereNull('read_at')` memakai indeks `(user_id, read_at)` sehingga hanya baris belum dibaca milik satu pengguna yang disentuh.

## Migration Plan

Tanpa migrasi dan tanpa perubahan data. Deploy hanya kode: route, controller, action, komponen, halaman. Rollback dengan membuang route dan komponen; baris `notifications` yang sudah ada tetap utuh dan tetap dapat dibaca setelah itu. Generate Wayfinder (`php artisan wayfinder:generate`) dijalankan sebagai bagian build seperti biasa.

## Open Questions

- Interval polling akhir (30 detik) dan jumlah baris daftar singkat (lima) adalah nilai awal yang dapat disetel tanpa mengubah spec.
- Perlu tidaknya indeks tambahan pada `created_at` untuk pengurutan halaman — ditunda sampai volume data nyata menunjukkan kebutuhan; indeks sekarang sudah menutup penyaringan `user_id` + `read_at`.
