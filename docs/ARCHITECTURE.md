# Architecture — AYGKOS

> **Dokumen:** Arsitektur Sistem
> **Status:** Aktif
> **Tujuan:** Menjelaskan konteks sistem, batas layer, struktur proyek, peta route, alur data, dan konvensi implementasi AYGKOS.

Dokumen ini adalah arsitektur **target**: keadaan yang dituju setelah seluruh FR di `docs/FunctionalRequirment/functional_requirment.md` dan ERD di `docs/ERD/erd.md` dikerjakan. Bagian 12 mencatat selisih antara dokumen ini dan kode saat ini.

---

## 1. Konteks Sistem

AYGKOS adalah aplikasi web monolith Laravel. Browser memuat UI React melalui Inertia.js; server Laravel menangani routing, autentikasi, otorisasi, logika bisnis, dan akses basis data. Tidak ada backend terpisah dan tidak ada API publik berbasis token: satu proses PHP melayani HTML awal, prop Inertia, dan seluruh mutasi.

```text
+----------+        +---------------------------+        +-----------------+
| Browser  | <----> | Laravel 13 (satu proses)  | <----> | PostgreSQL 16   |
| React 19 |        | Inertia + Fortify + Eloquent|       +-----------------+
+----------+        +-------------+-------------+
                                  |
        +-----------------+-------+--------+------------------+
        |                 |                |                  |
        v                 v                v                  v
  +-----------+    +-------------+  +-------------+   +--------------+
  | Disk      |    | Fonnte      |  | Geoapify    |   | SMTP (Gmail) |
  | public +  |    | OTP WhatsApp|  | peta, tile, |   | email        |
  | private   |    |             |  | geocoding   |   | verifikasi   |
  +-----------+    +-------------+  +-------------+   +--------------+
```

Alur umum: pengguna membuka route, Laravel menjalankan middleware (sesi, peran, verifikasi), controller menyiapkan prop Inertia, React merender halaman, lalu mutasi dikirim kembali ke route Laravel sebagai permintaan Inertia dan diproses lewat Form Request, Policy, dan Action sebelum menyentuh basis data.

---

## 2. Stack

| Komponen         | Teknologi                                      | Keterangan                                                  |
| ---------------- | ---------------------------------------------- | ----------------------------------------------------------- |
| Framework        | Laravel 13 (PHP 8.3)                           | Routing, middleware, validasi, ORM, antrean, penjadwal       |
| Jembatan UI      | Inertia.js v3 (`inertiajs/inertia-laravel`)    | Halaman React langsung dari route Laravel, tanpa REST terpisah|
| Bahasa klien     | TypeScript strict                              | Alias `@/*` ke `resources/js/*`                             |
| UI               | React 19, Tailwind CSS v4, shadcn/ui, Radix UI | Token tema di `resources/css/app.css`, `cn()` di `lib/utils` |
| Ikon             | `lucide-react`                                 | Ikon antarmuka                                              |
| Notifikasi        | `sonner` (via `components/ui/sonner`)          | Toast keberhasilan dan kegagalan                            |
| Peta             | Leaflet + tile Geoapify                        | `components/map/leaflet-map.tsx`, dimuat dinamis            |
| Geocoding        | Geoapify Geocoding + Autocomplete (klien)      | `components/map/location-autocomplete.tsx`, debounce 400 ms |
| Helfer route     | Laravel Wayfinder                               | `@/routes/*` dan `@/actions/*`, dibangkitkan otomatis       |
| Autentikasi      | Laravel Fortify (`config/fortify.php`)         | Login, registrasi, reset password, verifikasi email         |
| OTP WhatsApp     | `App\Contracts\OtpSender` (log, fonnte)        | `config/otp.php`, binding di `AppServiceProvider`           |
| Basis data       | PostgreSQL 16                                  | UUID7 (`HasUuids`), check constraint, indeks komposit       |
| Berkas           | Disk `public` dan `local` (`config/filesystems.php`) | Foto publik; dokumen verifikasi dan lampiran privat    |
| Email            | SMTP Gmail (App Password), fallback `log`      | `MAIL_MAILER` di `.env`                                     |
| Build            | `vp` (vite-plus), `babel-plugin-react-compiler`| Konfigurasi di `vite.config.ts`                             |
| SSR              | Inertia SSR (`config/inertia.php` `ssr.enabled`) | Halaman wajib aman dijalankan tanpa `window`               |
| Tes              | Pest 5 + `pest-plugin-laravel`                 | `tests/Feature`, `tests/Unit`                               |
| Analisis statis  | PHPStan (Larastan) level 7, Pint              | `phpstan.neon`, `composer lint`                             |

Kontrak perintah: `composer dev`, `composer test`, `composer lint`, `npm run types:check`, `npm run check`.

---

## 3. Layer

