# Roadmap — AYGKOS

> **Dokumen:** Roadmap Implementasi
> **Status:** Aktif
> **Tujuan:** Urutan fase implementasi dari fondasi sampai fitur lengkap.

---

## 1. Legenda Status

| Status | Arti |
|---|---|
| Selesai | Deliverable dan exit criteria terpenuhi |
| Sebagian | Sebagian deliverable selesai, ada blocker |
| Berjalan | Sedang dikerjakan |
| Belum | Belum dimulai |

---

## 2. Ringkasan Timeline

| Fase | Fokus | FR | Status |
|---|---|---|---|
| M0 | Foundation: scaffold, dependensi, primitif platform, dokumentasi | — | Sebagian |
| M1.1 | Skema domain, model, relasi | — | Selesai |
| M1.2 | Autentikasi, verifikasi dua langkah, otorisasi per peran | FR-AUT-01 s.d. FR-AUT-13 | Selesai |
| M2.1 | Profil dan keamanan akun | FR-PRF-01, FR-PRF-02, FR-PRF-03 | Sebagian |
| M2.2 | Pemilik Kost: pengelolaan kost dan kamar | FR-KOS-01 s.d. FR-KOS-10, FR-DSH-01 | Belum |
| M3.1 | Admin: verifikasi dan penangguhan kost | FR-ADM-VER-01 s.d. FR-ADM-VER-03, FR-ADM-KOS-01, FR-DSH-02 | Belum |
| M3.2 | Publik: pencarian, detail, kontak WhatsApp | FR-SRC-01 s.d. FR-SRC-09, FR-KTK-01 s.d. FR-KTK-06 | Belum |
| M4.1 | Aduan | FR-ADU-01 s.d. FR-ADU-05 | Belum |
| M4.2 | Notifikasi in-app | FR-NTF-01, FR-NTF-02 | Belum |
| M4.3 | Admin: pengguna dan laporan | FR-ADM-USR-01, FR-ADM-REP-01, FR-ADM-REP-02 | Belum |
| M5 | Pengerasan: audit otorisasi, cakupan tes, gate mutu | — | Belum |

Kode FR merujuk `docs/FunctionalRequirment/functional_requirment.md` (57 FR, 12 kelompok).

---

## 3. M0 — Foundation

**Deliverable**

- Scaffold Laravel 13 + Inertia v3 + React 19 + TypeScript + Tailwind v4 terverifikasi.
- Dependensi: Fortify, Wayfinder, Leaflet, Pest 5, PHPStan (Larastan), Pint.
- shadcn/ui dan Radix UI terpasang; token tema di `resources/css/app.css`.
- `compose.yaml`: PostgreSQL 16 dan Mailpit (profil `mail`).
- `config/uploads.php` (batas tipe dan ukuran berkas), `config/otp.php` (masa berlaku, batas percobaan, jeda kirim ulang), `config/services.php` (fonnte, geoapify).
- Primitif platform: `App\Contracts\OtpSender` dengan `LogOtpSender` dan `FonnteOtpSender`, `App\Support\PrivateFileUrl`, Form Request unggahan (`Uploads/*`), komponen peta `leaflet-map` dan `location-autocomplete`.
- Dokumentasi: `README.md`, `docs/OVERVIEW.md`, `docs/ARCHITECTURE.md`, `docs/ROADMAP.md`, `docs/ERD/erd.md`, `docs/FunctionalRequirment/functional_requirment.md`.

**Exit criteria**

- [x] `npm run build` lulus.
- [x] `npm run types:check` dan `npm run check` lulus.
- [x] `pint --test` lulus.
- [x] `php artisan test` lulus: 56 tes, 53 lulus, 3 dilewati karena fitur Fortify belum aktif.
- [x] `php artisan migrate:fresh` terhadap PostgreSQL berjalan.
- [x] Berkas privat dapat disajikan lewat route bertanda tangan; berkas tanpa tanda tangan ditolak 403.
- [x] Validasi unggahan menolak berkas melebihi batas dan tipe yang tidak diizinkan.
- [ ] `composer test` lulus utuh — PHPStan berhenti karena batas memori 128 MB pada lingkungan ini, bukan karena temuan analisis.
- [ ] Empat tugas platform yang bergantung pada model domain: penghapusan berkas pada event model (2.4), pemilih titik koordinat pada form kost (3.4), pencarian radius (3.6), dan uji alur OTP (4.6).

