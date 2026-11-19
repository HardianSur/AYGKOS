# Spec Delta

## Purpose

Menetapkan aturan yang berlaku seragam pada seluruh tabel domain AYGKOS, sehingga setiap tabel baru otomatis mewarisi identitas, penamaan, timestamp, dan aksi referensial yang sama tanpa perlu diputuskan ulang.

## ADDED Requirements

### Requirement: Identitas setiap tabel domain berupa UUID yang dibangkitkan aplikasi

Setiap tabel domain SHALL memakai kolom `id` bertipe `uuid` sebagai primary key tunggal, dan nilainya SHALL dibangkitkan lapisan aplikasi. Tidak ada tabel domain yang memakai primary key integer, dan tidak ada default basis data yang membangkitkan nilai `id`. Setiap kolom foreign key yang menunjuk tabel domain SHALL bertipe `uuid`.

#### Scenario: Baris baru mendapat id uuid tanpa default basis data

- **WHEN** satu baris disisipkan ke tabel domain tanpa menyertakan `id`
- **THEN** tersimpan `id` berformat uuid yang dibangkitkan aplikasi, dan definisi kolom `id` tidak memiliki nilai default di basis data

#### Scenario: Foreign key bertipe uuid menolak nilai integer

- **WHEN** sebuah baris disisipkan dengan nilai foreign key berupa integer, misalnya `role_id = 1`
- **THEN** basis data menolak penyisipan karena tipe kolomnya uuid, bukan integer

### Requirement: Kebijakan timestamp ditentukan per tabel

Tabel domain yang menyimpan riwayat perubahan SHALL memiliki `created_at` dan `updated_at`. Tabel yang hanya mencatat kejadian sekali tulis (`whatsapp_verifications`, `notifications`) SHALL hanya memiliki `created_at` dan SHALL NOT memiliki `updated_at`. Tabel data master tanpa riwayat (`facilities`, `kost_photos`) SHALL NOT memiliki kolom timestamp apa pun. Setiap tabel yang memiliki kolom timestamp SHALL mengisi `created_at` saat baris dibuat.

#### Scenario: Tabel sekali tulis tidak mengisi updated_at

- **WHEN** sebuah baris `notifications` atau `whatsapp_verifications` dibuat
- **THEN** `created_at` terisi dan tabel tersebut tidak memiliki kolom `updated_at`

#### Scenario: Tabel data master tidak memiliki kolom waktu

- **WHEN** struktur `facilities` atau `kost_photos` diperiksa
- **THEN** tidak ditemukan kolom `created_at` maupun `updated_at`

### Requirement: Aksi referensial foreign key mengikuti ERD

Setiap foreign key SHALL memiliki aksi `on delete` yang dinyatakan ERD, yaitu `cascade` untuk data anak yang tidak bermakna tanpa induknya, `restrict` untuk data yang menahan penghapusan induk, dan `set null` untuk kolom penanggung jawab yang boleh kosong. Foreign key yang menunjuk `users` dari tabel `kosts` dan `complaints` SHALL NOT memakai `cascade`.

#### Scenario: Menghapus kost menghapus kamar dan fotonya

- **WHEN** sebuah baris `kosts` dihapus secara paksa
- **THEN** baris `rooms`, `kost_photos`, dan `kost_facilities` yang menunjuk kost tersebut ikut terhapus

#### Scenario: Menghapus peran yang masih dipakai ditolak

- **WHEN** sebuah baris `roles` dihapus padahal masih ada baris `users` yang menunjuknya
- **THEN** basis data menolak penghapusan

#### Scenario: Menghapus Admin yang pernah menangani kost mengosongkan kolom penanggung jawab

- **WHEN** akun Admin dihapus
- **THEN** `kosts.reviewed_by` dan `complaints.handled_by` pada baris yang ia tangani menjadi null tanpa menghapus baris tersebut

### Requirement: Nama tabel dan kolom mengikuti konvensi plural snake_case

Nama tabel domain SHALL berupa bentuk plural snake_case dari nama model, sehingga tidak ada model yang memerlukan penimpaan nama tabel manual. Nama kolom SHALL berupa snake_case. Nama kolom bertipe timestamp SHALL berupa `created_at`, `updated_at`, dan `deleted_at`.

#### Scenario: Nama tabel sesuai bentuk plural model

- **WHEN** model `Kost`, `Facility`, `Room`, `Complaint`, `Notification`, `WhatsappVerification`, `KostPhoto`, dan `ComplaintAttachment` disimpan ke basis data
- **THEN** masing-masing menulis ke tabel `kosts`, `facilities`, `rooms`, `complaints`, `notifications`, `whatsapp_verifications`, `kost_photos`, dan `complaint_attachments` tanpa penimpaan nama tabel