```text
routes/web.php  routes/settings.php        Deklarasi route + middleware (auth, role, verified)
        |
        v
app/Http/Controllers/{Catalog,Owner,Admin,Settings}   Controller tipis: siapkan prop Inertia
        |
        v
app/Http/Requests/**                      Form Request: validasi + otorisasi awal
        |
        v
app/Policies/*.php                        Policy: kepemilikan dan peran per model
        |
        v
app/Actions/{Domain}/*.php                Action: satu use case, transaksi, efek samping
        |
        v
app/Models/*.php                          Eloquent: relasi, cast, scope, event berkas
        |
        v
PostgreSQL 16
```

Aturan layer:

- Controller tidak berisi aturan bisnis. Tugasnya memvalidasi lewat Form Request yang sudah di-*type-hint*, memanggil Policy bila perlu, memanggil satu Action, lalu mengembalikan `Inertia::render` atau `redirect()`.
- Halaman dan komponen React tidak pernah mengakses basis data. Semua data masuk sebagai prop Inertia.
- Satu pintu mutasi per use case: satu kelas Action. Controller tidak menulis `Model::create()` langsung untuk operasi yang menyentuh lebih dari satu tabel, mengirim pesan keluar, atau mengubah status.
- Otorisasi diperiksa di server (middleware `role` dan Policy). Menyembunyikan tombol di UI bukan batas keamanan.
- Query pembacaan boleh berada di controller selama hanya satu model dan tanpa aturan bisnis. Begitu dipakai lebih dari satu tempat, pindahkan ke scope model.
- **Tanpa layer repository.** Eloquent sudah menjadi abstraksi basis data; menambah `RepositoryInterface` + implementasi untuk setiap model hanya menggandakan pemanggilan tanpa memberi kemampuan uji atau pertukaran penyimpanan yang nyata. Batas yang dijaga adalah Action, bukan repository.
- Kode lintas domain yang bukan Action (pembentuk URL berkas privat, penormat nomor WhatsApp) berada di `app/Support/`.

---

## 4. Struktur Proyek

### 4.1 Struktur Folder

```text
aygkos/
|-- app/
|   |-- Actions/
|   |   |-- Fortify/                 CreateNewUser, ResetUserPassword (kontrak Fortify)
|   |   |-- Auth/                    SendWhatsappOtp, VerifyWhatsappOtp, ChangeWhatsappNumber
|   |   |-- Kost/                     SubmitKost, ApproveKost, RejectKost, SuspendKost, DeactivateKost
|   |   |-- RoomType/                 CreateRoomType, UpdateRoomType, DeleteRoomType
|   |   |-- MasterData/               ManageFacility, ManageRule, ManageSpecification
|   |   |-- Complaint/                SubmitComplaint, RespondToComplaint
|   |   `-- Notification/             MarkNotificationRead
|   |-- Concerns/                     PasswordValidationRules, ProfileValidationRules
|   |-- Console/                      Perintah artisan (penjadwal pembersihan OTP kedaluwarsa)
|   |-- Contracts/                    OtpSender
|   |-- Http/
|   |   |-- Controllers/
|   |   |   |-- Catalog/              KostController, KostSearchController
|   |   |   |-- Owner/                DashboardController, KostController, RoomTypeController, ComplaintController
|   |   |   |-- Admin/                DashboardController, KostVerificationController, UserController,
|   |   |   |                         ComplaintController, ReportController, FacilityController,
|   |   |   |                         RuleController, SpecificationController
|   |   |   |-- Settings/             ProfileController, SecurityController (sudah ada)
|   |   |   `-- NotificationController.php
|   |   |-- Middleware/               HandleAppearance, HandleInertiaRequests, EnsureRole, EnsureAccountVerified
|   |   `-- Requests/
|   |       |-- Auth/                 RegisterOwnerRequest, VerifyOtpRequest, UpdateWhatsappRequest
|   |       |-- Kost/                 StoreKostRequest, UpdateKostRequest, KostSearchRequest
|   |       |-- RoomType/             StoreRoomTypeRequest, UpdateRoomTypeRequest
|   |       |-- Complaint/            StoreComplaintRequest, RespondComplaintRequest
|   |       |-- Admin/                RejectKostRequest, SuspendKostRequest, UpdateUserStatusRequest,
|   |       |                         StoreFacilityRequest, StoreRuleRequest, StoreSpecificationRequest
|   |       |-- Settings/             ProfileUpdateRequest, PasswordUpdateRequest, ... (sudah ada)
|   |       `-- Uploads/              KostPhotoUploadRequest, KostDocumentUploadRequest,
|   |                                 ComplaintAttachmentUploadRequest (sudah ada)
|   |-- Models/                       Role, User, WhatsappVerification, Kost, RoomType, Photo,
|   |                                 KostDocument, Facility, Rule, Specification,
|   |                                 Complaint, ComplaintAttachment, Notification
|   |-- Policies/                     KostPolicy, RoomTypePolicy, ComplaintPolicy, UserPolicy
|   |-- Providers/                    AppServiceProvider, FortifyServiceProvider
|   |-- Services/
|   |   `-- Otp/                      LogOtpSender, FonnteOtpSender
|   `-- Support/                      PrivateFileUrl, WhatsappNumber (normalisasi +62), FacilityIcons
|-- config/                           otp.php, uploads.php, services.php (fonnte, geoapify), fortify.php
|-- database/
|   |-- migrations/                   Satu berkas per tabel; constraint check lewat DB::statement
|   |-- factories/                    Satu factory per model yang diuji
|   `-- seeders/                      RoleSeeder, FacilitySeeder, RuleSeeder, SpecificationSeeder, DatabaseSeeder
|-- docs/
|   |-- ARCHITECTURE.md               Dokumen ini
|   |-- ERD/                          erd.md (versi terkini), logs/, release.md
|   `-- FunctionalRequirment/         functional_requirment.md, logs/, release.md
|-- resources/
|   |-- css/app.css                   Token Tailwind v4
|   `-- js/
|       |-- actions/                  Wayfinder: fungsi controller (gitignored)
|       |-- routes/                   Wayfinder: fungsi route (gitignored)
|       |-- components/
|       |   |-- ui/                   shadcn (dikecualikan dari vp lint/fmt)
|       |   `-- map/                  leaflet-map, location-autocomplete
|       |-- layouts/                  app, auth, settings
|       |-- pages/                    Satu berkas per halaman Inertia
|       |-- hooks/                    use-debounced-value, use-appearance, use-flash-toast, ...
|       |-- lib/utils.ts              cn()
|       |-- types/                    Tipe prop bersama (auth.ts, navigation.ts)
|       `-- app.tsx                   Bootstrap Inertia + resolusi layout
|-- routes/
|   |-- web.php                       Route publik + area terautentikasi
|   |-- settings.php                  Profil dan keamanan akun
|   `-- console.php                   Jadwal perintah
|-- tests/
|   |-- Feature/                      Feature test per FR (Pest)
|   `-- Unit/                         Unit test logika murni
|-- storage/app/public/               Foto kost (via symlink /storage)
`-- storage/app/private/              Dokumen verifikasi dan lampiran aduan
```

