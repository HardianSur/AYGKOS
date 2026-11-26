# Spec Delta

## ADDED Requirements

### Requirement: Halaman pemberitahuan verifikasi email memakai tata letak autentikasi

Halaman pemberitahuan verifikasi email SHALL memakai tata letak kartu autentikasi yang sama dengan halaman verifikasi WhatsApp dan login, SHALL menyebut alamat email tujuan verifikasi, dan SHALL menyediakan aksi kirim ulang serta keluar sebagai dua aksi terpisah.

#### Scenario: Halaman verifikasi email dibuka

- **WHEN** Customer yang belum memverifikasi email membuka halaman pemberitahuan verifikasi
- **THEN** halaman tampil dengan tata letak kartu autentikasi yang sama seperti halaman verifikasi WhatsApp, beserta judul dan penjelasan singkat di atas isi

#### Scenario: Alamat email tujuan ditampilkan

- **WHEN** halaman pemberitahuan verifikasi email dibuka oleh akun yang sedang login
- **THEN** halaman menyebut alamat email akun tersebut sebagai tujuan tautan verifikasi

#### Scenario: Kirim ulang berhasil

- **WHEN** pengguna menekan aksi kirim ulang dan permintaan diterima
- **THEN** halaman menampilkan pesan status bahwa tautan verifikasi baru sudah dikirim, tanpa memuat ulang mekanisme verifikasi

#### Scenario: Aksi keluar tersedia terpisah

- **WHEN** halaman pemberitahuan verifikasi email ditampilkan
- **THEN** tersedia aksi keluar yang terpisah dari aksi kirim ulang, dan aksi keluar mengakhiri sesi lalu mengarahkan ke halaman login