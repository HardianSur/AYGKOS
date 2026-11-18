# Project Overview — AYGKOS

**Dokumen:** Project Overview
**Status:** Aktif
**Tujuan:** Memberikan gambaran umum mengenai sistem dan tujuan Project AYGKOS.

---

## 1. Overview Project

**AYGKOS** adalah platform marketplace kost berbasis web yang mempertemukan pencari kost dengan pemilik kost melalui satu kanal terpusat.
Platform ini memungkinkan Customer, Pemilik Kost, dan Admin untuk mengakses serta mengelola informasi kost sesuai dengan hak akses masing-masing.
AYGKOS berfokus pada pengelolaan **data kost, ketersediaan kamar, pencarian berbasis lokasi, verifikasi kost, dan penanganan aduan**.

---

## 2. Latar Belakang

Pencarian kost saat ini umumnya bergantung pada informasi yang tersebar: unggahan di media sosial, grup perpesanan, papan pengumuman, atau rekomendasi lisan. Informasi semacam ini sulit diverifikasi, cepat kedaluwarsa, tidak memuat ketersediaan kamar yang mutakhir, dan tidak dapat disaring berdasarkan lokasi, harga, tipe, maupun fasilitas. Pemilik kost juga tidak memiliki kanal publikasi yang dapat menampilkan daftar kamar dan status ketersediaannya secara terkini, sementara pencari kost tidak memiliki jalur yang jelas untuk menyampaikan keluhan atas informasi yang tidak sesuai.

AYGKOS dikembangkan sebagai sistem terpusat yang menyediakan akses terhadap informasi kost berdasarkan peran pengguna.
Melalui sistem ini, Customer dapat mencari kost berdasarkan lokasi dan penyaring yang relevan, melihat detail kost beserta kamar yang tersedia, lalu menghubungi Pemilik Kost melalui WhatsApp. Pemilik Kost dapat mendaftarkan kost, mengelola kamar dan status ketersediaannya, serta memantau status verifikasi kost yang diajukan. Sementara itu, Admin bertanggung jawab memverifikasi kelayakan kost, mengelola status akun pengguna, menangani aduan, dan memantau laporan sistem.

---

## 3. Tujuan Project

AYGKOS dikembangkan dengan tujuan:

- Menyediakan informasi kost secara terpusat dalam satu platform.
- Memudahkan Customer dalam mencari kost berdasarkan lokasi, harga, tipe, ketersediaan kamar, dan fasilitas.
- Memudahkan Customer dalam melihat detail kost, galeri foto, peraturan, serta kamar yang tersedia.
- Memudahkan Customer dalam menghubungi Pemilik Kost melalui WhatsApp dengan pesan yang telah terisi konteks kost dan kamar.
- Memudahkan Pemilik Kost dalam mendaftarkan dan mengubah data kost.
- Memudahkan Pemilik Kost dalam mengelola kamar beserta status ketersediaannya.
- Memudahkan Admin dalam memverifikasi, menolak, dan menangguhkan kost.
- Memudahkan Admin dalam mengelola status akun pengguna.
- Memudahkan Admin dalam menangani aduan dan memantau laporan sistem.
- Menyediakan sistem dengan hak akses berdasarkan peran pengguna.
- Menjaga kerahasiaan dokumen verifikasi kost dan lampiran aduan sebagai berkas privat.

---

## 4. Output yang Diharapkan

Hasil yang diharapkan dari pengembangan AYGKOS adalah sebuah platform marketplace kost yang dapat:

- Menyediakan katalog kost yang hanya menampilkan kost berstatus disetujui dan pemiliknya berstatus aktif.
- Menyediakan pencarian kost dengan penyaring harga, tipe, ketersediaan kamar, dan fasilitas, serta pengurutan berdasarkan jarak dari lokasi yang dipilih.
- Menyediakan halaman detail kost yang memuat lokasi pada peta, fasilitas, peraturan, galeri foto, dan daftar kamar.
- Menyediakan jalur kontak Pemilik Kost melalui tautan WhatsApp berisi pesan yang telah terisi konteks.
- Menyediakan pengelolaan kost dan kamar oleh Pemilik Kost, termasuk pengajuan ulang kost yang ditolak.
- Menyediakan verifikasi kost oleh Admin dengan alasan penolakan atau penangguhan yang tercatat.
- Menyediakan pengelolaan status akun pengguna oleh Admin.
- Menyediakan pengajuan aduan oleh Customer dan Pemilik Kost beserta penanganannya oleh Admin.
- Menyediakan notifikasi in-app atas perubahan status kost dan aduan.
- Menerapkan pembatasan akses berdasarkan peran pengguna, diverifikasi di sisi server.
- Menyediakan penyajian dokumen verifikasi dan lampiran aduan melalui tautan bertanda tangan yang berlaku terbatas.