### 4.2 Resolusi Layout

Layout ditentukan dari nama halaman di `resources/js/app.tsx`, bukan per halaman:

| Pola nama halaman | Layout                                |
| ----------------- | ------------------------------------- |
| `welcome`         | tanpa layout                          |
| `auth/*`          | `AuthLayout`                          |
| `settings/*`      | `AppLayout` + `SettingsLayout`        |
| `owner/*`         | `AppLayout` (+ `OwnerLayout`)         |
| `admin/*`         | `AppLayout` (+ `AdminLayout`)         |
| selain itu        | `AppLayout`                           |

Halaman menyetel breadcrumb lewat `Page.layout = { breadcrumbs: [...] }`, bukan pembungkus layout sendiri.

---

## 5. Route Map

Seluruh route berada di `routes/web.php` (domain) dan `routes/settings.php` (akun). Nama route mengikuti prefiks yang dibangkitkan Wayfinder.

### 5.1 Publik dan Tamu

| No. | Nama Halaman      | Tujuan                                                                             | Route                  | Akses |
| --- | ----------------- | ---------------------------------------------------------------------------------- | ---------------------- | ----- |
| 1   | Beranda           | Titik masuk aplikasi, ringkasan nilai, dan pintu ke pencarian kost.                | `/`                    | Guest |
| 2   | Pencarian Kost    | Penelusuran, pencarian lokasi radius 10 km, filter harga/tipe/ketersediaan/fasilitas, pengurutan, hasil per halaman dan pada peta. | `/kosts` | Guest, Customer |
| 3   | Detail Kost       | Nama, alamat, lokasi pada peta, tipe, deskripsi, tipe kamar aktif berurutan (spesifikasi, fasilitas kamar, peraturan khusus, catatan), fasilitas umum, peraturan kost, galeri foto berkategori, dan nama Pemilik Kost. | `/kosts/{kost}` | Guest, Customer |
| 4   | Login             | Autentikasi email dan password untuk ketiga peran.                                  | `/login`               | Guest |
| 5   | Registrasi        | Pendaftaran Customer atau Pemilik Kost; peran Admin tidak tersedia.                 | `/register`            | Guest |
| 6   | Lupa Password     | Permintaan tautan reset password melalui email.                                     | `/forgot-password`     | Guest |
| 7   | Reset Password    | Penetapan password baru dari tautan bertanda tangan.                                | `/reset-password/{token}` | Guest |
| 8   | Verifikasi Email  | Pemberitahuan bahwa tautan verifikasi sudah dikirim, beserta kirim ulang.            | `/verify-email`        | Guest, Customer |
| 9   | Verifikasi WhatsApp | Input kode OTP WhatsApp dan kirim ulang kode untuk Pemilik Kost.                  | `/verify-whatsapp`     | Guest, Pemilik Kost |

### 5.2 Area Customer dan Pemilik Kost

