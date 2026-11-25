# kost-catalog Specification

## Purpose

Menyimpan data kost yang diiklankan Pemilik Kost beserta foto, fasilitas, dan kamarnya, sehingga pencarian, penyaringan, dan halaman detail kost memiliki sumber data yang lengkap dan konsisten.

## Requirements

### Requirement: Kost menyimpan data lokasi, klasifikasi, dan status verifikasi

Setiap baris `kosts` SHALL menyimpan pemilik, nama, alamat lengkap, kota, koordinat lintang dan bujur, tipe kost, deskripsi, dan status verifikasi dengan nilai awal menunggu verifikasi. Status SHALL berupa salah satu dari `menunggu_verifikasi`, `disetujui`, `ditolak`, `nonaktif`, atau `ditangguhkan`. Tipe SHALL berupa salah satu dari `putra`, `putri`, atau `campur`. Peraturan dan dokumen verifikasi SHALL NOT disimpan sebagai kolom pada `kosts`.

#### Scenario: Kost baru berstatus menunggu verifikasi

- **WHEN** sebuah kost dibuat tanpa menyebut status
- **THEN** `status` bernilai `menunggu_verifikasi`

#### Scenario: Penolakan dan penangguhan menyimpan alasan

- **WHEN** Admin menolak atau menangguhkan sebuah kost dengan alasan
- **THEN** `status_reason` terisi dan `reviewed_by` serta `reviewed_at` mencatat Admin beserta waktunya

#### Scenario: Koordinat wajib terisi

- **WHEN** sebuah kost hendak disimpan tanpa `latitude` atau `longitude`
- **THEN** basis data menolak penyisipan

#### Scenario: Pemilik Kost memiliki lebih dari satu kost

- **WHEN** seorang Pemilik Kost dengan satu kost yang sudah disetujui menambahkan kost kedua
- **THEN** kost kedua tersimpan dengan status `menunggu_verifikasi` dan dokumen izinnya sendiri

### Requirement: Foto kost tersimpan sebagai path dan satu foto ditandai utama

Setiap baris `photos` SHALL menyimpan path berkas, bukan URL, `kost_id`, `room_type_id` yang boleh kosong, `category`, `sort_order`, dan `is_primary`. `category` SHALL berupa salah satu dari `bangunan`, `kamar`, `kamar_mandi`, atau `fasilitas_umum`. Foto SHALL hanya ditandai utama satu kali per kost, dan satu kali per tipe kamar untuk foto bertipe kamar.

#### Scenario: Foto tersimpan sebagai path relatif

- **WHEN** sebuah foto kost disimpan
- **THEN** kolom `photo_path` memuat path atau key objek dan tidak memuat URL lengkap

#### Scenario: Foto utama ditandai

- **WHEN** sebuah foto ditetapkan sebagai foto utama
- **THEN** `is_primary` bernilai benar pada foto tersebut dan tidak ada foto lain bertanda utama pada tingkat yang sama

#### Scenario: Kategori bangunan berada di level kost

- **WHEN** sebuah foto berkategori `bangunan` atau `fasilitas_umum` disimpan
- **THEN** `room_type_id` bernilai kosong

#### Scenario: Kategori kamar wajib milik sebuah tipe kamar

- **WHEN** sebuah foto berkategori `kamar` atau `kamar_mandi` disimpan
- **THEN** `room_type_id` terisi dan menunjuk tipe kamar pada kost yang sama

#### Scenario: Dua foto utama pada satu tipe kamar ditolak

- **WHEN** sebuah foto ditandai utama padahal tipe kamar yang sama sudah memiliki foto utama
- **THEN** basis data menolak penandaan kedua

#### Scenario: Foto utama bangunan tidak membatasi tipe kamar

- **WHEN** kost memiliki foto utama bangunan dan setiap tipe kamarnya memiliki foto utama sendiri
- **THEN** seluruh penandaan tersimpan tanpa melanggar aturan unik

### Requirement: Fasilitas berupa data master dan dihubungkan banyak ke banyak

Setiap fasilitas SHALL berasal dari master `facilities` dengan `name` unik dan `scope` bernilai `umum` atau `kamar`. Hubungan fasilitas umum dengan kost SHALL disimpan pada `kost_facilities`, dan hubungan fasilitas kamar dengan tipe kamar SHALL disimpan pada `room_type_facilities`. Pasangan yang sama SHALL NOT tersimpan dua kali, dan pasangan SHALL NOT melanggar `scope`.

#### Scenario: Fasilitas yang sama tidak tercatat dua kali pada satu kost

