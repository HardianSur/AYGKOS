# Spec Delta

## MODIFIED Requirements

### Requirement: Master aturan menyimpan nama dan ruang lingkup

Setiap baris `rules` SHALL menyimpan `name` unik tanpa membedakan huruf besar/kecil, `scope` bernilai `kost` atau `kamar`, dan `icon` berisi nama ikon dalam bentuk slug dari katalog ikon aplikasi. Aturan ber-`scope` `kost` SHALL hanya dapat dipilih pada `kost_rules`, dan aturan ber-`scope` `kamar` SHALL hanya dapat dipilih pada `room_type_rules`.

#### Scenario: Aturan kost tidak dapat dipilih sebagai aturan kamar

- **WHEN** sebuah aturan ber-`scope` `kost` disimpan ke `room_type_rules`
- **THEN** basis data atau validasi aplikasi menolak penyimpanan

#### Scenario: Nama aturan ganda ditolak

- **WHEN** Admin menyimpan aturan dengan `name` yang sudah ada
- **THEN** penyimpanan ditolak dan pesan galat menempel pada kolom nama

#### Scenario: Nama aturan yang hanya berbeda huruf besar/kecil ditolak

- **WHEN** Admin menyimpan aturan bernama "dilarang merokok" sementara "Dilarang merokok" sudah ada
- **THEN** penyimpanan ditolak dan pesan galat menempel pada kolom nama

#### Scenario: Spasi berlebih pada nama aturan dirapikan sebelum disimpan

- **WHEN** Admin menyimpan aturan bernama "  Tamu   Dilarang  Menginap  "
- **THEN** nama tersimpan sebagai "Tamu Dilarang Menginap"

#### Scenario: Aturan tanpa ikon ditolak

- **WHEN** Admin menyimpan aturan tanpa `icon`
- **THEN** penyimpanan ditolak dan pesan galat menempel pada kolom ikon

#### Scenario: Ikon di luar bentuk slug ditolak

- **WHEN** Admin menyimpan aturan dengan `icon` bernilai "Dilarang Merokok", berisi garis miring, atau lebih dari 64 karakter
- **THEN** penyimpanan ditolak dan pesan galat menempel pada kolom ikon

#### Scenario: Ikon yang tidak dikenal tetap tersimpan tanpa galat render

- **WHEN** sebuah aturan tersimpan dengan `icon` berbentuk slug yang tidak ada di katalog
- **THEN** halaman yang menampilkan aturan itu tetap tampil dan ikonnya kosong, tanpa galat

### Requirement: Admin mengelola master data

Admin SHALL dapat menambah, mengubah, dan menghapus baris `facilities`, `rules`, dan `specifications` melalui halaman pengelolaan master data. Penghapusan SHALL ditolak selama baris tersebut masih dipakai pada kost atau tipe kamar mana pun. Mengubah `scope` sebuah fasilitas atau aturan SHALL ditolak selama baris itu masih dipakai, karena relasi yang ada berada di ruang lingkup lama.

#### Scenario: Master yang masih dipakai tidak dapat dihapus

- **WHEN** Admin menghapus fasilitas yang masih dirujuk `kost_facilities` atau `room_type_facilities`
- **THEN** penghapusan ditolak dan nama kost serta tipe kamar pemakainya ditampilkan

#### Scenario: Aturan yang masih dipakai tidak dapat dihapus

- **WHEN** Admin menghapus aturan yang masih dirujuk `kost_rules` atau `room_type_rules`
- **THEN** penghapusan ditolak dan nama kost serta tipe kamar pemakainya ditampilkan

#### Scenario: Ruang lingkup fasilitas yang masih dipakai tidak dapat diubah

- **WHEN** Admin mengubah `scope` fasilitas yang masih dirujuk `kost_facilities` atau `room_type_facilities`
- **THEN** penyimpanan ditolak dan nama fasilitas beserta pemakainya ditampilkan

#### Scenario: Ruang lingkup fasilitas tanpa pemakai dapat diubah

- **WHEN** Admin mengubah `scope` fasilitas yang tidak dirujuk kost maupun tipe kamar mana pun
- **THEN** perubahan tersimpan

#### Scenario: Ruang lingkup aturan yang masih dipakai tidak dapat diubah

- **WHEN** Admin mengubah `scope` aturan yang masih dirujuk `kost_rules` atau `room_type_rules`
- **THEN** penyimpanan ditolak dan nama aturan beserta pemakainya ditampilkan

#### Scenario: Ruang lingkup aturan tanpa pemakai dapat diubah

- **WHEN** Admin mengubah `scope` aturan yang tidak dirujuk kost maupun tipe kamar mana pun
- **THEN** perubahan tersimpan

#### Scenario: Perubahan nama master berlaku pada semua pemakai

- **WHEN** Admin mengubah `name` sebuah aturan
- **THEN** seluruh kost dan tipe kamar yang memilih aturan tersebut menampilkan nama baru

