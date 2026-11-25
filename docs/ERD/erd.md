# ERD AYGKOS

## Versi V005

Dibuat: 25-11-2026:09:28 WIB
Dipensiunkan: -

## Daftar Entitas

| No | Tabel | Fungsi |
|---|---|---|
| 1 | roles | Peran pengguna |
| 2 | users | Akun Customer, Pemilik Kost, dan Admin |
| 3 | whatsapp_verifications | Kode OTP verifikasi nomor WhatsApp Pemilik Kost |
| 4 | kosts | Data kost beserta status verifikasi |
| 5 | room_types | Tipe kamar yang diiklankan pada sebuah kost |
| 6 | photos | Foto kost dan foto tipe kamar, berkategori |
| 7 | kost_documents | Dokumen izin per bangunan kost |
| 8 | facilities | Master fasilitas beserta ruang lingkup dan ikon |
| 9 | kost_facilities | Relasi fasilitas umum pada kost |
| 10 | rules | Master peraturan kost dan peraturan kamar |
| 11 | kost_rules | Relasi peraturan pada kost |
| 12 | specifications | Master spesifikasi kamar |
| 13 | room_type_specifications | Nilai spesifikasi pada tipe kamar |
| 14 | room_type_facilities | Relasi fasilitas kamar pada tipe kamar |
| 15 | room_type_rules | Relasi peraturan khusus pada tipe kamar |
| 16 | complaints | Aduan pengguna |
| 17 | complaint_attachments | Lampiran aduan |
| 18 | notifications | Notifikasi in-app |

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
| avatar_path | varchar(500) | | Ya | Path relatif foto profil pada disk `public`, bukan URL; kosong berarti belum ada foto |
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
| status | varchar(30) | | Tidak | Default menunggu_verifikasi |
| status_reason | text | | Ya | Alasan penolakan atau penangguhan |
| reviewed_at | timestamp | | Ya | |
| created_at | timestamp | | Tidak | |
| updated_at | timestamp | | Tidak | |
| deleted_at | timestamp | | Ya | Penghapusan lunak |

### room_types

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| kost_id | uuid | FK ke kosts.id | Tidak | |
| name | varchar(100) | Unique per kost | Tidak | Nama tipe kamar yang diisi Pemilik Kost |
| price_per_month | int | | Tidak | Harga sewa per bulan (rupiah), lebih besar dari 0 |
| total_rooms | int | | Tidak | Jumlah kamar pada tipe ini, lebih besar dari 0 |
| available_rooms | int | | Tidak | Default 0; jumlah kamar tersedia, antara 0 dan total_rooms |
| notes | text | | Ya | Catatan Pemilik Kost untuk tipe kamar |
| status | varchar(20) | | Tidak | Default nonaktif; aktif atau nonaktif |
| created_at | timestamp | | Tidak | |
| updated_at | timestamp | | Tidak | |
| deleted_at | timestamp | | Ya | Penghapusan lunak |

### photos

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| kost_id | uuid | FK ke kosts.id | Tidak | |
| room_type_id | uuid | FK ke room_types.id | Ya | Wajib untuk kategori kamar dan kamar_mandi; kosong untuk kategori bangunan dan fasilitas_umum |
| category | varchar(20) | | Tidak | bangunan, kamar, kamar_mandi, fasilitas_umum |
| photo_path | varchar(500) | | Tidak | Path atau key berkas pada disk `public`, bukan URL |
| sort_order | int | | Tidak | Default 0; urutan tampil dalam kategori |
| is_primary | boolean | | Tidak | Default false; menandai foto utama per kost atau per tipe kamar |

### kost_documents

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| kost_id | uuid | FK ke kosts.id | Tidak | |
| type | varchar(30) | Unique per kost | Tidak | domisili_usaha, izin_lingkungan, izin_kelurahan, nib, surat_pernyataan |
| file_path | varchar(500) | | Tidak | Path berkas privat pada disk `local`, bukan URL |

### facilities

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| name | varchar(100) | Unique | Tidak | |
| scope | varchar(10) | | Tidak | umum, kamar |
| icon | varchar(50) | | Tidak | Nama ikon lucide dari daftar yang diizinkan aplikasi |

### kost_facilities

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| kost_id | uuid | PK komposit, FK ke kosts.id | Tidak | |
| facility_id | uuid | PK komposit, FK ke facilities.id | Tidak | |

### rules

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| name | varchar(100) | Unique | Tidak | |
| scope | varchar(10) | | Tidak | kost, kamar |

