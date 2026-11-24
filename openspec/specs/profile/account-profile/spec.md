# account-profile Specification

## Purpose

Mengatur halaman profil akun: data yang dapat dilihat dan diubah pemiliknya sendiri, unggahan foto profil beserta pembatasannya, dan penyajian nomor WhatsApp yang hanya dapat dibaca tanpa mengubah cara verifikasinya.

## Requirements

### Requirement: Profil menampilkan dan mengubah nama serta email

Halaman profil SHALL menampilkan nama lengkap dan email akun yang sedang masuk, dan SHALL menyimpan perubahan keduanya setelah validasi berhasil. Mengubah email SHALL membatalkan bukti verifikasi email sebelumnya.

#### Scenario: Profil ditampilkan

- **WHEN** pengguna yang sudah masuk membuka halaman profil
- **THEN** halaman menampilkan nama lengkap, email, dan foto profil akun tersebut

#### Scenario: Nama dan email disimpan

- **WHEN** pengguna mengirim nama dan email yang sah pada formulir profil
- **THEN** perubahan tersimpan dan pengguna kembali ke halaman profil dengan pemberitahuan berhasil

#### Scenario: Email diubah membatalkan verifikasi email

- **WHEN** pengguna mengubah emailnya menjadi alamat lain lalu menyimpan
- **THEN** `email_verified_at` akun tersebut menjadi kosong

#### Scenario: Nama kosong ditolak

- **WHEN** formulir dikirim dengan nama kosong
- **THEN** permintaan ditolak dan pesan galat tampil pada kolom nama tanpa menyimpan perubahan

### Requirement: Foto profil diunggah dengan batasan jenis dan ukuran

Formulir profil SHALL menerima satu berkas foto profil bertipe `jpg`, `jpeg`, `png`, atau `webp` berukuran paling besar 3 MB, dan SHALL menolak berkas di luar batasan itu dengan pesan galat pada kolom foto tanpa menyimpan berkas.

#### Scenario: Foto sah diterima

- **WHEN** pengguna mengunggah berkas `image/jpeg` berukuran 1 MB pada formulir profil
- **THEN** foto tersimpan dan tampil sebagai foto profil pengguna tersebut

#### Scenario: Foto melebihi batas ukuran

- **WHEN** pengguna mengunggah berkas gambar berukuran 4 MB
- **THEN** unggahan ditolak dengan pesan galat pada kolom foto dan tidak ada berkas tersimpan

#### Scenario: Foto bertipe tidak diizinkan

- **WHEN** pengguna mengunggah berkas PDF atau GIF pada kolom foto profil
- **THEN** unggahan ditolak dengan pesan galat pada kolom foto

#### Scenario: Formulir tanpa foto tetap sah

- **WHEN** pengguna menyimpan formulir profil tanpa memilih berkas foto
- **THEN** perubahan nama dan email tetap tersimpan dan foto profil yang sudah ada tidak berubah

### Requirement: Foto profil disimpan sebagai WebP berukuran tetap

Setiap foto profil yang diterima SHALL dikonversi menjadi WebP dengan sisi 400×400 piksel, dipotong dari bagian tengah, dan mengikuti orientasi yang tercatat pada metadata EXIF berkas asal sebelum disimpan. Berkas asal SHALL NOT disimpan.

#### Scenario: Foto ponsel berorientasi tegak

- **WHEN** pengguna mengunggah foto potret yang orientasinya ditandai pada metadata EXIF
- **THEN** foto tersimpan dalam orientasi tegak, bukan miring

#### Scenario: Foto bukan persegi dipotong tengah

- **WHEN** pengguna mengunggah gambar berukuran 1200×800
- **THEN** foto tersimpan berukuran 400×400 dengan bagian tengah gambar dipertahankan

#### Scenario: Hasil konversi berformat WebP

- **WHEN** pengguna mengunggah berkas PNG dan foto berhasil disimpan
- **THEN** berkas tersimpan berekstensi `.webp` dengan tipe `image/webp`

### Requirement: Penggantian dan penghapusan foto profil

Sistem SHALL menghapus berkas foto lama setelah berkas foto baru berhasil tersimpan, SHALL menghapus berkas foto ketika akun dihapus, dan SHALL NOT menghapus berkas apa pun bila penyimpanan foto baru gagal.

#### Scenario: Foto diganti

- **WHEN** pengguna yang sudah punya foto mengunggah foto pengganti yang sah
- **THEN** foto baru tersimpan, path pada akun diperbarui, dan berkas foto lama tidak lagi ada di penyimpanan

#### Scenario: Penyimpanan foto baru gagal

- **WHEN** penulisan berkas foto baru gagal
- **THEN** permintaan gagal dengan pesan galat dan foto lama tetap tersimpan serta tetap tampil

#### Scenario: Akun dihapus

- **WHEN** pengguna menghapus akunnya sendiri
- **THEN** berkas foto profilnya turut terhapus dari penyimpanan

### Requirement: Foto profil tampil sebagai lingkaran dengan cadangan inisial

Foto profil SHALL ditampilkan berbentuk lingkaran pada halaman profil, header aplikasi, dan menu pengguna. Akun yang belum memiliki foto SHALL menampilkan inisial nama sebagai gantinya.

#### Scenario: Akun memiliki foto

- **WHEN** pengguna berfoto membuka header aplikasi
- **THEN** foto profilnya tampil berbentuk lingkaran

#### Scenario: Akun belum memiliki foto

- **WHEN** pengguna tanpa foto membuka menu pengguna
- **THEN** inisial nama pengguna tampil pada lingkaran yang sama

#### Scenario: Foto gagal dimuat

- **WHEN** berkas foto profil tidak dapat dimuat peramban
- **THEN** inisial nama tampil menggantikan gambar yang gagal

### Requirement: Nomor WhatsApp hanya dapat dibaca pada halaman profil

Halaman profil SHALL menampilkan nomor WhatsApp akun beserta status verifikasinya tanpa isian yang dapat menyunting nomor tersebut, dan SHALL NOT menampilkan kolom nomor WhatsApp untuk akun yang nomornya kosong. Perubahan nomor SHALL tetap hanya melalui alur verifikasi OTP tersendiri.

#### Scenario: Pemilik Kost melihat nomornya

- **WHEN** Pemilik Kost yang nomornya sudah terverifikasi membuka halaman profil
- **THEN** nomor WhatsApp dan status terverifikasi tampil sebagai teks tanpa isian yang dapat diubah

#### Scenario: Pemilik Kost belum memverifikasi nomor

- **WHEN** Pemilik Kost yang nomornya belum terverifikasi membuka halaman profil
- **THEN** nomor tampil beserta keterangan belum terverifikasi

#### Scenario: Customer dan Admin tidak melihat kolom nomor

- **WHEN** akun Customer atau Admin membuka halaman profil
- **THEN** tidak ada kolom maupun teks nomor WhatsApp pada halaman tersebut

#### Scenario: Nomor tidak berubah lewat formulir profil

- **WHEN** formulir profil dikirim dengan menyertakan nilai `whatsapp_number`
- **THEN** nilai itu diabaikan dan `users.whatsapp_number` tidak berubah
