# auth/verification Specification

## Purpose

Mengatur penyelesaian verifikasi akun AYGKOS lewat dua jalur: tautan email untuk Customer dan kode OTP WhatsApp untuk Pemilik Kost, termasuk penerbitan kode, masa berlaku, batas percobaan, kirim ulang dengan pembatasan frekuensi, dan penetapan status terverifikasi.

## Requirements

### Requirement: Customer menerima tautan verifikasi email setelah mendaftar

Sistem SHALL mengirim email berisi tautan verifikasi ke alamat terdaftar setelah registrasi Customer berhasil, dan SHALL menandai `email_verified_at` hanya ketika tautan tersebut dibuka oleh pemilik akun.

#### Scenario: Email verifikasi terkirim setelah registrasi

- **WHEN** registrasi Customer berhasil
- **THEN** sistem mengirim email verifikasi ke alamat email akun tersebut dan `email_verified_at` masih kosong

#### Scenario: Tautan verifikasi dibuka

- **WHEN** pemilik akun membuka tautan verifikasi yang dikirim
- **THEN** `email_verified_at` terisi dan akun dianggap terverifikasi

#### Scenario: Tautan verifikasi tidak sah

- **WHEN** tautan verifikasi dibuka dengan tanda tangan yang tidak sah, sudah kedaluwarsa, atau untuk akun lain
- **THEN** verifikasi ditolak dan `email_verified_at` tetap kosong

### Requirement: Pemilik Kost menerima kode OTP WhatsApp setelah mendaftar

Sistem SHALL membuat kode OTP setelah registrasi Pemilik Kost berhasil, menyimpan hash kode beserta nomor tujuan dan masa berlakunya, dan mengirim kode terbaca ke nomor WhatsApp yang didaftarkan.

#### Scenario: Kode OTP terbit setelah registrasi

- **WHEN** registrasi Pemilik Kost berhasil
- **THEN** sistem menyimpan satu baris verifikasi berisi hash kode, nomor tujuan, dan waktu kedaluwarsa, lalu mengirim kode terbaca ke nomor tersebut

#### Scenario: Kode terbaca tidak tersimpan

- **WHEN** kode OTP terbit
- **THEN** tabel verifikasi hanya memuat hash kode dan kode terbaca tidak tersimpan di mana pun

#### Scenario: Pengirim OTP gagal

- **WHEN** pengiriman kode ke penyedia WhatsApp gagal
- **THEN** akun tetap ada pada status belum terverifikasi, kegagalan dicatat pada log, dan pengguna dapat meminta kirim ulang setelah jeda terlewati

### Requirement: Kode OTP yang benar menetapkan nomor WhatsApp sebagai terverifikasi

Sistem SHALL menandai kode sebagai terpakai dan mengisi `whatsapp_verified_at` ketika kode yang dimasukkan benar, belum kedaluwarsa, dan belum melampaui batas percobaan.

#### Scenario: Kode benar diterima

- **WHEN** Pemilik Kost memasukkan kode yang benar, belum kedaluwarsa, dan belum melampaui batas percobaan
- **THEN** kode ditandai terpakai, `whatsapp_verified_at` terisi, dan akun dianggap terverifikasi

#### Scenario: Kode terpakai ditolak

- **WHEN** kode yang sudah pernah berhasil dipakai dimasukkan kembali
- **THEN** verifikasi ditolak dan status terverifikasi tidak berubah

### Requirement: Kode OTP yang salah, kedaluwarsa, atau melebihi batas percobaan ditolak

Sistem SHALL menolak kode yang salah, kode yang masa berlakunya lewat, dan kode yang percobaannya sudah melampaui batas, serta SHALL menaikkan penghitung percobaan tanpa menandai kode sebagai terpakai.

#### Scenario: Kode salah ditolak

