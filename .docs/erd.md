# ERD AYGKOS

## Daftar Entitas

| No | Tabel | Fungsi |
|---|---|---|
| 1 | role | Peran pengguna |
| 2 | users | Akun Customer, Pemilik Kost, dan Admin |
| 3 | whatsapp_verification | Kode OTP verifikasi nomor WhatsApp Pemilik Kost |
| 4 | kost | Data kost beserta status verifikasi |
| 5 | kost_photo | Foto kost |
| 6 | facility | Master fasilitas |
| 7 | kost_facility | Relasi banyak ke banyak antara kost dan fasilitas |
| 8 | room | Kamar pada kost |
| 9 | complaint | Aduan pengguna |
| 10 | complaint_attachment | Lampiran aduan |
| 11 | notification | Notifikasi in-app |

## Struktur Tabel

### role

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| name | varchar(50) | Unique | Tidak | customer, pemilik_kost, admin |

### users

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| role_id | uuid | FK ke role.id | Tidak | |
| full_name | varchar(255) | | Tidak | |
| email | varchar(255) | Unique | Tidak | |
| email_verified_at | timestamp | | Ya | Terisi saat Customer memverifikasi email; untuk Admin diisi melalui seeder |
| whatsapp_number | varchar(20) | Unique | Ya | Wajib untuk Pemilik Kost, kosong untuk peran lain; format +62 |
| whatsapp_verified_at | timestamp | | Ya | Terisi saat Pemilik Kost memverifikasi OTP WhatsApp |
| password | varchar(255) | | Tidak | Hasil hash |
| is_active | boolean | | Tidak | Default true; false apabila akun dinonaktifkan Admin |
| created_at | timestamp | | Tidak | |
| updated_at | timestamp | | Tidak | |

### whatsapp_verification

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

### kost

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

### kost_photo

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| kost_id | uuid | FK ke kost.id | Tidak | |
| photo_path | varchar(500) | | Tidak | Path atau key berkas, bukan URL |
| is_primary | boolean | | Tidak | Default false; menandai foto utama |

### facility

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| name | varchar(100) | Unique | Tidak | |

### kost_facility

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| kost_id | uuid | PK komposit, FK ke kost.id | Tidak | |
| facility_id | uuid | PK komposit, FK ke facility.id | Tidak | |

### room

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| kost_id | uuid | FK ke kost.id | Tidak | |
| room_number | varchar(50) | | Tidak | Nomor atau nama kamar |
| price_per_month | int | | Tidak | Harga sewa per bulan (rupiah) |
| status | varchar(20) | | Tidak | Default tersedia |
| created_at | timestamp | | Tidak | |
| updated_at | timestamp | | Tidak | |
| deleted_at | timestamp | | Ya | Penghapusan lunak |

### complaint

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| user_id | uuid | FK ke users.id | Tidak | Pengadu (Customer atau Pemilik Kost) |
| kost_id | uuid | FK ke kost.id | Ya | Kost yang dilaporkan, untuk kategori laporan_kost |
| handled_by | uuid | FK ke users.id | Ya | Admin yang menangani |
| category | varchar(30) | | Tidak | |
| title | varchar(255) | | Tidak | |
| description | text | | Tidak | |
| status | varchar(20) | | Tidak | Default baru |
| admin_response | text | | Ya | Tanggapan Admin |
| responded_at | timestamp | | Ya | |
| created_at | timestamp | | Tidak | |
| updated_at | timestamp | | Tidak | |

### complaint_attachment

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| complaint_id | uuid | FK ke complaint.id | Tidak | |
| file_path | varchar(500) | | Tidak | Path berkas privat; maksimal 3 berkas per aduan |
| original_name | varchar(255) | | Ya | Nama berkas asli |

