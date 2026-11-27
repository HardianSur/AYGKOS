# Spec Delta

## Purpose

Mengatur pengelolaan tipe kamar oleh Pemilik Kost pada kost miliknya: menambah, mengubah, mengaktifkan, menonaktifkan, dan menghapus tipe kamar beserta fasilitas, peraturan khusus, nilai spesifikasi, dan foto kategorinya.

## ADDED Requirements

### Requirement: Halaman pengelolaan tipe kamar hanya terbuka bagi pemilik kost

Halaman daftar dan formulir tipe kamar SHALL hanya dapat dibuka oleh Pemilik Kost aktif dan terverifikasi yang memiliki kost tersebut. Peran lain, akun nonaktif, dan pemilik kost lain SHALL ditolak dengan status 403 tanpa menampilkan isi halaman.

#### Scenario: Pemilik membuka tipe kamar kostnya

- **WHEN** Pemilik Kost terverifikasi membuka halaman tipe kamar pada kost miliknya
- **THEN** daftar tipe kamar kost tersebut tampil beserta data master fasilitas, peraturan, dan spesifikasi berlingkup kamar

#### Scenario: Pemilik lain ditolak

- **WHEN** Pemilik Kost membuka tipe kamar pada kost yang dimiliki akun lain
- **THEN** permintaan dijawab dengan status 403

#### Scenario: Customer ditolak

- **WHEN** akun Customer membuka halaman tipe kamar sebuah kost
- **THEN** permintaan dijawab dengan status 403

#### Scenario: Akun nonaktif ditolak

- **WHEN** Pemilik Kost yang sudah dinonaktifkan membuka halaman tipe kamar kostnya
- **THEN** permintaan dijawab dengan status 403

### Requirement: Tipe kamar baru selalu berstatus nonaktif

Pembuatan tipe kamar SHALL mengabaikan kolom status pada masukan dan menyimpan tipe kamar baru berstatus `nonaktif`. Nilai status SHALL hanya berubah melalui aksi pengaktifan dan penonaktifan.

#### Scenario: Status kiriman diabaikan

- **WHEN** formulir pembuatan dikirim dengan kolom status bernilai `aktif`
- **THEN** tipe kamar tersimpan berstatus `nonaktif` dan tidak tampil kepada publik

#### Scenario: Tipe kamar baru tampil di daftar pemilik

- **WHEN** Pemilik Kost menyimpan tipe kamar baru
- **THEN** tipe kamar tersebut tampil pada halaman tipe kamar kostnya

### Requirement: Data pokok tipe kamar divalidasi sebelum disimpan

Nama SHALL wajib diisi dan unik di antara tipe kamar yang belum dihapus pada kost yang sama. Harga sewa per bulan, jumlah kamar, dan kamar tersedia SHALL berupa bilangan bulat dengan harga dan jumlah kamar lebih besar dari nol serta kamar tersedia antara nol dan jumlah kamar. Catatan bersifat opsional.

#### Scenario: Kamar tersedia melebihi jumlah kamar ditolak

- **WHEN** formulir dikirim dengan kamar tersedia 5 dan jumlah kamar 3
- **THEN** permintaan ditolak dengan pesan galat pada kolom kamar tersedia dan tidak ada baris tersimpan

#### Scenario: Nama ganda pada kost yang sama ditolak

- **WHEN** Pemilik Kost menyimpan tipe kamar bernama "AC" padahal kost tersebut sudah memiliki tipe kamar bernama "AC"
- **THEN** permintaan ditolak dengan pesan galat pada kolom nama

#### Scenario: Harga nol ditolak

- **WHEN** formulir dikirim dengan harga sewa per bulan 0
- **THEN** permintaan ditolak dengan pesan galat pada kolom harga

#### Scenario: Catatan boleh kosong

- **WHEN** formulir dikirim tanpa mengisi catatan
- **THEN** tipe kamar tersimpan dan kolom catatan bernilai kosong

### Requirement: Fasilitas, peraturan, dan spesifikasi tipe kamar dipilih dari master

Fasilitas SHALL dipilih dari master ber-`scope` `kamar` dan peraturan khusus dari master ber-`scope` `kamar`; nilai di luar master yang ada SHALL ditolak. Nilai spesifikasi SHALL mengikuti `input_type` masternya: `number` menerima angka, `boolean` menerima benar atau salah, dan `text` menerima teks paling panjang 100 karakter.

#### Scenario: Fasilitas di luar lingkup kamar ditolak

- **WHEN** formulir dikirim dengan fasilitas ber-`scope` `umum` pada kolom fasilitas kamar
- **THEN** permintaan ditolak dan tidak ada pasangan fasilitas tersimpan

#### Scenario: Nilai spesifikasi tidak sesuai jenis masukan ditolak

- **WHEN** nilai spesifikasi ber-`input_type` `number` diisi teks yang bukan angka
- **THEN** permintaan ditolak dengan pesan galat pada spesifikasi tersebut

#### Scenario: Peraturan khusus boleh kosong

- **WHEN** formulir dikirim tanpa memilih peraturan khusus
- **THEN** tipe kamar tersimpan dan tidak ada pasangan peraturan kamar tersimpan

### Requirement: Foto tipe kamar disimpan berkategori dengan satu foto utama

Formulir tipe kamar SHALL menerima paling banyak sepuluh foto berformat JPG, PNG, atau WebP berukuran paling besar 3 MB per berkas, masing-masing berkategori `kamar` atau `kamar_mandi`. Foto pertama yang diunggah SHALL ditandai utama, dan Pemilik Kost SHALL dapat memilih foto utama lain di antara foto yang sudah tersimpan. Path berkas SHALL tersimpan pada `photos` dengan `room_type_id` menunjuk tipe kamar tersebut.

