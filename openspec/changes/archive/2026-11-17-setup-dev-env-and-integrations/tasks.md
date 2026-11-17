# Tasks

## 1. Lingkungan Basis Data

- [x] 1.1 Tambahkan `compose.yaml` di akar repositori berisi layanan `pgsql` (`postgres:16-alpine`), `POSTGRES_DB=aygkos`, `POSTGRES_USER=aygkos`, `POSTGRES_PASSWORD` dari variabel, `ports: 5432:5432`, named volume `pgdata`, dan healthcheck `pg_isready`; verifikasi dengan `docker compose config` yang mengeluarkan YAML tanpa galat, lalu `docker compose up -d` dan `docker compose ps` menunjukkan status `healthy`
- [x] 1.2 Tambahkan layanan `mailpit` di `compose.yaml` pada profil terpisah (port 1025 dan 8025) tanpa volume; verifikasi `docker compose --profile mail up -d` berjalan dan antarmuka `http://localhost:8025` dapat dibuka
- [x] 1.3 Ubah `.env`: `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE=aygkos`, `DB_USERNAME=aygkos`, `DB_PASSWORD`; verifikasi `php artisan db:show` melaporkan driver `pgsql` dan koneksi berhasil
- [x] 1.4 Ubah `.env.example` agar memuat seluruh kunci yang sama dengan placeholder aman dan tetap `MAIL_MAILER=log`; verifikasi `diff <(grep -o '^[A-Z_]*' .env | sort) <(grep -o '^[A-Z_]*' .env.example | sort)` tidak menampilkan kunci yang hanya ada di salah satu berkas
- [x] 1.5 Jalankan `php artisan migrate:fresh` terhadap PostgreSQL dan pastikan seluruh migrasi berhasil; verifikasi dengan `php artisan migrate:status` yang menampilkan semua migrasi berstatus `Ran`

## 2. Penyimpanan Berkas

- [x] 2.1 Tambahkan `config/uploads.php` berisi batas per jenis berkas (foto kost 3 MB, dokumen verifikasi 5 MB, lampiran aduan 5 MB dan maksimum 3 berkas) beserta daftar ekstensi yang diizinkan; verifikasi `php artisan tinker --execute="dump(config('uploads'));"` menampilkan struktur lengkap
- [x] 2.2 Tambahkan helper pembentuk tautan berkas privat memakai `URL::temporarySignedRoute('storage.local', ...)` dengan masa berlaku 10 menit; verifikasi dengan uji fitur yang memastikan URL hasilnya mengandung parameter `signature` dan permintaan tanpa tanda tangan menghasilkan 403
- [x] 2.3 Tambahkan Form Request validasi unggahan untuk foto kost, dokumen verifikasi, dan lampiran aduan yang membaca batas dari `config('uploads')`; verifikasi dengan uji fitur yang mengirim berkas melebihi batas dan bertipe tidak diizinkan, keduanya menghasilkan galat validasi pada kolom yang benar
- [ ] 2.4 Tambahkan penghapusan berkas pada event model `kost`, `kost_photo`, dan `complaint_attachment` (berkas dihapus setelah data tersimpan/terhapus, kegagalan dihapus dicatat ke log tanpa menggagalkan operasi); verifikasi dengan uji fitur yang menghapus kost memakai `Storage::fake` dan memastikan berkas terkait hilang
- [x] 2.5 Pastikan `php artisan storage:link` dijalankan pada README dan verifikasi foto pada disk `public` dapat diakses lewat `/storage/...` tanpa autentikasi

## 3. Peta dan Geocoding

- [x] 3.1 Tambahkan `leaflet` dan `@types/leaflet` ke `package.json`, lalu jalankan pemasangan; verifikasi `npm run types:check` lulus tanpa galat
- [x] 3.2 Tambahkan `GEOAPIFY_API_KEY` ke `.env` dan `.env.example`, serta entri `geoapify.key` di `config/services.php`; verifikasi `php artisan tinker --execute="dump(config('services.geoapify.key') !== null);"` mengembalikan `true`
- [x] 3.3 Buat komponen peta React yang memuat Leaflet secara dinamis dan hanya dirender di klien, dipakai untuk menampilkan marker hasil pencarian; verifikasi halaman yang memuat peta dapat dirender tanpa galat `window is not defined` pada `npm run build`
- [ ] 3.4 Buat komponen pemilih titik koordinat untuk form kost dengan validasi rentang lintang/bujur dan wilayah Indonesia; verifikasi dengan uji fitur yang menolak koordinat di luar rentang dan menerima koordinat valid
- [x] 3.5 Buat komponen input lokasi dengan autocomplete ber-debounce minimal 400 ms; verifikasi secara manual bahwa mengetik cepat hanya menghasilkan satu permintaan dalam log jaringan peramban
- [ ] 3.6 Implementasikan pencarian radius memakai bounding box pada `(latitude, longitude)` dilanjutkan Haversine di SQL, beserta pengurutan jarak; verifikasi dengan uji fitur yang memastikan kost di luar 10 km tidak muncul dan urutan hasil menaik berdasarkan jarak

