# auth/login Specification

## Purpose

Mengatur perilaku halaman masuk AYGKOS: penerimaan kredensial email dan kata sandi, penolakan kredensial salah, akun nonaktif, dan akun belum terverifikasi, serta tujuan pengarahan setelah masuk berhasil.

## Requirements

### Requirement: Formulir masuk menerima email dan kata sandi

Halaman masuk SHALL menampilkan formulir dengan isian email, isian kata sandi, dan pilihan "Ingat saya", serta SHALL mengirim keduanya ke endpoint masuk yang sama dengan kredensial akun.

#### Scenario: Halaman masuk ditampilkan

- **WHEN** pengunjung yang belum masuk membuka `/login`
- **THEN** halaman menampilkan isian email, isian kata sandi, pilihan "Ingat saya", tombol masuk, dan tautan menuju halaman registrasi

#### Scenario: Email dan kata sandi wajib diisi

- **WHEN** formulir dikirim dengan email atau kata sandi kosong
- **THEN** permintaan ditolak dan pesan galat validasi ditampilkan pada isian yang bersangkutan tanpa menyebut ada atau tidaknya akun

#### Scenario: Kata sandi dapat diperiksa keterbacaannya

- **WHEN** pengguna menekan tombol tampilkan kata sandi
- **THEN** isi isian kata sandi berganti antara tersembunyi dan terbaca tanpa mengubah nilainya

### Requirement: Kredensial yang salah ditolak dengan pesan generik

Sistem SHALL menolak percobaan masuk dengan email tidak terdaftar dan percobaan masuk dengan kata sandi salah memakai satu pesan galat yang sama, yang tidak mengungkap bagian kredensial mana yang keliru.

#### Scenario: Email tidak terdaftar

- **WHEN** percobaan masuk memakai email yang tidak terdaftar disertai kata sandi apa pun
- **THEN** pengguna tetap tamu dan ditampilkan pesan galat yang sama dengan kasus kata sandi salah

#### Scenario: Kata sandi salah untuk email terdaftar

- **WHEN** percobaan masuk memakai email terdaftar dengan kata sandi yang salah
- **THEN** pengguna tetap tamu dan ditampilkan pesan galat yang sama dengan kasus email tidak terdaftar

### Requirement: Akun nonaktif ditolak saat masuk

Sistem SHALL menolak percobaan masuk ke akun yang ditandai nonaktif walaupun email dan kata sandinya benar, dan SHALL menyatakan bahwa akun tersebut nonaktif.

#### Scenario: Akun nonaktif dengan kredensial benar

- **WHEN** percobaan masuk memakai kredensial akun yang benar sedangkan akun ditandai nonaktif
- **THEN** pengguna tetap tamu dan ditampilkan pesan bahwa akun dinonaktifkan, bukan pesan kredensial salah

#### Scenario: Akun nonaktif tidak memulai sesi

- **WHEN** percobaan masuk akun nonaktif ditolak
- **THEN** tidak ada sesi masuk yang dibuat dan permintaan ke halaman terlindung tetap dialihkan ke halaman masuk

### Requirement: Akun belum terverifikasi ditolak saat masuk

Sistem SHALL menolak percobaan masuk akun yang belum memenuhi bukti verifikasi sesuai perannya walaupun kredensial benar, dan SHALL mengarahkan pengguna menyelesaikan verifikasi. Customer dan Admin SHALL dianggap terverifikasi bila email terbukti, sedangkan Pemilik Kost SHALL dianggap terverifikasi bila nomor WhatsApp terbukti.

#### Scenario: Customer belum memverifikasi email

- **WHEN** percobaan masuk memakai kredensial benar akun Customer yang emailnya belum terbukti
- **THEN** pengguna tetap tamu dan ditampilkan pesan bahwa akun belum terverifikasi beserta arahan verifikasi email

#### Scenario: Admin belum memverifikasi email

- **WHEN** percobaan masuk memakai kredensial benar akun Admin yang emailnya belum terbukti
- **THEN** pengguna tetap tamu dan ditampilkan pesan bahwa akun belum terverifikasi