### Requirement: Validasi domain ditegakkan basis data melalui constraint check

Basis data SHALL menolak `kosts.latitude` di luar -90 sampai 90, `kosts.longitude` di luar -180 sampai 180, dan `rooms.price_per_month` yang tidak lebih besar dari 0. Penolakan SHALL terjadi pada tingkat basis data, bukan hanya pada validasi formulir, sehingga jalur seeder, impor data, dan perintah konsol ikut tertahan.

#### Scenario: Koordinat di luar rentang ditolak

- **WHEN** sebuah baris `kosts` disisipkan dengan `latitude = 100` atau `longitude = 200`
- **THEN** basis data menolak penyisipan dan baris tidak tersimpan

#### Scenario: Harga kamar nol atau negatif ditolak

- **WHEN** sebuah baris `rooms` disisipkan dengan `price_per_month = 0` atau nilai negatif
- **THEN** basis data menolak penyisipan dan baris tidak tersimpan

### Requirement: Kunci unik mencegah data ganda

Basis data SHALL menolak `users.email` dan `users.whatsapp_number` yang sudah dipakai, `roles.name` dan `facilities.name` yang sudah ada, pasangan `kost_facilities` yang sama, serta pasangan `rooms.kost_id` dengan `rooms.room_number` yang sama. Nilai `users.whatsapp_number` kosong SHALL diizinkan lebih dari satu baris.

#### Scenario: Nomor kamar ganda pada satu kost ditolak

- **WHEN** dua baris `rooms` dengan `kost_id` yang sama disimpan dengan `room_number` yang sama
- **THEN** basis data menolak baris kedua

#### Scenario: Banyak akun tanpa nomor WhatsApp tetap tersimpan

- **WHEN** lebih dari satu baris `users` disimpan tanpa `whatsapp_number`
- **THEN** seluruh baris tersimpan tanpa melanggar aturan unik

### Requirement: Penghapusan lunak hanya pada kost dan kamar

Tabel `kosts` dan `rooms` SHALL menyimpan `deleted_at` dan penghapusannya SHALL NOT menghapus baris. Tabel domain lainnya SHALL NOT memiliki `deleted_at`. Kueri baku model `Kost` dan `Room` SHALL mengabaikan baris yang sudah dihapus.

#### Scenario: Kost yang dihapus tidak muncul pada kueri baku

- **WHEN** sebuah baris `kosts` dihapus melalui model
- **THEN** `deleted_at` terisi, baris tetap ada di basis data, dan kueri `Kost::all()` tidak lagi menyertakannya

#### Scenario: Tabel aduan tidak memiliki penghapusan lunak

- **WHEN** sebuah baris `complaints` dihapus
- **THEN** baris tersebut hilang dari basis data karena tabelnya tidak memiliki `deleted_at`

### Requirement: Tabel milik framework berada di luar aturan tabel domain

Tabel bawaan framework (`sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`) SHALL NOT terikat aturan identitas uuid, kebijakan timestamp, maupun penamaan tabel domain. Satu-satunya penyesuaian pada tabel framework SHALL berupa tipe `sessions.user_id` yang mengikuti tipe `users.id`.

#### Scenario: Tabel sesi tetap memakai primary key string

- **WHEN** struktur tabel `sessions` diperiksa
- **THEN** `sessions.id` bertipe string dan `sessions.user_id` bertipe uuid, tanpa kolom `created_at`

#### Scenario: Tabel cache tidak diubah menjadi uuid

- **WHEN** struktur tabel `cache` diperiksa
- **THEN** `cache.key` tetap berupa primary key string dan tabel tersebut tidak memiliki kolom `id` uuid

### Requirement: Basis data pengujian memakai PostgreSQL yang sama dengan aplikasi

Suite tes SHALL berjalan pada PostgreSQL, bukan SQLite. Nama basis data pengujian SHALL berbeda dari basis data aplikasi agar `RefreshDatabase` tidak menghapus data pengembangan. Constraint check dan tipe uuid SHALL ikut teruji pada suite.

#### Scenario: Suite tes memakai PostgreSQL

- **WHEN** `php artisan test` dijalankan
- **THEN** tes terhubung ke koneksi `pgsql` dengan basis data `aygkos_testing`, dan tes skema yang bergantung pada constraint check serta tipe uuid berjalan

#### Scenario: Data pengembangan tidak tersentuh saat tes

- **WHEN** suite tes berjalan sementara basis data aplikasi berisi data
- **THEN** data pada basis data aplikasi tetap utuh karena tes memakai basis data tersendiri