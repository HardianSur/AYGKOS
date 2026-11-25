# Spec Delta

## MODIFIED Requirements

### Requirement: Kebijakan timestamp ditentukan per tabel

Tabel domain yang menyimpan riwayat perubahan SHALL memiliki `created_at` dan `updated_at`. Tabel yang hanya mencatat kejadian sekali tulis (`whatsapp_verifications`, `notifications`) SHALL hanya memiliki `created_at` dan SHALL NOT memiliki `updated_at`. Tabel data master tanpa riwayat (`facilities`, `rules`, `specifications`, `photos`, `kost_documents`) SHALL NOT memiliki kolom timestamp apa pun. Setiap tabel yang memiliki kolom timestamp SHALL mengisi `created_at` saat baris dibuat.

#### Scenario: Tabel sekali tulis tidak mengisi updated_at

- **WHEN** sebuah baris `notifications` atau `whatsapp_verifications` dibuat
- **THEN** `created_at` terisi dan tabel tersebut tidak memiliki kolom `updated_at`

#### Scenario: Tabel data master tidak memiliki kolom waktu

- **WHEN** struktur `facilities`, `rules`, `specifications`, `photos`, atau `kost_documents` diperiksa
- **THEN** tidak ditemukan kolom `created_at` maupun `updated_at`

#### Scenario: Tipe kamar menyimpan riwayat perubahan

- **WHEN** struktur `room_types` diperiksa
- **THEN** tabel memiliki `created_at`, `updated_at`, dan `deleted_at`

### Requirement: Aksi referensial foreign key mengikuti ERD

Setiap foreign key SHALL memiliki aksi `on delete` yang dinyatakan ERD, yaitu `cascade` untuk data anak yang tidak bermakna tanpa induknya, `restrict` untuk data yang menahan penghapusan induk, dan `set null` untuk kolom penanggung jawab yang boleh kosong. Foreign key yang menunjuk `users` dari tabel `kosts` dan `complaints` SHALL NOT memakai `cascade`. Foreign key dari tabel master (`facilities`, `rules`, `specifications`) ke tabel pivot SHALL memakai `restrict`.

#### Scenario: Menghapus kost menghapus kamar dan fotonya

- **WHEN** sebuah baris `kosts` dihapus secara paksa
- **THEN** baris `room_types`, `photos`, `kost_documents`, `kost_facilities`, dan `kost_rules` yang menunjuk kost tersebut ikut terhapus

#### Scenario: Menghapus peran yang masih dipakai ditolak

- **WHEN** sebuah baris `roles` dihapus padahal masih ada baris `users` yang menunjuknya
- **THEN** basis data menolak penghapusan

#### Scenario: Menghapus Admin yang pernah menangani kost mengosongkan kolom penanggung jawab

- **WHEN** akun Admin dihapus
- **THEN** `kosts.reviewed_by` dan `complaints.handled_by` pada baris yang ia tangani menjadi null tanpa menghapus baris tersebut

#### Scenario: Master yang masih dipakai menahan penghapusan

- **WHEN** sebuah baris `facilities` dihapus padahal masih dirujuk `kost_facilities` atau `room_type_facilities`
- **THEN** basis data menolak penghapusan

### Requirement: Nama tabel dan kolom mengikuti konvensi plural snake_case

Nama tabel domain SHALL berupa bentuk plural snake_case dari nama model, sehingga tidak ada model yang memerlukan penimpaan nama tabel manual. Nama kolom SHALL berupa snake_case. Nama kolom bertipe timestamp SHALL berupa `created_at`, `updated_at`, dan `deleted_at`.

#### Scenario: Nama tabel sesuai bentuk plural model

- **WHEN** model `Kost`, `RoomType`, `Facility`, `Rule`, `Specification`, `Photo`, `KostDocument`, `Complaint`, `Notification`, `WhatsappVerification`, dan `ComplaintAttachment` disimpan ke basis data
- **THEN** masing-masing menulis ke tabel `kosts`, `room_types`, `facilities`, `rules`, `specifications`, `photos`, `kost_documents`, `complaints`, `notifications`, `whatsapp_verifications`, dan `complaint_attachments` tanpa penimpaan nama tabel

### Requirement: Validasi domain ditegakkan basis data melalui constraint check

Basis data SHALL menolak `kosts.latitude` di luar -90 sampai 90, `kosts.longitude` di luar -180 sampai 180, `room_types.price_per_month` yang tidak lebih besar dari 0, `room_types.total_rooms` yang tidak lebih besar dari 0, dan `room_types.available_rooms` yang negatif atau melebihi `total_rooms`. Penolakan SHALL terjadi pada tingkat basis data, bukan hanya pada validasi formulir, sehingga jalur seeder, impor data, dan perintah konsol ikut tertahan.

