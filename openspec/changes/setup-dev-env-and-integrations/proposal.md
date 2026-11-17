# Proposal

## Why

Repositori masih memakai SQLite, belum punya `compose.yaml`, dan belum ada keputusan tertulis mengenai penyimpanan berkas, peta, pengiriman email, serta pengiriman OTP WhatsApp. Tanpa keputusan ini, empat anggota tim akan menyiapkan lingkungan yang berbeda-beda dan fitur FR-AUT-05, FR-AUT-06, FR-KOS-01, FR-SRC-07, dan FR-ADU-02 tidak dapat diimplementasikan secara konsisten. Seluruh opsi yang dipilih di sini harus tetap gratis (Rp 0) selama pengembangan dan demo.

## What Changes

- Menambahkan `compose.yaml` berisi PostgreSQL 16 dan Mailpit (opsional), dengan named volume dan healthcheck. Docker adalah satu-satunya kontrak lingkungan; Podman/Lerd tidak diwajibkan.
- Mengubah `.env` dan `.env.example` dari `DB_CONNECTION=sqlite` menjadi `pgsql` beserta variabel `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- Menetapkan kebijakan dua disk: foto kost pada disk `public`, dokumen verifikasi kost dan lampiran aduan pada disk `local` (privat) yang disajikan melalui temporary URL.
- Menetapkan Leaflet + Geoapify sebagai satu-satunya penyedia peta, geocoding, dan autocomplete, dengan kunci API di `.env`.
- Menetapkan Gmail SMTP (App Password) sebagai pengirim email sungguhan, dengan `MAIL_MAILER=log` sebagai fallback darurat.
- Menetapkan antarmuka `OtpSender` dengan driver `log` dan `fonnte`, sehingga pengiriman OTP WhatsApp dapat diganti tanpa mengubah alur verifikasi.
- Menetapkan batas ukuran dan tipe berkas untuk foto kost, dokumen verifikasi, dan lampiran aduan.
- Tidak ada tabel baru. Seluruh parameter (radius, batas lampiran, parameter OTP) tetap berada di konfigurasi aplikasi sesuai `.docs/erd.md`.

## Capabilities

### New Capabilities

- `platform/local-dev-environment`: lingkungan pengembangan lokal yang seragam untuk seluruh anggota tim, mencakup layanan basis data, variabel koneksi, dan langkah menjalankan aplikasi.
- `platform/file-storage`: kebijakan penyimpanan dan penyajian berkas unggahan, mencakup pemisahan berkas publik dan privat, pembatasan tipe dan ukuran, serta penamaan berkas.
- `platform/map-geocoding`: integrasi peta dan geocoding untuk pencarian berbasis lokasi, mencakup konversi alamat menjadi koordinat, autocomplete, penyaringan radius, dan penandaan titik pada peta.
- `platform/outbound-messaging`: pengiriman pesan keluar sistem ke pengguna, mencakup email verifikasi dan reset password serta kode OTP WhatsApp, termasuk perilaku fallback saat penyedia tidak tersedia.

### Modified Capabilities

Tidak ada. Repositori belum memiliki spec utama (`openspec/specs/` kosong), sehingga seluruh capability di atas dibuat baru.

## Impact

- **Berkas baru**: `compose.yaml`, `README.md` (bagian lingkungan pengembangan), `app/Contracts/OtpSender.php`, `app/Services/Otp/LogOtpSender.php`, `app/Services/Otp/FonnteOtpSender.php`, `config/otp.php`, `config/services.php` (kunci Geoapify), berkas uji terkait.
- **Berkas diubah**: `.env`, `.env.example`.
- **Berkas yang TIDAK diubah**: `.docs/functional_requirment.md`, `.docs/erd.md`, `config/filesystems.php` (disk `local` dan `public` sudah memenuhi kebutuhan).
- **Dependensi baru**: `leaflet` (npm), `@types/leaflet` (dev). Tidak ada dependensi PHP baru.
- **Layanan pihak ketiga**: Geoapify (kunci API gratis, 3.000 kredit/hari), Gmail SMTP (App Password, ±500 email/hari), Fonnte (token gratis untuk pengembangan). Semua kredensial masuk `.env` dan tidak di-commit.
- **Risiko**: gateway WhatsApp tidak resmi dapat memblokir nomor secara permanen; driver `log` disediakan sebagai saklar darurat menjelang demo. Akun Gmail memerlukan verifikasi dua langkah sebelum App Password dapat dibuat.
- **Di luar cakupan**: hosting gratis, penyimpanan objek S3/R2/MinIO, PostGIS, payment gateway, chat, dan booking.