**Blocker:** batas memori PHPStan. Perbaikan: tambahkan `memory_limit` pada `phpstan.neon` atau jalankan `vendor/bin/phpstan analyse --memory-limit=1G`. Tiga tugas lain diturunkan ke fase yang membutuhkan model domain: 2.4 ke M1, 3.4 ke M4, 3.6 ke M6, 4.6 ke M2.

**Berikutnya:** M1 — skema domain, model, dan relasi.

---

## 4. M1.1 — Skema Domain, Model, Relasi

**Deliverable**

- `phpunit.xml` beralih ke `DB_CONNECTION=pgsql`, `DB_DATABASE=aygkos_testing`; suite lama tetap lulus pada PostgreSQL.
- Peran dan identitas: tabel `roles`, `users` ber-UUID7 dengan `role_id`, `whatsapp_number`, `whatsapp_verified_at`, `is_active`; `sessions.user_id` menjadi UUID.
- Sepuluh tabel domain sesuai ERD: `kosts`, `kost_photos`, `facilities`, `kost_facilities`, `rooms`, `complaints`, `complaint_attachments`, `notifications`, `whatsapp_verifications`.
- Constraint: check rentang koordinat, check harga kamar positif, unique nomor kamar per kost, primary key komposit `kost_facilities`, indeks `(latitude, longitude)`, `(kost_id, status)`, `(user_id, read_at)`, `(status, category)`.
- Model dengan `HasUuids`, `SoftDeletes` pada `Kost` dan `Room`, `$timestamps = false` pada `Facility` dan `KostPhoto`, `const UPDATED_AT = null` pada `Notification`; relasi dan blok `@property` lengkap untuk PHPStan level 7.
- `RoleSeeder` (customer, pemilik_kost, admin), `FacilitySeeder`; `UserFactory` menetapkan peran `customer`.
- Penghapusan berkas pada event model `Kost`, `KostPhoto`, `ComplaintAttachment` (tugas 2.4 tertunda dari M0).
- `ProfileValidationRules` dan `resources/js/types/auth.ts` beralih ke `string` untuk ID; `CreateNewUser` menetapkan `role_id`.

**Exit criteria**

- [ ] `php artisan migrate:fresh --seed` sukses; dijalankan dua kali berturut-turut tetap menghasilkan tepat tiga baris `roles`.
- [ ] Insert `latitude = 100` dan `price_per_month = 0` ditolak basis data dengan menyebut nama constraint.
- [ ] `tests/Feature/Schema/DomainSchemaTest.php` menegakkan PK UUID, PK komposit, aksi `on delete`, dan daftar kolom timestamp per tabel.
- [ ] `php artisan test` lulus seluruhnya pada PostgreSQL tanpa satu pun kegagalan `role_id`.
- [ ] `vendor/bin/phpstan analyse` lulus pada seluruh model baru.
- [ ] `npm run types:check` lulus setelah `id` menjadi `string`.
- [ ] Menghapus kost dengan `Storage::fake()` menghilangkan berkas foto dan dokumennya.

**Prasyarat:** tidak ada. Fase ini memblokir M2 sampai M9.

**Berikutnya:** M2 — autentikasi dan otorisasi per peran.

---

## 5. M1.2 — Autentikasi dan Otorisasi

**FR:** FR-AUT-01 s.d. FR-AUT-13.

**Deliverable**