| No. | Nama Halaman      | Tujuan                                                                             | Route                     | Akses |
| --- | ----------------- | ---------------------------------------------------------------------------------- | ------------------------- | ----- |
| 10  | Ajukan Aduan      | Form aduan: kategori, judul, deskripsi, kost terkait, maksimal 3 lampiran.          | `/complaints/create`      | Customer, Pemilik Kost |
| 11  | Riwayat Aduan     | Daftar aduan milik pengguna beserta status dan tanggapan Admin.                     | `/complaints`             | Customer, Pemilik Kost |
| 12  | Detail Aduan      | Isi aduan, lampiran, status, dan tanggapan Admin.                                   | `/complaints/{complaint}` | Customer, Pemilik Kost |
| 13  | Notifikasi        | Daftar notifikasi in-app beserta penanda belum dibaca.                              | `/notifications`          | Semua peran |
| 14  | Profil            | Melihat dan mengubah nama lengkap.                                                  | `/settings/profile`       | Customer, Pemilik Kost |
| 15  | Keamanan Akun     | Mengubah password dengan verifikasi password lama.                                  | `/settings/security`      | Semua peran |
| 16  | Nomor WhatsApp    | Mengubah nomor WhatsApp Pemilik Kost; nomor baru berlaku setelah OTP benar.         | `/settings/whatsapp`      | Pemilik Kost |

### 5.3 Area Pemilik Kost

| No. | Nama Halaman      | Tujuan                                                                             | Route                        | Akses |
| --- | ----------------- | ---------------------------------------------------------------------------------- | ---------------------------- | ----- |
| 17  | Dashboard Pemilik | Jumlah kost per status verifikasi, jumlah kamar tersedia, dan jumlah kamar terisi per tipe kamar. | `/owner/dashboard`           | Pemilik Kost |
| 18  | Daftar Kost       | Kost milik Pemilik beserta status verifikasi, alasan penolakan atau penangguhan.    | `/owner/kosts`               | Pemilik Kost |
| 19  | Tambah Kost       | Form pengajuan kost: data, titik lokasi, fasilitas umum, peraturan dari master, foto berkategori, dan dokumen izin. | `/owner/kosts/create`   | Pemilik Kost |
| 20  | Ubah Kost         | Perubahan data kost dan pengajuan ulang kost yang ditolak.                          | `/owner/kosts/{kost}/edit`   | Pemilik Kost |
| 21  | Tipe Kamar Kost   | Daftar tipe kamar, tambah, ubah, aktifkan/nonaktifkan, dan hapus tipe kamar.        | `/owner/kosts/{kost}/types`  | Pemilik Kost |

### 5.4 Area Admin

| No. | Nama Halaman       | Tujuan                                                                            | Route                          | Akses |
| --- | ------------------ | --------------------------------------------------------------------------------- | ------------------------------ | ----- |
| 22  | Dashboard Admin    | Jumlah kost menunggu verifikasi dan jumlah aduan baru.                             | `/admin/dashboard`             | Admin |
| 23  | Verifikasi Kost    | Daftar kost menunggu verifikasi beserta detail dan lima jenis dokumen izinnya.    | `/admin/kosts/verification`    | Admin |
| 24  | Detail Verifikasi  | Detail kost, dokumen, serta aksi setujui atau tolak dengan alasan; penangguhan kost. | `/admin/kosts/{kost}`        | Admin |
| 25  | Pengelolaan Pengguna | Daftar pengguna, penonaktifan dan pengaktifan akun.                              | `/admin/users`                 | Admin |
| 26  | Penanganan Aduan   | Daftar aduan dengan filter status dan kategori, detail, ubah status, tulis tanggapan. | `/admin/complaints`         | Admin |
| 27  | Laporan            | Statistik jumlah kost per status, kamar tersedia, pengguna per peran, aduan per status, dengan filter periode. | `/admin/reports` | Admin |
| 28  | Master Fasilitas   | Daftar, tambah, ubah, dan hapus master fasilitas beserta ruang lingkup dan ikon.    | `/admin/facilities`            | Admin |
| 29  | Master Peraturan   | Daftar, tambah, ubah, dan hapus master peraturan beserta ruang lingkupnya.           | `/admin/rules`                 | Admin |
| 30  | Master Spesifikasi | Daftar, tambah, ubah, dan hapus master spesifikasi beserta jenis masukan dan satuan. | `/admin/specifications`        | Admin |

### 5.5 Route Tanpa Halaman

| Route                              | Kegunaan                                                        |
| ---------------------------------- | --------------------------------------------------------------- |
| `POST /logout`                     | Mengakhiri sesi lalu mengarahkan ke `/login`.                   |
| `POST /verify-whatsapp`            | Memverifikasi kode OTP.                                         |
| `POST /verify-whatsapp/resend`     | Kirim ulang kode OTP dengan pembatasan frekuensi.               |
| `POST /kosts/{kost}/contact`       | Menyiapkan tautan WhatsApp (Tanya Kost / Ajukan Sewa) untuk Customer terautentikasi. |
| `POST /notifications/read`         | Menandai notifikasi sudah dibaca.                               |
| `GET /storage/local/{path}`        | Menyajikan berkas privat bertanda tangan (disediakan framework). |
| `GET /up`                          | Health check.                                                   |

