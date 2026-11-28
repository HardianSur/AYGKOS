# design-tokens Specification

## Purpose

Menetapkan nilai token warna, batas kontras, dan aturan elevasi yang wajib dipakai semua halaman AYGKOS, sehingga mode terang dan gelap tetap satu sistem dan tidak ada warna yang ditulis ad-hoc per komponen.

## Requirements

### Requirement: Token warna adalah satu sumber

Setiap warna antarmuka MUST berasal dari token di `resources/css/app.css`. Komponen MUST NOT menulis nilai warna literal (`bg-[#...]`, `text-[#...]`, `style={{ color: ... }}`) untuk warna yang sudah punya token.

#### Scenario: Halaman baru memakai token

- **WHEN** halaman baru menampilkan badge status, tombol, atau kartu
- **THEN** kelas yang dipakai merujuk token (`bg-surface`, `text-status-approved`, `border-hairline`), bukan nilai hex

#### Scenario: Warna literal ditolak saat review

- **WHEN** sebuah komponen memuat warna hex untuk peran yang sudah punya token
- **THEN** temuan review menunjuk token pengganti dan komponen diperbaiki sebelum merge

### Requirement: Batas kontras teks dua arah

Teks normal MUST mencapai kontras minimal `4,5 : 1` terhadap latarnya, teks besar minimal `3 : 1`. Pada mode gelap, teks isi MUST NOT melebihi `12 : 1` terhadap Permukaan, agar teks tidak menyilaukan.

#### Scenario: Teks isi mode gelap

- **WHEN** teks isi ditampilkan di atas Permukaan gelap
- **THEN** rasio berada di antara `4,5 : 1` dan `12 : 1`

#### Scenario: Teks di atas jingga utama

- **WHEN** teks diletakkan di atas isian Jingga utama
- **THEN** teks memakai warna Latar mode aktif (`#1B2038` pada mode terang, `#101317` pada mode gelap), bukan Teks utama atau putih

### Requirement: Pemisahan permukaan mode gelap terukur

Mode gelap MUST membedakan Latar, Permukaan, dan Permukaan terangkat melalui luminance, bukan bayangan. Permukaan terhadap Latar MUST minimal `1,24 : 1`, Permukaan terangkat terhadap Permukaan minimal `1,16 : 1`, dan Permukaan terangkat terhadap Latar minimal `1,44 : 1`.

#### Scenario: Kartu di atas latar gelap

- **WHEN** kartu dirender pada mode gelap
- **THEN** rasio Permukaan terhadap Latar minimal `1,24 : 1` dan tepi kartu memakai Garis

#### Scenario: Popover di atas kartu

- **WHEN** dropdown atau popover terbuka di atas kartu pada mode gelap
- **THEN** latarnya memakai Permukaan terangkat dengan rasio minimal `1,16 : 1` terhadap Permukaan, tanpa bayangan

### Requirement: Warna status adalah token

Warna status (tersedia, menunggu verifikasi, ditolak, nonaktif) MUST tersedia sebagai token CSS untuk teks dan latar chip. Pada mode gelap, latar chip MUST minimal `1,2 : 1` terhadap Permukaan agar tidak menyatu dengan kartu, dan teks status MUST minimal `4,5 : 1` terhadap latar chip-nya. Status MUST tetap dibedakan oleh label teks, bukan warna saja.

#### Scenario: Chip status di dalam kartu mode gelap

- **WHEN** chip status ditampilkan di dalam kartu pada mode gelap
- **THEN** latar chip terpisah minimal `1,2 : 1` dari Permukaan dan teksnya terbaca minimal `4,5 : 1`

#### Scenario: Status tanpa warna

- **WHEN** pengguna tidak dapat membedakan warna
- **THEN** label teks status tetap menyampaikan keadaan yang sama

### Requirement: Palet chart mengikuti palet merek

Token `--chart-1` sampai `--chart-5` MUST berupa turunan jingga, kuning, dan netral dari palet merek. Warna di luar palet merek, termasuk ungu dan pink bawaan, MUST NOT dipakai.

#### Scenario: Grafik baru dibuat

- **WHEN** sebuah halaman menampilkan grafik
- **THEN** semua seri memakai token chart yang berasal dari palet merek dan setiap seri mencapai minimal `3 : 1` terhadap latar grafik

#### Scenario: Seri tanpa warna

- **WHEN** pengguna tidak dapat membedakan warna seri
- **THEN** setiap seri tetap memiliki label langsung atau tabel nilai pengganti

### Requirement: Satu latar gelap per halaman

Token komponen pihak ketiga (shadcn) pada mode gelap MUST dipetakan ke token merek, sehingga `body`, kartu, dan popover memakai keluarga warna yang sama. Dua latar gelap berbeda dalam satu halaman MUST NOT terjadi.

#### Scenario: Halaman tanpa kartu

- **WHEN** halaman hanya berisi teks di atas latar
- **THEN** latarnya sama dengan Latar merek, bukan abu netral bawaan

#### Scenario: Kartu di atas latar

- **WHEN** kartu dirender
- **THEN** warna Latar di belakangnya identik dengan Latar merek

### Requirement: Aturan elevasi per mode

Mode terang MUST memakai tepi Garis untuk memisahkan kartu dan satu tingkat bayangan hanya untuk elemen melayang. Mode gelap MUST memakai tingkat permukaan dan Garis, tanpa bayangan. Warna bayangan mode terang MUST memakai nilai Latar mode terang yang berlaku.

#### Scenario: Dropdown pada mode terang

- **WHEN** dropdown terbuka pada mode terang
- **THEN** elemen memakai satu tingkat bayangan yang didefinisikan dan tepi Garis

#### Scenario: Dropdown pada mode gelap

- **WHEN** dropdown terbuka pada mode gelap
- **THEN** elemen memakai Permukaan terangkat dan Garis, tanpa bayangan

### Requirement: Overlay memakai token scrim

Latar gelap di belakang dialog dan sheet MUST memakai token scrim tersendiri, bukan warna Latar yang diberi opasitas langsung di komponen.

#### Scenario: Dialog dibuka

- **WHEN** dialog atau sheet dibuka
- **THEN** latar belakangnya memakai token scrim, tanpa warna atau opasitas ad-hoc di komponen
