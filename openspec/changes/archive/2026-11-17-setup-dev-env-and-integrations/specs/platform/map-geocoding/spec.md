# Spec Delta

## Purpose

Menyediakan pencarian kost berbasis lokasi dengan konversi alamat menjadi koordinat dan tampilan titik pada peta, sehingga Customer dapat menemukan kost dalam radius tertentu dari lokasi yang dipilih.

## ADDED Requirements

### Requirement: Konversi lokasi menjadi koordinat

Sistem SHALL mengonversi masukan lokasi berupa nama tempat atau alamat menjadi pasangan koordinat lintang dan bujur sebelum menjalankan pencarian radius.

#### Scenario: Lokasi ditemukan

- **WHEN** pengguna memasukkan nama tempat yang dapat dikenali penyedia geocoding
- **THEN** sistem memperoleh koordinat dan menjalankan pencarian kost dalam radius 10 km dari koordinat tersebut

#### Scenario: Lokasi tidak ditemukan

- **WHEN** pengguna memasukkan teks yang tidak dapat dikonversi menjadi koordinat
- **THEN** sistem menampilkan pesan bahwa lokasi tidak ditemukan dan menampilkan hasil tanpa penyaringan radius

#### Scenario: Penyedia geocoding tidak tersedia

- **WHEN** permintaan ke penyedia geocoding gagal atau melewati batas waktu
- **THEN** sistem menampilkan pesan galat yang dapat dimengerti pengguna dan mencatat kegagalan pada log, tanpa menampilkan galat internal

### Requirement: Saran lokasi saat mengetik

Sistem SHALL menyediakan saran lokasi saat pengguna mengetik, dengan jeda minimum antar permintaan agar tidak melampaui kuota penyedia.

#### Scenario: Saran muncul saat mengetik

- **WHEN** pengguna mengetik minimal tiga karakter pada kolom lokasi
- **THEN** sistem menampilkan daftar saran lokasi yang dapat dipilih

#### Scenario: Permintaan dibatasi saat mengetik cepat

- **WHEN** pengguna mengetik dengan cepat sehingga menghasilkan banyak perubahan masukan
- **THEN** sistem hanya mengirim permintaan saran setelah masukan berhenti berubah dalam jeda yang ditetapkan

#### Scenario: Kuota penyedia habis

- **WHEN** penyedia mengembalikan tanggapan kuota terlampaui
- **THEN** sistem menonaktifkan saran untuk sesi tersebut dan tetap mengizinkan pengguna memilih lokasi secara manual

### Requirement: Penyaringan radius

Sistem SHALL menampilkan hanya kost yang jaraknya tidak melebihi 10 km dari koordinat lokasi terpilih, dan SHALL menghitung jarak tanpa memerlukan ekstensi basis data spasial.

#### Scenario: Hasil berada di dalam radius

- **WHEN** pencarian dijalankan untuk suatu lokasi
- **THEN** setiap kost yang tampil memiliki jarak terhitung tidak lebih dari 10 km dari koordinat lokasi

#### Scenario: Tidak ada hasil dalam radius

- **WHEN** tidak ada kost yang memenuhi radius maupun filter lain
- **THEN** sistem menampilkan pesan informatif bahwa hasil kosong beserta saran memperluas kriteria

#### Scenario: Pengurutan berdasarkan jarak

- **WHEN** pengguna memilih urutan jarak terdekat pada pencarian berbasis lokasi
- **THEN** hasil diurutkan menaik berdasarkan jarak terhitung dan setiap kartu menampilkan jaraknya

#### Scenario: Pengurutan jarak tanpa lokasi

- **WHEN** pengguna memilih urutan jarak terdekat tanpa menentukan lokasi
- **THEN** pilihan tersebut tidak tersedia atau dinonaktifkan

### Requirement: Penandaan titik pada peta

Sistem SHALL menampilkan kost hasil pencarian sebagai titik pada peta, dan SHALL menampilkan ringkasan kost beserta tautan ke halaman detail ketika suatu titik dipilih.

#### Scenario: Peta menampilkan hasil

- **WHEN** pencarian menghasilkan satu atau lebih kost
- **THEN** peta menampilkan titik untuk setiap kost hasil beserta cakupan wilayah radius lokasi pencarian

#### Scenario: Titik dipilih

- **WHEN** pengguna memilih salah satu titik pada peta
- **THEN** sistem menampilkan ringkasan kost berupa nama, harga mulai dari, dan tautan menuju halaman detail

#### Scenario: Peta tanpa hasil

- **WHEN** pencarian tidak menghasilkan kost
- **THEN** peta tetap tampil pada lokasi pencarian tanpa titik kost

### Requirement: Penentuan titik koordinat kost

Sistem SHALL memungkinkan Pemilik Kost menentukan titik koordinat kost melalui peta, dan SHALL memvalidasi bahwa koordinat berada pada rentang lintang -90 sampai 90 dan bujur -180 sampai 180 serta berada di wilayah Indonesia.

#### Scenario: Menentukan titik melalui peta

- **WHEN** Pemilik Kost memilih titik pada peta saat mengajukan kost
- **THEN** nilai lintang dan bujur terisi otomatis sesuai titik yang dipilih

#### Scenario: Koordinat di luar Indonesia

- **WHEN** Pemilik Kost menyimpan kost dengan koordinat di luar wilayah Indonesia
- **THEN** sistem menolak penyimpanan dengan pesan kesalahan pada kolom titik lokasi

#### Scenario: Koordinat tidak diisi

- **WHEN** Pemilik Kost mengajukan kost tanpa menentukan titik lokasi
- **THEN** sistem menolak pengajuan dengan pesan kesalahan pada kolom titik lokasi

#### Scenario: Bantuan pengisian alamat

- **WHEN** Pemilik Kost mengisi alamat lengkap dan meminta bantuan penentuan titik
- **THEN** sistem mengusulkan koordinat berdasarkan alamat tersebut dan Pemilik Kost dapat mengoreksinya secara manual sebelum menyimpan