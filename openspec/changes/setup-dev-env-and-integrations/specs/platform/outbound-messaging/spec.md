# Spec Delta

## Purpose

Menyediakan satu jalur pengiriman pesan keluar sistem untuk email dan OTP WhatsApp, sehingga pengiriman dapat diverifikasi, dibatasi frekuensinya, dan diganti penyedianya tanpa mengubah alur verifikasi akun.

## ADDED Requirements

### Requirement: Pengiriman email verifikasi dan reset password

Sistem SHALL mengirim email verifikasi akun Customer dan tautan reset password melalui penyedia SMTP yang dikonfigurasi, dan SHALL menyediakan mode tulis-ke-log agar pengembangan dan demo tidak bergantung pada penyedia eksternal.

#### Scenario: Email verifikasi terkirim

- **WHEN** registrasi Customer berhasil
- **THEN** sistem mengirim email berisi tautan verifikasi ke alamat email terdaftar dan mencatat keberhasilan pengiriman pada log

#### Scenario: Penyedia email gagal

- **WHEN** penyedia SMTP menolak atau tidak merespons
- **THEN** akun tetap terbuat, kegagalan dicatat pada log, dan pengguna dapat meminta kirim ulang tautan verifikasi

#### Scenario: Mode tulis-ke-log

- **WHEN** mode pengiriman email diatur ke log
- **THEN** isi email beserta tautannya tertulis pada log aplikasi tanpa upaya pengiriman keluar

### Requirement: Pengiriman kode OTP WhatsApp

Sistem SHALL mengirim kode OTP ke nomor WhatsApp Pemilik Kost melalui antarmuka pengirim yang dapat diganti penyedianya, dan SHALL menyediakan pengirim berbasis log sebagai saklar darurat.

#### Scenario: OTP terkirim setelah registrasi

- **WHEN** registrasi Pemilik Kost berhasil
- **THEN** sistem membuat kode OTP, menyimpan hash-nya, dan mengirim kode ke nomor WhatsApp yang didaftarkan

#### Scenario: OTP untuk nomor baru

- **WHEN** Pemilik Kost mengubah nomor WhatsApp
- **THEN** sistem mengirim kode OTP ke nomor baru tanpa mengubah nomor yang tersimpan sampai verifikasi berhasil

#### Scenario: Pengirim OTP gagal

- **WHEN** penyedia pengiriman OTP mengembalikan kegagalan
- **THEN** sistem menampilkan pesan bahwa kode gagal dikirim, mempertahankan akun pada status belum terverifikasi, dan mengizinkan kirim ulang setelah batas waktu tunggu

#### Scenario: Pengirim OTP berbasis log

- **WHEN** driver pengiriman OTP diatur ke log
- **THEN** kode OTP tertulis pada log aplikasi dan tidak ada pesan WhatsApp yang dikirim

### Requirement: Batas waktu dan percobaan kode OTP

Sistem SHALL membatasi masa berlaku kode OTP dan jumlah percobaan verifikasi, serta SHALL menolak kode yang kedaluwarsa atau telah melampaui batas percobaan.

#### Scenario: Kode kedaluwarsa

- **WHEN** pengguna memasukkan kode OTP setelah masa berlakunya lewat
- **THEN** sistem menolak kode dan menawarkan pengiriman kode baru

#### Scenario: Batas percobaan terlampaui

- **WHEN** pengguna memasukkan kode salah melebihi jumlah percobaan yang diizinkan
- **THEN** sistem menolak kode tersebut untuk seterusnya dan mewajibkan permintaan kode baru

#### Scenario: Kode benar

- **WHEN** pengguna memasukkan kode yang benar, belum kedaluwarsa, dan belum melampaui batas percobaan
- **THEN** sistem menandai kode sebagai terpakai dan menetapkan nomor WhatsApp sebagai terverifikasi

#### Scenario: Kode yang sudah terpakai

- **WHEN** pengguna memasukkan kembali kode yang sudah pernah berhasil dipakai
- **THEN** sistem menolak kode tersebut

### Requirement: Pembatasan frekuensi pengiriman

Sistem SHALL membatasi frekuensi permintaan kirim ulang email verifikasi dan OTP WhatsApp per akun, dan SHALL menampilkan sisa waktu tunggu ketika batas belum terlewati.

#### Scenario: Kirim ulang terlalu cepat

- **WHEN** pengguna meminta kirim ulang sebelum jeda minimum terlewati
- **THEN** sistem menolak permintaan dan menampilkan sisa waktu tunggu

#### Scenario: Batas pengiriman per jam terlampaui

- **WHEN** permintaan kirim ulang melebihi jumlah maksimum dalam satu jam
- **THEN** sistem menolak permintaan dan menginformasikan bahwa batas pengiriman tercapai

#### Scenario: Kirim ulang diperbolehkan

- **WHEN** jeda minimum telah terlewati dan batas per jam belum tercapai
- **THEN** sistem mengirim kode baru dan membatalkan kode sebelumnya

### Requirement: Rahasia penyedia tidak masuk repositori

Sistem SHALL membaca kredensial penyedia email, penyedia OTP, dan penyedia peta hanya dari variabel lingkungan, dan SHALL NOT menyimpan kredensial tersebut pada berkas yang dilacak repositori.

#### Scenario: Kredensial tidak dilacak

- **WHEN** repositori diperiksa dengan `git status`
- **THEN** tidak ada berkas berisi token atau kata sandi penyedia yang termasuk berkas terlacak

#### Scenario: Kredensial tidak tersedia

- **WHEN** aplikasi dijalankan tanpa kredensial penyedia OTP
- **THEN** aplikasi tetap dapat dijalankan dan pengiriman OTP memakai driver log dengan peringatan pada log