- **WHEN** sebuah pasangan kost dan fasilitas disimpan dua kali
- **THEN** basis data menolak pasangan kedua

#### Scenario: Nama fasilitas unik antarkost

- **WHEN** sebuah fasilitas disimpan dengan nama yang sudah ada
- **THEN** basis data menolak penyisipan

#### Scenario: Fasilitas berlingkup kamar tidak dapat dipasang pada kost

- **WHEN** sebuah fasilitas ber-`scope` `kamar` disimpan ke `kost_facilities`
- **THEN** penyimpanan ditolak

#### Scenario: Menghapus kost membersihkan pasangan fasilitasnya

- **WHEN** sebuah kost dihapus secara paksa
- **THEN** baris `kost_facilities`, `room_type_facilities`, dan `photos` miliknya ikut terhapus sementara baris `facilities` tetap ada

### Requirement: Hanya kost disetujui milik akun aktif yang tampil kepada publik

Kost SHALL tampil kepada publik hanya ketika berstatus `disetujui`, belum dihapus secara lunak, dan pemiliknya berstatus aktif. Setiap tipe kamar SHALL tampil hanya ketika berstatus `aktif`; tipe kamar berstatus `nonaktif` SHALL NOT tampil pada daftar publik maupun halaman detail. Kost berstatus lain SHALL tetap tersimpan dan tetap dapat dikelola pemiliknya.

#### Scenario: Kost menunggu verifikasi tidak tampil

- **WHEN** kost berstatus `menunggu_verifikasi` dicari melalui daftar publik
- **THEN** kost tersebut tidak muncul pada hasil

#### Scenario: Kost milik akun nonaktif hilang dari daftar publik

- **WHEN** akun pemilik sebuah kost dinonaktifkan Admin
- **THEN** kost miliknya tidak muncul pada daftar publik meskipun statusnya `disetujui`, dan data kost tidak berubah

#### Scenario: Kost disetujui milik akun aktif tampil

- **WHEN** kost berstatus `disetujui` dimiliki akun aktif dan belum dihapus
- **THEN** kost tersebut muncul pada daftar publik

#### Scenario: Tipe kamar nonaktif tidak tampil

- **WHEN** kost berstatus `disetujui` memiliki satu tipe kamar berstatus `nonaktif`
- **THEN** tipe kamar tersebut tidak tampil pada halaman detail maupun dihitung pada jumlah kamar tersedia

### Requirement: Menghapus kost menghapus kamarnya dan menyembunyikan kost

Penghapusan kost oleh pemiliknya SHALL menyembunyikan kost dari seluruh daftar dan SHALL menyembunyikan tipe kamarnya, tanpa menghapus baris kost maupun tipe kamar dari basis data.

#### Scenario: Kamar ikut tersembunyi saat kost dihapus

- **WHEN** seorang Pemilik Kost menghapus kostnya
- **THEN** kost dan seluruh tipe kamarnya tidak lagi muncul pada daftar mana pun, sementara barisnya tetap tersimpan dengan `deleted_at` terisi

#### Scenario: Kamar tidak dapat diakses melalui kost yang terhapus

- **WHEN** tipe kamar dari kost yang sudah dihapus diambil melalui relasi baku dari kost
- **THEN** tipe kamar tersebut tidak ikut terambil

### Requirement: Tipe kamar menyimpan nama, harga, dan ketersediaan

Setiap baris `room_types` SHALL menyimpan `kost_id`, `name` unik dalam satu kost yang diisi Pemilik Kost, `price_per_month` dalam rupiah, `total_rooms`, `available_rooms`, `notes` yang boleh kosong, dan `status`. `status` SHALL berupa `aktif` atau `nonaktif` dengan nilai awal `nonaktif`. `available_rooms` SHALL NOT melebihi `total_rooms`, dan `total_rooms` SHALL lebih besar dari nol.

#### Scenario: Tipe kamar baru berstatus nonaktif

- **WHEN** sebuah tipe kamar dibuat tanpa menyebut status
- **THEN** `status` bernilai `nonaktif`

#### Scenario: Nama tipe kamar boleh sama pada kost berbeda

- **WHEN** dua kost berbeda masing-masing memiliki tipe kamar bernama "AC"
- **THEN** kedua baris tersimpan tanpa melanggar aturan unik

#### Scenario: Ketersediaan melebihi jumlah kamar ditolak

- **WHEN** sebuah tipe kamar disimpan dengan `available_rooms` lebih besar dari `total_rooms`
- **THEN** basis data menolak penyisipan

