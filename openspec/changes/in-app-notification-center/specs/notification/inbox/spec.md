# Spec Delta

## Purpose

Menyediakan tempat membaca notifikasi in-app milik sendiri bagi Pemilik Kost dan Admin — daftar berpaginasi dengan penanda belum dibaca, penandaan baca per baris maupun menyeluruh, ringkasan belum dibaca pada lonceng header, dan pemberitahuan toast saat notifikasi baru tiba selama aplikasi terbuka.

## ADDED Requirements

### Requirement: Halaman notifikasi menampilkan notifikasi milik pengguna yang sedang masuk

Pengguna yang sudah masuk dan terverifikasi SHALL dapat membuka `/notifications` dan melihat notifikasi miliknya saja, terurut dari yang terbaru, dengan penanda belum dibaca pada baris yang `read_at`-nya kosong. Halaman SHALL membagi hasil per halaman. Notifikasi milik pengguna lain SHALL NOT ikut tampil maupun terhitung.

#### Scenario: Halaman menampilkan notifikasi sendiri

- **WHEN** pengguna membuka `/notifications` dan ia memiliki tiga notifikasi, satu di antaranya belum dibaca
- **THEN** halaman menampilkan ketiga baris terbaru lebih dulu dan tepat satu baris ditandai belum dibaca

#### Scenario: Notifikasi pengguna lain tidak tampil

- **WHEN** pengguna membuka `/notifications` sementara pengguna lain memiliki notifikasi
- **THEN** halaman hanya memuat notifikasi milik pengguna yang membuka halaman

#### Scenario: Tamu tidak dapat membuka halaman

- **WHEN** pengunjung yang belum masuk membuka `/notifications`
- **THEN** pengguna dialihkan ke halaman masuk

#### Scenario: Halaman tanpa notifikasi

- **WHEN** pengguna membuka `/notifications` dan belum memiliki notifikasi
- **THEN** halaman tetap tampil tanpa galat dan menampilkan keadaan kosong

### Requirement: Membuka satu notifikasi menandainya dibaca lalu mengarahkan ke rujukannya

Membuka satu notifikasi SHALL mengisi `read_at` notifikasi tersebut lalu mengarahkan ke halaman rujukannya. Notifikasi berkaitan kost SHALL mengarahkan ke daftar kost milik Pemilik Kost; notifikasi tanpa rujukan SHALL kembali ke halaman notifikasi. Permintaan menandai notifikasi milik pengguna lain SHALL ditolak dan SHALL NOT mengubah `read_at` notifikasi tersebut.

#### Scenario: Menandai notifikasi sendiri

- **WHEN** pengguna membuka sebuah notifikasi belum dibaca miliknya
- **THEN** `read_at` notifikasi itu terisi dan halaman berpindah ke rujukannya

#### Scenario: Notifikasi kost mengarah ke daftar kost

- **WHEN** pengguna membuka notifikasi yang merujuk sebuah kost
- **THEN** halaman berpindah ke daftar kost miliknya

#### Scenario: Notifikasi tanpa rujukan

- **WHEN** pengguna membuka notifikasi yang tidak merujuk apa pun
- **THEN** `read_at` terisi dan halaman kembali ke daftar notifikasi

#### Scenario: Menandai notifikasi pengguna lain ditolak

- **WHEN** pengguna meminta penandaan atas notifikasi milik pengguna lain
- **THEN** permintaan dijawab dengan galat dan `read_at` notifikasi tersebut tetap kosong

#### Scenario: Notifikasi yang sudah dibaca tidak berubah waktunya

- **WHEN** pengguna membuka notifikasi yang `read_at`-nya sudah terisi
- **THEN** `read_at` yang lama dipertahankan dan halaman tetap berpindah ke rujukannya

### Requirement: Halaman notifikasi menyediakan penandaan semua notifikasi dibaca

Halaman notifikasi SHALL menyediakan satu kendali yang menandai seluruh notifikasi belum dibaca milik pengguna yang sedang masuk sebagai dibaca, dan SHALL NOT menyentuh notifikasi milik pengguna lain.

#### Scenario: Tandai semua dibaca

- **WHEN** pengguna menekan kendali "Tandai semua dibaca" sementara ia memiliki lima notifikasi belum dibaca
- **THEN** kelima notifikasi tersebut terisi `read_at` dan penanda belum dibaca hilang dari daftar

