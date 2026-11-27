# Tasks

## 1. Backend: penandaan baca dan ringkasan belum dibaca

- [ ] 1.1 Buat `app/Actions/Notification/MarkNotificationRead.php` dengan `handle(User $user, string $id): Notification` yang memakai `$user->notifications()->findOrFail($id)` dan menetapkan `read_at` hanya bila masih kosong (`??= now()`); verifikasi tes: baris milik pengguna terisi, baris yang sudah dibaca mempertahankan `read_at` lama, dan id milik pengguna lain menghasilkan `ModelNotFoundException`.
- [ ] 1.2 Buat `app/Http/Controllers/NotificationController.php` dengan `index`, `unread`, `read`, dan `readAll`; `index` mengembalikan `Inertia::render('notifications/index', [...])` berisi paginator (10 per halaman, `orderByDesc('created_at')` lalu `orderByDesc('id')`, kolom `id`, `type`, `message`, `reference_type`, `read_at`, `created_at`) dan `unread_count`, `unread` mengembalikan JSON `{ count, latest }` dengan `latest` maksimal lima baris `whereNull('read_at')`, `read` memanggil Action lalu `to_route('owner.kosts.index')` bila `reference_type === 'kost'` selain itu `to_route('notifications.index')`, dan `readAll` menjalankan satu `update(['read_at' => now()])` pada `whereNull('read_at')` milik pengguna lalu kembali ke halaman; verifikasi tes: keempat aksi menjawab dengan bentuk yang ditetapkan spec dan `unread` kosong mengembalikan `count = 0` beserta `latest = []`.
- [ ] 1.3 Daftarkan empat route di grup `['auth', 'verified']` pada `routes/web.php` di luar grup peran: `GET notifications` → `index` (nama `notifications.index`), `GET notifications/unread` → `unread` (nama `notifications.unread`), `POST notifications/read` → `read` (nama `notifications.read`), `POST notifications/read-all` → `readAll` (nama `notifications.readAll`); verifikasi `php artisan route:list --name=notifications` menampilkan keempat route dengan middleware `auth` dan `verified`.
- [ ] 1.4 Tulis `tests/Feature/Notification/NotificationInboxTest.php` memakai `Notification::create([...])` (belum ada factory) untuk seluruh skenario pada spec `notification/inbox` yang menyentuh server: daftar hanya milik sendiri, paginasi, keadaan kosong, tamu dialihkan, tamu ditolak pada route `POST`, penandaan per baris, 404 untuk baris orang lain beserta `read_at` tetap kosong, redirect ke daftar kost untuk `reference_type = 'kost'`, redirect ke halaman notifikasi untuk rujukan kosong dan rujukan tak dikenal, `read-all` hanya menyentuh milik sendiri, serta `unread` menghitung penuh dan membatasi lima; verifikasi `php artisan test --filter=NotificationInboxTest` hijau.

## 2. Frontend: halaman notifikasi

- [ ] 2.1 Jalankan `php artisan wayfinder:generate` lalu buat `resources/js/pages/notifications/index.tsx` berisi daftar notifikasi dari paginator, penanda belum dibaca per baris, waktu relatif, keadaan kosong, tombol "Tandai semua dibaca" (form `POST` ke helper `notifications.readAll`), dan setiap baris berupa `Link` `method="post"` ke helper `notifications.read` dengan id baris; verifikasi `npm run types:check` dan `npm run check` hijau, serta halaman merender tanpa galat saat props kosong.
- [ ] 2.2 Tambahkan tipe props halaman (`NotificationItem`, `Paginated<NotificationItem>`) memakai tipe bersama yang sudah ada di `resources/js/types` bila serupa tersedia, tanpa menduplikasi tipe paginator; verifikasi `npm run types:check` hijau.

## 3. Frontend: lonceng, toast, dan jalan masuk

- [ ] 3.1 Buat `resources/js/components/notification-bell.tsx`: tombol lonceng dengan badge jumlah, dropdown berisi lima notifikasi terbaru dan tautan "Lihat semua" ke `notifications.index`, `fetch` ke helper `notifications.unread` tiap 30 detik, berhenti saat `document.hidden` dan memeriksa ulang saat `visibilitychange` kembali `visible`, `lastSeenId` disimpan di `sessionStorage` dengan kunci per id pengguna, toast `sonner` hanya untuk notifikasi yang tiba setelah sesi dibuka, dan tidak ada toast untuk hasil pemeriksaan pertama; verifikasi `npm run types:check` dan `npm run check` hijau, dan badge tanpa angka saat `count = 0`.
- [ ] 3.2 Pasang komponen di `resources/js/components/app-sidebar-header.tsx` (slot kanan dengan `ml-auto`) dan `resources/js/components/app-header.tsx` (bersebelahan dengan tombol `Search`); verifikasi `npm run build` hijau dan lonceng tampil pada halaman Owner maupun Admin.
- [ ] 3.3 Tambahkan item "Notifikasi" berikon `Bell` di `resources/js/components/user-menu-content.tsx` menuju helper `notifications.index`, diletakkan sebelum item pengaturan; verifikasi `npm run check` hijau dan item tampil di dropdown pengguna pada halaman berheader.

## 4. Integrasi dan dokumentasi

- [ ] 4.1 Perbarui `docs/ARCHITECTURE.md` §5.5 agar mencantumkan `POST /notifications/read-all` di samping `POST /notifications/read` yang sudah ada; verifikasi baris baru terbaca pada bagian "Route Tanpa Halaman".
- [ ] 4.2 Jalankan seluruh gate proyek (`composer test`, `npm run check`, `npm run types:check`, `npm run build`) dan perbaiki kegagalan; verifikasi keempat perintah berakhir sukses.
- [ ] 4.3 Tinjau diff akhir: pastikan tidak ada URL notifikasi yang ditulis manual (semua lewat Wayfinder), tidak ada perubahan pada `HandleInertiaRequests` maupun tabel `notifications`, dan tidak ada berkas hasil generate yang ikut ter-commit; verifikasi `git status` hanya memuat berkas yang tercantum di proposal.

## Workflow follow-up

- Arsipkan change setelah gate proyek hijau dan spec `notification/inbox` disalin ke `openspec/specs/` oleh `openspec archive`.
- Setelah `admin-kost-verification-actions` selesai, uji manual satu putaran penuh: Admin menolak sebuah kost, Pemilik Kost melihat badge dan toast di dashboard, lalu notifikasi bertanda dibaca pada `/notifications`.
