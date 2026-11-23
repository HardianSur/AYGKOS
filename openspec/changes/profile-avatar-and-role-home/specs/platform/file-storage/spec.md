# Spec Delta

## MODIFIED Requirements

### Requirement: Pemisahan berkas publik dan privat

Sistem SHALL menyimpan foto kost dan foto profil pengguna pada penyimpanan publik, dan SHALL menyimpan dokumen verifikasi kost serta lampiran aduan pada penyimpanan privat yang tidak dapat diakses langsung melalui URL publik.

#### Scenario: Foto kost diakses publik

- **WHEN** pengguna membuka halaman detail kost yang telah disetujui
- **THEN** foto kost tampil melalui URL publik yang dapat diakses tanpa autentikasi

#### Scenario: Foto profil diakses publik

- **WHEN** foto profil seorang pengguna ditampilkan pada header aplikasi
- **THEN** foto dimuat melalui URL publik tanpa memerlukan autentikasi tambahan

#### Scenario: Dokumen verifikasi diakses langsung

- **WHEN** pengguna tanpa peran Admin mencoba membuka URL langsung berkas dokumen verifikasi kost
- **THEN** permintaan ditolak dan isi berkas tidak dikirimkan

#### Scenario: Admin membuka dokumen verifikasi

- **WHEN** Admin membuka detail kost berstatus Menunggu Verifikasi
- **THEN** sistem menyajikan dokumen verifikasi melalui tautan sementara yang berlaku terbatas waktu

#### Scenario: Lampiran aduan hanya untuk pihak berwenang

- **WHEN** pengguna selain pemilik aduan dan Admin meminta lampiran aduan
- **THEN** permintaan ditolak

### Requirement: Pembersihan berkas saat data dihapus

Sistem SHALL menghapus berkas terkait ketika entitas pemiliknya dihapus, sehingga tidak meninggalkan berkas yatim pada penyimpanan.

#### Scenario: Kost dihapus

- **WHEN** Pemilik Kost menghapus kost miliknya
- **THEN** foto kost dan dokumen verifikasinya turut terhapus dari penyimpanan

#### Scenario: Penggantian foto kost

- **WHEN** Pemilik Kost mengganti foto utama kost
- **THEN** berkas foto lama terhapus dari penyimpanan setelah penyimpanan berkas baru berhasil

#### Scenario: Akun dihapus

- **WHEN** sebuah akun dihapus
- **THEN** berkas foto profil akun tersebut turut terhapus dari penyimpanan

#### Scenario: Penggantian foto profil

- **WHEN** pengguna mengganti foto profilnya
- **THEN** berkas foto lama terhapus dari penyimpanan setelah penyimpanan berkas baru berhasil, dan tetap tersimpan bila penyimpanan berkas baru gagal