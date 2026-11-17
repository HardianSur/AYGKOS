# Design

## Context

Repositori adalah Laravel 13 + Inertia v3 + React 19 dengan Fortify terpasang. Keadaan saat ini: `DB_CONNECTION=sqlite`, `MAIL_MAILER=log`, `FILESYSTEM_DISK=local`, tidak ada `compose.yaml`, dan `openspec/specs/` masih kosong. `config/filesystems.php` sudah mendefinisikan disk `local` (`storage/app/private`, `serve => true`) dan `public` (`storage/app/public`), jadi tidak perlu disk baru. `composer.json` sudah memuat `laravel/sail` sebagai dev dependency. Lihat `proposal.md` untuk motivasi.

## Goals / Non-Goals

**Goals**

- Satu berkas `compose.yaml` yang menjadi kontrak lingkungan untuk Docker maupun Podman.
- Dua tingkat visibilitas berkas yang ditegakkan oleh framework, bukan oleh kode pemeriksaan manual.
- Pengiriman OTP yang dapat diganti penyedianya melalui satu antarmuka, dengan driver `log` yang selalu tersedia.
- Semua kredensial hanya dari `.env`.

**Non-Goals**

- Kontainerisasi aplikasi PHP/Node. Aplikasi berjalan di host; hanya layanan data yang berkontainer.
- Abstraksi penyimpanan objek (S3/R2/MinIO) atau disk adapter kustom.
- PostGIS atau perhitungan jarak berbasis indeks spasial.
- Antrean pekerja dan penjadwal untuk pengiriman pesan; pengiriman berjalan sinkron pada request.
- Antarmuka penyedia email yang dapat diganti-ganti (cukup SMTP, yang sudah abstrak di Laravel).

## Decisions

### 1. Compose hanya untuk layanan data

`compose.yaml` berisi `pgsql` (postgres:16-alpine) dan `mailpit` (opsional, profil terpisah). Aplikasi, Vite, dan artisan dijalankan di host.

**Alternatif ditolak:** Sail penuh dengan container `laravel.test`. Alasan: butuh bind mount kode ke container, dan bind mount inilah yang paling rapuh di Podman rootless (pemetaan UID berbeda; wajib `userns_mode: keep-id` atau `:z`). Dengan aplikasi di host, masalah itu tidak pernah muncul. Efek samping: `DB_HOST=127.0.0.1` identik di Docker dan Podman karena port di-publish ke host.

**Catatan operasional Podman:** `podman compose` adalah pembungkus yang memanggil provider eksternal dan memerlukan socket Podman aktif. Wajib `systemctl --user enable --now podman.socket` plus `loginctl enable-linger $USER`. Bila Postgres dari lerd juga aktif, port 5432 bentrok — jalankan salah satu.

### 2. Postgres 16-alpine, versi dipin

Pin `postgres:16-alpine`, bukan `postgres:latest`. Alasan: Podman/Lerd memakai satu instance global yang versinya tidak dapat dipin per proyek; memilih 16 secara eksplisit membuat Docker dan Lerd bertemu di versi mayor yang sama. Named volume `pgdata`, bukan bind mount, agar tidak tersentuh masalah kepemilikan berkas.

### 3. Disk `public` dan `local` dipakai apa adanya

- Foto kost → disk `public` (`storage/app/public`), disajikan lewat symlink `/storage`.
- Dokumen verifikasi kost dan lampiran aduan → disk `local` (`storage/app/private`).

`local` sudah memiliki `serve => true`, sehingga framework mendaftarkan route bernama `storage.local` yang menolak permintaan tanpa tanda tangan sah (route hanya menerima `hasValidRelativeSignature()`). **Penting:** adapter `local` tidak mendukung `temporaryUrl()` — method itu melempar `RuntimeException`. Jadi tautan sementara dibentuk dengan `URL::temporarySignedRoute('storage.local', now()->addMinutes(10), ['path' => $path])`, bukan `Storage::disk('local')->temporaryUrl()`.

**Alternatif ditolak:** controller unduhan khusus per model. Alasan: `serve` sudah menyediakan validasi tanda tangan, pencegahan path traversal, dan header `Content-Security-Policy`. Controller buatan sendiri harus mengulang semuanya.

**Alternatif ditolak:** MinIO/RustFS di Compose. Alasan: menambah satu layanan, satu kredensial, dan satu lapisan ketidakcocokan (endpoint, path-style) tanpa manfaat untuk demo lokal. Kolom basis data sudah menyimpan path, jadi migrasi ke S3 kelak hanya mengubah konfigurasi disk.

