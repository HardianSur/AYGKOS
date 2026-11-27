# Spec Delta

## Purpose

Memberi Admin satu tempat untuk menelusuri seluruh pengajuan kost, menyaringnya menurut status dan tanggal pengajuan, lalu memeriksa isi pengajuan beserta kelengkapan dokumen izin sebelum memutuskan hasil verifikasi.

## ADDED Requirements

### Requirement: Halaman verifikasi hanya dapat dibuka Admin

Halaman master dan detail verifikasi kost SHALL hanya dapat dibuka akun yang sudah masuk, sudah terverifikasi sesuai perannya, dan memegang peran `admin`. Permintaan oleh pengguna dengan peran lain, pengguna tanpa peran, maupun tamu SHALL ditolak tanpa mengirimkan isi halaman.

#### Scenario: Admin membuka halaman master

- **WHEN** akun Admin terverifikasi membuka `/admin/kosts`
- **THEN** permintaan dijawab tanpa galat dan tabel kost ditampilkan

#### Scenario: Admin membuka halaman detail

- **WHEN** akun Admin terverifikasi membuka `/admin/kosts/{kost}`
- **THEN** permintaan dijawab tanpa galat dan data pengajuan kost tersebut ditampilkan

#### Scenario: Peran lain ditolak

- **WHEN** akun Customer atau Pemilik Kost membuka `/admin/kosts` maupun `/admin/kosts/{kost}`
- **THEN** permintaan ditolak dengan status 403 tanpa prop halaman

#### Scenario: Tamu dialihkan ke halaman masuk

- **WHEN** pengunjung yang belum masuk membuka `/admin/kosts`
- **THEN** pengguna dialihkan ke halaman masuk

### Requirement: Master menampilkan seluruh kost berpaginasi sepuluh baris

Halaman master SHALL menampilkan seluruh kost yang belum dihapus secara lunak tanpa memandang statusnya, sepuluh baris per halaman, terurut dari pengajuan terbaru. Setiap baris SHALL memuat nomor urut, nama kost, nama pemilik, status verifikasi, dan tanggal pengajuan, serta menyediakan tautan menuju halaman detail kost tersebut.

#### Scenario: Halaman memuat sepuluh baris

- **WHEN** basis data berisi dua puluh lima kost dan Admin membuka halaman pertama
- **THEN** halaman menampilkan sepuluh baris dan navigasi menuju tiga halaman

#### Scenario: Seluruh status tampil tanpa filter

- **WHEN** Admin membuka halaman master tanpa memilih filter apa pun
- **THEN** kost berstatus `menunggu_verifikasi`, `disetujui`, `ditolak`, `ditangguhkan`, dan `nonaktif` sama-sama dapat muncul

#### Scenario: Urutan dari pengajuan terbaru

- **WHEN** Admin membuka halaman pertama tanpa filter
- **THEN** baris teratas adalah kost dengan tanggal pengajuan paling akhir

#### Scenario: Kost terhapus lunak tidak tampil

- **WHEN** sebuah kost dihapus secara lunak
- **THEN** kost tersebut tidak muncul pada tabel maupun dihitung dalam jumlah baris

#### Scenario: Baris menuju detail

- **WHEN** Admin memilih satu baris kost pada tabel
- **THEN** halaman detail kost tersebut ditampilkan

### Requirement: Master dapat disaring menurut status pengajuan

Halaman master SHALL menyediakan penyaring status yang menerima nilai `menunggu_verifikasi`, `disetujui`, `ditolak`, `ditangguhkan`, `nonaktif`, atau tanpa penyaring. Nilai di luar daftar itu SHALL diabaikan sehingga halaman tetap tampil tanpa galat.

#### Scenario: Filter status menyempitkan tabel

- **WHEN** Admin memilih status `menunggu_verifikasi`
- **THEN** tabel hanya memuat kost berstatus `menunggu_verifikasi` dan jumlah baris mengikuti hasil saringan

#### Scenario: Filter status dikosongkan

- **WHEN** Admin menghapus pilihan status
- **THEN** tabel kembali memuat kost dari semua status

#### Scenario: Nilai status tidak dikenal

- **WHEN** permintaan menyertakan status di luar lima nilai yang sah
- **THEN** halaman tetap dijawab tanpa galat dan penyaring itu tidak diterapkan

### Requirement: Master dapat disaring menurut rentang tanggal pengajuan

Halaman master SHALL menyediakan penyaring tanggal awal dan tanggal akhir yang membandingkan tanggal pengajuan kost, dan SHALL mengabaikan batas yang tidak diisi. Bila tanggal awal lebih akhir daripada tanggal akhir, halaman SHALL tetap dijawab tanpa galat dan tanpa hasil yang menyesatkan.

#### Scenario: Hanya batas awal diisi

- **WHEN** Admin mengisi tanggal awal 1 Desember 2026 dan membiarkan tanggal akhir kosong
- **THEN** tabel hanya memuat kost yang diajukan pada atau setelah tanggal tersebut

#### Scenario: Kedua batas diisi

- **WHEN** Admin mengisi tanggal awal dan tanggal akhir
- **THEN** tabel hanya memuat kost yang tanggal pengajuannya berada di antara kedua batas tersebut, termasuk kedua batasnya

#### Scenario: Tanpa batas tanggal

- **WHEN** Admin tidak mengisi kedua kolom tanggal
- **THEN** tidak ada penyaringan tanggal yang diterapkan

### Requirement: Penyaring dan halaman bertahan pada URL

Nilai penyaring status, tanggal awal, dan tanggal akhir SHALL ikut terbawa pada tautan navigasi halaman, sehingga berpindah halaman tidak menghilangkan saringan yang sedang aktif.

