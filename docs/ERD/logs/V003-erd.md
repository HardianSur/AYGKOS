# ERD AYGKOS

## Versi V003

Dibuat: 18-11-2026:16:10 WIB
Dipensiunkan: -

## Daftar Entitas

| No | Tabel | Fungsi |
|---|---|---|
| 1 | roles | Peran pengguna |
| 2 | users | Akun Customer, Pemilik Kost, dan Admin |
| 3 | whatsapp_verifications | Kode OTP verifikasi nomor WhatsApp Pemilik Kost |
| 4 | kosts | Data kost beserta status verifikasi |
| 5 | kost_photos | Foto kost |
| 6 | facilities | Master fasilitas |
| 7 | kost_facilities | Relasi banyak ke banyak antara kost dan fasilitas |
| 8 | rooms | Kamar pada kost |
| 9 | complaints | Aduan pengguna |
| 10 | complaint_attachments | Lampiran aduan |
| 11 | notifications | Notifikasi in-app |

## Struktur Tabel

### roles

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| name | varchar(50) | Unique | Tidak | customer, pemilik_kost, admin |
| created_at | timestamp | | Tidak | |

### users

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| role_id | uuid | FK ke roles.id | Tidak | |
| name | varchar(255) | | Tidak | Nama lengkap |
| email | varchar(255) | Unique | Tidak | |
| email_verified_at | timestamp | | Ya | Terisi saat Customer memverifikasi email; untuk Admin diisi melalui seeder |
| whatsapp_number | varchar(20) | Unique | Ya | Wajib untuk Pemilik Kost, kosong untuk peran lain; format +62 |
| whatsapp_verified_at | timestamp | | Ya | Terisi saat Pemilik Kost memverifikasi OTP WhatsApp |
| password | varchar(255) | | Tidak | Hasil hash |
| is_active | boolean | | Tidak | Default true; false apabila akun dinonaktifkan Admin |
| remember_token | varchar(100) | | Ya | Diisi framework saat opsi "ingat saya" dipakai |
| created_at | timestamp | | Tidak | |
| updated_at | timestamp | | Tidak | |

### whatsapp_verifications

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| user_id | uuid | FK ke users.id | Tidak | Pemilik Kost yang diverifikasi |
| target_number | varchar(20) | | Tidak | Nomor yang diverifikasi (registrasi atau perubahan nomor) |
| code_hash | varchar(255) | | Tidak | Hasil hash kode OTP |
| expires_at | timestamp | | Tidak | Batas berlaku kode |
| attempt_count | int | | Tidak | Default 0; jumlah percobaan memasukkan kode |
| resend_count | int | | Tidak | Default 0; jumlah kirim ulang |
| last_sent_at | timestamp | | Tidak | Dipakai untuk pembatasan frekuensi pengiriman |
| consumed_at | timestamp | | Ya | Terisi saat kode berhasil dipakai |
| created_at | timestamp | | Tidak | |

### kosts

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| owner_id | uuid | FK ke users.id | Tidak | Pemilik Kost |
| reviewed_by | uuid | FK ke users.id | Ya | Admin yang terakhir memproses verifikasi atau penangguhan |
| name | varchar(255) | | Tidak | |
| address | varchar(500) | | Tidak | Alamat lengkap |
| city | varchar(100) | | Tidak | |
| latitude | decimal(10,7) | | Tidak | Titik lokasi kost |
| longitude | decimal(10,7) | | Tidak | Titik lokasi kost |
| type | varchar(20) | | Tidak | putra, putri, campur |
| description | text | | Ya | |
| rules | text | | Ya | Peraturan kost |
| status | varchar(30) | | Tidak | Default menunggu_verifikasi |
| status_reason | text | | Ya | Alasan penolakan atau penangguhan |
| verification_document_path | varchar(500) | | Tidak | Path berkas privat (KTP atau bukti kepemilikan) |
| reviewed_at | timestamp | | Ya | |
| created_at | timestamp | | Tidak | |
| updated_at | timestamp | | Tidak | |
| deleted_at | timestamp | | Ya | Penghapusan lunak |

### kost_photos

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| kost_id | uuid | FK ke kosts.id | Tidak | |
| photo_path | varchar(500) | | Tidak | Path atau key berkas, bukan URL |
| is_primary | boolean | | Tidak | Default false; menandai foto utama |