#### Scenario: Pemilik Kost belum memverifikasi WhatsApp

- **WHEN** percobaan masuk memakai kredensial benar akun Pemilik Kost yang emailnya terbukti tetapi nomor WhatsApp-nya belum terbukti
- **THEN** pengguna tetap tamu dan ditampilkan pesan bahwa akun belum terverifikasi beserta arahan verifikasi WhatsApp

#### Scenario: Pemilik Kost sudah memverifikasi WhatsApp

- **WHEN** percobaan masuk memakai kredensial benar akun Pemilik Kost yang nomor WhatsApp-nya sudah terbukti walaupun emailnya belum terbukti
- **THEN** pengguna masuk sebagai Pemilik Kost

### Requirement: Pesan galat masuk disampaikan dalam bahasa Indonesia

Pesan galat masuk dan pesan galat validasi dasar pada formulir masuk SHALL disampaikan dalam bahasa Indonesia dengan istilah yang dipakai dokumen proyek.

#### Scenario: Pesan galat berbahasa Indonesia

- **WHEN** percobaan masuk gagal karena kredensial salah, akun nonaktif, akun belum terverifikasi, atau batas percobaan terlampaui
- **THEN** pesan yang tampil menggunakan kalimat bahasa Indonesia

#### Scenario: Galat validasi berbahasa Indonesia

- **WHEN** formulir masuk dikirim dengan isian wajib yang kosong atau format email yang tidak sah
- **THEN** pesan galat pada isian tersebut menggunakan kalimat bahasa Indonesia

### Requirement: Masuk berhasil mengarahkan ke dashboard

Setelah autentikasi berhasil, sistem SHALL memulai sesi terautentikasi dan mengarahkan pengguna ke `/dashboard` untuk semua peran, termasuk bila pengguna sebelumnya terhalang saat membuka halaman terlindung.

#### Scenario: Customer masuk berhasil

- **WHEN** percobaan masuk memakai kredensial benar akun Customer yang aktif dan terverifikasi
- **THEN** pengguna terautentikasi dan dialihkan ke `/dashboard`

#### Scenario: Pemilik Kost dan Admin masuk berhasil

- **WHEN** percobaan masuk memakai kredensial benar akun Pemilik Kost atau Admin yang aktif dan terverifikasi
- **THEN** pengguna terautentikasi dan dialihkan ke `/dashboard`

#### Scenario: Tujuan awal dihormati

- **WHEN** pengguna yang belum masuk diarahkan ke halaman terlindung lalu menyelesaikan masuk dengan kredensial benar
- **THEN** pengguna dialihkan ke halaman terlindung yang semula dituju

### Requirement: Pilihan "Ingat saya" memperpanjang sesi

Sistem SHALL memperpanjang masa sesi pengguna yang masuk dengan pilihan "Ingat saya" aktif, dan SHALL memakai sesi biasa bila pilihan tersebut tidak aktif.

#### Scenario: Masuk dengan "Ingat saya"

- **WHEN** pengguna mengirim formulir masuk dengan pilihan "Ingat saya" aktif
- **THEN** sesi pengguna disimpan sebagai sesi yang bertahan setelah peramban ditutup

#### Scenario: Masuk tanpa "Ingat saya"

- **WHEN** pengguna mengirim formulir masuk tanpa mengaktifkan pilihan "Ingat saya"
- **THEN** sesi pengguna hanya berlaku selama sesi peramban

### Requirement: Percobaan masuk dibatasi

Sistem SHALL membatasi jumlah percobaan masuk yang gagal berulang dan SHALL menolak percobaan berikutnya hingga tenggang waktu habis.

#### Scenario: Batas percobaan terlampaui

- **WHEN** percobaan masuk yang gagal melewati batas yang ditetapkan dalam tenggang waktu tertentu
- **THEN** percobaan masuk berikutnya ditolak dengan pesan batas percobaan beserta lama waktu tunggu, tanpa memeriksa kredensial

#### Scenario: Tenggang waktu habis

- **WHEN** tenggang waktu setelah batas percobaan terlampaui terlewati
- **THEN** percobaan masuk dengan kredensial benar diterima kembali