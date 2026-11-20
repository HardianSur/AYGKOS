# auth/registration Specification

## Purpose

Mengatur pendaftaran mandiri AYGKOS: pemilihan peran Customer atau Pemilik Kost, isian yang wajib diisi tiap peran, validasi kolom, penolakan pendaftaran peran Admin, dan pengarahan setelah pendaftaran berhasil.

## Requirements

### Requirement: Formulir registrasi menawarkan dua peran

Halaman registrasi SHALL menampilkan pilihan peran sebagai isian paling atas formulir dengan dua opsi: pencari kos (Customer) dan pemilik kos (Pemilik Kost). Tidak ada opsi Admin pada formulir.

#### Scenario: Halaman registrasi ditampilkan

- **WHEN** pengunjung yang belum masuk membuka `/register`
- **THEN** halaman menampilkan pilihan peran sebelum isian nama, email, dan kata sandi, dan hanya memuat dua opsi peran

#### Scenario: Pemilihan peran menentukan isian tambahan

- **WHEN** pengunjung memilih peran pemilik kos
- **THEN** formulir menampilkan isian nomor WhatsApp yang wajib diisi
- **AND** ketika peran pencari kos dipilih, isian nomor WhatsApp tidak ditampilkan dan tidak diminta

#### Scenario: Permintaan tanpa peran ditolak

- **WHEN** formulir dikirim tanpa nilai peran yang valid
- **THEN** permintaan ditolak dan pesan galat validasi ditampilkan pada pilihan peran

### Requirement: Pendaftaran peran Admin ditolak

Sistem SHALL menolak setiap permintaan registrasi yang menyebut peran Admin, walaupun pemanggil mengirim nilai tersebut langsung tanpa melalui formulir.

#### Scenario: Peran Admin dikirim langsung

- **WHEN** permintaan registrasi dikirim dengan nilai peran Admin
- **THEN** tidak ada akun yang dibuat, permintaan ditolak validasi, dan pengunjung tetap tamu

### Requirement: Data akun yang diminta berbeda per peran

Sistem SHALL meminta nama lengkap, email, kata sandi, dan konfirmasi kata sandi untuk kedua peran, serta SHALL meminta nomor WhatsApp hanya untuk Pemilik Kost.

#### Scenario: Registrasi Customer berhasil

- **WHEN** pengunjung mengirim nama, email, kata sandi, dan konfirmasi kata sandi yang valid dengan peran pencari kos
- **THEN** akun dibuat dengan peran Customer dan tanpa nomor WhatsApp

#### Scenario: Registrasi Pemilik Kost berhasil

- **WHEN** pengunjung mengirim nama, email, nomor WhatsApp, kata sandi, dan konfirmasi kata sandi yang valid dengan peran pemilik kos
- **THEN** akun dibuat dengan peran Pemilik Kost dan nomor WhatsApp tersimpan pada akun tersebut

#### Scenario: Nomor WhatsApp tidak diminta dari Customer

- **WHEN** permintaan registrasi peran pencari kos menyertakan nomor WhatsApp
- **THEN** nomor tersebut tidak disimpan pada akun yang dibuat

### Requirement: Setiap kolom registrasi divalidasi dan galatnya ditampilkan per kolom

Sistem SHALL memvalidasi format email, keunikan email, kesesuaian kata sandi dengan konfirmasinya, kepatuhan kata sandi pada aturan kekuatan kata sandi, serta format dan keunikan nomor WhatsApp Pemilik Kost, dan SHALL menampilkan pesan galat pada kolom yang bersangkutan.

#### Scenario: Email tidak valid atau sudah terdaftar

- **WHEN** registrasi dikirim dengan email berformat salah atau email milik akun lain
- **THEN** tidak ada akun yang dibuat dan pesan galat tampil pada kolom email

#### Scenario: Konfirmasi kata sandi tidak sesuai

- **WHEN** registrasi dikirim dengan konfirmasi kata sandi yang berbeda dari kata sandi
- **THEN** tidak ada akun yang dibuat dan pesan galat tampil pada kolom konfirmasi kata sandi

#### Scenario: Kata sandi tidak memenuhi aturan kekuatan

- **WHEN** registrasi dikirim dengan kata sandi yang melanggar aturan kekuatan kata sandi yang berlaku
- **THEN** tidak ada akun yang dibuat dan pesan galat tampil pada kolom kata sandi

#### Scenario: Nomor WhatsApp tidak valid atau sudah dipakai

- **WHEN** registrasi Pemilik Kost dikirim dengan nomor WhatsApp berformat salah atau nomor yang sudah dipakai akun lain
- **THEN** tidak ada akun yang dibuat dan pesan galat tampil pada kolom nomor WhatsApp

#### Scenario: Nomor WhatsApp wajib bagi Pemilik Kost

- **WHEN** registrasi Pemilik Kost dikirim tanpa nomor WhatsApp
- **THEN** tidak ada akun yang dibuat dan pesan galat wajib isi tampil pada kolom nomor WhatsApp

### Requirement: Pendaftaran berhasil mengarahkan ke verifikasi sesuai peran

Sistem SHALL mengarahkan Customer yang baru mendaftar ke pemberitahuan verifikasi email dan Pemilik Kost yang baru mendaftar ke halaman verifikasi WhatsApp, dan SHALL menyatakan akun baru belum terverifikasi sampai verifikasi peran tersebut selesai.

#### Scenario: Customer diarahkan ke verifikasi email

- **WHEN** registrasi Customer berhasil
- **THEN** pengguna diarahkan ke halaman pemberitahuan verifikasi email dan `email_verified_at` akun masih kosong

#### Scenario: Pemilik Kost diarahkan ke verifikasi WhatsApp

- **WHEN** registrasi Pemilik Kost berhasil
- **THEN** pengguna diarahkan ke halaman verifikasi WhatsApp dan `whatsapp_verified_at` akun masih kosong

### Requirement: Pesan formulir registrasi berbahasa Indonesia

Sistem SHALL menyampaikan label, pilihan peran, dan pesan galat formulir registrasi dalam bahasa Indonesia dengan istilah yang dipakai dokumen proyek.

#### Scenario: Galat validasi berbahasa Indonesia

- **WHEN** formulir registrasi dikirim dengan kolom yang tidak valid
- **THEN** pesan galat yang tampil berbahasa Indonesia dan menyebut nama kolom yang bersangkutan
