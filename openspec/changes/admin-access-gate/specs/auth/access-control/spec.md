# Spec Delta

## ADDED Requirements

### Requirement: Akses halaman per peran dibatasi di server

Sistem SHALL membatasi setiap route yang ditandai peran kepada pengguna yang sudah masuk, sudah memenuhi verifikasi perannya, dan memegang peran yang ditandai. Permintaan oleh pengguna dengan peran lain SHALL ditolak, bukan dialihkan ke dashboard. Permintaan oleh pengguna yang tidak punya peran SHALL ditolak dengan cara yang sama.

#### Scenario: Peran yang berwenang membuka route

- **WHEN** akun Admin yang terverifikasi membuka route yang ditandai `role:admin`
- **THEN** permintaan dijawab tanpa galat dan halaman ditampilkan

#### Scenario: Peran lain ditolak

- **WHEN** akun Customer atau Pemilik Kost yang terverifikasi membuka route yang ditandai `role:admin`
- **THEN** permintaan ditolak dengan status 403 dan tidak ada halaman ditampilkan

#### Scenario: Tamu dialihkan ke halaman masuk

- **WHEN** pengunjung yang belum masuk membuka route yang ditandai `role:admin`
- **THEN** pengguna dialihkan ke halaman masuk, bukan menerima 403

#### Scenario: Pengguna tanpa peran ditolak

- **WHEN** akun yang tidak menunjuk baris peran mana pun membuka route yang ditandai `role:admin`
- **THEN** permintaan ditolak dengan status 403

#### Scenario: Verifikasi peran tetap berlaku

- **WHEN** akun Admin yang emailnya belum terbukti membuka route yang ditandai `role:admin`
- **THEN** pengguna dialihkan ke halaman verifikasi, bukan menerima 403

### Requirement: Penolakan akses tidak mengungkap isi halaman

Balasan penolakan SHALL berupa status 403 tanpa data halaman, tanpa prop Inertia, dan tanpa jejak internal aplikasi.

#### Scenario: Balasan 403 tanpa prop halaman

- **WHEN** permintaan oleh peran yang tidak berwenang ditolak
- **THEN** balasan hanya berstatus 403 dan tidak memuat prop halaman yang dilindungi

#### Scenario: Permintaan JSON juga ditolak

- **WHEN** permintaan oleh peran yang tidak berwenang dikirim dengan harapan balasan JSON
- **THEN** balasan berstatus 403 tanpa jejak internal

### Requirement: Peran pengguna tersedia untuk antarmuka

Data pengguna yang dibagikan ke antarmuka SHALL memuat nama peran pengguna yang sedang masuk sebagai nilai teks, sehingga antarmuka dapat menyusun navigasi sesuai peran tanpa meminta data tambahan.

#### Scenario: Nama peran ikut dibagikan

- **WHEN** akun Admin membuka halaman mana pun
- **THEN** data pengguna yang diterima antarmuka memuat nama peran `admin`

#### Scenario: Pengguna tanpa peran tetap dapat dirender

- **WHEN** akun tanpa peran membuka halaman yang tidak dibatasi peran
- **THEN** data peran bernilai kosong dan halaman tetap dapat dirender
