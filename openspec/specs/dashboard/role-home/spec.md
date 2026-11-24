# role-home Specification

## Purpose

Menetapkan isi halaman `/dashboard` menurut peran pengguna selama dashboard per peran belum dibangun, sehingga Customer tidak melihat kerangka dashboard Pemilik Kost dan setiap peran tetap punya tujuan pengarahan setelah masuk yang sama.

## Requirements

### Requirement: Kendali akses dashboard mengikuti peran

Halaman `/dashboard` SHALL tetap hanya dapat dibuka pengguna yang sudah masuk dan terverifikasi sesuai perannya. Tamu SHALL dialihkan ke halaman masuk, dan pengguna yang bukti verifikasinya belum terpenuhi SHALL dialihkan ke halaman verifikasi.

#### Scenario: Tamu membuka dashboard

- **WHEN** pengunjung yang belum masuk membuka `/dashboard`
- **THEN** pengguna dialihkan ke halaman masuk

#### Scenario: Pengguna terverifikasi membuka dashboard

- **WHEN** pengguna yang sudah masuk dan terverifikasi membuka `/dashboard`
- **THEN** permintaan dijawab tanpa galat dan konten sesuai perannya ditampilkan

### Requirement: Customer melihat halaman sapaan sementara

Selama halaman pencarian kost belum tersedia, `/dashboard` untuk Customer SHALL menampilkan sapaan berisi nama lengkap pengguna pada satu judul tingkat pertama, tanpa kerangka dashboard Pemilik Kost maupun Admin. Halaman itu SHALL menyediakan satu kendali keluar akun, karena tanpa sidebar tidak ada menu pengguna untuk keluar.

#### Scenario: Customer membuka dashboard

- **WHEN** akun Customer yang terverifikasi membuka `/dashboard`
- **THEN** halaman menampilkan judul tingkat pertama berisi sapaan dan nama lengkap pengguna

#### Scenario: Customer keluar dari halaman sapaan

- **WHEN** akun Customer menekan kendali keluar pada halaman sapaan
- **THEN** sesinya berakhir dan pengguna dialihkan ke halaman masuk

#### Scenario: Customer tidak melihat kerangka dashboard

- **WHEN** akun Customer membuka `/dashboard`
- **THEN** halaman tersebut tidak menampilkan panel, kartu statistik, maupun wadah placeholder

### Requirement: Pemilik Kost dan Admin tetap melihat dashboard aplikasi

`/dashboard` untuk Pemilik Kost dan Admin SHALL tetap menampilkan dashboard aplikasi yang sudah ada, tanpa halaman sapaan sementara.

#### Scenario: Pemilik Kost membuka dashboard

- **WHEN** akun Pemilik Kost yang terverifikasi membuka `/dashboard`
- **THEN** dashboard aplikasi yang ada ditampilkan, bukan halaman sapaan

#### Scenario: Admin membuka dashboard

- **WHEN** akun Admin yang terverifikasi membuka `/dashboard`
- **THEN** dashboard aplikasi yang ada ditampilkan

### Requirement: Pengarahan setelah masuk tetap menuju dashboard untuk semua peran

Sistem SHALL mengarahkan setiap peran yang berhasil masuk ke `/dashboard`, dan perbedaan konten antar peran SHALL ditentukan oleh halaman itu sendiri, bukan oleh tujuan pengarahan yang berbeda.

#### Scenario: Customer, Pemilik Kost, dan Admin masuk berhasil

- **WHEN** salah satu dari ketiga peran berhasil masuk tanpa tujuan awal yang tertunda
- **THEN** pengguna dialihkan ke `/dashboard` dan melihat konten sesuai perannya