#### Scenario: Foto pertama menjadi utama

- **WHEN** Pemilik Kost mengunggah tiga foto pada satu tipe kamar tanpa memilih foto utama
- **THEN** foto pertama tersimpan dengan tanda utama dan dua lainnya tidak

#### Scenario: Foto utama dipindahkan

- **WHEN** Pemilik Kost memilih foto ketiga sebagai foto utama
- **THEN** hanya foto ketiga bertanda utama dan penandaan foto pertama dilepas

#### Scenario: Berkas melebihi batas ditolak

- **WHEN** Pemilik Kost mengunggah foto berukuran 4 MB pada formulir tipe kamar
- **THEN** permintaan ditolak dan tidak ada baris tipe kamar maupun foto tersimpan

#### Scenario: Melebihi sepuluh foto ditolak

- **WHEN** Pemilik Kost mengirim sebelas foto sekaligus pada satu tipe kamar
- **THEN** permintaan ditolak dengan pesan galat pada kolom foto

#### Scenario: Hapus foto menghapus berkasnya

- **WHEN** Pemilik Kost menghapus satu foto tipe kamar
- **THEN** baris foto hilang dan berkas tersebut tidak lagi ada di penyimpanan

### Requirement: Tipe kamar dapat diaktifkan dan dinonaktifkan terpisah dari penyuntingan

Pemilik Kost SHALL dapat mengubah status tipe kamar antara `aktif` dan `nonaktif` tanpa membuka formulir penyuntingan. Aksi ini SHALL hanya mengubah status dan tidak menyentuh data lain pada tipe kamar.

#### Scenario: Tipe kamar diaktifkan

- **WHEN** Pemilik Kost mengaktifkan tipe kamar berstatus `nonaktif` pada kost berstatus `disetujui`
- **THEN** statusnya menjadi `aktif` dan tipe kamar tersebut tampil pada halaman detail publik

#### Scenario: Tipe kamar dinonaktifkan

- **WHEN** Pemilik Kost menonaktifkan tipe kamar berstatus `aktif`
- **THEN** statusnya menjadi `nonaktif` dan tipe kamar tersebut hilang dari halaman detail publik sementara data lainnya tidak berubah

#### Scenario: Status tidak dapat diubah pemilik lain

- **WHEN** Pemilik Kost mengubah status tipe kamar pada kost yang bukan miliknya
- **THEN** permintaan ditolak dengan status 403 dan status tidak berubah

### Requirement: Tipe kamar diubah seluruhnya dalam satu penyimpanan

Penyimpanan perubahan SHALL mengganti data tipe kamar beserta pasangan fasilitas, peraturan khusus, nilai spesifikasi, dan foto yang dikirim, sehingga pasangan yang tidak lagi dipilih hilang. Bila satu langkah gagal, SHALL tidak ada perubahan tersisa dan berkas foto yang sudah tertulis SHALL dihapus.

#### Scenario: Fasilitas yang dilepas hilang

- **WHEN** Pemilik Kost menyimpan perubahan dengan melepas fasilitas "AC" dari pilihan
- **THEN** pasangan tipe kamar dan fasilitas "AC" tidak lagi tersimpan

#### Scenario: Perubahan gagal tidak meninggalkan data separuh jadi

- **WHEN** penyimpanan baris basis data gagal setelah berkas foto tertulis
- **THEN** tidak ada perubahan tersimpan dan berkas yang sudah tertulis tidak tertinggal di penyimpanan

### Requirement: Tipe kamar dihapus secara lunak dan namanya bebas dipakai ulang

Penghapusan tipe kamar SHALL mengisi `deleted_at` pada baris tipe kamar, menghapus berkas fotonya dari penyimpanan, dan tidak menghapus baris foto secara fisik. Setelah dihapus, tipe kamar SHALL tidak tampil pada daftar pemilik maupun halaman publik, dan nama yang sama SHALL dapat dipakai kembali pada kost yang sama.

#### Scenario: Tipe kamar terhapus hilang dari daftar

- **WHEN** Pemilik Kost menghapus satu tipe kamar
- **THEN** tipe kamar tersebut tidak tampil pada daftar tipe kamar kostnya dan barisnya tetap tersimpan dengan `deleted_at` terisi

#### Scenario: Nama bekas tipe kamar terhapus dapat dipakai lagi

- **WHEN** Pemilik Kost menghapus tipe kamar bernama "AC" lalu membuat tipe kamar baru bernama "AC" pada kost yang sama
- **THEN** tipe kamar baru tersimpan tanpa melanggar aturan unik

#### Scenario: Pemilik lain tidak dapat menghapus

- **WHEN** Pemilik Kost menghapus tipe kamar pada kost yang bukan miliknya
- **THEN** permintaan ditolak dengan status 403 dan tipe kamar tidak terhapus

### Requirement: Data contoh menyediakan kost terverifikasi beserta tipe kamarnya

Sistem SHALL menyediakan satu seeder yang membuat kost berstatus `disetujui` beserta dua tipe kamar — satu `aktif` dan satu `nonaktif` — lengkap dengan fasilitas kamar, peraturan khusus, dan nilai spesifikasi, sehingga pengelolaan tipe kamar dapat diuji tanpa menunggu fitur verifikasi Admin selesai.

#### Scenario: Seeder menyiapkan kost terverifikasi

- **WHEN** seeder dijalankan pada basis data pengembangan
- **THEN** tersedia kost berstatus `disetujui` dengan `reviewed_at` terisi dan dua tipe kamar berstatus berbeda beserta pivotnya

#### Scenario: Kost hasil seeder tampil kepada publik

- **WHEN** daftar publik dimuat setelah seeder dijalankan
- **THEN** kost tersebut tampil dan hanya tipe kamar berstatus `aktif` yang dihitung