- Registrasi dengan pilihan peran Customer atau Pemilik Kost; Admin tidak dapat mendaftar (FR-AUT-01 s.d. FR-AUT-04).
- Verifikasi dua jalur: tautan email untuk Customer (FR-AUT-05), OTP WhatsApp untuk Pemilik Kost (FR-AUT-06) via `OtpSender`.
- Kirim ulang verifikasi dengan pembatasan frekuensi (FR-AUT-07).
- Login tunggal email dan password dengan pesan galat tidak spesifik (FR-AUT-08); akun nonaktif ditolak (FR-AUT-09).
- Pengarahan setelah login per peran melalui `LoginResponse` (FR-AUT-10): Customer ke `/kosts`, Pemilik Kost ke `/owner/dashboard`, Admin ke `/admin/dashboard`; `redirect()->intended()` untuk Guest yang teralihkan.
- Lupa password dan reset password (FR-AUT-11), logout yang mengakhiri sesi (FR-AUT-12).
- Middleware `EnsureRole` dan `EnsureAccountVerified`; grup route `role:pemilik_kost` dan `role:admin` (FR-AUT-13).
- Halaman `verify-email` dan `verify-whatsapp`.
- Uji alur OTP (tugas 4.6 tertunda dari M0): kode benar, kode kedaluwarsa, melebihi percobaan, kode terpakai, kirim ulang terlalu cepat.

**Exit criteria**

- [ ] Customer mendaftar lalu terverifikasi setelah membuka tautan email.
- [ ] Pemilik Kost mendaftar lalu terverifikasi setelah memasukkan OTP benar; kode kedaluwarsa, melebihi percobaan, dan sudah terpakai ditolak.
- [ ] Akun nonaktif ditolak saat login.
- [ ] Setiap peran diarahkan ke tujuan yang benar setelah login; Guest kembali ke detail kost semula.
- [ ] Route `/owner/*` dan `/admin/*` mengembalikan 403 untuk peran lain.
- [ ] Logout menghapus sesi dan mengarahkan ke `/login`.
- [ ] Seluruh uji `tests/Feature/Auth` lulus.

**Prasyarat:** M1 (kolom `role_id`, `whatsapp_number`, `whatsapp_verified_at`, `is_active`, tabel `whatsapp_verifications`).

**Berikutnya:** M2.1 — profil dan keamanan akun.

---

## 6. M2.1 — Profil dan Keamanan Akun

**FR:** FR-PRF-01, FR-PRF-02, FR-PRF-03.

**Deliverable**

- Lihat dan ubah nama lengkap (FR-PRF-01) — halaman `settings/profile` sudah ada, tinggal penyesuaian peran.
- Ubah password dengan verifikasi password lama (FR-PRF-02) — sudah ada di `settings/security`.
- Halaman `settings/whatsapp`: nomor baru berlaku setelah OTP benar (FR-PRF-03), memakai `SendWhatsappOtp` dan `VerifyWhatsappOtp`.
- Action `ApplyUserProfileUpdate` dan `UpdateUserPassword`, bila controller saat ini menulis langsung ke model.

**Exit criteria**

- [ ] Setiap peran dapat mengubah nama lengkap dan perubahan langsung terlihat.
- [ ] Password lama yang salah ditolak dengan pesan pada kolom yang benar.
- [ ] Nomor WhatsApp baru tidak berlaku sebelum OTP benar; nomor lama tetap dipakai sampai verifikasi selesai.
- [ ] Nomor WhatsApp yang sudah dipakai akun lain ditolak.

**Prasyarat:** M1.

**Berikutnya:** M2.2 — pengelolaan kost oleh Pemilik Kost.

---

## 7. M2.2 — Pemilik Kost: Kost dan Kamar

**FR:** FR-KOS-01 s.d. FR-KOS-10, FR-DSH-01.

**Deliverable**

- Dashboard Pemilik Kost: jumlah kost per status verifikasi dan jumlah kamar per status (FR-DSH-01).
- Daftar kost milik Pemilik beserta status dan alasan penolakan atau penangguhan (FR-KOS-09).
- Form tambah kost: nama, alamat, kota, titik lokasi, tipe, deskripsi, fasilitas, peraturan, foto, dokumen verifikasi (FR-KOS-01); status awal selalu Menunggu Verifikasi (FR-KOS-02).
- Form ubah kost (FR-KOS-03), nonaktifkan dan aktifkan kembali (FR-KOS-04), hapus kost beserta kamarnya (FR-KOS-05).
- Pengelolaan kamar: tambah (FR-KOS-06), ubah dan atur status Tersedia/Terisi/Nonaktif (FR-KOS-07), hapus (FR-KOS-08).
- Perbaikan dan pengajuan ulang kost yang ditolak (FR-KOS-10).
- Action `SubmitKost`, `UpdateKost`, `DeactivateKost`, `DeleteKost`, `CreateRoom`, `UpdateRoom`, `UpdateRoomStatus`, `DeleteRoom`; `KostPolicy` dan `RoomPolicy`.
- Pemilih titik koordinat dengan validasi rentang wilayah Indonesia (tugas 3.4 tertunda dari M0).