### 4. Validasi berkas di Form Request, bukan di model

Batas tipe dan ukuran didefinisikan sekali sebagai konfigurasi (`config/uploads.php`) lalu dipakai aturan `mimes`/`max` pada Form Request masing-masing fitur. Alasan: pesan kesalahan per kolom sudah menjadi kebutuhan FR; menaruhnya di Form Request membuat pesan otomatis menempel pada kolom yang benar.

Nilai awal: foto kost maksimal 3 MB (`jpg,jpeg,png,webp`); dokumen verifikasi maksimal 5 MB (`jpg,jpeg,png,pdf`); lampiran aduan maksimal 5 MB per berkas dan maksimal 3 berkas (`jpg,jpeg,png,pdf`). Batas `upload_max_filesize` dan `post_max_size` PHP harus di atas nilai terbesar; kalau memakai `php artisan serve`, atur lewat `php.ini` atau naikkan batas agar konsisten.

### 5. Penghapusan berkas lewat event model

Berkas dihapus pada event `deleting`/`updated` model terkait, bukan di controller. Alasan: penghapusan kost juga terjadi lewat alur Admin dan seeder; menaruhnya di controller membuat jalur lain meninggalkan berkas yatim. Hapus berkas **setelah** transaksi basis data berhasil, dan jangan menggagalkan penghapusan data hanya karena berkas gagal dihapus — catat ke log.

### 6. Leaflet dimuat dinamis di klien

Leaflet mengakses `window`/`document` saat modul dievaluasi. Komponen peta harus diimpor dinamis dengan `ssr: false` dan hanya dirender setelah mount. Alasan: Inertia SSR dan prerender akan gagal dengan `ReferenceError: window is not defined`.

Tile, geocoding, dan autocomplete semuanya dari Geoapify (satu kunci). Kunci dibaca dari `config/services.php` (`services.geoapify.key` ← `GEOAPIFY_API_KEY`) dan dikirim ke halaman hanya melalui prop yang membutuhkannya — **jangan** pakai `VITE_` prefix, karena `VITE_` membuat nilainya tertanam di bundel JS dan tidak dapat diganti saat deploy. Untuk demo lokal, kunci tidak dibatasi HTTP referrer karena origin berbeda antara `localhost:8000`, `127.0.0.1:8000`, dan `<proyek>.test`.

### 7. Radius tanpa PostGIS

Kueri memakai bounding box pada indeks `(latitude, longitude)` untuk mempersempit kandidat, lalu rumus Haversine di SQL untuk jarak sebenarnya, dengan hasil diurutkan menaik. Alasan: `erde` sudah menetapkan indeks komposit tersebut dan melarang ketergantungan ekstensi spasial. Bounding box mencegah pemindaian penuh tabel. Haversine ditulis sebagai ekspresi SQL (`radians`, `sin`, `cos`, `acos` aman di PostgreSQL tanpa ekstensi tambahan).

### 8. `OtpSender` sebagai antarmuka, binding lewat config

```
App\Contracts\OtpSender          send(string $number, string $code): void
  ├── LogOtpSender               menulis kode ke log
  └── FonnteOtpSender            POST ke endpoint Fonnte
```

Binding dilakukan di service provider berdasarkan `config('otp.driver')` (`log|fonnte`). Alasan: antarmuka memisahkan alur verifikasi dari penyedia, dan driver `log` menjadi saklar darurat tanpa perubahan kode. Kode OTP **tidak** dikembalikan antarmuka; kode dibuat, di-hash, dan disimpan oleh pemanggil sehingga `OtpSender` murni transport.

**Alternatif ditolak:** notifikasi Laravel (`Notification` + channel WhatsApp kustom). Alasan: kanal kustom tetap memerlukan implementasi transport yang sama, ditambah lapisan `Notifiable` dan tabel `notifications` untuk pesan yang tidak perlu disimpan.

### 9. Parameter OTP di konfigurasi, bukan tabel

`config/otp.php`: masa berlaku 5 menit, maksimum 5 percobaan, jeda kirim ulang 60 detik, maksimum 3 pengiriman per jam per akun. Sesuai catatan `erd.md` baris 204. Kode disimpan sebagai `Hash::make` pada `whatsapp_verification.code_hash`, dikirim ke `target_number` (bukan `users.whatsapp_number`), sehingga alur perubahan nomor tidak menimpa nomor lama sebelum verifikasi berhasil.