---

## 5. Project Specification

| Komponen      | Teknologi / Spesifikasi                                     | Deskripsi                                                                                                                                                              |
| ------------- | ----------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Nama Proyek   | AYGKOS                                                      | Perangkat lunak berbasis web sebagai marketplace kost                                                                                                                  |
| Arsitektur    | Monolith                                                    | Seluruh komponen utama aplikasi berjalan pada satu proses Laravel; tidak ada backend terpisah maupun API publik berbasis token.                                        |
| Frontend      | React 19, TypeScript, Inertia.js v3, Tailwind CSS v4        | Digunakan untuk membangun antarmuka pengguna. Komponen shadcn/ui dan Radix UI sebagai dasar tampilan, Leaflet dengan tile Geoapify untuk peta.                          |
| Backend       | Laravel 13 (PHP 8.3+)                                       | Menangani logika bisnis, pemrosesan permintaan, autentikasi, otorisasi, serta komunikasi antara frontend dan database.                                                  |
| Database      | PostgreSQL 16                                               | Digunakan untuk menyimpan dan mengelola data. Identitas memakai UUID versi 7, integritas ditegakkan melalui foreign key dan check constraint.                          |
| Deployment    | PHP-FPM + Nginx                                             | Aplikasi disajikan melalui PHP-FPM di balik Nginx. Lingkungan pengembangan lokal memakai lerd, atau Docker Compose (`compose.yaml`) untuk PostgreSQL 16 dan Mailpit.     |
| Platform      | Web (browser)                                               | Aplikasi diakses melalui browser pada desktop maupun perangkat mobile. Aplikasi tidak menyediakan manifest PWA maupun service worker, sehingga tidak dapat dipasang.   |
| Layout        | Web responsif                                               | Tata letak menyesuaikan ukuran layar: navigasi sidebar pada layar lebar dan menu lipat (sheet) pada layar sempit.                                                       |
| Maximum Width | Tidak dibatasi secara global                                | Konten utama memenuhi lebar layar. Batas lebar diterapkan per halaman: form autentikasi `max-w-sm` s.d. `max-w-md`, halaman pengaturan `max-w-xl` s.d. `max-w-2xl`.      |

---

## 6. Software Architecture Characteristics

| Komponen        | Fokus Utama                                                                                                                       |
| --------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| Performance     | Kecepatan sistem dalam memproses permintaan dan menampilkan informasi. Pencarian lokasi memakai indeks komposit `(latitude, longitude)` dengan penyaringan bounding box dan perhitungan jarak Haversine, hasil dipaginasi di server, dan relasi dimuat secara eager load untuk menghindari kueri berulang. |
| Security        | Kata sandi di-hash dengan `bcrypt`, autentikasi ditangani Laravel Fortify, sesi disimpan di database, otorisasi ditegakkan middleware peran dan Policy, dokumen verifikasi serta lampiran aduan disajikan melalui route bertanda tangan berumur 10 menit, dan pengiriman OTP WhatsApp dibatasi laju. |
| Maintainability | Kemudahan dalam pemeliharaan dan perbaikan sistem. Batas layer dijaga melalui Controller, Form Request, Policy, dan Action; gaya kode diseragamkan dengan Pint, tipe diperiksa PHPStan level 7 dan TypeScript, serta URL route dibangkitkan Wayfinder agar tidak ditulis manual. |
| Reliability     | Keandalan sistem dalam menjalankan fungsi secara konsisten. Operasi yang menyentuh lebih dari satu tabel dijalankan dalam transaksi, integritas dijaga foreign key dan check constraint, penghapusan data memakai soft delete, dan notifikasi disimpan persisten. |
| Testability     | Kemudahan sistem untuk diuji guna memastikan setiap fungsi berjalan sesuai kebutuhan. Pengujian memakai Pest dengan `RefreshDatabase` dan `Storage::fake()`, mencakup jalur otorisasi yang ditolak, serta dijalankan terhadap PostgreSQL agar constraint dan tipe UUID benar-benar teruji. |

---

## 7. Referensi

| Dokumen                     | Link                                                                     |
| --------------------------- | ------------------------------------------------------------------------ |
| Index Dokumentasi           | [README](../README.md)                                                   |
| Arsitektur Sistem           | [ARCHITECTURE](ARCHITECTURE.md)                                          |
| Entity Relationship Diagram | [ERD](ERD/erd.md)                                                        |
| Functional Requirement      | [FUNCTIONAL REQUIREMENT](FunctionalRequirment/functional_requirment.md)  |