- **WHEN** Pemilik Kost memasukkan kode yang tidak cocok dengan hash tersimpan
- **THEN** verifikasi ditolak, penghitung percobaan bertambah, dan `whatsapp_verified_at` tetap kosong

#### Scenario: Kode kedaluwarsa ditolak

- **WHEN** kode dimasukkan setelah masa berlakunya lewat
- **THEN** verifikasi ditolak walaupun kodenya benar, dan pengguna ditawari permintaan kode baru

#### Scenario: Batas percobaan terlampaui

- **WHEN** jumlah percobaan memasukkan kode melampaui batas yang ditetapkan
- **THEN** kode tersebut ditolak untuk seterusnya walaupun kodenya benar, dan pengguna wajib meminta kode baru

### Requirement: Kirim ulang verifikasi dibatasi frekuensinya

Sistem SHALL membatasi permintaan kirim ulang tautan email dan kode OTP per akun dengan jeda minimum dan batas jumlah pengiriman per jam, SHALL menampilkan sisa waktu tunggu ketika jeda belum terlewati, dan SHALL membatalkan kode sebelumnya ketika kode baru terbit.

#### Scenario: Kirim ulang terlalu cepat

- **WHEN** pengguna meminta kirim ulang sebelum jeda minimum sejak pengiriman terakhir terlewati
- **THEN** permintaan ditolak dan sisa waktu tunggu ditampilkan

#### Scenario: Batas pengiriman per jam terlampaui

- **WHEN** permintaan kirim ulang melampaui jumlah maksimum dalam satu jam untuk akun tersebut
- **THEN** permintaan ditolak dan pengguna diberi tahu bahwa batas pengiriman tercapai

#### Scenario: Kirim ulang diperbolehkan

- **WHEN** jeda minimum sudah terlewati dan batas per jam belum terlampaui
- **THEN** sistem mengirim kode atau tautan baru dan kode sebelumnya tidak lagi dapat dipakai

### Requirement: Status terverifikasi mengikuti peran akun

Sistem SHALL menganggap Customer dan Admin terverifikasi ketika `email_verified_at` terisi, dan Pemilik Kost terverifikasi ketika `whatsapp_verified_at` terisi, dan pemeriksaan verifikasi pada route terlindung SHALL memakai aturan tersebut.

#### Scenario: Pemilik Kost terverifikasi tanpa email terbukti

- **WHEN** Pemilik Kost membuka route yang mensyaratkan akun terverifikasi sedangkan `whatsapp_verified_at` terisi dan `email_verified_at` kosong
- **THEN** permintaan dilayani tanpa pengalihan ke halaman verifikasi email

#### Scenario: Customer belum terverifikasi email

- **WHEN** Customer membuka route yang mensyaratkan akun terverifikasi sedangkan `email_verified_at` kosong
- **THEN** permintaan dialihkan ke halaman pemberitahuan verifikasi email

#### Scenario: Pemilik Kost belum terverifikasi WhatsApp

- **WHEN** Pemilik Kost membuka route yang mensyaratkan akun terverifikasi sedangkan `whatsapp_verified_at` kosong
- **THEN** permintaan dialihkan ke halaman verifikasi WhatsApp, bukan ke halaman verifikasi email

### Requirement: Halaman verifikasi berbahasa Indonesia

Sistem SHALL menyampaikan petunjuk, keadaan berhasil, dan pesan galat pada halaman verifikasi email dan verifikasi WhatsApp dalam bahasa Indonesia.

#### Scenario: Galat kode OTP berbahasa Indonesia

- **WHEN** kode OTP yang dimasukkan ditolak
- **THEN** pesan galat yang tampil berbahasa Indonesia dan menyatakan sebab penolakan

#### Scenario: Keadaan kirim ulang berbahasa Indonesia

- **WHEN** kirim ulang berhasil atau ditolak karena batas waktu
- **THEN** halaman menampilkan pemberitahuan berbahasa Indonesia yang menyatakan hasilnya