## 4. Pengiriman Pesan Keluar

- [x] 4.1 Tambahkan `config/otp.php` berisi driver, masa berlaku 5 menit, maksimum 5 percobaan, jeda kirim ulang 60 detik, dan maksimum 3 pengiriman per jam; verifikasi `php artisan tinker --execute="dump(config('otp'));"` menampilkan nilai lengkap
- [x] 4.2 Tambahkan antarmuka `App\Contracts\OtpSender` dengan satu method `send`, implementasi `LogOtpSender`, dan binding di service provider berdasarkan `config('otp.driver')`; verifikasi uji unit yang memastikan container mengembalikan `LogOtpSender` saat driver `log`
- [x] 4.3 Tambahkan implementasi `FonnteOtpSender` yang memanggil endpoint Fonnte dengan token dari konfigurasi dan menangani kegagalan tanpa melempar pengecualian ke pengguna; verifikasi uji dengan HTTP fake untuk respons sukses dan gagal
- [x] 4.4 Tambahkan `OTP_DRIVER` dan `FONNTE_TOKEN` ke `.env`/`.env.example`, serta konfigurasi SMTP Gmail (`MAIL_MAILER=smtp`, host, port 587, skema tls, kredensial) di `.env.example`; verifikasi `php artisan config:show mail` menampilkan mailer `smtp` setelah `.env` diisi
- [x] 4.5 Pastikan aplikasi tetap dapat dijalankan tanpa `FONNTE_TOKEN` dan jatuh ke driver `log` dengan peringatan pada log; verifikasi dengan menghapus token sementara lalu menjalankan `php artisan tinker --execute="app(App\Contracts\OtpSender::class);"` tanpa galat
- [ ] 4.6 Tambahkan uji fitur alur verifikasi OTP: kode benar menandai terverifikasi, kode kedaluwarsa ditolak, kode melebihi percobaan ditolak, kode terpakai ditolak, kirim ulang terlalu cepat ditolak beserta sisa waktu tunggu; verifikasi seluruh uji tersebut lulus

## 5. Dokumentasi dan Pemeriksaan Integrasi

- [x] 5.1 Tulis bagian penyiapan pada `README.md` (prasyarat, perintah Compose, migrasi, `storage:link`, `php artisan serve`, `npm run dev`, catatan bentrok port 5432 dan catatan Podman socket bila dipakai); verifikasi dengan mengikuti petunjuk dari kloning bersih dan aplikasi dapat dibuka di peramban
- [x] 5.2 Catat pada README bahwa berkas `.env` tidak dilacak dan kredensial penyedia hanya berasal dari `.env`; verifikasi `git status` tidak menampilkan `.env` sebagai berkas terlacak
- [x] 5.3 Jalankan `php artisan test` dan `npm run build` sebagai pemeriksaan integrasi akhir; verifikasi keduanya selesai tanpa galat

## Workflow follow-up

- Tugas 2.4, 3.4, 3.6, dan 4.6 sengaja ditunda karena memerlukan tabel/model domain (`kost`, `kost_photo`, `complaint_attachment`, `whatsapp_verification`) yang belum ada di kode. Infrastruktur pendukungnya (helper URL privat, Form Request, komponen peta, dan `OtpSender`) sudah tersedia untuk dipakai change berikutnya.
- Arsipkan change setelah seluruh tugas selesai dan ditinjau.
- Perbarui `.docs/functional_requirment.md` untuk usulan FR (normalisasi nomor WhatsApp, parameter OTP, prioritas FR-SRC-07, batas berkas) sebagai perubahan terpisah, bukan bagian change ini.