### facilities

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| name | varchar(100) | Unique | Tidak | |

### kost_facilities

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| kost_id | uuid | PK komposit, FK ke kosts.id | Tidak | |
| facility_id | uuid | PK komposit, FK ke facilities.id | Tidak | |

### rooms

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| kost_id | uuid | FK ke kosts.id | Tidak | |
| room_number | varchar(50) | | Tidak | Nomor atau nama kamar |
| price_per_month | int | | Tidak | Harga sewa per bulan (rupiah) |
| status | varchar(20) | | Tidak | Default tersedia |
| created_at | timestamp | | Tidak | |
| updated_at | timestamp | | Tidak | |
| deleted_at | timestamp | | Ya | Penghapusan lunak |

### complaints

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| user_id | uuid | FK ke users.id | Tidak | Pengadu (Customer atau Pemilik Kost) |
| kost_id | uuid | FK ke kosts.id | Ya | Kost yang dilaporkan, untuk kategori laporan_kost |
| handled_by | uuid | FK ke users.id | Ya | Admin yang menangani |
| category | varchar(30) | | Tidak | |
| title | varchar(255) | | Tidak | |
| description | text | | Tidak | |
| status | varchar(20) | | Tidak | Default baru |
| admin_response | text | | Ya | Tanggapan Admin |
| responded_at | timestamp | | Ya | |
| created_at | timestamp | | Tidak | |
| updated_at | timestamp | | Tidak | |

### complaint_attachments

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| complaint_id | uuid | FK ke complaints.id | Tidak | |
| file_path | varchar(500) | | Tidak | Path berkas privat; maksimal 3 berkas per aduan |
| original_name | varchar(255) | | Ya | Nama berkas asli |

### notifications

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| user_id | uuid | FK ke users.id | Tidak | Penerima |
| type | varchar(50) | | Tidak | |
| message | text | | Tidak | |
| reference_type | varchar(50) | | Ya | kost atau complaint |
| reference_id | uuid | | Ya | Rujukan polimorfik tanpa foreign key |
| read_at | timestamp | | Ya | Kosong berarti belum dibaca |
| created_at | timestamp | | Tidak | |

## Relasi Antar Tabel

| No | Tabel Induk (sisi 1) | Tabel Anak (sisi N) | Kolom FK | Kardinalitas | On Delete | Keterangan |
|---|---|---|---|---|---|---|
| 1 | roles | users | users.role_id | 1 : N | Restrict | Satu peran dimiliki banyak pengguna |
| 2 | users | whatsapp_verifications | whatsapp_verifications.user_id | 1 : N | Cascade | Satu Pemilik Kost dapat memiliki beberapa kode verifikasi |
| 3 | users (Pemilik Kost) | kosts | kosts.owner_id | 1 : N | Restrict | Satu Pemilik Kost memiliki banyak kost |
| 4 | users (Admin) | kosts | kosts.reviewed_by | 1 : N | Set null | Satu Admin memproses banyak kost |
| 5 | kosts | kost_photos | kost_photos.kost_id | 1 : N | Cascade | Satu kost memiliki banyak foto |
| 6 | kosts | kost_facilities | kost_facilities.kost_id | 1 : N | Cascade | Bagian dari relasi banyak ke banyak |
| 7 | facilities | kost_facilities | kost_facilities.facility_id | 1 : N | Cascade | Bagian dari relasi banyak ke banyak |
| 8 | kosts dan facilities | (melalui kost_facilities) | kost_id, facility_id | N : M | Cascade | Satu kost memiliki banyak fasilitas, satu fasilitas dimiliki banyak kost |
| 9 | kosts | rooms | rooms.kost_id | 1 : N | Cascade | Satu kost memiliki banyak kamar |
| 10 | users (Customer, Pemilik Kost) | complaints | complaints.user_id | 1 : N | Restrict | Satu pengguna dapat mengajukan banyak aduan |
| 11 | kosts | complaints | complaints.kost_id | 1 : N (opsional) | Set null | Aduan dapat merujuk satu kost |
| 12 | users (Admin) | complaints | complaints.handled_by | 1 : N | Set null | Satu Admin menangani banyak aduan |
| 13 | complaints | complaint_attachments | complaint_attachments.complaint_id | 1 : N | Cascade | Satu aduan memiliki hingga 3 lampiran |
| 14 | users | notifications | notifications.user_id | 1 : N | Cascade | Satu pengguna menerima banyak notifikasi |