**Exit criteria**

- [ ] Kost baru tidak muncul di tampilan publik sebelum disetujui.
- [ ] Pemilik Kost tidak dapat mengubah atau menghapus kost milik Pemilik lain; permintaan ditolak di server.
- [ ] Kost berstatus ditangguhkan tidak dapat diaktifkan kembali oleh Pemilik Kost.
- [ ] Nomor kamar yang sama pada satu kost ditolak; nomor sama pada kost berbeda diterima.
- [ ] Koordinat di luar rentang Indonesia ditolak validasi.
- [ ] Berkas yang gagal disimpan tidak meninggalkan baris basis data; kegagalan transaksi membersihkan berkas yang sudah tertulis.
- [ ] Dashboard menampilkan angka yang sama dengan hitungan basis data.

**Prasyarat:** M1 (tabel `kosts`, `rooms`, `kost_photos`, `facilities`, `kost_facilities`), M2 (peran dan verifikasi).

**Berikutnya:** M3.1 — verifikasi oleh Admin.

---

## 8. M3.1 — Admin: Verifikasi dan Penangguhan Kost

**FR:** FR-ADM-VER-01 s.d. FR-ADM-VER-03, FR-ADM-KOS-01, FR-DSH-02.

**Deliverable**

- Dashboard Admin: jumlah kost menunggu verifikasi dan jumlah aduan baru (FR-DSH-02).
- Daftar kost menunggu verifikasi beserta detail dan dokumen verifikasi lewat tautan bertanda tangan (FR-ADM-VER-01).
- Setujui kost sehingga tampil kepada publik (FR-ADM-VER-02); tolak dengan alasan wajib (FR-ADM-VER-03).
- Tangguhkan kost yang melanggar ketentuan dengan alasan (FR-ADM-KOS-01).
- Action `ApproveKost`, `RejectKost`, `SuspendKost` mengisi `reviewed_by`, `reviewed_at`, `status_reason`.
- Form Request `RejectKostRequest` dan `SuspendKostRequest`.

**Exit criteria**

- [ ] Menyetujui kost membuatnya tampil pada halaman publik dan mencatat peninjau beserta waktunya.
- [ ] Menolak tanpa alasan ditolak validasi.
- [ ] Kost ditangguhkan hilang dari tampilan publik dan Pemilik Kost menerima notifikasi.
- [ ] Dokumen verifikasi hanya dapat dibuka Admin melalui tautan bertanda tangan; tautan kedaluwarsa setelah 10 menit.
- [ ] Peran selain Admin mendapat 403 pada seluruh route verifikasi.

**Prasyarat:** M2 (kost harus ada untuk diverifikasi).

**Berikutnya:** M3.2 — pencarian publik dan kontak WhatsApp.

---

## 9. M3.2 — Publik: Pencarian, Detail, Kontak

**FR:** FR-SRC-01 s.d. FR-SRC-09, FR-KTK-01 s.d. FR-KTK-06.

**Deliverable**

