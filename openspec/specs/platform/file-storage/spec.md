# file-storage Specification

## Purpose

Menetapkan cara berkas unggahan disimpan dan disajikan, sehingga foto kost dapat diakses publik sementara dokumen identitas dan lampiran aduan tetap privat tanpa bergantung pada penyedia penyimpanan tertentu.

## Requirements

### Requirement: Pemisahan berkas publik dan privat

Sistem SHALL menyimpan foto kost pada penyimpanan publik, dan SHALL menyimpan dokumen verifikasi kost serta lampiran aduan pada penyimpanan privat yang tidak dapat diakses langsung melalui URL publik.

#### Scenario: Foto kost diakses publik

- **WHEN** pengguna membuka halaman detail kost yang telah disetujui
- **THEN** foto kost tampil melalui URL publik yang dapat diakses tanpa autentikasi

#### Scenario: Dokumen verifikasi diakses langsung

- **WHEN** pengguna tanpa peran Admin mencoba membuka URL langsung berkas dokumen verifikasi kost
- **THEN** permintaan ditolak dan isi berkas tidak dikirimkan

#### Scenario: Admin membuka dokumen verifikasi

- **WHEN** Admin membuka detail kost berstatus Menunggu Verifikasi
- **THEN** sistem menyajikan dokumen verifikasi melalui tautan sementara yang berlaku terbatas waktu

#### Scenario: Lampiran aduan hanya untuk pihak berwenang

- **WHEN** pengguna selain pemilik aduan dan Admin meminta lampiran aduan
- **THEN** permintaan ditolak

### Requirement: Batas tipe dan ukuran berkas

Sistem SHALL menolak unggahan yang tipenya tidak termasuk jenis berkas yang diizinkan atau yang ukurannya melebihi batas per jenis berkas, dan SHALL menampilkan pesan kesalahan pada kolom unggahan tanpa menyimpan berkas tersebut.

#### Scenario: Foto kost melebihi batas ukuran

- **WHEN** Pemilik Kost mengunggah foto kost berukuran lebih besar dari 3 MB
- **THEN** unggahan ditolak dengan pesan kesalahan dan tidak ada berkas tersimpan

#### Scenario: Dokumen verifikasi bertipe tidak diizinkan

- **WHEN** Pemilik Kost mengunggah dokumen verifikasi dengan tipe berkas di luar daftar yang diizinkan (gambar atau PDF)
- **THEN** unggahan ditolak dengan pesan kesalahan

#### Scenario: Lampiran aduan melebihi jumlah maksimum

- **WHEN** pengguna melampirkan lebih dari 3 berkas pada satu aduan
- **THEN** sistem menolak berkas berlebih dan meminta pengguna mengurangi jumlah lampiran

#### Scenario: Unggahan sah diterima

- **WHEN** pengguna mengunggah berkas dengan tipe yang diizinkan dan ukuran di bawah batas
- **THEN** berkas tersimpan pada penyimpanan yang sesuai dan path-nya tercatat pada basis data

### Requirement: Penyimpanan path, bukan URL

Sistem SHALL menyimpan lokasi berkas sebagai path atau key pada kolom basis data, dan SHALL membentuk URL sajian saat runtime sehingga penggantian penyedia penyimpanan tidak memerlukan perubahan data.

#### Scenario: Berkas lama tetap dapat disajikan

- **WHEN** konfigurasi penyimpanan diubah ke penyedia lain yang memuat berkas dengan path yang sama
- **THEN** berkas lama tetap dapat disajikan tanpa memperbarui kolom path pada basis data

#### Scenario: Berkas hilang dari penyimpanan

- **WHEN** permintaan penyajian berkas menunjuk path yang tidak ada pada penyimpanan
- **THEN** sistem menampilkan tanggapan tidak ditemukan dan tidak menampilkan galat internal

### Requirement: Pembersihan berkas saat data dihapus

Sistem SHALL menghapus berkas terkait ketika entitas pemiliknya dihapus, sehingga tidak meninggalkan berkas yatim pada penyimpanan.

#### Scenario: Kost dihapus

- **WHEN** Pemilik Kost menghapus kost miliknya
- **THEN** foto kost dan dokumen verifikasinya turut terhapus dari penyimpanan

#### Scenario: Penggantian foto kost

- **WHEN** Pemilik Kost mengganti foto utama kost
- **THEN** berkas foto lama terhapus dari penyimpanan setelah penyimpanan berkas baru berhasil
