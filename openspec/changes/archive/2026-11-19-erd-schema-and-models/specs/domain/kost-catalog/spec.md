# Spec Delta

## Purpose

Menyimpan data kost yang diiklankan Pemilik Kost beserta foto, fasilitas, dan kamarnya, sehingga pencarian, penyaringan, dan halaman detail kost memiliki sumber data yang lengkap dan konsisten.

## ADDED Requirements

### Requirement: Kost menyimpan data lokasi, klasifikasi, dan status verifikasi

Setiap baris `kosts` SHALL menyimpan pemilik, nama, alamat lengkap, kota, koordinat lintang dan bujur, tipe kost, deskripsi, peraturan, dokumen verifikasi, dan status verifikasi dengan nilai awal menunggu verifikasi. Status SHALL berupa salah satu dari `menunggu_verifikasi`, `disetujui`, `ditolak`, `nonaktif`, atau `ditangguhkan`. Tipe SHALL berupa salah satu dari `putra`, `putri`, atau `campur`.

#### Scenario: Kost baru berstatus menunggu verifikasi

- **WHEN** sebuah kost dibuat tanpa menyebut status
- **THEN** `status` bernilai `menunggu_verifikasi`

#### Scenario: Penolakan dan penangguhan menyimpan alasan

- **WHEN** Admin menolak atau menangguhkan sebuah kost dengan alasan
- **THEN** `status_reason` terisi dan `reviewed_by` serta `reviewed_at` mencatat Admin beserta waktunya

#### Scenario: Koordinat wajib terisi

- **WHEN** sebuah kost hendak disimpan tanpa `latitude` atau `longitude`
- **THEN** basis data menolak penyisipan

### Requirement: Foto kost tersimpan sebagai path dan satu foto ditandai utama

Setiap baris `kost_photos` SHALL menyimpan path berkas, bukan URL, dan SHALL menandai satu foto sebagai utama. Kost SHALL dapat memiliki lebih dari satu foto.

#### Scenario: Foto tersimpan sebagai path relatif

- **WHEN** sebuah foto kost disimpan
- **THEN** kolom `photo_path` memuat path atau key objek dan tidak memuat URL lengkap

#### Scenario: Foto utama ditandai

- **WHEN** sebuah foto ditetapkan sebagai foto utama
- **THEN** `is_primary` bernilai benar pada foto tersebut

### Requirement: Fasilitas berupa data master dan dihubungkan banyak ke banyak

Setiap fasilitas SHALL berupa satu baris `facilities` dengan nama unik, dan hubungan kost dengan fasilitas SHALL disimpan pada `kost_facilities`. Pasangan kost dan fasilitas yang sama SHALL NOT tersimpan dua kali. Relasi SHALL bertahan sebagai pasangan tanpa kolom identitas tambahan.

#### Scenario: Fasilitas yang sama tidak tercatat dua kali pada satu kost

- **WHEN** sebuah pasangan kost dan fasilitas disimpan dua kali
- **THEN** basis data menolak pasangan kedua

#### Scenario: Nama fasilitas unik antarkost

- **WHEN** sebuah fasilitas disimpan dengan nama yang sudah ada
- **THEN** basis data menolak penyisipan

#### Scenario: Menghapus kost membersihkan pasangan fasilitasnya

- **WHEN** sebuah kost dihapus secara paksa
- **THEN** baris `kost_facilities` miliknya ikut terhapus sementara baris `facilities` tetap ada

### Requirement: Kamar menyimpan nomor, harga, dan ketersediaan

Setiap baris `rooms` SHALL menyimpan nomor atau nama kamar yang unik dalam satu kost, harga sewa per bulan dalam rupiah, dan status ketersediaan dengan nilai awal tersedia. Status SHALL berupa salah satu dari `tersedia`, `terisi`, atau `nonaktif`.

#### Scenario: Kamar baru berstatus tersedia

- **WHEN** sebuah kamar dibuat tanpa menyebut status
- **THEN** `status` bernilai `tersedia`

#### Scenario: Nomor kamar boleh sama pada kost berbeda

- **WHEN** dua kost berbeda masing-masing memiliki kamar bernomor sama
- **THEN** kedua baris tersimpan tanpa melanggar aturan unik

### Requirement: Hanya kost disetujui milik akun aktif yang tampil kepada publik

Kost SHALL tampil kepada publik hanya ketika berstatus `disetujui`, belum dihapus secara lunak, dan pemiliknya berstatus aktif. Kost berstatus lain SHALL tetap tersimpan dan tetap dapat dikelola pemiliknya.

#### Scenario: Kost menunggu verifikasi tidak tampil

- **WHEN** kost berstatus `menunggu_verifikasi` dicari melalui daftar publik
- **THEN** kost tersebut tidak muncul pada hasil

#### Scenario: Kost milik akun nonaktif hilang dari daftar publik

- **WHEN** akun pemilik sebuah kost dinonaktifkan Admin
- **THEN** kost miliknya tidak muncul pada daftar publik meskipun statusnya `disetujui`, dan data kost tidak berubah

#### Scenario: Kost disetujui milik akun aktif tampil

- **WHEN** kost berstatus `disetujui` dimiliki akun aktif dan belum dihapus
- **THEN** kost tersebut muncul pada daftar publik

### Requirement: Menghapus kost menghapus kamarnya dan menyembunyikan kost

Penghapusan kost oleh pemiliknya SHALL menyembunyikan kost dari seluruh daftar dan SHALL menyembunyikan kamar-kamarnya, tanpa menghapus baris kost maupun kamar dari basis data.

#### Scenario: Kamar ikut tersembunyi saat kost dihapus

- **WHEN** seorang Pemilik Kost menghapus kostnya
- **THEN** kost dan seluruh kamarnya tidak lagi muncul pada daftar mana pun, sementara barisnya tetap tersimpan dengan `deleted_at` terisi

#### Scenario: Kamar tidak dapat diakses melalui kost yang terhapus

- **WHEN** kamar dari kost yang sudah dihapus diambil melalui relasi baku dari kost
- **THEN** kamar tersebut tidak ikut terambil