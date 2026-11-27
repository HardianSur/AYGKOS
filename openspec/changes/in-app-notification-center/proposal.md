# Proposal

## Why

Tabel `notifications`, model `Notification`, dan relasi `User::notifications()` sudah ada sejak M0, dan `admin-kost-verification-actions` mulai menulis baris notifikasi bagi Pemilik Kost saat kost disetujui, ditolak, atau ditangguhkan. Tetapi FR-NTF-01 belum punya jalur apa pun untuk membacanya: tidak ada halaman, tidak ada route, tidak ada penanda baca. Notifikasi yang ditulis akan menumpuk tanpa pernah terlihat, sehingga pemicu FR-NTF-02 tidak berguna bagi pengguna.

## What Changes

- Halaman `/notifications` menampilkan notifikasi milik pengguna yang sedang masuk, terbaru lebih dulu, terpaginasi, dengan penanda belum dibaca.
- Endpoint ringan `GET /notifications/unread` mengembalikan jumlah belum dibaca dan beberapa notifikasi terbaru, dipakai lonceng untuk badge dan toast.
- Menandai satu notifikasi dibaca (`POST /notifications/read`) saat barisnya dibuka, lalu mengarahkan ke rujukannya: notifikasi berkaitan kost menuju `/owner/kosts` (halaman detail kost belum ada), notifikasi tanpa rujukan tetap di halaman notifikasi.
- Menandai semua notifikasi milik pengguna dibaca lewat tombol di kepala halaman.
- Lonceng notifikasi dengan badge jumlah belum dibaca di header area aplikasi (`AppSidebarHeader` dan `AppHeader`), berisi lima notifikasi terbaru dan tautan ke `/notifications`. Header itu hanya dipakai layout Pemilik Kost dan Admin, jadi change ini menyasar kedua peran; Customer menyusul bersama notifikasi aduan (M4.1) karena dashboard-nya belum memakai layout berheader.
- Selama dashboard terbuka, halaman memeriksa notifikasi baru tiap 30 detik dan memunculkan toast `sonner` untuk notifikasi yang belum pernah ditampilkan pada sesi tersebut. Pemeriksaan berhenti saat tab tersembunyi.
- Item "Notifikasi" pada menu pengguna (`UserMenuContent`) sebagai jalur kedua menuju halaman.
- Opsi "Tandai semua dibaca" ditambahkan ke dalam change ini, bukan menyusul.

## Capabilities

### New Capabilities

- `notification/inbox`: halaman notifikasi per penerima, penanda belum dibaca, penandaan baca per baris dan menyeluruh, ringkasan belum dibaca untuk lonceng, serta pemberitahuan toast untuk notifikasi baru selama aplikasi terbuka.

### Modified Capabilities

Tidak ada. Requirement notifikasi pada `domain/complaint-and-notification` sudah menetapkan penyimpanan, rujukan polimorfik, pengambilan notifikasi belum dibaca per penerima, dan peralihan `read_at` saat penerima membuka notifikasi; change ini merealisasikannya di lapisan halaman dan route tanpa mengubah kontrak datanya.

## Impact

- **Dokumen**: tidak ada perubahan skema, sehingga `docs/ERD/erd.md` tetap V005 dan `docs/FunctionalRequirment/functional_requirment.md` tetap V002. Change ini merealisasikan FR-NTF-01 dan melengkapi jalur baca bagi FR-NTF-02.
- **Tanpa migrasi dan tanpa dependensi baru**: tabel `notifications` beserta indeks `(user_id, read_at)` sudah ada, dan `sonner` sudah terpasang.
- **Backend baru**: `app/Http/Controllers/NotificationController.php` (`index`, `unread`, `read`, `readAll`) dan `app/Actions/Notification/MarkNotificationRead.php` sesuai `docs/ARCHITECTURE.md`.
- **Backend berubah**: `routes/web.php` menambah grup `/notifications` di dalam middleware `auth` + `verified`, di luar grup peran.
- **Frontend baru**: `resources/js/components/notification-bell.tsx` dan halaman `resources/js/pages/notifications/index.tsx`.
- **Frontend berubah**: `resources/js/components/app-sidebar-header.tsx` (slot kanan), `resources/js/components/app-header.tsx` (varian header), dan `resources/js/components/user-menu-content.tsx` (item Notifikasi).
- **Tes**: `tests/Feature/Notification/NotificationInboxTest.php` untuk daftar, kepemilikan, penandaan baca, penandaan menyeluruh, dan ringkasan belum dibaca.
- **Di luar cakupan**: notifikasi aduan (M4.1) dan pemicunya, notifikasi realtime lewat WebSocket/Reverb, halaman detail kost sebagai tujuan tautan, lonceng di dashboard Customer, preferensi notifikasi, dan penghapusan notifikasi oleh pengguna.