### notification

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
| 1 | role | users | users.role_id | 1 : N | Restrict | Satu peran dimiliki banyak pengguna |
| 2 | users | whatsapp_verification | whatsapp_verification.user_id | 1 : N | Cascade | Satu Pemilik Kost dapat memiliki beberapa kode verifikasi |
| 3 | users (Pemilik Kost) | kost | kost.owner_id | 1 : N | Restrict | Satu Pemilik Kost memiliki banyak kost |
| 4 | users (Admin) | kost | kost.reviewed_by | 1 : N | Set null | Satu Admin memproses banyak kost |
| 5 | kost | kost_photo | kost_photo.kost_id | 1 : N | Cascade | Satu kost memiliki banyak foto |
| 6 | kost | kost_facility | kost_facility.kost_id | 1 : N | Cascade | Bagian dari relasi banyak ke banyak |
| 7 | facility | kost_facility | kost_facility.facility_id | 1 : N | Cascade | Bagian dari relasi banyak ke banyak |
| 8 | kost dan facility | (melalui kost_facility) | kost_id, facility_id | N : M | Cascade | Satu kost memiliki banyak fasilitas, satu fasilitas dimiliki banyak kost |
| 9 | kost | room | room.kost_id | 1 : N | Cascade | Satu kost memiliki banyak kamar |
| 10 | users (Customer, Pemilik Kost) | complaint | complaint.user_id | 1 : N | Restrict | Satu pengguna dapat mengajukan banyak aduan |
| 11 | kost | complaint | complaint.kost_id | 1 : N (opsional) | Set null | Aduan dapat merujuk satu kost |
| 12 | users (Admin) | complaint | complaint.handled_by | 1 : N | Set null | Satu Admin menangani banyak aduan |
| 13 | complaint | complaint_attachment | complaint_attachment.complaint_id | 1 : N | Cascade | Satu aduan memiliki hingga 3 lampiran |
| 14 | users | notification | notification.user_id | 1 : N | Cascade | Satu pengguna menerima banyak notifikasi |

## Nilai Enumerasi

| Tabel.Kolom | Nilai |
|---|---|
| role.name | customer, pemilik_kost, admin |
| kost.type | putra, putri, campur |
| kost.status | menunggu_verifikasi, disetujui, ditolak, nonaktif, ditangguhkan |
| room.status | tersedia, terisi, nonaktif |
| complaint.category | laporan_kost, laporan_pengguna, kendala_sistem, lainnya |
| complaint.status | baru, diproses, selesai |
| notification.type | hasil_verifikasi_kost, kost_ditangguhkan, aduan_diperbarui |

## Constraint, Indeks, dan Catatan

| Tabel | Constraint atau Catatan | Tujuan |
|---|---|---|
| Seluruh tabel | Seluruh kolom id dan foreign key bertipe uuid; primary key dibangkitkan aplikasi (misalnya trait HasUuids pada Laravel) atau default gen_random_uuid() | Identitas seragam |
| users | Unique pada email dan whatsapp_number (nilai kosong diizinkan) | Mencegah akun dan nomor ganda |
| users | Customer dan Admin terverifikasi apabila email_verified_at terisi; Pemilik Kost terverifikasi apabila whatsapp_verified_at terisi | FR-AUT-05, FR-AUT-06, FR-AUT-09 |
| whatsapp_verification | Indeks (user_id, consumed_at) | Pencarian kode aktif |
| kost | Indeks (latitude, longitude) dan indeks status | Pencarian radius 10 km dan penyaringan kost tampil |
| kost | Check latitude antara -90 dan 90, longitude antara -180 dan 180 | Validitas koordinat |
| kost | Hanya kost berstatus disetujui milik pengguna dengan is_active true yang tampil kepada publik | FR-SRC-09 |
| kost_facility | Primary key komposit (kost_id, facility_id) | Mencegah fasilitas ganda pada satu kost |
| room | Unique (kost_id, room_number); check price_per_month lebih dari 0; indeks (kost_id, status) | Nomor kamar tidak ganda dan harga valid |
| complaint | Indeks (status, category) | Penyaringan oleh Admin |
| notification | Indeks (user_id, read_at) | Daftar notifikasi belum dibaca |
| Kolom berkas | photo_path, verification_document_path, dan file_path menyimpan path atau key objek, bukan URL; berkas verifikasi dan lampiran aduan bersifat privat | Tidak bergantung pada penyedia penyimpanan |
| Konfigurasi | Template pesan WhatsApp, radius pencarian 10 km, batas lampiran aduan, dan parameter OTP disimpan pada konfigurasi aplikasi, bukan tabel | Tidak memerlukan tabel pengaturan |
| kost, room | Penghapusan lunak melalui deleted_at | Menjaga integritas data terkait |

## Ketertelusuran FR ke Tabel

| Modul FR | Tabel yang digunakan |
|---|---|
| FR-AUT | role, users, whatsapp_verification |
| FR-PRF | users, whatsapp_verification |
| FR-DSH | kost, room, complaint (agregasi) |
| FR-KOS | kost, kost_photo, facility, kost_facility, room |
| FR-SRC | kost, room, kost_photo, facility, kost_facility |
| FR-KTK | kost, room, users (nomor WhatsApp Pemilik Kost dan nama Customer); tidak memerlukan tabel khusus |
| FR-ADU | complaint, complaint_attachment, kost |
| FR-ADM | kost, users, complaint (agregasi) |
| FR-NTF | notification |
