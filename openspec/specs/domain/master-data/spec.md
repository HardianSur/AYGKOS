# master-data Specification

## Purpose

Menyediakan master data fasilitas, aturan, dan spesifikasi kamar yang dikelola Admin, sehingga Pemilik Kost memilih dari daftar terkurasi alih-alih mengetik teks bebas dan tampilan katalog memiliki label serta ikon yang seragam.

## Requirements

### Requirement: Master fasilitas menyimpan nama, ruang lingkup, dan ikon

Setiap baris `facilities` SHALL menyimpan `name` unik tanpa membedakan huruf besar/kecil, `scope` bernilai `umum` atau `kamar`, dan `icon` berisi nama ikon dari himpunan ikon yang diizinkan aplikasi. `icon` SHALL NOT berupa berkas gambar atau markup.

#### Scenario: Fasilitas tanpa ruang lingkup ditolak

- **WHEN** sebuah fasilitas disimpan tanpa `scope`
- **THEN** basis data menolak penyisipan

#### Scenario: Nama fasilitas ganda ditolak

- **WHEN** sebuah fasilitas disimpan dengan `name` yang sudah ada
- **THEN** basis data menolak penyisipan

#### Scenario: Nama yang hanya berbeda huruf besar/kecil ditolak

- **WHEN** Admin menyimpan fasilitas bernama "wifi" sementara "WiFi" sudah ada
- **THEN** penyimpanan ditolak dan pesan galat menempel pada kolom nama

#### Scenario: Spasi berlebih pada nama dirapikan sebelum disimpan

- **WHEN** Admin menyimpan fasilitas bernama "  Ruang   Tamu  "
- **THEN** nama tersimpan sebagai "Ruang Tamu"

#### Scenario: Nama ikon di luar daftar ditolak

- **WHEN** Admin menyimpan fasilitas dengan `icon` yang tidak ada pada daftar ikon yang diizinkan aplikasi
- **THEN** penyimpanan ditolak dan pesan galat menempel pada kolom ikon

### Requirement: Master aturan menyimpan nama dan ruang lingkup

Setiap baris `rules` SHALL menyimpan `name` unik dan `scope` bernilai `kost` atau `kamar`. Aturan ber-`scope` `kost` SHALL hanya dapat dipilih pada `kost_rules`, dan aturan ber-`scope` `kamar` SHALL hanya dapat dipilih pada `room_type_rules`.

#### Scenario: Aturan kost tidak dapat dipilih sebagai aturan kamar

- **WHEN** sebuah aturan ber-`scope` `kost` disimpan ke `room_type_rules`
- **THEN** basis data atau validasi aplikasi menolak penyimpanan

### Requirement: Master spesifikasi menyimpan jenis masukan dan satuan

Setiap baris `specifications` SHALL menyimpan `name` unik, `input_type` bernilai `boolean`, `number`, atau `text`, serta `unit` yang boleh kosong. Nilai spesifikasi pada tipe kamar SHALL disimpan pada `room_type_specifications.value`.

#### Scenario: Spesifikasi angka tanpa nilai angka ditolak

- **WHEN** sebuah spesifikasi ber-`input_type` `number` diisi nilai yang bukan angka pada sebuah tipe kamar
- **THEN** penyimpanan ditolak

#### Scenario: Satuan tampil bersama nilai

- **WHEN** spesifikasi ber-`name` "Luas Kamar" dengan `unit` "m2" bernilai 9 pada sebuah tipe kamar ditampilkan
- **THEN** tampilan memuat nilai dan satuannya sebagai "9 m2"

### Requirement: Admin mengelola master data

Admin SHALL dapat menambah, mengubah, dan menghapus baris `facilities`, `rules`, dan `specifications` melalui halaman pengelolaan master data. Penghapusan SHALL ditolak selama baris tersebut masih dipakai pada kost atau tipe kamar mana pun. Mengubah `scope` sebuah fasilitas SHALL ditolak selama fasilitas itu masih dipakai, karena relasi yang ada berada di ruang lingkup lama.

#### Scenario: Master yang masih dipakai tidak dapat dihapus