### kost_rules

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| kost_id | uuid | PK komposit, FK ke kosts.id | Tidak | |
| rule_id | uuid | PK komposit, FK ke rules.id | Tidak | |

### specifications

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| id | uuid | PK | Tidak | |
| name | varchar(100) | Unique | Tidak | |
| input_type | varchar(20) | | Tidak | boolean, number, text |
| unit | varchar(20) | | Ya | Satuan tampil bersama nilai, misalnya m2 |

### room_type_specifications

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| room_type_id | uuid | PK komposit, FK ke room_types.id | Tidak | |
| specification_id | uuid | PK komposit, FK ke specifications.id | Tidak | |
| value | varchar(100) | | Tidak | Nilai spesifikasi pada tipe kamar |

### room_type_facilities

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| room_type_id | uuid | PK komposit, FK ke room_types.id | Tidak | |
| facility_id | uuid | PK komposit, FK ke facilities.id | Tidak | |

### room_type_rules

| Kolom | Tipe Data | Key | Null | Keterangan |
|---|---|---|---|---|
| room_type_id | uuid | PK komposit, FK ke room_types.id | Tidak | |
| rule_id | uuid | PK komposit, FK ke rules.id | Tidak | |

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
| 5 | kosts | room_types | room_types.kost_id | 1 : N | Cascade | Satu kost memiliki banyak tipe kamar |
| 6 | kosts | photos | photos.kost_id | 1 : N | Cascade | Satu kost memiliki banyak foto |
| 7 | room_types | photos | photos.room_type_id | 1 : N (opsional) | Cascade | Foto kategori kamar dan kamar_mandi milik satu tipe kamar |
| 8 | kosts | kost_documents | kost_documents.kost_id | 1 : N | Cascade | Satu kost memiliki dokumen izinnya sendiri |
| 9 | kosts | kost_facilities | kost_facilities.kost_id | 1 : N | Cascade | Bagian dari relasi banyak ke banyak |
| 10 | facilities | kost_facilities | kost_facilities.facility_id | 1 : N | Restrict | Master yang masih dipakai menahan penghapusan |
| 11 | kosts dan facilities | (melalui kost_facilities) | kost_id, facility_id | N : M | Cascade / Restrict | Satu kost memiliki banyak fasilitas umum |
| 12 | kosts | kost_rules | kost_rules.kost_id | 1 : N | Cascade | Bagian dari relasi banyak ke banyak |
| 13 | rules | kost_rules | kost_rules.rule_id | 1 : N | Restrict | Master yang masih dipakai menahan penghapusan |
| 14 | kosts dan rules | (melalui kost_rules) | kost_id, rule_id | N : M | Cascade / Restrict | Satu kost memilih banyak peraturan kost |
| 15 | room_types | room_type_facilities | room_type_facilities.room_type_id | 1 : N | Cascade | Bagian dari relasi banyak ke banyak |
| 16 | facilities | room_type_facilities | room_type_facilities.facility_id | 1 : N | Restrict | Master yang masih dipakai menahan penghapusan |
| 17 | room_types dan facilities | (melalui room_type_facilities) | room_type_id, facility_id | N : M | Cascade / Restrict | Satu tipe kamar memiliki banyak fasilitas kamar |
| 18 | room_types | room_type_rules | room_type_rules.room_type_id | 1 : N | Cascade | Bagian dari relasi banyak ke banyak |
| 19 | rules | room_type_rules | room_type_rules.rule_id | 1 : N | Restrict | Master yang masih dipakai menahan penghapusan |
| 20 | room_types dan rules | (melalui room_type_rules) | room_type_id, rule_id | N : M | Cascade / Restrict | Satu tipe kamar memilih peraturan khusus |
| 21 | room_types | room_type_specifications | room_type_specifications.room_type_id | 1 : N | Cascade | Satu tipe kamar memiliki banyak nilai spesifikasi |
| 22 | specifications | room_type_specifications | room_type_specifications.specification_id | 1 : N | Restrict | Master yang masih dipakai menahan penghapusan |
| 23 | room_types dan specifications | (melalui room_type_specifications) | room_type_id, specification_id | N : M | Cascade / Restrict | Satu tipe kamar menyimpan nilai per spesifikasi |
| 24 | users (Customer, Pemilik Kost) | complaints | complaints.user_id | 1 : N | Restrict | Satu pengguna dapat mengajukan banyak aduan |
| 25 | kosts | complaints | complaints.kost_id | 1 : N (opsional) | Set null | Aduan dapat merujuk satu kost |
| 26 | users (Admin) | complaints | complaints.handled_by | 1 : N | Set null | Satu Admin menangani banyak aduan |
| 27 | complaints | complaint_attachments | complaint_attachments.complaint_id | 1 : N | Cascade | Satu aduan memiliki hingga 3 lampiran |
| 28 | users | notifications | notifications.user_id | 1 : N | Cascade | Satu pengguna menerima banyak notifikasi |

