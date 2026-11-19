# AYGKOS

Marketplace kost berbasis Laravel 13, Inertia v3, React 19, dan Tailwind v4
dengan PostgreSQL.

## Prasyarat

- PHP 8.4.1 atau lebih baru dengan ekstensi `pdo_pgsql`, `mbstring`, `xml`, dan
  `curl`
- Composer 2
- Node.js 22 dan npm (lockfile yang dipakai adalah `package-lock.json`)
- PostgreSQL 16

## Penyiapan

1. Pasang dependensi:

   ```bash
   composer install
   npm install
   ```

2. Salin contoh konfigurasi dan buat kunci aplikasi:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Siapkan PostgreSQL. Buat role dan database sesuai nilai `DB_USERNAME` dan
   `DB_DATABASE` pada `.env`, lalu isi `DB_PASSWORD`:

   ```bash
   sudo -u postgres psql -c "CREATE ROLE aygkos LOGIN PASSWORD 'ganti-sandi-ini';"
   sudo -u postgres psql -c "CREATE DATABASE aygkos OWNER aygkos;"
   ```

   Sesuaikan `DB_HOST`, `DB_PORT`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`
   bila memakai role atau port lain.

4. Isi kredensial penyedia bila diperlukan (lihat bagian **Kredensial**).
   Untuk pengembangan lokal, `OTP_DRIVER=log` dan `MAIL_MAILER=log` sudah cukup.

5. Jalankan migrasi dan seeder:

   ```bash
   php artisan migrate --seed
   ```

   Seeder membuat peran `customer`, `pemilik_kost`, dan `admin`, serta satu
   akun uji `test@example.com` dengan kata sandi `password`.

6. Tautkan penyimpanan publik untuk foto kost:

   ```bash
   php artisan storage:link
   ```

7. Jalankan aplikasi:

   ```bash
   composer dev
   ```

   Perintah ini menjalankan server, antrean, log, dan Vite sekaligus. Aplikasi
   dapat dibuka di `http://localhost:8000`.

   Bila ingin menjalankan terpisah, gunakan `php artisan serve` dan
   `npm run dev` pada dua terminal.

## Kredensial

Seluruh kredensial dibaca **hanya dari `.env`**. Berkas `.env` tidak dilacak
oleh Git dan tidak boleh di-commit; `.env.example` hanya berisi placeholder.

| Variabel | Kegunaan |
|---|---|
| `DB_*` | Koneksi PostgreSQL (`DB_CONNECTION=pgsql`) |
| `GEOAPIFY_API_KEY` | Tile peta, geocoding, dan autocomplete lokasi (Leaflet) |
| `OTP_DRIVER` | `log` untuk pengembangan, `fonnte` untuk WhatsApp |
| `FONNTE_TOKEN` | Token gateway WhatsApp Fonnte |
| `MAIL_*` | SMTP Gmail (`MAIL_MAILER=smtp`, port 587, `MAIL_SCHEME=tls`) |

Catatan pengirim pesan:

- Kode OTP dengan driver `log` ditulis ke log aplikasi. Mode ini hanya untuk
  pengembangan; sebelum demo, tetapkan `OTP_DRIVER` secara eksplisit dan jangan
  membagikan log.
- Jika `OTP_DRIVER=fonnte` tetapi `FONNTE_TOKEN` kosong, aplikasi otomatis
  jatuh ke driver `log` dan mencatat peringatan.
- `MAIL_MAILER=log` pada `.env.example` adalah nilai aman untuk kloning baru.
  Gmail SMTP memerlukan akun dengan verifikasi dua langkah dan App Password.
  App Password berhenti berlaku bila kata sandi akun diganti (galat 535).
- Untuk mengirim email nyata cukup ubah variabel `MAIL_*`; tidak ada perubahan
  kode yang diperlukan.

## Basis data pengujian

Tes memakai database terpisah `aygkos_testing` (lihat `phpunit.xml`). Buat
sekali saja:

```bash
sudo -u postgres psql -c "CREATE DATABASE aygkos_testing OWNER aygkos;"
```

## Pemeriksaan

```bash
composer test       # Pint, PHPStan, dan seluruh tes
composer ci:check   # npm run check + TypeScript + composer test
npm run build
```

`composer test` membutuhkan PostgreSQL aktif dan database `aygkos_testing`
sudah dibuat. Batas unggahan (foto kost 3 MB, dokumen verifikasi dan lampiran
aduan 5 MB, maksimal 3 lampiran) serta parameter OTP berada di
`config/uploads.php` dan `config/otp.php`. Pastikan `upload_max_filesize` dan
`post_max_size` PHP lebih besar dari batas terbesar.

## Catatan pengembangan

- Foto kost pada disk `public` dapat diakses tanpa autentikasi melalui
  `/storage/...`. Dokumen verifikasi dan lampiran aduan bersifat privat dan
  diakses lewat tautan sementara dari `app/Support/PrivateFileUrl.php`.
- `resources/js/{actions,routes,wayfinder}/` adalah hasil generate Wayfinder
  dan tidak dilacak Git. Berkas tersebut dibuat otomatis oleh plugin Vite saat
  `npm run dev` atau `npm run build`.
- Panduan kontribusi dan konvensi kode ada di `AGENTS.md`. Spesifikasi fitur ada
  di `docs/` dan `openspec/`.
