# Spec Delta

## Purpose

Memberi Admin kendali memutuskan hasil verifikasi pengajuan kost — menyetujui, menolak dengan alasan, atau menangguhkan kost yang sudah tayang — sehingga status kost bergerak dari antrean menjadi tayang atau ditolak tanpa ada jalur status yang melompat.

## ADDED Requirements

### Requirement: Keputusan verifikasi hanya dapat dilakukan Admin

Ketiga route keputusan verifikasi SHALL hanya menerima permintaan dari akun yang sudah masuk, sudah terverifikasi sesuai perannya, dan memegang peran `admin`. Permintaan oleh peran lain, akun tanpa peran, akun nonaktif, maupun tamu SHALL ditolak tanpa mengubah status kost.

#### Scenario: Admin memutuskan pengajuan

- **WHEN** akun Admin terverifikasi mengirim permintaan menyetujui sebuah kost berstatus `menunggu_verifikasi`
- **THEN** permintaan diproses dan status kost berubah

#### Scenario: Peran lain ditolak

- **WHEN** akun Customer atau Pemilik Kost mengirim permintaan menyetujui, menolak, atau menangguhkan kost
- **THEN** permintaan ditolak dengan status 403 dan status kost tidak berubah

#### Scenario: Tamu dialihkan ke halaman masuk

- **WHEN** pengunjung yang belum masuk mengirim permintaan keputusan verifikasi
- **THEN** pengguna dialihkan ke halaman masuk dan tidak ada kost yang berubah

#### Scenario: Akun Admin nonaktif ditolak

- **WHEN** akun berperan `admin` yang sudah dinonaktifkan mengirim permintaan keputusan verifikasi
- **THEN** permintaan ditolak dan status kost tidak berubah

### Requirement: Admin menyetujui kost sehingga tampil kepada publik

Menyetujui SHALL mengubah status kost dari `menunggu_verifikasi` menjadi `disetujui` serta mencatat Admin peninjau dan waktu tinjauan. Kolom alasan SHALL dikembalikan menjadi kosong agar alasan penolakan sebelumnya tidak tertinggal.

#### Scenario: Kost disetujui menjadi tayang

- **WHEN** Admin menyetujui kost berstatus `menunggu_verifikasi` milik Pemilik Kost aktif
- **THEN** status kost menjadi `disetujui` dan kost tersebut dapat muncul pada daftar publik

#### Scenario: Peninjau dan waktu tercatat

- **WHEN** Admin menyetujui sebuah kost
- **THEN** `reviewed_by` memuat Admin tersebut dan `reviewed_at` memuat waktu keputusan

#### Scenario: Alasan lama dibersihkan

- **WHEN** Admin menyetujui kost yang sebelumnya pernah ditolak dan memiliki alasan
- **THEN** `status_reason` pada kost tersebut kembali kosong

#### Scenario: Persetujuan tidak diperlukan tanpa pengajuan

- **WHEN** Admin mengirim permintaan menyetujui kost yang sudah berstatus `disetujui`
- **THEN** permintaan ditolak dan data kost tidak berubah

### Requirement: Admin menolak kost dengan alasan wajib

Menolak SHALL mengubah status kost dari `menunggu_verifikasi` menjadi `ditolak`, menyimpan alasan penolakan, serta mencatat Admin peninjau dan waktu tinjauan. Alasan SHALL wajib diisi dan panjangnya antara 10 sampai 500 karakter.

#### Scenario: Penolakan dengan alasan tersimpan

- **WHEN** Admin menolak kost berstatus `menunggu_verifikasi` dengan alasan yang sah
- **THEN** status kost menjadi `ditolak`, `status_reason` memuat alasan tersebut, dan peninjau beserta waktunya tercatat

#### Scenario: Penolakan tanpa alasan ditolak validasi

- **WHEN** Admin menolak kost tanpa menyertakan alasan
- **THEN** permintaan ditolak dengan galat validasi pada kolom alasan dan status kost tidak berubah

#### Scenario: Alasan terlalu pendek ditolak validasi

- **WHEN** Admin menolak kost dengan alasan kurang dari 10 karakter
- **THEN** permintaan ditolak dengan galat validasi pada kolom alasan dan status kost tidak berubah

#### Scenario: Penolakan atas kost yang sudah diputuskan

- **WHEN** Admin menolak kost yang statusnya bukan `menunggu_verifikasi`
- **THEN** permintaan ditolak dan data kost tidak berubah

### Requirement: Admin menangguhkan kost yang sudah disetujui

Menangguhkan SHALL mengubah status kost dari `disetujui` menjadi `ditangguhkan`, menyimpan alasan penangguhan, serta mencatat Admin peninjau dan waktu tinjauan. Alasan SHALL wajib diisi dan panjangnya antara 10 sampai 500 karakter.

#### Scenario: Kost ditangguhkan hilang dari publik