### 5.6 Komponen Pendukung (tanpa route)

| Komponen                  | Kegunaan                                                       |
| ------------------------- | -------------------------------------------------------------- |
| `AppHeader`, `AppSidebar` | Identitas aplikasi dan navigasi sesuai peran.                   |
| `KostCard`                | Kartu hasil pencarian: foto utama, nama, kota, tipe, harga mulai dari tipe kamar aktif termurah, jumlah kamar tersedia dari penjumlahan tipe kamar aktif. |
| `LeafletMap`              | Peta titik hasil pencarian dan peta lokasi pada detail kost.    |
| `LocationAutocomplete`    | Input lokasi dengan autocomplete Geoapify.                      |
| `KostStatusBadge`         | Label status verifikasi kost dan status tipe kamar.             |
| `ConfirmDialog`           | Konfirmasi tindakan penting (hapus kost, nonaktifkan akun).     |
| `EmptyState`              | Tampilan ketika hasil pencarian atau daftar kosong.             |
| `SubmitButton`            | Tombol dengan status memproses.                                 |
| `InputError`, `AlertError`| Pesan galat per kolom dan galat umum.                           |
| `Toaster`                 | Notifikasi toast (sonner).                                      |

Form panjang (pengajuan kost, aduan) memakai halaman tersendiri; form pendek (tipe kamar, ubah nama) memakai dialog. Konfirmasi tindakan destruktif selalu memakai dialog konfirmasi.

---

## 6. Alur Data

### 6.1 Pengajuan Kost hingga Disetujui

```text
Pemilik Kost          Laravel                        Admin
     |                    |                            |
     |  GET /owner/kosts/create                          |
     |------------------->| Form Request + props        |
     |  POST (multipart)  |                            |
     |------------------->| StoreKostRequest validasi   |
     |                    | Policy: peran pemilik_kost  |
     |                    | Action SubmitKost:          |
     |                    |  - simpan kosts (status      |
     |                    |    menunggu_verifikasi)     |
     |                    |  - simpan foto berkategori   |
     |                    |    -> disk public           |
     |                    |  - simpan dokumen izin      |
     |                    |    -> disk local            |
     |                    |  - simpan fasilitas umum    |
     |                    |    dan peraturan pivot      |
     |<-------------------| redirect + toast            |
     |                    |                            |
     |                    |  GET /admin/kosts/verification
     |                    |<---------------------------|
     |                    |  POST setujui/tolak        |
     |                    |<---------------------------|
     |                    | Action ApproveKost/RejectKost
     |                    |  - ubah status + reviewed_by |
     |                    |  - notification in-app     |
     |<-------------------| notifikasi (FR-NTF-02)      |
```

Rincian langkah:

1. `GET /owner/kosts/create` mengirim prop daftar fasilitas umum, peraturan kost, dan kunci Geoapify.
2. Form mengirim `multipart/form-data`; `StoreKostRequest` memvalidasi kolom wajib, koordinat, minimal satu foto, serta dokumen wajib `nib` dan `surat_pernyataan` sesuai `config/uploads.php`.
3. `KostPolicy::create` memastikan pelaku berperan `pemilik_kost` dan akunnya aktif serta terverifikasi.
4. `SubmitKost` berjalan dalam satu transaksi: baris `kosts`, `photos`, `kost_facilities`, `kost_rules`, dan `kost_documents`. Berkas ditulis ke disk lebih dulu; bila transaksi gagal, berkas yang sudah tertulis dihapus.
5. Status awal selalu `menunggu_verifikasi`; tidak ada jalur yang membuat kost langsung `disetujui` (FR-KOS-02).
6. Aksi Admin memanggil `ApproveKost`, `RejectKost`, atau `SuspendKost`; masing-masing mengisi `reviewed_by`, `reviewed_at`, `status_reason`, lalu membuat baris `notifications`.
7. Halaman publik hanya membaca `kosts` berstatus `disetujui`, tidak terhapus lunak, dan pemiliknya `is_active = true` (FR-SRC-09).

### 6.2 Pencarian Berbasis Lokasi

```text
Input lokasi  -->  Geoapify Autocomplete (klien)  -->  { lat, lng }
                                                          |
                                                          v
                             GET /kosts?lat=..&lng=..&radius=10&filters...
                                                          |
                                                          v
                          Bounding box pada indeks (latitude, longitude)
                                                          |
                                                          v
                          Haversine di SQL -> jarak, urut naik, paginasi
```