#### Scenario: Selain Admin tidak dapat mengubah master

- **WHEN** pengguna berperan Customer atau Pemilik Kost membuka halaman pengelolaan master data
- **THEN** akses ditolak

## ADDED Requirements

### Requirement: Daftar master aturan menampilkan ikon, ruang lingkup, dan jumlah pemakai

Halaman pengelolaan master aturan SHALL menampilkan, untuk setiap baris, ikon, nama, ruang lingkup, jumlah kost pemakai, dan jumlah tipe kamar pemakai. Kendali hapus dan kendali ubah ruang lingkup SHALL tidak aktif pada baris yang jumlah pemakainya bukan nol.

#### Scenario: Aturan yang dipakai ditandai pada daftar

- **WHEN** Admin membuka halaman master aturan yang memuat aturan dipakai 3 kost dan 2 tipe kamar
- **THEN** baris tersebut menampilkan ikon, ruang lingkup, jumlah pemakai, dan kendali hapus beserta ubah ruang lingkupnya tidak aktif

#### Scenario: Aturan tanpa pemakai dapat dihapus dari daftar

- **WHEN** Admin membuka halaman master aturan yang memuat aturan tanpa pemakai
- **THEN** kendali hapus dan ubah ruang lingkup pada baris tersebut aktif

#### Scenario: Aturan baru muncul pada daftar

- **WHEN** Admin menyimpan aturan baru yang sah
- **THEN** aturan itu tampil pada daftar dengan ikon, ruang lingkup, dan jumlah pemakai nol

### Requirement: Formulir master aturan menawarkan ikon dari katalog dengan pencarian

Formulir tambah dan ubah master aturan SHALL menawarkan ikon sebagai pilihan visual dari katalog ikon aplikasi, bukan isian teks bebas. Pilihan ikon SHALL dapat disaring lewat kolom pencarian, dan tampilan pilihan SHALL dibatasi maksimal 20 ikon pada satu waktu. Ruang lingkup SHALL ditawarkan sebagai pilihan `kost` atau `kamar`.

#### Scenario: Tanpa kata kunci formulir menampilkan ikon kurasi

- **WHEN** Admin membuka pemilih ikon pada formulir aturan tanpa mengetik apa pun
- **THEN** pemilih menampilkan paling banyak 20 ikon kurasi yang relevan dengan aturan

#### Scenario: Pencarian menyaring pilihan ikon

- **WHEN** Admin mengetik "paw" pada kolom pencarian pemilih ikon
- **THEN** pilihan menyusut menjadi ikon yang namanya memuat kata berawalan "paw", termasuk `paw-print`

#### Scenario: Pencarian menerima spasi dan tanda hubung

- **WHEN** Admin mengetik "paw print" atau "paw-print"
- **THEN** pemilih menampilkan hasil yang sama, termasuk `paw-print`

#### Scenario: Pencarian menerima huruf besar

- **WHEN** Admin mengetik "PAW"
- **THEN** pemilih menampilkan hasil yang sama dengan "paw"

#### Scenario: Hasil pencarian dibatasi 20 ikon

- **WHEN** Admin mengetik kata kunci yang cocok dengan lebih dari 20 ikon
- **THEN** pemilih menampilkan tepat 20 ikon pertama

#### Scenario: Pencarian tanpa hasil menampilkan keadaan kosong

- **WHEN** Admin mengetik kata kunci yang tidak cocok dengan ikon mana pun
- **THEN** pemilih menyatakan tidak ada ikon yang cocok tanpa menampilkan galat

#### Scenario: Ikon terpilih tersimpan bersama aturan

- **WHEN** Admin memilih sebuah ikon lalu menyimpan aturan
- **THEN** aturan tersimpan dengan ikon yang dipilih dan ikon itu tampil pada daftar

#### Scenario: Ruang lingkup di luar dua nilai tidak dapat dikirim

- **WHEN** formulir aturan dikirim dengan `scope` selain `kost` atau `kamar`
- **THEN** penyimpanan ditolak dan pesan galat menempel pada kolom ruang lingkup

### Requirement: Peraturan ditampilkan beserta ikon pada formulir Pemilik Kost

Daftar peraturan yang ditawarkan pada formulir kost dan formulir tipe kamar SHALL menampilkan ikon tiap aturan bersama namanya, memakai ikon yang sama dengan daftar master aturan Admin.

#### Scenario: Formulir kost menampilkan ikon aturan

- **WHEN** Pemilik Kost membuka formulir tambah kost
- **THEN** tiap aturan ber-`scope` `kost` ditampilkan dengan ikon dan namanya

#### Scenario: Aturan tanpa ikon dikenali tetap terbaca

- **WHEN** Pemilik Kost membuka formulir kost yang memuat aturan dengan ikon berbentuk slug tak dikenal
- **THEN** nama aturan tetap tampil dan bagian ikonnya kosong tanpa merusak formulir