- Penelusuran dan pencarian kost tanpa login (FR-SRC-01).
- Input lokasi dengan autocomplete Geoapify, konversi ke koordinat (FR-SRC-02).
- Filter harga, tipe, ketersediaan kamar (FR-SRC-03), dan fasilitas (FR-SRC-04).
- Pengurutan jarak terdekat, harga, dan terbaru (FR-SRC-05).
- Hasil per halaman dalam bentuk kartu: foto utama, nama, kota, tipe, harga mulai, jumlah kamar tersedia (FR-SRC-06).
- Penanda hasil pencarian pada peta beserta ringkasan kost terpilih (FR-SRC-07).
- Halaman detail kost: nama, alamat, lokasi peta, tipe, deskripsi, fasilitas, galeri, peraturan, daftar kamar, nama Pemilik Kost (FR-SRC-08).
- Hanya kost Disetujui, kost aktif, dan pemilik aktif yang tampil (FR-SRC-09).
- Pencarian radius: bounding box pada indeks `(latitude, longitude)` dilanjutkan Haversine di SQL (tugas 3.6 tertunda dari M0), tanpa PostGIS.
- Tombol Hubungi Pemilik dengan pilihan Tanya Kost dan Ajukan Sewa (FR-KTK-01, FR-KTK-02), template pesan terisi nama kost, nomor kamar, dan nama Customer (FR-KTK-03).
- Ajukan Sewa hanya untuk kamar Tersedia (FR-KTK-04); sistem hanya mengisi draf WhatsApp tanpa mengirim (FR-KTK-05).
- Guest diarahkan ke login lalu dikembalikan ke detail kost (FR-KTK-06).

**Exit criteria**

- [ ] Kost di luar radius 10 km tidak muncul pada hasil pencarian radius.
- [ ] Hasil terurut menaik berdasarkan jarak ketika pencarian lokasi dipakai.
- [ ] Filter harga, tipe, ketersediaan, dan fasilitas diterapkan bersamaan tanpa mengabaikan salah satunya.
- [ ] Paginasi konsisten: jumlah hasil dan halaman sesuai pada peta maupun daftar.
- [ ] Kost berstatus selain Disetujui, atau milik Pemilik nonaktif, tidak muncul pada daftar maupun detail.
- [ ] Tombol Ajukan Sewa tidak tersedia untuk kamar Terisi atau Nonaktif.
- [ ] Guest yang memilih Hubungi Pemilik kembali ke detail kost setelah login.
- [ ] Halaman tanpa `window` aman dirender di SSR (Leaflet dimuat dinamis).

**Prasyarat:** M3 (data kost yang layak tampil).

**Berikutnya:** M4.1 — aduan.

---

## 10. M4.1 — Aduan

**FR:** FR-ADU-01 s.d. FR-ADU-05.

**Deliverable**

- Form aduan: kategori (Laporan Kost, Laporan Pengguna, Kendala Teknis), judul, deskripsi, kost terkait opsional (FR-ADU-01).
- Lampiran maksimal 3 foto atau dokumen, disimpan pada disk privat (FR-ADU-02).
- Riwayat dan detail aduan bagi pengguna, beserta status Baru/Diproses/Selesai dan tanggapan Admin (FR-ADU-03).
- Daftar aduan Admin dengan filter status dan kategori, detail beserta lampiran (FR-ADU-04).
- Admin mengubah status dan menulis tanggapan (FR-ADU-05).
- Action `SubmitComplaint`, `RespondToComplaint`; `ComplaintPolicy` menegakkan kepemilikan.

**Exit criteria**

- [ ] Lampiran keempat ditolak validasi; berkas melebihi 5 MB ditolak.
- [ ] Pengguna hanya melihat aduannya sendiri; aduan pengguna lain menghasilkan 403, bukan halaman kosong.
- [ ] Lampiran hanya dapat dibuka lewat tautan bertanda tangan; akses tanpa tanda tangan menghasilkan 403.
- [ ] Admin mengubah status dan menulis tanggapan; perubahan langsung terlihat pada halaman pengguna.
- [ ] Penghapusan aduan tidak tersedia; aduan diakhiri dengan status Selesai.

**Prasyarat:** M2 (rujukan kost) dan M1 (identitas pelaku).

**Berikutnya:** M4.2 — notifikasi in-app.

---

## 11. M4.2 — Notifikasi In-App

**FR:** FR-NTF-01, FR-NTF-02.

**Deliverable**

- Halaman notifikasi dengan penanda belum dibaca (FR-NTF-01); `NotificationController` dan `MarkNotificationRead`.
- Notifikasi otomatis bagi Pemilik Kost ketika kost disetujui, ditolak, atau ditangguhkan (FR-NTF-02), dibuat di dalam Action verifikasi.

**Exit criteria**

- [ ] Notifikasi muncul setelah Admin menyetujui, menolak, atau menangguhkan kost.
- [ ] Penanda belum dibaca hilang setelah notifikasi dibuka; menandai notifikasi pengguna lain ditolak.
- [ ] Notifikasi tetap ada setelah sesi berakhir (tersimpan di basis data, bukan state klien).