- Autocomplete hanya memanggil Geoapify setelah masukan diam 400 ms dan membatalkan permintaan lama (`use-debounced-value`).
- Server menerima koordinat dan radius, menyaring kandidat dengan bounding box agar indeks komposit terpakai, lalu menghitung jarak sebenarnya dengan rumus Haversine di SQL. Tanpa PostGIS.
- Filter harga, tipe, ketersediaan kamar, dan fasilitas diterapkan sebagai klausa tambahan pada query yang sama; pengurutan jarak hanya tersedia bila koordinat dikirim.
- Ketersediaan dan harga memakai `EXISTS` pada `room_types` aktif, sehingga satu kost tetap satu baris hasil meski memiliki beberapa tipe kamar.
- Hasil dipaginasi di server; peta menerima himpunan penanda halaman aktif.

### 6.3 Kontak Pemilik melalui WhatsApp

1. Guest yang memilih tombol Hubungi Pemilik diarahkan ke `/login` dengan URL detail kost disimpan sebagai tujuan kembali (FR-KTK-06).
2. Customer terautentikasi mengirim `POST /kosts/{kost}/contact` dengan pilihan Tanya Kost atau Ajukan Sewa, plus tipe kamar bila Ajukan Sewa.
3. Server memvalidasi tipe kamar berstatus `aktif` dengan `available_rooms` lebih dari nol untuk Ajukan Sewa (FR-KTK-04), menyusun teks pesan berisi nama kost, nama tipe kamar, harga sewa per bulan, dan nama Customer, lalu mengembalikan URL `https://wa.me/<nomor>?text=...`.
4. Klien membuka URL tersebut; pesan tetap berupa draf yang dapat diubah sebelum dikirim (FR-KTK-05). Sistem tidak pernah mengirim pesan sendiri.

### 6.4 Tipe Kamar, Fasilitas, dan Galeri

- Tipe kamar disimpan pada `room_types` dengan status awal `nonaktif`; bagian `POST /owner/kosts/{kost}/types` mengaktifkannya setelah pemilik meninjau isinya.
- Fasilitas dan peraturan dipilih dari master sesuai `scope`: fasilitas `umum` dan aturan `kost` pada kost, fasilitas `kamar` dan aturan `kamar` pada tipe kamar. Master yang masih dipakai ditahan oleh `restrict`, sehingga daftar pemakainya ditampilkan sebelum Admin menghapus.
- Spesifikasi hanya melekat pada tipe kamar; nilai disimpan di `room_type_specifications.value`.
- Galeri detail kost memakai satu tabel `photos`: kategori `bangunan` dan `fasilitas_umum` di level kost, kategori `kamar` dan `kamar_mandi` di level tipe kamar. Halaman detail hanya menampilkan foto utama; galeri penuh menampilkan sisa foto terkelompok per kategori.
- Foto utama dijaga indeks unik parsial: satu per kost untuk foto level kost, satu per tipe kamar untuk foto level tipe kamar.

---

## 7. Autentikasi dan Otorisasi

### 7.1 Autentikasi

Fortify menangani seluruh alur: login, registrasi, lupa password, reset password, verifikasi email, konfirmasi password, dan pembaruan password. Halaman disajikan lewat `Fortify::*View` di `app/Providers/FortifyServiceProvider.php`; tidak ada controller autentikasi buatan sendiri.

- Password di-hash dengan bcrypt (`BCRYPT_ROUNDS`), aturan kekuatan password dari `Password::defaults()`.
- Registrasi memakai `App\Actions\Fortify\CreateNewUser`, yang menetapkan `role_id` peran `customer`; registrasi Pemilik Kost memakai Action terpisah yang juga menulis `whatsapp_number` dan memicu OTP.
- Peran Admin tidak dapat didaftarkan; akunnya dibuat lewat seeder.
- Sesi disimpan di basis data (`SESSION_DRIVER=database`) dengan cookie `HttpOnly` dan `SameSite=Lax`.

### 7.2 Verifikasi Dua Jalur

| Peran          | Bukti terverifikasi        | Mekanisme                                                        |
| -------------- | -------------------------- | ---------------------------------------------------------------- |
| Customer       | `users.email_verified_at`  | Tautan bertanda tangan yang dikirim ke email (FR-AUT-05)          |
| Pemilik Kost   | `users.whatsapp_verified_at` | Kode OTP pada `whatsapp_verifications`, dikirim lewat `OtpSender` (FR-AUT-06) |
| Admin          | `users.email_verified_at`  | Diisi seeder                                                     |

Middleware `verified` bawaan framework hanya membaca `email_verified_at`, sehingga Pemilik Kost memerlukan middleware `EnsureAccountVerified` yang menerima salah satu bukti sesuai peran. Kode OTP disimpan sebagai hash, dengan masa berlaku, batas percobaan, jeda kirim ulang, dan batas kirim per jam dari `config/otp.php`.

### 7.3 Otorisasi