## Nilai Enumerasi

| Tabel.Kolom | Nilai |
|---|---|
| roles.name | customer, pemilik_kost, admin |
| kosts.type | putra, putri, campur |
| kosts.status | menunggu_verifikasi, disetujui, ditolak, nonaktif, ditangguhkan |
| rooms.status | tersedia, terisi, nonaktif |
| complaints.category | laporan_kost, laporan_pengguna, kendala_sistem, lainnya |
| complaints.status | baru, diproses, selesai |
| notifications.type | hasil_verifikasi_kost, kost_ditangguhkan, aduan_diperbarui |

## Constraint, Indeks, dan Catatan

| Tabel | Constraint atau Catatan | Tujuan |
|---|---|---|
| Seluruh tabel | Seluruh kolom id dan foreign key bertipe uuid; primary key dibangkitkan aplikasi melalui trait HasUuids (uuid7), bukan default basis data | Identitas seragam |
| users | Unique pada email dan whatsapp_number (nilai kosong diizinkan) | Mencegah akun dan nomor ganda |
| users | Customer dan Admin terverifikasi apabila email_verified_at terisi; Pemilik Kost terverifikasi apabila whatsapp_verified_at terisi | FR-AUT-05, FR-AUT-06, FR-AUT-09 |
| users.role_id | Wajib terisi; tiga peran (customer, pemilik_kost, admin) dibuat oleh RoleSeeder sebelum akun apa pun dibuat | Registrasi dan seeder tidak dapat berjalan tanpa peran |
| whatsapp_verifications | Indeks (user_id, consumed_at) | Pencarian kode aktif |
| kosts | Indeks (latitude, longitude) dan indeks status | Pencarian radius 10 km dan penyaringan kost tampil |
| kosts | Check latitude antara -90 dan 90, longitude antara -180 dan 180 | Validitas koordinat |
| kosts | Hanya kost berstatus disetujui milik pengguna dengan is_active true yang tampil kepada publik | FR-SRC-09 |
| kost_facilities | Primary key komposit (kost_id, facility_id) | Mencegah fasilitas ganda pada satu kost |
| rooms | Unique (kost_id, room_number); check price_per_month lebih dari 0; indeks (kost_id, status) | Nomor kamar tidak ganda dan harga valid |
| complaints | Indeks (status, category) | Penyaringan oleh Admin |
| notifications | Indeks (user_id, read_at) | Daftar notifikasi belum dibaca |
| Kolom berkas | photo_path, verification_document_path, dan file_path menyimpan path atau key objek, bukan URL; berkas verifikasi dan lampiran aduan bersifat privat | Tidak bergantung pada penyedia penyimpanan |
| Konfigurasi | Template pesan WhatsApp, radius pencarian 10 km, batas lampiran aduan, dan parameter OTP disimpan pada konfigurasi aplikasi, bukan tabel | Tidak memerlukan tabel pengaturan |
| kosts, rooms | Penghapusan lunak melalui deleted_at | Menjaga integritas data terkait |
| Di luar cakupan | Tabel milik framework (sessions, cache, cache_locks, jobs, job_batches, failed_jobs, password_reset_tokens) tidak termasuk ERD ini sehingga tidak terikat aturan uuid dan created_at | Aturan identitas hanya berlaku pada tabel domain |

## Ketertelusuran FR ke Tabel

| Modul FR | Tabel yang digunakan |
|---|---|
| FR-AUT | roles, users, whatsapp_verifications |
| FR-PRF | users, whatsapp_verifications |
| FR-DSH | kosts, rooms, complaints (agregasi) |
| FR-KOS | kosts, kost_photos, facilities, kost_facilities, rooms |
| FR-SRC | kosts, rooms, kost_photos, facilities, kost_facilities |
| FR-KTK | kosts, rooms, users (nomor WhatsApp Pemilik Kost dan nama Customer); tidak memerlukan tabel khusus |
| FR-ADU | complaints, complaint_attachments, kosts |
| FR-ADM | kosts, users, complaints (agregasi) |
| FR-NTF | notifications |