- **WHEN** Admin menangguhkan kost berstatus `disetujui` dengan alasan yang sah
- **THEN** status kost menjadi `ditangguhkan`, `status_reason` memuat alasan tersebut, dan kost tersebut tidak lagi tampil kepada publik

#### Scenario: Penangguhan tanpa alasan ditolak validasi

- **WHEN** Admin menangguhkan kost tanpa menyertakan alasan
- **THEN** permintaan ditolak dengan galat validasi pada kolom alasan dan status kost tidak berubah

#### Scenario: Kost yang belum disetujui tidak dapat ditangguhkan

- **WHEN** Admin menangguhkan kost berstatus `menunggu_verifikasi`, `ditolak`, atau `nonaktif`
- **THEN** permintaan ditolak dan data kost tidak berubah

#### Scenario: Pemilik Kost tidak dapat mencabut penangguhan

- **WHEN** Pemilik Kost mencoba mengubah status kost `ditangguhkan` miliknya menjadi aktif
- **THEN** permintaan ditolak dan status kost tetap `ditangguhkan`

### Requirement: Keputusan ditolak bila status kost sudah berubah

Setiap keputusan SHALL diperiksa terhadap status kost saat permintaan diproses. Bila status tidak lagi sesuai dengan status asal yang disyaratkan, permintaan SHALL ditolak dengan pesan galat yang dapat dibaca Admin, dan tidak ada kolom kost yang berubah.

#### Scenario: Dua Admin memutuskan kost yang sama

- **WHEN** Admin pertama menyetujui sebuah kost lalu Admin kedua menolak kost yang sama
- **THEN** permintaan Admin kedua ditolak, status `disetujui` tetap, dan alasan penolakan tidak tersimpan

#### Scenario: Pesan galat menyebut status sudah berubah

- **WHEN** keputusan ditolak karena status asal tidak sesuai
- **THEN** Admin menerima pesan galat yang menyatakan status kost sudah berubah

### Requirement: Keputusan verifikasi meninggalkan notifikasi bagi Pemilik Kost

Setiap keputusan yang berhasil SHALL menulis satu baris notifikasi in-app bagi Pemilik Kost pemilik kost tersebut, dengan jenis yang menandai keputusannya dan rujukan ke kost yang bersangkutan. Bila keputusan ditolak, SHALL NOT ada baris notifikasi yang ditulis.

#### Scenario: Notifikasi persetujuan dibuat

- **WHEN** Admin menyetujui sebuah kost
- **THEN** satu baris notifikasi baru tercatat bagi Pemilik Kost pemilik kost tersebut dan merujuk ke kost itu

#### Scenario: Notifikasi penolakan dibuat

- **WHEN** Admin menolak sebuah kost
- **THEN** satu baris notifikasi baru tercatat bagi Pemilik Kost pemilik kost tersebut

#### Scenario: Notifikasi penangguhan dibuat

- **WHEN** Admin menangguhkan sebuah kost
- **THEN** satu baris notifikasi baru tercatat bagi Pemilik Kost pemilik kost tersebut

#### Scenario: Keputusan gagal tidak menulis notifikasi

- **WHEN** permintaan keputusan ditolak karena status asal tidak sesuai
- **THEN** tidak ada baris notifikasi yang ditulis

### Requirement: Halaman master verifikasi tetap baca-saja

Tabel pada halaman master `/admin/kosts` SHALL NOT menyediakan kendali yang mengubah status kost; seluruh keputusan SHALL dikerjakan dari halaman detail satu kost. Halaman detail SHALL menyediakan kendali sesuai status kost saat itu: Setujui dan Tolak untuk `menunggu_verifikasi`, Tangguhkan untuk `disetujui`, dan tidak ada kendali keputusan untuk status lainnya.

#### Scenario: Tabel master tanpa kendali perubahan

- **WHEN** Admin membuka halaman master `/admin/kosts`
- **THEN** halaman tidak menampilkan tombol yang mengubah status kost

#### Scenario: Kendali detail mengikuti status

- **WHEN** Admin membuka detail kost berstatus `menunggu_verifikasi`
- **THEN** halaman menampilkan kendali Setujui dan Tolak, serta tidak menampilkan kendali Tangguhkan

#### Scenario: Kost ditangguhkan tanpa kendali keputusan

- **WHEN** Admin membuka detail kost berstatus `ditangguhkan`
- **THEN** halaman tidak menampilkan kendali keputusan apa pun

## REMOVED Requirements

### Requirement: Halaman verifikasi bersifat baca-saja

**Reason**: Halaman verifikasi kini memiliki jalur keputusan; larangan menyediakan kendali perubahan dan route mutasi tidak berlaku lagi. Aturan baca-saja dipersempit hanya untuk tabel master pada requirement `Halaman master verifikasi tetap baca-saja`.

**Migration**: Route `GET` yang sudah ada tetap. Perilaku baca-saja pada tabel master diuji ulang lewat scenario `Tabel master tanpa kendali perubahan`; tidak ada data yang perlu dipindahkan.