#### Scenario: Penandaan menyeluruh tidak menyentuh pengguna lain

- **WHEN** pengguna menekan kendali "Tandai semua dibaca"
- **THEN** notifikasi belum dibaca milik pengguna lain tetap bernilai kosong pada `read_at`

#### Scenario: Tandai semua tanpa notifikasi belum dibaca

- **WHEN** pengguna menekan kendali "Tandai semua dibaca" sementara tidak ada notifikasi belum dibaca miliknya
- **THEN** permintaan dijawab tanpa galat dan daftar tidak berubah

### Requirement: Lonceng header menampilkan jumlah notifikasi belum dibaca

Header aplikasi SHALL menampilkan lonceng berisi jumlah notifikasi belum dibaca milik pengguna yang sedang masuk, beserta daftar beberapa notifikasi terbaru dan tautan menuju `/notifications`. Jumlah tersebut SHALL turun setelah notifikasi dibaca. Pengguna tanpa notifikasi belum dibaca SHALL melihat lonceng tanpa angka.

#### Scenario: Jumlah belum dibaca tampil

- **WHEN** Pemilik Kost membuka halaman yang memakai header aplikasi dan memiliki dua notifikasi belum dibaca
- **THEN** lonceng menampilkan angka dua

#### Scenario: Jumlah turun setelah dibaca

- **WHEN** pengguna menandai satu dari dua notifikasi belum dibaca sebagai dibaca
- **THEN** lonceng menampilkan angka satu

#### Scenario: Tanpa notifikasi belum dibaca

- **WHEN** pengguna dengan seluruh notifikasi sudah dibaca membuka halaman berheader
- **THEN** lonceng tampil tanpa angka penanda

#### Scenario: Daftar singkat dan tautan halaman penuh

- **WHEN** pengguna membuka lonceng yang memuat lebih dari lima notifikasi terbaru
- **THEN** daftar singkat memuat lima notifikasi terbaru dan tersedia tautan menuju `/notifications`

### Requirement: Notifikasi baru dimunculkan sebagai toast selama aplikasi terbuka

Selama halaman dashboard terbuka, sistem SHALL memeriksa notifikasi belum dibaca secara berkala dan memunculkan toast untuk setiap notifikasi yang belum pernah ditampilkan pada sesi tersebut. Pemeriksaan SHALL berhenti saat tab tidak terlihat dan SHALL berjalan lagi saat tab kembali terlihat. Notifikasi yang sudah ditampilkan pada sesi yang sama SHALL NOT dimunculkan ulang setelah halaman dimuat ulang.

#### Scenario: Notifikasi baru memunculkan toast

- **WHEN** sebuah notifikasi baru ditulis bagi pengguna yang sedang membuka dashboard
- **THEN** pada pemeriksaan berikutnya toast berisi pesan notifikasi tersebut muncul

#### Scenario: Toast tidak berulang

- **WHEN** halaman dimuat ulang setelah sebuah notifikasi sudah ditampilkan sebagai toast pada sesi yang sama
- **THEN** notifikasi tersebut tidak lagi dimunculkan sebagai toast

#### Scenario: Pemeriksaan berhenti saat tab tersembunyi

- **WHEN** tab dashboard tidak terlihat
- **THEN** tidak ada pemeriksaan notifikasi baru sampai tab kembali terlihat

#### Scenario: Pemeriksaan berjalan lagi saat tab kembali terlihat

- **WHEN** tab dashboard kembali terlihat setelah beberapa saat tersembunyi
- **THEN** pemeriksaan notifikasi berjalan lagi tanpa menunggu satu interval penuh

### Requirement: Menu pengguna menyediakan jalan menuju halaman notifikasi

Menu pengguna pada header SHALL memuat item "Notifikasi" yang mengarah ke `/notifications`, berdampingan dengan item pengaturan dan keluar yang sudah ada, dan hanya tampil bagi pengguna yang sudah masuk.

#### Scenario: Item notifikasi tersedia

- **WHEN** Pemilik Kost atau Admin membuka menu pengguna
- **THEN** menu memuat item "Notifikasi" yang menuju `/notifications`

#### Scenario: Item hanya untuk pengguna masuk

- **WHEN** menu pengguna tidak ditampilkan karena tidak ada pengguna yang masuk
- **THEN** item "Notifikasi" tidak dirender
