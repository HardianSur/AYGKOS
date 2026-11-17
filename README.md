# AYGKOS

Marketplace kost berbasis Laravel 13, Inertia v3, React 19, dan Tailwind v4.

## Penyiapan lingkungan pengembangan

### Prasyarat

- PHP 8.4+ dengan ekstensi `pdo_pgsql`, `mbstring`, `xml`, dan `curl`
- Composer
- Node.js dan npm (lockfile yang dipakai adalah `package-lock.json`)
- Docker dengan plugin Compose. Podman juga dapat dipakai (lihat catatan di bawah)

### Langkah

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

3. Isi kredensial penyedia di `.env` (lihat bagian **Kredensial**):
   `GEOAPIFY_API_KEY`, `OTP_DRIVER`, `FONNTE_TOKEN`, dan `MAIL_*`.

4. Siapkan layanan data. Pilih salah satu — jangan menjalankan keduanya
   bersamaan tanpa penyesuaian port:

   **lerd (digunakan saat ini):** gunakan PostgreSQL bawaan lerd. Dari dalam
   container PHP-FPM, host layanan bukan `127.0.0.1` melainkan
   `lerd-postgres`. Nilai ini sudah disimpan di `.env.lerd_override`
   (gitignored) agar tidak ditimpa saat `lerd env` dijalankan ulang:

   ```bash
   lerd env
   lerd db list
   ```

   **Docker Compose:**

   ```bash
   docker compose up -d
   ```

   Penangkap email lokal bersifat opsional pada profil terpisah:

   ```bash
   docker compose --profile mail up -d
   ```

   Antarmuka Mailpit dapat dibuka di `http://localhost:8025`.

5. Siapkan basis data dan tautan penyimpanan publik:

   ```bash
   php artisan config:clear
   php artisan migrate:fresh
   php artisan storage:link
   ```

6. Jalankan aplikasi dan aset front-end di dua terminal terpisah:

   ```bash
   php artisan serve
   npm run dev
   ```

   Aplikasi dapat dibuka di `http://localhost:8000`. Foto kost yang tersimpan
   pada disk `public` dapat diakses tanpa autentikasi melalui `/storage/...`.

### Catatan port dan konflik layanan

`compose.yaml` memetakan port standar `5432:5432`, `1025:1025`, dan
`8025:8025`. Port tersebut bentrok bila `lerd-postgres` / `lerd-mailpit`
juga aktif — jalankan hanya satu layanan PostgreSQL pada satu waktu. Bila
keduanya harus hidup bersama, ubah `ports` di `compose.yaml` **dan** `DB_PORT`
di `.env` secara bersamaan (misalnya host 5433 → container 5432).

### Catatan Podman / Lerd

`podman compose` memerlukan socket Podman aktif dan provider Compose terpasang:

```bash
systemctl --user enable --now podman.socket
loginctl enable-linger "$USER"
podman compose up -d
```

Untuk lerd, konfigurasi yang terbukti bekerja dari container FPM adalah:

```dotenv
APP_URL=http://aygkos.test
DB_CONNECTION=pgsql
DB_HOST=lerd-postgres
DB_PORT=5432
DB_DATABASE=aygkos
DB_USERNAME=postgres
DB_PASSWORD=lerd
```

`DB_HOST=127.0.0.1` hanya benar bila PHP dijalankan langsung di host. Di dalam
kontainer FPM, `127.0.0.1` adalah loopback kontainer itu sendiri, sehingga
koneksi ke PostgreSQL ditolak. `host.containers.internal` juga tidak membantu
untuk layanan lerd karena PostgreSQL-nya hanya terikat di `127.0.0.1` host.

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

## Pemeriksaan

```bash
composer test
npm run types:check
npm run build
```

`composer test` menjalankan Pint, PHPStan, dan seluruh tes otomatis.
Batas unggahan (foto kost 3 MB, dokumen verifikasi dan lampiran aduan 5 MB,
maksimal 3 lampiran) serta parameter OTP berada di `config/uploads.php` dan
`config/otp.php`. Pastikan `upload_max_filesize` dan `post_max_size` PHP lebih
besar dari batas terbesar bila menjalankan `php artisan serve`.