### 10. Email: Gmail SMTP dengan fallback log

`MAIL_MAILER=smtp`, `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, `MAIL_SCHEME=tls`, kredensial dari App Password. Alasan: domain belum tersedia, dan Gmail adalah satu-satunya penyedia gratis yang mengirim ke penerima mana pun tanpa domain terverifikasi. `MAIL_MAILER=log` tetap valid sebagai fallback. Mailpit di Compose hanya untuk pengembangan lokal, tidak menuntut kredensial.

**Alternatif ditolak:** Resend free (menolak penerima selain pemilik akun tanpa domain terverifikasi), Brevo (menulis ulang `From:` menjadi `@brevosend.com`).

### 11. Konfigurasi yang wajib diubah pada `.env` dan `.env.example`

`DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE=aygkos`, `DB_USERNAME=aygkos`, `DB_PASSWORD`, `MAIL_*`, `GEOAPIFY_API_KEY`, `OTP_DRIVER=log`, `FONNTE_TOKEN`. `.env.example` memuat semua kunci dengan nilai kosong/placeholder yang aman, dan tetap `MAIL_MAILER=log` agar kloning baru tidak langsung gagal.

## Risks / Trade-offs

- **[Kode OTP bocor ke log]** Driver `log` menulis kode apa adanya untuk kenyamanan pengembangan → hanya aktif di `APP_ENV=local`; sebelum demo, `OTP_DRIVER` harus diset eksplisit dan log tidak boleh dibagikan.
- **[Nomor Fonnte diblokir permanen]** Gateway tidak resmi melanggar ToS → nomor sekunder khusus, bukan nomor pribadi; driver `log` sebagai rencana B; jangan memakai nomor itu pada data seed kontak kost, karena akan memutus alur FR-KTK-01 saat demo.
- **[Kuota Geoapify habis]** 3.000 kredit/hari, dan geocoding + autocomplete memakai kredit yang sama → debounce autocomplete minimal 400 ms, cache hasil geocoding per kueri pada session/cache, dan batalkan permintaan lama.
- **[Gmail membatasi ±500 email/hari]** volume demo kecil, tetapi uji kirim ulang yang berlebihan bisa menghabiskan kuota → App Password disimpan di akun khusus, bukan akun pribadi; pemakaian email diuji sekali, selanjutnya beralih ke Mailpit.
- **[App Password Gmail wajib 2FA dan mati saat password diganti]** → dicatat pada README; kegagalan muncul sebagai galat 535 yang mudah dikenali.
- **[Port 5432 bentrok dengan Postgres lerd atau Postgres host]** → dokumentasikan; bila bentrok, ubah `ports` di `compose.yaml` **dan** `DB_PORT` di `.env` secara bersamaan.
- **[Berkas privat tersaji bila tanda tangan bocor]** tanda tangan sementara 10 menit dan tidak dapat dicabut → jangan menaruh tautan di halaman publik, jangan log URL lengkap, dan gunakan masa berlaku pendek.
- **[Bind mount tidak dipakai]** tidak ada risiko kepemilikan berkas, tetapi perubahan pada `storage/` hanya terlihat di host — memang yang diinginkan.
- **[Haversine di SQL]** perhitungan per baris tidak terindeks → selalu sertakan bounding box; bila kelak volume besar, pertimbangkan PostGIS sebagai perubahan terpisah.

## Migration Plan

1. Tambahkan `compose.yaml` dan README; jalankan `docker compose up -d`; tunggu healthcheck.
2. Ubah `.env` (`pgsql` + kredensial) dan `.env.example`; jalankan `php artisan config:clear`.
3. `php artisan migrate:fresh` — SQLite tidak dimigrasikan. Repositori belum berisi data nyata, jadi tidak ada rollback data yang diperlukan.
4. `php artisan storage:link` untuk disk `public`.
5. Jalankan `php artisan test` untuk memastikan tidak ada tes yang masih mengandalkan SQLite.

**Rollback:** `docker compose down -v` menghapus volume; kembalikan `.env` ke SQLite bila benar-benar diperlukan. Tidak ada perubahan destruktif pada data yang dilacak repositori.

## Open Questions

Tidak ada. Nilai batas (masa berlaku OTP, jumlah percobaan, batas ukuran berkas) sudah ditetapkan di atas dan dapat disesuaikan tanpa mengubah spec.