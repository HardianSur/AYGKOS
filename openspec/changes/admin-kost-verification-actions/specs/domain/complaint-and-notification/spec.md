# Spec Delta

## ADDED Requirements

### Requirement: Keputusan verifikasi kost menghasilkan notifikasi bagi Pemilik Kost

Ketika Admin menyetujui, menolak, atau menangguhkan sebuah kost, sistem SHALL menulis satu baris `notifications` bagi Pemilik Kost yang memiliki kost tersebut. Baris itu SHALL memuat jenis yang menandai keputusannya, pesan yang menyebut nama kost dan keputusannya, rujukan jenis `kost` beserta identitas kost, serta `read_at` kosong.

#### Scenario: Notifikasi persetujuan tercatat

- **WHEN** Admin menyetujui sebuah kost
- **THEN** satu baris notifikasi baru tercatat bagi pemilik kost tersebut dengan jenis yang menandai persetujuan

#### Scenario: Notifikasi penolakan menyebut alasan

- **WHEN** Admin menolak sebuah kost dengan alasan
- **THEN** satu baris notifikasi baru tercatat bagi pemilik kost tersebut dan pesannya menyertakan alasan penolakan

#### Scenario: Notifikasi penangguhan tercatat

- **WHEN** Admin menangguhkan sebuah kost dengan alasan
- **THEN** satu baris notifikasi baru tercatat bagi pemilik kost tersebut dan pesannya menyertakan alasan penangguhan

#### Scenario: Notifikasi belum dibaca saat dibuat

- **WHEN** notifikasi keputusan verifikasi baru tercatat
- **THEN** `read_at` pada baris tersebut kosong

#### Scenario: Notifikasi merujuk kost tanpa foreign key

- **WHEN** notifikasi keputusan verifikasi tercatat lalu kostnya dihapus
- **THEN** baris notifikasi tetap tersimpan dan `reference_type` maupun `reference_id` tidak berubah

#### Scenario: Keputusan yang gagal tidak menulis notifikasi

- **WHEN** permintaan keputusan verifikasi ditolak karena status kost sudah berubah atau alasannya tidak sah
- **THEN** tidak ada baris notifikasi baru yang tercatat

### Requirement: Notifikasi keputusan verifikasi ditulis bersama perubahan status

Penulisan notifikasi keputusan verifikasi SHALL menjadi bagian dari perubahan status kost yang sama: bila salah satunya gagal, keduanya SHALL NOT tersimpan.

#### Scenario: Kegagalan menulis notifikasi membatalkan perubahan status

- **WHEN** penulisan baris notifikasi gagal saat keputusan verifikasi diproses
- **THEN** status kost kembali seperti sebelum permintaan dan tidak ada notifikasi yang tersimpan

#### Scenario: Keputusan berhasil menyimpan keduanya

- **WHEN** keputusan verifikasi berhasil diproses
- **THEN** perubahan status kost dan baris notifikasinya sama-sama tersimpan