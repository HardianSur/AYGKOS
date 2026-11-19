# local-dev-environment Specification

## Purpose

Menjamin seluruh anggota tim menjalankan aplikasi dengan layanan basis data dan konfigurasi yang sama, sehingga perilaku query, migrasi, dan pengujian identik di setiap mesin.

## Requirements

### Requirement: Layanan basis data seragam

Lingkungan pengembangan SHALL menyediakan PostgreSQL versi mayor 16 sebagai satu-satunya basis data aplikasi, dijalankan sebagai layanan container melalui satu berkas Compose di akar repositori.

#### Scenario: Menjalankan layanan basis data

- **WHEN** pengembang menjalankan `docker compose up -d` pada akar repositori
- **THEN** container PostgreSQL 16 aktif, menerima koneksi pada port 5432 host, dan data tersimpan pada volume bernama sehingga bertahan setelah container dihentikan

#### Scenario: Menunggu basis data siap

- **WHEN** container PostgreSQL baru dibuat
- **THEN** container menyatakan sehat setelah perintah `pg_isready` berhasil terhadap basis data aplikasi

#### Scenario: Anggota tim memakai Podman

- **WHEN** pengembang menjalankan berkas Compose yang sama melalui `podman compose` dengan socket Podman aktif dan provider Compose terpasang
- **THEN** layanan basis data berjalan dengan konfigurasi, port, dan volume yang sama seperti pada Docker

### Requirement: Konfigurasi koneksi basis data

Aplikasi SHALL terhubung ke PostgreSQL melalui variabel lingkungan `DB_CONNECTION=pgsql` beserta `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`, dan SHALL NOT menggunakan SQLite pada lingkungan pengembangan.

#### Scenario: Migrasi pada basis data baru

- **WHEN** pengembang menjalankan `php artisan migrate` setelah layanan basis data aktif
- **THEN** seluruh migrasi berhasil tanpa galat dan tabel terbentuk pada basis data PostgreSQL

#### Scenario: Variabel lingkungan tidak di-commit

- **WHEN** repositori diperiksa dengan `git status`
- **THEN** berkas `.env` tidak termasuk berkas yang dilacak, sedangkan `.env.example` memuat seluruh kunci yang dibutuhkan dengan nilai contoh

#### Scenario: Kredensial basis data tidak valid

- **WHEN** aplikasi dijalankan dengan kredensial basis data yang salah
- **THEN** aplikasi menampilkan galat koneksi basis data dan tidak diam-diam beralih ke basis data lain

### Requirement: Layanan surat elektronik lokal

Lingkungan pengembangan SHALL menyediakan penangkap surat lokal agar email verifikasi dan reset password dapat diperiksa tanpa pengiriman nyata, dan SHALL memungkinkan pengembang beralih ke pengiriman nyata hanya dengan mengubah variabel lingkungan.

#### Scenario: Memeriksa email verifikasi

- **WHEN** pengembang mendaftarkan akun Customer dengan penangkap surat lokal aktif
- **THEN** email verifikasi dapat dibaca pada antarmuka penangkap surat tanpa keluar dari mesin pengembang

#### Scenario: Beralih ke pengiriman nyata

- **WHEN** variabel lingkungan pengirim email diubah menjadi penyedia SMTP nyata
- **THEN** aplikasi mengirim email melalui penyedia tersebut tanpa perubahan kode

### Requirement: Dokumentasi penyiapan lingkungan

Repositori SHALL memuat petunjuk penyiapan lingkungan yang mencakup prasyarat, perintah menjalankan layanan, perintah migrasi, dan perintah menjalankan aplikasi serta aset front-end.

#### Scenario: Anggota tim baru menyiapkan proyek

- **WHEN** anggota tim baru mengikuti petunjuk penyiapan dari repositori yang baru dikloning
- **THEN** aplikasi dapat diakses pada peramban tanpa langkah tambahan yang tidak terdokumentasi