- **WHEN** Admin menghapus fasilitas yang masih dirujuk `kost_facilities` atau `room_type_facilities`
- **THEN** penghapusan ditolak dan nama kost serta tipe kamar pemakainya ditampilkan

#### Scenario: Ruang lingkup fasilitas yang masih dipakai tidak dapat diubah

- **WHEN** Admin mengubah `scope` fasilitas yang masih dirujuk `kost_facilities` atau `room_type_facilities`
- **THEN** penyimpanan ditolak dan nama fasilitas beserta pemakainya ditampilkan

#### Scenario: Ruang lingkup fasilitas tanpa pemakai dapat diubah

- **WHEN** Admin mengubah `scope` fasilitas yang tidak dirujuk kost maupun tipe kamar mana pun
- **THEN** perubahan tersimpan

#### Scenario: Perubahan nama master berlaku pada semua pemakai

- **WHEN** Admin mengubah `name` sebuah aturan
- **THEN** seluruh kost dan tipe kamar yang memilih aturan tersebut menampilkan nama baru

#### Scenario: Selain Admin tidak dapat mengubah master

- **WHEN** pengguna berperan Customer atau Pemilik Kost membuka halaman pengelolaan master data
- **THEN** akses ditolak

### Requirement: Pemilik Kost hanya memilih dari master

Pemilik Kost SHALL memilih fasilitas, aturan, dan spesifikasi dari master data yang tersedia; formulir SHALL NOT menyediakan pengetikan nama fasilitas atau aturan baru. Spesifikasi hanya ditawarkan pada tipe kamar, sedangkan fasilitas dan aturan ditawarkan pada kost maupun tipe kamar sesuai `scope`.

#### Scenario: Formulir kost hanya menawarkan fasilitas berlingkup umum

- **WHEN** Pemilik Kost membuka formulir tambah atau ubah kost
- **THEN** daftar fasilitas yang ditawarkan hanya berisi fasilitas ber-`scope` `umum`

#### Scenario: Formulir tipe kamar hanya menawarkan fasilitas berlingkup kamar

- **WHEN** Pemilik Kost membuka formulir tipe kamar
- **THEN** daftar fasilitas yang ditawarkan hanya berisi fasilitas ber-`scope` `kamar`

### Requirement: Daftar master fasilitas menampilkan jumlah pemakai

Halaman pengelolaan master fasilitas SHALL menampilkan, untuk setiap baris, jumlah kost dan jumlah tipe kamar yang memakai fasilitas tersebut, sehingga Admin melihat dampak penghapusan sebelum menekannya. Kendali hapus SHALL tidak aktif pada baris yang jumlah pemakainya bukan nol.

#### Scenario: Fasilitas yang dipakai ditandai pada daftar

- **WHEN** Admin membuka halaman master fasilitas yang memuat fasilitas dipakai 3 kost dan 2 tipe kamar
- **THEN** baris tersebut menampilkan jumlah pemakai dan kendali hapusnya tidak aktif

#### Scenario: Fasilitas tanpa pemakai dapat dihapus dari daftar

- **WHEN** Admin membuka halaman master fasilitas yang memuat fasilitas tanpa pemakai
- **THEN** kendali hapus pada baris tersebut aktif

### Requirement: Formulir master fasilitas menawarkan ikon dari daftar yang diizinkan

Formulir tambah dan ubah master fasilitas SHALL menawarkan ikon sebagai pilihan visual dari himpunan ikon yang diizinkan aplikasi, bukan isian teks bebas. Ruang lingkup SHALL ditawarkan sebagai pilihan `umum` atau `kamar`.

#### Scenario: Ikon dipilih secara visual

- **WHEN** Admin membuka formulir tambah fasilitas
- **THEN** formulir menampilkan seluruh ikon yang diizinkan sebagai pilihan yang dapat diklik beserta namanya

#### Scenario: Ruang lingkup di luar dua nilai tidak dapat dikirim

- **WHEN** formulir tambah fasilitas dikirim dengan `scope` selain `umum` atau `kamar`
- **THEN** penyimpanan ditolak dan pesan galat menempel pada kolom ruang lingkup
