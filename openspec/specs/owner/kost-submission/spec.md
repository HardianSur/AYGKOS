# kost-submission Specification

## Purpose

Mengatur cara Pemilik Kost mengajukan kost baru beserta berkas pendukungnya, dan cara pemilik melihat status verifikasi kost miliknya sendiri.

## Requirements

### Requirement: Pengajuan kost menyimpan data wajib dan berstatus menunggu verifikasi

Pemilik Kost SHALL dapat mengajukan kost dengan data nama, alamat lengkap, kota, titik lokasi, tipe kost, deskripsi opsional, fasilitas umum, peraturan kost, minimal satu foto, serta dokumen izin. Kost baru SHALL tersimpan dengan status `menunggu_verifikasi` dan pemiliknya SHALL diambil dari akun yang sedang masuk.

#### Scenario: Pengajuan lengkap diterima

- **WHEN** Pemilik Kost mengirim formulir dengan seluruh data wajib, satu foto, NIB, dan surat pernyataan
- **THEN** kost tersimpan berstatus `menunggu_verifikasi` dan halaman daftar kost miliknya menampilkan kost tersebut

#### Scenario: Pemilik tidak dipilih dari masukan

- **WHEN** formulir dikirim dengan kolom pemilik yang menunjuk akun lain
- **THEN** kost tetap tersimpan atas nama akun yang mengirim formulir

#### Scenario: Deskripsi boleh kosong

- **WHEN** Pemilik Kost mengirim formulir tanpa mengisi deskripsi
- **THEN** pengajuan diterima dan kolom deskripsi pada kost bernilai kosong

### Requirement: Status verifikasi tidak pernah diambil dari masukan pengguna

Kolom status SHALL NOT dibaca dari data permintaan. Setiap pengajuan baru SHALL berstatus `menunggu_verifikasi`, dan kolom `status_reason`, `reviewed_by`, serta `reviewed_at` SHALL kosong hingga Admin memprosesnya.

#### Scenario: Status yang dikirim diabaikan

- **WHEN** formulir dikirim dengan kolom status bernilai `disetujui`
- **THEN** kost tersimpan berstatus `menunggu_verifikasi` dan tidak tampil kepada publik

#### Scenario: Alasan penolakan tidak dapat diisi pemilik

- **WHEN** formulir dikirim dengan kolom alasan penolakan berisi teks
- **THEN** kolom `status_reason` pada kost yang tersimpan tetap kosong

### Requirement: Titik lokasi wajib berada di wilayah Indonesia

Koordinat SHALL berasal dari titik yang dipilih Pemilik Kost pada peta, bukan dari alamat teks. Lintang SHALL berada pada rentang -11 sampai 6,1 dan bujur pada rentang 94,9 sampai 141,1. Pengajuan tanpa titik lokasi atau di luar rentang tersebut SHALL ditolak dengan pesan galat pada kolom titik lokasi.

#### Scenario: Titik di dalam Indonesia diterima

- **WHEN** Pemilik Kost memilih titik pada peta di Kota Bandung lalu menyimpan
- **THEN** kost tersimpan dengan lintang dan bujur sesuai titik tersebut

#### Scenario: Koordinat di luar Indonesia ditolak

- **WHEN** Pemilik Kost mengirim formulir dengan lintang -20 dan bujur 100
- **THEN** pengajuan ditolak dan pesan galat menempel pada kolom lintang

#### Scenario: Titik lokasi tidak diisi

- **WHEN** Pemilik Kost mengirim formulir tanpa memilih titik pada peta
- **THEN** pengajuan ditolak dan pesan galat menempel pada kolom titik lokasi

#### Scenario: Alamat tidak menentukan titik

- **WHEN** Pemilik Kost mengisi alamat yang tidak dikenali penyedia geocoding namun memilih titik pada peta
- **THEN** pengajuan diterima tanpa galat pada kolom alamat

### Requirement: Kota wajib terisi saat pengajuan

Pengajuan SHALL memuat kota yang tidak kosong. Cara kota terisi dan aturan pertahanan terhadap suntingan manual diatur pada capability `platform/map-geocoding`.

#### Scenario: Kota kosong ditolak

- **WHEN** Pemilik Kost mengirim formulir dengan kolom kota kosong
- **THEN** pengajuan ditolak dan pesan galat menempel pada kolom kota

