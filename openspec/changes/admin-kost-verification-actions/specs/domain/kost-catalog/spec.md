# Spec Delta

## ADDED Requirements

### Requirement: Status verifikasi kost hanya berpindah lewat jalur yang sah

Perubahan `kosts.status` SHALL hanya terjadi lewat transisi berikut: `menunggu_verifikasi` menjadi `disetujui`, `menunggu_verifikasi` menjadi `ditolak`, dan `disetujui` menjadi `ditangguhkan`. Setiap transisi SHALL mencatat `reviewed_by` dan `reviewed_at`. Transisi lain, termasuk mengembalikan `ditangguhkan` menjadi aktif oleh Pemilik Kost, SHALL ditolak.

#### Scenario: Pengajuan baru menuju disetujui

- **WHEN** Admin menyetujui kost berstatus `menunggu_verifikasi`
- **THEN** status berubah menjadi `disetujui` dan `reviewed_by` serta `reviewed_at` terisi

#### Scenario: Pengajuan baru menuju ditolak

- **WHEN** Admin menolak kost berstatus `menunggu_verifikasi` dengan alasan
- **THEN** status berubah menjadi `ditolak`, `status_reason` terisi, dan `reviewed_by` serta `reviewed_at` terisi

#### Scenario: Kost disetujui menuju ditangguhkan

- **WHEN** Admin menangguhkan kost berstatus `disetujui` dengan alasan
- **THEN** status berubah menjadi `ditangguhkan`, `status_reason` terisi, dan `reviewed_by` serta `reviewed_at` terisi

#### Scenario: Transisi di luar jalur ditolak

- **WHEN** permintaan mengubah status kost dari `ditolak`, `ditangguhkan`, atau `nonaktif` menjadi `disetujui`
- **THEN** permintaan ditolak dan kolom status tidak berubah

#### Scenario: Kost ditangguhkan tidak dapat diaktifkan pemiliknya

- **WHEN** Pemilik Kost mencoba mengaktifkan kembali kost berstatus `ditangguhkan` miliknya
- **THEN** permintaan ditolak dan status kost tetap `ditangguhkan`

### Requirement: Persetujuan membersihkan alasan keputusan sebelumnya

Kost yang disetujui SHALL tidak menyimpan alasan penolakan atau penangguhan lama; `status_reason` SHALL bernilai kosong setelah persetujuan berhasil.

#### Scenario: Alasan lama hilang setelah disetujui

- **WHEN** kost yang pernah ditolak dengan alasan kemudian disetujui Admin
- **THEN** `status_reason` pada kost tersebut kosong dan statusnya `disetujui`