#### Scenario: Berpindah halaman mempertahankan saringan

- **WHEN** Admin menyaring status `ditolak` lalu membuka halaman kedua
- **THEN** halaman kedua tetap hanya memuat kost berstatus `ditolak`

#### Scenario: Tautan halaman memuat nilai saringan

- **WHEN** Admin menyaring tanggal pengajuan lalu menyalin tautan navigasi halaman
- **THEN** tautan yang disalin memuat nilai tanggal tersebut

### Requirement: Detail menampilkan data pengajuan kost

Halaman detail SHALL menampilkan data yang diinput Pemilik Kost: nama, tipe, kota, alamat lengkap, deskripsi bila terisi, titik lokasi, fasilitas umum, peraturan kost, foto bangunan, nama dan kontak pemilik, status verifikasi, serta tanggal pengajuan.

#### Scenario: Detail memuat identitas dan kontak pemilik

- **WHEN** Admin membuka detail sebuah kost
- **THEN** halaman menampilkan nama, tipe, kota, alamat, nama pemilik, dan nomor WhatsApp pemilik kost tersebut

#### Scenario: Deskripsi kosong

- **WHEN** kost diajukan tanpa deskripsi
- **THEN** halaman detail tetap tampil tanpa bagian deskripsi berisi teks kosong

#### Scenario: Fasilitas dan peraturan tampil

- **WHEN** kost memilih dua fasilitas umum dan satu peraturan kost
- **THEN** halaman detail menampilkan kedua fasilitas dan peraturan tersebut

#### Scenario: Foto bangunan tampil

- **WHEN** kost memiliki tiga foto bangunan
- **THEN** halaman detail menampilkan ketiga foto tersebut

#### Scenario: Titik lokasi dapat dibaca

- **WHEN** Admin membuka detail kost yang memiliki koordinat
- **THEN** halaman menampilkan titik lokasi kost pada peta

### Requirement: Detail menampilkan lima slot dokumen izin dengan penanda wajib

Halaman detail SHALL menampilkan kelima jenis dokumen izin — `domisili_usaha`, `izin_lingkungan`, `izin_kelurahan`, `nib`, dan `surat_pernyataan` — beserta status terunggah atau belum. Slot `nib` dan `surat_pernyataan` SHALL ditandai wajib, dan slot wajib yang belum terunggah SHALL ditandai sebagai kekurangan agar Admin dapat melihatnya tanpa menebak.

#### Scenario: Dokumen terunggah dapat dibuka

- **WHEN** Admin membuka detail kost yang memiliki dokumen `nib`
- **THEN** halaman menyediakan tautan menuju berkas NIB tersebut

#### Scenario: Dokumen opsional kosong ditampilkan

- **WHEN** kost hanya mengunggah `nib` dan `surat_pernyataan`
- **THEN** ketiga slot opsional tampil sebagai belum terunggah tanpa ditandai kekurangan

#### Scenario: Dokumen wajib kosong ditandai

- **WHEN** sebuah kost belum memiliki berkas `surat_pernyataan`
- **THEN** slot surat pernyataan tampil sebagai kekurangan yang perlu diperiksa Admin

### Requirement: Tautan dokumen izin hanya berlaku sementara

Tautan berkas dokumen izin SHALL berupa tautan bertanda tangan yang kedaluwarsa setelah sepuluh menit, dan permintaan atas tautan tanpa tanda tangan yang sah maupun tautan kedaluwarsa SHALL ditolak tanpa mengirimkan isi berkas.

#### Scenario: Tautan kedaluwarsa ditolak

- **WHEN** tautan dokumen dibuka setelah sepuluh menit berlalu
- **THEN** permintaan ditolak dan isi berkas tidak dikirimkan

#### Scenario: Tautan tanpa tanda tangan ditolak

- **WHEN** pengguna mencoba membuka path dokumen tanpa tanda tangan yang sah
- **THEN** permintaan ditolak dan isi berkas tidak dikirimkan

#### Scenario: Tautan baru dibuka dalam masa berlaku

- **WHEN** Admin membuka tautan dokumen yang baru dibentuk pada halaman detail
- **THEN** berkas dokumen dikirimkan

### Requirement: Detail menampilkan riwayat tinjauan bila sudah ada

Halaman detail SHALL menampilkan nama Admin peninjau, waktu tinjauan, dan alasan penolakan atau penangguhan ketika kost sudah pernah diproses, dan SHALL tidak menampilkan bagian riwayat itu ketika kost belum pernah diproses.

#### Scenario: Kost belum diproses

- **WHEN** Admin membuka detail kost berstatus `menunggu_verifikasi` yang belum pernah ditinjau
- **THEN** halaman tidak menampilkan peninjau, waktu tinjauan, maupun alasan

#### Scenario: Kost sudah ditolak

- **WHEN** Admin membuka detail kost berstatus `ditolak` dengan alasan terisi
- **THEN** halaman menampilkan nama peninjau, waktu tinjauan, dan alasan penolakan tersebut

### Requirement: Halaman verifikasi bersifat baca-saja

Halaman master dan detail SHALL NOT menyediakan kendali yang mengubah data kost, dan SHALL NOT ada route yang menerima permintaan perubahan dari halaman ini. Menyetujui, menolak, dan menangguhkan kost SHALL ditangani di luar cakupan halaman ini.

#### Scenario: Tidak ada kendali perubahan

- **WHEN** Admin membuka halaman master maupun detail
- **THEN** halaman tidak menampilkan tombol atau formulir yang mengubah status, alasan, maupun data kost

#### Scenario: Permintaan perubahan ditolak

- **WHEN** sebuah permintaan dikirim ke route verifikasi dengan metode selain `GET`
- **THEN** route tersebut tidak tersedia