#### Scenario: Koordinat di luar rentang ditolak

- **WHEN** sebuah baris `kosts` disisipkan dengan `latitude = 100` atau `longitude = 200`
- **THEN** basis data menolak penyisipan dan baris tidak tersimpan

#### Scenario: Harga kamar nol atau negatif ditolak

- **WHEN** sebuah baris `room_types` disisipkan dengan `price_per_month = 0` atau nilai negatif
- **THEN** basis data menolak penyisipan dan baris tidak tersimpan

#### Scenario: Ketersediaan di luar jumlah kamar ditolak

- **WHEN** sebuah baris `room_types` disisipkan dengan `available_rooms` bernilai negatif atau lebih besar dari `total_rooms`
- **THEN** basis data menolak penyisipan dan baris tidak tersimpan

### Requirement: Penghapusan lunak hanya pada kost dan kamar

Tabel `kosts` dan `room_types` SHALL menyimpan `deleted_at` dan penghapusannya SHALL NOT menghapus baris. Tabel domain lainnya SHALL NOT memiliki `deleted_at`. Kueri baku model `Kost` dan `RoomType` SHALL mengabaikan baris yang sudah dihapus.

#### Scenario: Kost yang dihapus tidak muncul pada kueri baku

- **WHEN** sebuah baris `kosts` dihapus melalui model
- **THEN** `deleted_at` terisi, baris tetap ada di basis data, dan kueri `Kost::all()` tidak lagi menyertakannya

#### Scenario: Tipe kamar yang dihapus tidak muncul pada kueri baku

- **WHEN** sebuah baris `room_types` dihapus melalui model
- **THEN** `deleted_at` terisi dan kueri baku model `RoomType` tidak lagi menyertakannya

#### Scenario: Tabel aduan tidak memiliki penghapusan lunak

- **WHEN** sebuah baris `complaints` dihapus
- **THEN** baris tersebut hilang dari basis data karena tabelnya tidak memiliki `deleted_at`

## REMOVED Requirements

### Requirement: Kunci unik mencegah data ganda

**Reason**: Requirement ini menyebut `rooms.kost_id` + `rooms.room_number`, sedangkan `rooms` diganti `room_types`; aturan uniknya kini mencakup master data baru (`rules`, `specifications`) dan penanda `is_primary` pada `photos`.

**Migration**: Diganti requirement "Keunikan data domain ditegakkan basis data" pada delta ini, yang memuat keunikan `room_types.kost_id` + `name`, tabel pivot baru, dan `is_primary` per kost maupun per tipe kamar.

## ADDED Requirements

### Requirement: Keunikan data domain ditegakkan basis data

Basis data SHALL menolak email dan nomor WhatsApp pengguna yang sudah dipakai, nama peran, nama fasilitas, nama aturan, dan nama spesifikasi yang sudah ada, pasangan pivot yang sama, nama tipe kamar ganda dalam satu kost, serta lebih dari satu foto utama pada satu tingkat. Nomor WhatsApp kosong SHALL boleh berulang.

#### Scenario: Nama tipe kamar ganda pada satu kost ditolak

- **WHEN** dua baris `room_types` dengan `kost_id` yang sama disimpan dengan `name` yang sama
- **THEN** basis data menolak baris kedua

#### Scenario: Nama tipe kamar sama pada kost berbeda diterima

- **WHEN** dua kost berbeda masing-masing menyimpan `room_types` bernama "AC"
- **THEN** kedua baris tersimpan

#### Scenario: Nama master ganda ditolak

- **WHEN** sebuah baris `facilities`, `rules`, atau `specifications` disimpan dengan `name` yang sudah ada pada tabelnya
- **THEN** basis data menolak baris kedua

#### Scenario: Pasangan pivot ganda ditolak

- **WHEN** pasangan yang sama disimpan dua kali pada `kost_facilities`, `kost_rules`, `room_type_facilities`, `room_type_rules`, atau `room_type_specifications`
- **THEN** basis data menolak pasangan kedua

#### Scenario: Dua foto utama pada satu kost ditolak

- **WHEN** dua baris `photos` dengan `kost_id` yang sama sama-sama bertanda `is_primary` dan tidak menunjuk tipe kamar disimpan
- **THEN** basis data menolak baris kedua

#### Scenario: Dua foto utama pada satu tipe kamar ditolak

- **WHEN** dua baris `photos` dengan `room_type_id` yang sama sama-sama bertanda `is_primary` disimpan
- **THEN** basis data menolak baris kedua

#### Scenario: Banyak akun tanpa nomor WhatsApp tetap tersimpan

- **WHEN** lebih dari satu baris `users` disimpan tanpa `whatsapp_number`
- **THEN** seluruh baris tersimpan tanpa melanggar aturan unik