#### Scenario: Harga tidak valid ditolak

- **WHEN** sebuah tipe kamar disimpan dengan `price_per_month` nol atau negatif
- **THEN** basis data menolak penyisipan

### Requirement: Pemilik Kost mengaktifkan tipe kamarnya sendiri

Pemilik Kost SHALL dapat menambah, mengubah, mengaktifkan, menonaktifkan, dan menghapus tipe kamar pada kost miliknya. Tipe kamar baru SHALL NOT tampil kepada publik sampai Pemilik Kost mengaktifkannya. Admin SHALL dapat menonaktifkan tipe kamar yang melanggar ketentuan.

#### Scenario: Pemilik mengaktifkan tipe kamar baru

- **WHEN** Pemilik Kost mengubah status tipe kamar dari `nonaktif` menjadi `aktif` pada kost berstatus `disetujui`
- **THEN** tipe kamar tersebut tampil pada halaman detail dan dihitung pada jumlah kamar tersedia

#### Scenario: Admin menonaktifkan tipe kamar yang melanggar

- **WHEN** Admin menonaktifkan sebuah tipe kamar
- **THEN** tipe kamar tersebut tidak tampil kepada publik sedangkan data lainnya tidak berubah

#### Scenario: Pemilik lain tidak dapat mengubah tipe kamar

- **WHEN** Pemilik Kost mengubah tipe kamar pada kost yang bukan miliknya
- **THEN** akses ditolak

### Requirement: Peraturan kost dan peraturan khusus tipe kamar dipilih dari master

Kost SHALL menyimpan pilihan peraturan pada `kost_rules` dari master `rules` ber-`scope` `kost`. Tipe kamar SHALL dapat menyimpan peraturan tambahan pada `room_type_rules` dari master `rules` ber-`scope` `kamar`. Peraturan khusus tipe kamar bersifat opsional.

#### Scenario: Kost tanpa peraturan khusus tetap tersimpan

- **WHEN** sebuah tipe kamar disimpan tanpa memilih peraturan kamar
- **THEN** tipe kamar tersimpan dan halaman detail tidak menampilkan bagian peraturan khusus

#### Scenario: Peraturan kost tampil pada setiap tipe kamar

- **WHEN** sebuah kost memiliki tiga tipe kamar aktif
- **THEN** peraturan kost tampil sekali pada halaman detail kost, bukan diulang pada setiap tipe kamar

### Requirement: Spesifikasi kamar disimpan per tipe kamar

Setiap baris `room_type_specifications` SHALL menyimpan `room_type_id`, `specification_id`, dan `value`. Satu tipe kamar SHALL NOT menyimpan spesifikasi yang sama dua kali.

#### Scenario: Spesifikasi ganda pada satu tipe kamar ditolak

- **WHEN** sebuah pasangan tipe kamar dan spesifikasi disimpan dua kali
- **THEN** basis data menolak pasangan kedua

### Requirement: Dokumen izin disimpan per kost dan dipilih dari jenis yang ditetapkan

Setiap baris `kost_documents` SHALL menyimpan `kost_id`, `type`, dan `file_path` yang memuat path berkas privat. `type` SHALL berupa salah satu dari `domisili_usaha`, `izin_lingkungan`, `izin_kelurahan`, `nib`, atau `surat_pernyataan`. Berkas SHALL berformat PDF dengan ukuran paling besar 5 MB. Jenis `nib` dan `surat_pernyataan` SHALL wajib tersedia sebelum kost diajukan; tiga jenis lainnya bersifat opsional. Setiap jenis SHALL tersimpan paling banyak satu kali per kost.

#### Scenario: Kost tanpa NIB tidak dapat diajukan

- **WHEN** Pemilik Kost mengajukan kost tanpa dokumen berjenis `nib`
- **THEN** pengajuan ditolak dan pesan galat menempel pada kolom NIB

#### Scenario: Dokumen opsional boleh kosong

- **WHEN** Pemilik Kost mengajukan kost dengan NIB dan surat pernyataan saja
- **THEN** pengajuan diterima dan Admin melihat dua jenis dokumen yang belum diunggah

#### Scenario: Jenis dokumen ganda pada satu kost ditolak

- **WHEN** sebuah kost menyimpan dua dokumen berjenis sama
- **THEN** basis data menolak dokumen kedua

#### Scenario: Dokumen izin tidak dapat diakses publik

- **WHEN** pengguna tanpa sesi meminta berkas dokumen izin
- **THEN** permintaan ditolak

#### Scenario: Berkas selain PDF ditolak