- Middleware `EnsureRole` dipasang per grup route: `role:pemilik_kost` untuk `/owner/*`, `role:admin` untuk `/admin/*`.
- Policy menegakkan kepemilikan sumber daya, bukan hanya peran: `KostPolicy::update` memeriksa `kost.owner_id`, `RoomTypePolicy::*` memeriksa rantai `roomType.kost.owner_id`, `ComplaintPolicy::view` memeriksa `complaint.user_id`.
- Kost berstatus `ditangguhkan` tidak dapat diaktifkan kembali oleh Pemilik Kost; hanya Admin yang boleh mengubahnya (FR-ADM-KOS-01).
- Akun dengan `is_active = false` ditolak saat login dan seluruh kost miliknya hilang dari tampilan publik (FR-ADM-USR-01).

### 7.4 Pengarahan Setelah Login

`config/fortify.php` `'home'` mengarahkan semua peran ke satu tujuan. Untuk memenuhi FR-AUT-10, implementasi `LoginResponse` (kontrak Fortify) di-override agar mengarahkan: Customer ke `/kosts`, Pemilik Kost ke `/owner/dashboard`, Admin ke `/admin/dashboard`. Guest yang teralihkan dari detail kost dikembalikan ke `redirect()->intended()`.

Batasan yang tidak berubah:

- Otorisasi tidak pernah bergantung pada UI; setiap route dan Action memeriksa ulang di server.
- Secret (`APP_KEY`, kredensial basis data, `FONNTE_TOKEN`, `GEOAPIFY_API_KEY`, kredensial SMTP) hanya berada di `.env`, tidak di bundel klien.
- Halaman publik tidak pernah memuat tautan bertanda tangan ke berkas privat.

---

## 8. Penyimpanan Berkas

Dua disk dengan visibilitas berbeda, ditetapkan di `config/filesystems.php`:

| Jenis berkas                                  | Disk      | Lokasi                    | Penyajian                                  |
| --------------------------------------------- | --------- | ------------------------- | ------------------------------------------ |
| Foto kost dan tipe kamar                      | `public`  | `storage/app/public`      | URL publik melalui symlink `/storage`      |
| Dokumen izin kost (lima jenis, PDF)           | `local`   | `storage/app/private`     | Tautan bertanda tangan berumur 10 menit    |
| Lampiran aduan (maksimal 3)                   | `local`   | `storage/app/private`     | Tautan bertanda tangan berumur 10 menit    |

Aturan:

- Basis data menyimpan **path**, bukan URL. Kolom terkait: `photos.photo_path`, `kost_documents.file_path`, `complaint_attachments.file_path`.
- Tautan berkas privat dibentuk dengan `App\Support\PrivateFileUrl::for($path)`, yang memanggil route `storage.local` bertanda tangan relatif. Adapter `local` tidak mendukung `Storage::temporaryUrl()`.
- Batas tipe dan ukuran berkas berada di `config/uploads.php` dan dipakai aturan `mimes`/`max` pada Form Request di `app/Http/Requests/Uploads/`.
- Penghapusan berkas terjadi pada event model (`deleting`, `updated`), bukan di controller, karena kost juga dapat dihapus atau diubah lewat alur Admin. Berkas dihapus setelah transaksi basis data berhasil; kegagalan hapus berkas dicatat ke log dan tidak membatalkan perubahan data.
- Nama berkas disimpan dengan path yang memuat `kosts/{id}/` atau `complaints/{id}/` sebagai penanda kepemilikan.

---

## 9. Konvensi Validasi dan Error

- Validasi selalu dijalankan di server melalui Form Request sebelum data ditulis.
- Pesan galat ditulis dalam bahasa Indonesia dan menempel pada kolom yang salah lewat `InputError`.
- Aturan TypeScript hanya untuk kenyamanan; server tetap memeriksa ulang.
- Galat validasi Inertia dikembalikan ke form; password tidak pernah dipertahankan pada nilai lama.
- Kegagalan tak terduga tidak membocorkan detail internal; pengguna menerima pesan umum dan pesan teknis masuk log.
- Status direpresentasikan sebagai string enumerasi (`menunggu_verifikasi`, `disetujui`, `ditolak`, `nonaktif`, `ditangguhkan`; tipe kamar: `aktif`, `nonaktif`) dan ditegakkan oleh validasi fitur, bukan kolom enum basis data.
- Penghapusan memakai `SoftDeletes` pada `kosts` dan `room_types`; aduan tidak dihapus, melainkan diubah statusnya menjadi `selesai`.

---

## 10. Keamanan

| Kebutuhan                          | Implementasi                                                                                     |
| ---------------------------------- | ------------------------------------------------------------------------------------------------ |
| Kerahasiaan kredensial             | Hash bcrypt, Fortify, `APP_KEY` di `.env`, kredensial pihak ketiga tidak pernah di klien          |
| Kontrol akses per peran            | Middleware `EnsureRole` + Policy per model; diperiksa ulang pada setiap Action                    |
| Perlindungan berkas privat         | Disk `local`, route bertanda tangan 10 menit, tanpa tautan di halaman publik                      |
| Pencegahan spam OTP                | `config/otp.php`: maksimum 3 kiriman per jam, jeda 60 detik, maksimum 5 percobaan                 |
| Pembatasan laju autentikasi        | Rate limiter Fortify; `throttle:6,1` pada pembaruan password                                     |
| Integritas data                    | Foreign key dengan aksi eksplisit, check constraint koordinat dan harga, transaksi pada Action    |
| Validasi unggahan                  | `mimes` dan `max` dari `config/uploads.php`, ditambah validasi tipe MIME oleh framework            |
| Pencegahan CSRF                    | Token CSRF Laravel pada seluruh permintaan mutasi Inertia                                         |

