# Spec Delta

## Purpose

Menyimpan aduan pengguna beserta lampiran dan tanggapan Admin, serta notifikasi in-app yang memberi tahu pengguna atas perubahan status kost dan aduan, sehingga riwayat penanganan dapat ditelusuri kembali.

## ADDED Requirements

### Requirement: Aduan menyimpan pengadu, kategori, isi, dan status penanganan

Setiap baris `complaints` SHALL menyimpan pengadu, kategori, judul, deskripsi, dan status dengan nilai awal `baru`. Kategori SHALL berupa salah satu dari `laporan_kost`, `laporan_pengguna`, `kendala_sistem`, atau `lainnya`. Status SHALL berupa salah satu dari `baru`, `diproses`, atau `selesai`.

#### Scenario: Aduan baru berstatus baru

- **WHEN** sebuah aduan dibuat tanpa menyebut status
- **THEN** `status` bernilai `baru`

#### Scenario: Aduan boleh merujuk satu kost

- **WHEN** aduan berkategori `laporan_kost` dibuat dengan menyebut kost yang dilaporkan
- **THEN** `kost_id` terisi dan merujuk baris `kosts` yang ada

#### Scenario: Aduan non-kost tidak wajib menyebut kost

- **WHEN** aduan berkategori `kendala_sistem` dibuat tanpa menyebut kost
- **THEN** `kost_id` bernilai kosong dan aduan tetap tersimpan

### Requirement: Aduan menyimpan tanggapan Admin beserta waktunya

Ketika Admin menanggapi aduan, sistem SHALL menyimpan teks tanggapan, Admin penanggung jawab, dan waktu tanggapan, serta SHALL mengubah status aduan sesuai keputusan Admin.

#### Scenario: Tanggapan Admin tercatat lengkap

- **WHEN** Admin menulis tanggapan atas sebuah aduan
- **THEN** `admin_response`, `handled_by`, dan `responded_at` terisi

#### Scenario: Penghapusan akun Admin tidak menghapus aduan

- **WHEN** akun Admin yang pernah menangani aduan dihapus
- **THEN** `handled_by` pada aduan tersebut menjadi kosong sementara baris aduan dan tanggapannya tetap tersimpan

### Requirement: Aduan memiliki lampiran pendukung maksimal tiga berkas

Setiap lampiran aduan SHALL berupa satu baris `complaint_attachments` yang menyimpan path berkas privat dan nama berkas asli. Satu aduan SHALL memiliki paling banyak tiga lampiran. Lampiran SHALL hanya dapat diakses oleh pengadu dan Admin yang berwenang.

#### Scenario: Aduan dengan tiga lampiran tersimpan

- **WHEN** sebuah aduan dibuat dengan tiga berkas pendukung
- **THEN** tersimpan tiga baris `complaint_attachments` yang menunjuk aduan tersebut

#### Scenario: Lampiran melebihi batas ditolak

- **WHEN** sebuah aduan mencoba melampirkan berkas keempat
- **THEN** berkas keempat ditolak dan tiga lampiran sebelumnya tetap tersimpan

#### Scenario: Menghapus aduan menghapus lampirannya

- **WHEN** sebuah aduan dihapus
- **THEN** seluruh baris `complaint_attachments` miliknya ikut terhapus

### Requirement: Notifikasi in-app menyimpan penerima, isi, dan status baca

Setiap baris `notifications` SHALL menyimpan penerima, jenis notifikasi, pesan, dan waktu baca yang boleh kosong. Notifikasi SHALL dianggap belum dibaca selama `read_at` kosong. Jenis notifikasi SHALL menandai peristiwa yang mendasarinya.

#### Scenario: Notifikasi baru belum dibaca

- **WHEN** sebuah notifikasi dibuat
- **THEN** `read_at` bernilai kosong dan notifikasi tersebut terhitung belum dibaca

#### Scenario: Notifikasi ditandai dibaca

- **WHEN** penerima membuka notifikasi
- **THEN** `read_at` terisi dan notifikasi tersebut tidak lagi terhitung belum dibaca

### Requirement: Notifikasi dapat merujuk kost atau aduan tanpa foreign key

Notifikasi SHALL menyimpan jenis rujukan dan identitas rujukan secara polimorfik, tanpa foreign key ke tabel yang dirujuk. Rujukan SHALL boleh kosong. Penghapusan baris yang dirujuk SHALL NOT menghapus notifikasi maupun menggagalkan penghapusan tersebut.

#### Scenario: Notifikasi merujuk kost yang dihapus tetap ada

- **WHEN** kost yang dirujuk sebuah notifikasi dihapus
- **THEN** baris notifikasi tetap tersimpan dengan `reference_type` dan `reference_id` tidak berubah

#### Scenario: Penghapusan akun penerima menghapus notifikasinya

- **WHEN** akun penerima dihapus
- **THEN** seluruh baris `notifications` miliknya ikut terhapus

### Requirement: Daftar notifikasi belum dibaca dapat diambil per penerima

Sistem SHALL menyediakan cara mengambil notifikasi satu penerima yang belum dibaca, terurut dari yang terbaru, memakai indeks yang menunjang penyaringan penerima dan status baca.

#### Scenario: Hanya notifikasi belum dibaca yang terambil

- **WHEN** notifikasi belum dibaca milik seorang pengguna diminta
- **THEN** hanya baris dengan `read_at` kosong milik pengguna tersebut yang terambil