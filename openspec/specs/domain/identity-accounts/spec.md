# identity-accounts Specification

## Purpose

Menyimpan identitas pengguna AYGKOS: peran yang menyertai setiap akun, bukti verifikasi email dan nomor WhatsApp, serta kode OTP yang dipakai untuk membuktikan kepemilikan nomor WhatsApp Pemilik Kost.

## Requirements

### Requirement: Tiga peran tetap tersedia dan dapat ditunjuk akun

Sistem SHALL menyediakan tepat tiga baris `roles` bernama `customer`, `pemilik_kost`, dan `admin`, dibuat oleh seeder peran. Setiap baris `users` SHALL menunjuk tepat satu peran melalui `role_id` yang wajib terisi, dan peran yang masih dirujuk akun SHALL NOT dapat dihapus.

#### Scenario: Seeder peran mengisi ketiga peran

- **WHEN** seeder peran dijalankan pada basis data kosong
- **THEN** tabel `roles` berisi tiga baris dengan nama `customer`, `pemilik_kost`, dan `admin`

#### Scenario: Seeder peran aman dijalankan berulang

- **WHEN** seeder peran dijalankan dua kali
- **THEN** jumlah baris `roles` tetap tiga tanpa galat duplikasi

#### Scenario: Akun tanpa peran ditolak

- **WHEN** sebuah baris `users` hendak disimpan tanpa `role_id`
- **THEN** basis data menolak penyisipan

#### Scenario: Peran yang masih dipakai tidak dapat dihapus

- **WHEN** sebuah baris `roles` dihapus padahal ada akun yang menunjuknya
- **THEN** basis data menolak penghapusan

### Requirement: Akun menyimpan identitas, kontak, dan status aktif

Setiap akun SHALL menyimpan nama lengkap, email unik, password ter-hash, dan status aktif dengan nilai awal aktif. Nomor WhatsApp SHALL kosong untuk Customer dan Admin, dan SHALL terisi untuk Pemilik Kost. Akun yang dinonaktifkan SHALL tetap tersimpan dan tetap dapat ditunjuk baris lain sebagai pemilik atau penanggung jawab.

#### Scenario: Akun baru aktif secara bawaan

- **WHEN** sebuah akun dibuat tanpa menyebut status aktif
- **THEN** `is_active` bernilai benar

#### Scenario: Dua akun tidak boleh memakai email yang sama

- **WHEN** sebuah akun disimpan dengan email yang sudah dipakai akun lain
- **THEN** basis data menolak penyisipan

#### Scenario: Akun dinonaktifkan tetap tersimpan

- **WHEN** sebuah akun dinonaktifkan
- **THEN** baris akun tetap ada dengan `is_active` bernilai salah dan seluruh baris `kosts` miliknya tetap merujuk akun tersebut

### Requirement: Status terverifikasi ditentukan oleh bukti verifikasi yang terisi

Customer dan Admin SHALL dianggap terverifikasi ketika `email_verified_at` terisi. Pemilik Kost SHALL dianggap terverifikasi ketika `whatsapp_verified_at` terisi. Kedua kolom SHALL boleh kosong dan SHALL hanya diisi ketika verifikasi yang bersangkutan berhasil.

#### Scenario: Akun baru belum terverifikasi

- **WHEN** sebuah akun dibuat
- **THEN** `email_verified_at` dan `whatsapp_verified_at` bernilai kosong

#### Scenario: Pemilik Kost terverifikasi melalui WhatsApp

- **WHEN** kode OTP untuk sebuah akun Pemilik Kost berhasil dipakai
- **THEN** `whatsapp_verified_at` akun tersebut terisi

### Requirement: Kode OTP WhatsApp disimpan sebagai hash dengan pembatas waktu dan percobaan

Setiap baris `whatsapp_verifications` SHALL menyimpan kode OTP dalam bentuk hash, nomor tujuan yang diverifikasi, waktu kedaluwarsa, penghitung percobaan, penghitung kirim ulang, waktu kirim terakhir, dan waktu kode dipakai. Kode SHALL NOT disimpan dalam bentuk terbaca. Nomor tujuan SHALL disimpan terpisah dari `users.whatsapp_number` sehingga perubahan nomor tidak menimpa nomor lama sebelum verifikasi berhasil.

#### Scenario: Kode OTP tersimpan sebagai hash

- **WHEN** sebuah kode OTP dibuat untuk sebuah akun
- **THEN** kolom `code_hash` berisi hash dan kode terbaca tidak tersimpan di mana pun pada tabel tersebut

#### Scenario: Kode yang sudah dipakai tidak dapat dipakai lagi

- **WHEN** sebuah kode berhasil diverifikasi
- **THEN** `consumed_at` terisi dan kode yang sama SHALL ditolak bila dimasukkan kembali

#### Scenario: Perubahan nomor memakai nomor tujuan tersendiri

- **WHEN** seorang Pemilik Kost mengajukan perubahan nomor WhatsApp
- **THEN** baris `whatsapp_verifications` baru memakai nomor tujuan yang baru sementara `users.whatsapp_number` tetap memuat nomor lama sampai verifikasi berhasil

### Requirement: Kode OTP kedaluwarsa dan dibatasi jumlah percobaan

Kode OTP SHALL ditolak setelah melewati `expires_at`, setelah jumlah percobaan memasukkan kode melewati batas, dan ketika pengiriman ulang diminta lebih cepat daripada jeda yang diizinkan. Setiap penolakan SHALL menaikkan penghitung yang bersangkutan tanpa menandai kode sebagai terpakai.

#### Scenario: Kode kedaluwarsa ditolak

- **WHEN** kode dimasukkan setelah `expires_at` terlewati
- **THEN** verifikasi gagal dan `consumed_at` tetap kosong

#### Scenario: Percobaan berlebih menutup kode

- **WHEN** jumlah percobaan memasukkan kode melewati batas yang ditetapkan konfigurasi
- **THEN** verifikasi gagal walaupun kode yang dimasukkan benar

#### Scenario: Kirim ulang terlalu cepat ditolak

- **WHEN** kirim ulang diminta sebelum jeda minimum sejak `last_sent_at` terlewati
- **THEN** permintaan kirim ulang ditolak dan `resend_count` tidak bertambah