## Nilai Enumerasi

| Tabel.Kolom | Nilai |
|---|---|
| roles.name | customer, pemilik_kost, admin |
| kosts.type | putra, putri, campur |
| kosts.status | menunggu_verifikasi, disetujui, ditolak, nonaktif, ditangguhkan |
| room_types.status | aktif, nonaktif |
| photos.category | bangunan, kamar, kamar_mandi, fasilitas_umum |
| kost_documents.type | domisili_usaha, izin_lingkungan, izin_kelurahan, nib, surat_pernyataan |
| facilities.scope | umum, kamar |
| rules.scope | kost, kamar |
| specifications.input_type | boolean, number, text |
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
| room_types | Unique (kost_id, name); indeks (kost_id, status); check price_per_month lebih dari 0, total_rooms lebih dari 0, available_rooms antara 0 dan total_rooms | Nama tipe tidak ganda dalam satu kost dan angka valid |
| photos | Check kategori bangunan/fasilitas_umum wajib room_type_id kosong, kategori kamar/kamar_mandi wajib room_type_id terisi | Keterkaitan kategori dan tipe kamar |
| photos | Indeks unik parsial (kost_id) where is_primary and room_type_id is null; indeks unik parsial (room_type_id) where is_primary and room_type_id is not null | Paling banyak satu foto utama per kost dan per tipe kamar |
| kost_documents | Unique (kost_id, type); check lima jenis dokumen | Satu berkas per jenis pada satu kost |
| facilities | Unique name; check scope umum atau kamar; icon wajib nama ikon dari daftar aplikasi | Master fasilitas terkurasi |
| rules | Unique name; check scope kost atau kamar | Master peraturan terkurasi |
| specifications | Unique name; check input_type boolean, number, atau text | Master spesifikasi terkurasi |
| kost_facilities, kost_rules, room_type_facilities, room_type_rules, room_type_specifications | Primary key komposit pasangan; foreign key master memakai restrict | Mencegah pasangan ganda dan menahan penghapusan master yang dipakai |
| complaints | Indeks (status, category) | Penyaringan oleh Admin |
| notifications | Indeks (user_id, read_at) | Daftar notifikasi belum dibaca |
| Kolom berkas | photo_path, file_path, dan avatar_path menyimpan path atau key objek, bukan URL; dokumen izin dan lampiran aduan bersifat privat, sedangkan foto kost dan foto profil publik | Tidak bergantung pada penyedia penyimpanan |
| Konfigurasi | Template pesan WhatsApp, radius pencarian 10 km, batas lampiran aduan, batas unggah, daftar dokumen wajib, dan parameter OTP disimpan pada konfigurasi aplikasi, bukan tabel | Tidak memerlukan tabel pengaturan |
| kosts, room_types | Penghapusan lunak melalui deleted_at | Menjaga integritas data terkait |
| tables master | facilities, rules, specifications, photos, dan kost_documents tidak memiliki kolom timestamp | Data master tanpa riwayat perubahan |
| Di luar cakupan | Tabel milik framework (sessions, cache, cache_locks, jobs, job_batches, failed_jobs, password_reset_tokens) tidak termasuk ERD ini sehingga tidak terikat aturan uuid dan created_at | Aturan identitas hanya berlaku pada tabel domain |

## Ketertelusuran FR ke Tabel

| Modul FR | Tabel yang digunakan |
|---|---|
| FR-AUT | roles, users, whatsapp_verifications |
| FR-PRF | users, whatsapp_verifications |
| FR-DSH | kosts, room_types, complaints (agregasi) |
| FR-KOS | kosts, room_types, photos, kost_documents, facilities, kost_facilities, rules, kost_rules, specifications, room_type_specifications, room_type_facilities, room_type_rules |
| FR-SRC | kosts, room_types, photos, facilities, kost_facilities, rules, kost_rules, specifications, room_type_specifications, room_type_facilities, room_type_rules |
| FR-KTK | kosts, room_types, users (nomor WhatsApp Pemilik Kost dan nama Customer); tidak memerlukan tabel khusus |
| FR-ADU | complaints, complaint_attachments, kosts |
| FR-ADM | kosts, room_types, facilities, rules, specifications, users, complaints (agregasi) |
| FR-NTF | notifications |