- **WHEN** Pemilik Kost mengunggah berkas `jpg` atau `png` sebagai dokumen izin
- **THEN** unggahan ditolak dengan pesan galat pada kolom berkas

### Requirement: Hasil pencarian menampilkan satu kartu per kost

Setiap hasil pencarian SHALL berupa satu kartu per kost, bukan per tipe kamar. Kartu SHALL memuat foto utama, nama, kota, tipe kost, harga mulai dari tipe kamar aktif termurah, dan jumlah kamar tersedia sebagai penjumlahan `available_rooms` seluruh tipe kamar aktif.

#### Scenario: Harga mulai diambil dari tipe termurah yang aktif

- **WHEN** sebuah kost memiliki tipe aktif berharga 700000 dan 1200000 serta satu tipe nonaktif berharga 500000
- **THEN** kartu menampilkan harga mulai 700000

#### Scenario: Tipe kamar nonaktif tidak dihitung

- **WHEN** sebuah kost memiliki tipe aktif dengan `available_rooms` 2 dan tipe nonaktif dengan `available_rooms` 5
- **THEN** kartu menampilkan 2 kamar tersedia

#### Scenario: Kost tanpa tipe kamar aktif tidak tampil

- **WHEN** sebuah kost berstatus `disetujui` tidak memiliki satu pun tipe kamar aktif
- **THEN** kost tersebut tidak muncul pada hasil pencarian

#### Scenario: Filter ketersediaan menyaring kost bukan tipe

- **WHEN** pengguna mengaktifkan filter ketersediaan kamar
- **THEN** hasil hanya memuat kost yang memiliki minimal satu tipe kamar aktif dengan `available_rooms` lebih besar dari nol, dan setiap hasil tetap satu kartu per kost

#### Scenario: Filter harga memakai harga tipe kamar terkait

- **WHEN** pengguna memfilter rentang harga 500000 sampai 1000000
- **THEN** hasil memuat kost yang memiliki minimal satu tipe kamar aktif dengan harga dalam rentang tersebut

### Requirement: Halaman detail kost menampilkan tipe kamar berurutan

Halaman detail kost SHALL menampilkan tipe kamar aktif secara berurutan, masing-masing memuat spesifikasi, fasilitas kamar, peraturan khusus bila ada, catatan Pemilik bila ada, dan tautan ke tipe kamar lain pada kost yang sama. Setelah seluruh tipe kamar, halaman SHALL menampilkan fasilitas umum dan peraturan kost.

#### Scenario: Tipe kamar lain dapat dijangkau

- **WHEN** pengguna membuka detail sebuah kost dengan tiga tipe kamar aktif
- **THEN** setiap bagian tipe kamar memuat tautan ke dua tipe kamar lainnya pada halaman yang sama

#### Scenario: Fasilitas umum dan peraturan kost tampil sekali

- **WHEN** halaman detail kost ditampilkan
- **THEN** fasilitas umum dan peraturan kost muncul satu kali setelah seluruh bagian tipe kamar

#### Scenario: Galeri penuh baru tampil setelah dipilih

- **WHEN** pengguna membuka halaman detail kost
- **THEN** halaman menampilkan foto utama saja, dan seluruh foto per kategori baru tampil setelah pengguna memilih untuk melihat semua foto

#### Scenario: Foto dikelompokkan per kategori pada galeri penuh

- **WHEN** pengguna membuka galeri penuh sebuah kost
- **THEN** foto ditampilkan terkelompok sebagai bangunan, kamar, kamar mandi, dan fasilitas umum, dan kelompok tanpa foto tidak ditampilkan

### Requirement: Ajukan Sewa merujuk tipe kamar yang tersedia

Pilihan Ajukan Sewa pada kontak WhatsApp SHALL hanya tersedia untuk tipe kamar aktif dengan `available_rooms` lebih besar dari nol. Draf pesan SHALL memuat nama kost, nama tipe kamar, harga sewa per bulan, dan nama lengkap Customer.

#### Scenario: Tipe kamar penuh tidak menawarkan Ajukan Sewa

- **WHEN** seluruh tipe kamar aktif sebuah kost memiliki `available_rooms` nol
- **THEN** halaman detail tidak menawarkan pilihan Ajukan Sewa

#### Scenario: Draf pesan memuat nama tipe kamar

- **WHEN** Customer memilih Ajukan Sewa pada tipe kamar bernama "AC"
- **THEN** draf pesan WhatsApp memuat nama kost, "AC", harga sewa per bulan, dan nama lengkap Customer