### Requirement: Foto kost dan dokumen izin wajib memenuhi batas berkas

Pengajuan SHALL memuat paling banyak sepuluh foto berformat JPG, PNG, atau WebP berukuran paling besar 3 MB per berkas. Dokumen berjenis `nib` dan `surat_pernyataan` SHALL wajib ada, sedangkan `domisili_usaha`, `izin_lingkungan`, dan `izin_kelurahan` SHALL opsional. Setiap dokumen SHALL berformat PDF berukuran paling besar 5 MB.

#### Scenario: Tanpa foto ditolak

- **WHEN** Pemilik Kost mengirim formulir tanpa satu pun foto
- **THEN** pengajuan ditolak dan pesan galat menempel pada kolom foto

#### Scenario: Tanpa NIB ditolak

- **WHEN** Pemilik Kost mengirim formulir tanpa dokumen berjenis `nib`
- **THEN** pengajuan ditolak dan pesan galat menempel pada kolom NIB

#### Scenario: Dokumen opsional boleh kosong

- **WHEN** Pemilik Kost mengirim formulir dengan NIB dan surat pernyataan saja
- **THEN** pengajuan diterima dan hanya dua baris dokumen tersimpan

#### Scenario: Berkas melebihi batas ditolak

- **WHEN** Pemilik Kost mengunggah foto berukuran 4 MB
- **THEN** pengajuan ditolak dan tidak ada baris kost yang tersimpan

### Requirement: Kegagalan penyimpanan tidak meninggalkan data atau berkas separuh jadi

Penyimpanan kost, foto, dokumen, fasilitas, dan peraturan SHALL berhasil seluruhnya atau tidak sama sekali. Bila salah satu langkah gagal, SHALL tidak ada baris basis data tersisa dan berkas yang sudah tertulis SHALL dihapus dari penyimpanan.

#### Scenario: Penyimpanan baris gagal

- **WHEN** penyimpanan baris basis data gagal setelah berkas foto dan dokumen tertulis
- **THEN** tidak ada baris kost yang tersimpan dan berkas yang sudah tertulis tidak tertinggal di penyimpanan

### Requirement: Daftar kost menampilkan status verifikasi dan alasannya

Halaman daftar kost milik Pemilik Kost SHALL menampilkan foto utama bila ada, nama, kota, tipe, status verifikasi, dan alasan penolakan atau penangguhan bila terisi. Halaman SHALL hanya memuat kost milik akun yang sedang masuk, termasuk yang sudah dihapus lunak tidak ditampilkan.

#### Scenario: Daftar hanya memuat kost sendiri

- **WHEN** Pemilik Kost membuka halaman daftar kost miliknya
- **THEN** hanya kost dengan pemilik tersebut yang tampil

#### Scenario: Kost ditolak menampilkan alasan

- **WHEN** sebuah kost berstatus `ditolak` dengan alasan terisi
- **THEN** baris kost tersebut menampilkan status dan alasannya

#### Scenario: Kost tanpa foto tetap tampil

- **WHEN** sebuah kost hanya memiliki satu foto yang gagal dimuat
- **THEN** baris kost tetap tampil dengan penanda gambar kosong, bukan halaman galat

### Requirement: Hanya Pemilik Kost aktif dan terverifikasi yang dapat mengajukan kost

Halaman pengajuan dan daftar kost SHALL hanya dapat dibuka oleh akun berperan Pemilik Kost yang identitasnya terverifikasi dan akunnya aktif. Peran lain dan akun nonaktif SHALL ditolak dengan status 403 tanpa menampilkan isi halaman.

#### Scenario: Pemilik Kost terverifikasi membuka formulir

- **WHEN** akun Pemilik Kost terverifikasi membuka halaman pengajuan kost
- **THEN** formulir tampil beserta daftar fasilitas berlingkup umum dan peraturan berlingkup kost

#### Scenario: Peran lain ditolak

- **WHEN** akun Customer atau Admin membuka halaman pengajuan kost
- **THEN** permintaan dijawab dengan status 403

#### Scenario: Akun nonaktif ditolak

- **WHEN** akun Pemilik Kost yang sudah dinonaktifkan membuka halaman pengajuan kost
- **THEN** permintaan dijawab dengan status 403