**Prasyarat:** M4 (pemicu notifikasi) dan M4.1 (bila notifikasi aduan disertakan).

**Berikutnya:** M4.3 — pengelolaan pengguna dan laporan.

---

## 12. M4.3 — Admin: Pengguna dan Laporan

**FR:** FR-ADM-USR-01, FR-ADM-REP-01, FR-ADM-REP-02.

**Deliverable**

- Daftar pengguna dengan penonaktifan dan pengaktifan akun (FR-ADM-USR-01); akun nonaktif tidak dapat login dan seluruh kostnya hilang dari tampilan publik.
- Ringkasan statistik: jumlah kost per status, jumlah kamar tersedia, jumlah pengguna per peran, jumlah aduan per status (FR-ADM-REP-01).
- Filter periode waktu pada ringkasan (FR-ADM-REP-02).
- `UserPolicy` dan Form Request `UpdateUserStatusRequest`.

**Exit criteria**

- [ ] Menonaktifkan akun memblokir login berikutnya dan menyembunyikan kost miliknya dari publik.
- [ ] Admin tidak dapat menonaktifkan akunnya sendiri.
- [ ] Statistik cocok dengan hitungan langsung pada basis data untuk periode yang sama.
- [ ] Filter periode mengubah angka sesuai rentang yang dipilih.

**Prasyarat:** M4, M4.1.

**Berikutnya:** M5 — pengerasan.

---

## 13. M5 — Pengerasan

**Deliverable**

- Audit otorisasi: setiap route terautentikasi memiliki middleware peran, setiap Action memeriksa ulang kepemilikan.
- Cakupan tes: satu berkas Feature per kelompok FR, termasuk jalur ditolak (peran salah, bukan pemilik, akun nonaktif, kost ditangguhkan).
- `composer test` lulus utuh di lingkungan bersih, termasuk PHPStan tanpa kehabisan memori.
- Repositori Wayfinder dibangkitkan ulang; tidak ada URL route yang ditulis manual.
- Perapian pesan galat berbahasa Indonesia, status memuat, dan aksesibilitas dasar (label, fokus, kontras).
- Pemeriksaan akhir: `npm run build`, `npm run types:check`, `npm run check`, `composer test`.

**Exit criteria**

- [ ] `npm run build`, `npm run types:check`, `npm run check`, dan `composer test` keluar dengan kode 0.
- [ ] Setiap FR memiliki minimal satu tes atau bukti verifikasi.
- [ ] Tidak ada route, Action, maupun komponen yang bergantung pada penyembunyian tombol sebagai otorisasi.
- [ ] Tidak ada secret di bundel klien: `GEOAPIFY_API_KEY`, `FONNTE_TOKEN`, kredensial SMTP, dan kredensial basis data hanya di `.env`.
- [ ] Tautan berkas privat tidak pernah muncul pada halaman publik.

---

## 14. Ketergantungan Antar Fase

```text
M0 Foundation (Sebagian)
  -> M1.1 Skema Domain
      -> M1.2 Auth dan Otorisasi
          -> M2.1 Profil dan Keamanan
          -> M2.2 Kost dan Kamar
              -> M3.1 Verifikasi Admin
                  -> M3.2 Pencarian, Detail, Kontak
                      -> M4.1 Aduan
                          -> M4.2 Notifikasi
                              -> M4.3 Pengguna dan Laporan
                                  -> M5 Pengerasan
```

M3 dan M4 dapat berjalan paralel setelah M1 dan M2 selesai. M8 dapat dikerjakan bersamaan dengan M6 selama pemicu notifikasi pada M5 sudah ada.

---

## 15. Referensi

- `docs/OVERVIEW.md`
- `docs/ARCHITECTURE.md`
- `docs/ERD/erd.md`
- `docs/FunctionalRequirment/functional_requirment.md`
- `openspec/changes/erd-schema-and-models/` — proposal, design, dan tugas M1
- `openspec/changes/archive/2026-11-17-setup-dev-env-and-integrations/` — tugas M0