Larangan:

- Jangan mengirim `GEOAPIFY_API_KEY` sebagai `VITE_*`; kunci dikirim sebagai prop dari server agar dapat diganti tanpa membangun ulang bundel.
- Jangan menaruh secret pada `.env.example`.
- Jangan mengandalkan penyembunyian tombol atau menu sebagai otorisasi.
- Jangan menyajikan dokumen verifikasi atau lampiran aduan lewat disk `public`.
- Jangan mencatat kode OTP pada log di luar driver `log` yang memang disediakan untuk pengembangan.

---

## 11. Pengujian

- Gate utama: `composer test` = `config:clear` → `pint --test` → `phpstan analyse` (level 7) → `php artisan test`. Kegagalan format menghentikan seluruh gate, sehingga `composer lint` dijalankan lebih dulu.
- Sisi klien: `npm run types:check` dan `npm run check`. Berkas `components/ui/*` dan `routes|actions|wayfinder` dikecualikan.
- Feature test Pest berada di `tests/Feature`, satu berkas per FR atau per kelompok FR, memakai `RefreshDatabase`.
- Basis data pengujian adalah PostgreSQL (`aygkos_testing`) agar check constraint dan tipe `uuid` benar-benar diuji, bukan SQLite `:memory:`.
- Setiap perbaikan bug meninggalkan satu tes regresi.
- Pengujian otorisasi mencakup jalur ditolak: peran salah, bukan pemilik, akun nonaktif, kost berstatus ditangguhkan.
- Pengujian berkas memakai `Storage::fake()`; tidak ada berkas nyata yang tertulis saat tes.
- Pengujian halaman Inertia memakai `assertInertia` dengan pemeriksaan berkas halaman (`config/inertia.php` `ensure_pages_exist`).

---

## 12. Status Implementasi

Dokumen ini menggambarkan target. Keadaan kode pada saat dokumen ditulis:

| Bagian                                   | Status                                                                                     | Rujukan                          |
| ---------------------------------------- | ------------------------------------------------------------------------------------------ | -------------------------------- |
| Scaffold Laravel + Inertia + React + TS   | Selesai                                                                                    | `app/`, `resources/js/`          |
| Autentikasi Fortify (login, registrasi, reset, verifikasi email) | Selesai, belum dipecah per peran                                    | `config/fortify.php`             |
| Halaman profil, keamanan, tampilan        | Selesai                                                                                    | `routes/settings.php`            |
| Skema domain, model, relasi, seeder peran | Belum dimigrasikan; masih `User` saja dengan `id` bigint                                   | `openspec/changes/erd-schema-and-models` |
| Middleware `EnsureRole`, `EnsureAccountVerified` | Belum ada                                                                           | §7                               |
| Policy, Action, Form Request domain       | Belum ada                                                                                  | §3                               |
| Peta, autocomplete, penyimpanan berkas, OTP | Primitif tersedia (komponen, `OtpSender`, `PrivateFileUrl`, `config/uploads.php`), belum tersambung ke fitur | `resources/js/components/map`, `app/Services/Otp`, `app/Support` |
| Pengarahan login per peran (`LoginResponse`) | Belum ada                                                                                | §7.4                             |
| Basis data pengujian PostgreSQL           | Belum; `phpunit.xml` masih SQLite `:memory:`                                                | `openspec/changes/erd-schema-and-models` |

Perubahan pada dokumen ini mengikuti aturan yang sama dengan dokumen lain di `docs/`: versi terkini pada berkas ini, versi lama beku di `docs/Architecture/logs/` bila pemilik dokumen memutuskan pengelolaan versi diterapkan.

---

## 13. Keputusan Terkait

| Keputusan                                                        | Rujukan                                                       |
| ---------------------------------------------------------------- | ------------------------------------------------------------- |
| Entitas, kolom, relasi, enumerasi, constraint, indeks             | `docs/ERD/erd.md` (V003)                                       |
| Perilaku fungsional per aktor dan prioritas                       | `docs/FunctionalRequirment/functional_requirment.md`           |
| Skema domain, UUID7, kebijakan timestamp, basis data pengujian    | `openspec/changes/erd-schema-and-models/design.md`             |
| Lingkungan pengembangan, penyimpanan berkas, peta, pengiriman pesan | `openspec/changes/archive/2026-11-17-setup-dev-env-and-integrations/specs/platform/` |
| Batas unggahan berkas                                             | `config/uploads.php`                                           |
| Parameter OTP                                                     | `config/otp.php`                                               |
| Fitur Fortify yang aktif                                          | `config/fortify.php`                                           |