# Spec Delta

## MODIFIED Requirements

### Requirement: Tipe kamar menyimpan nama, harga, dan ketersediaan

Setiap baris `room_types` SHALL menyimpan `kost_id`, `name` unik dalam satu kost di antara tipe kamar yang belum dihapus, `price_per_month` dalam rupiah, `total_rooms`, `available_rooms`, `notes` yang boleh kosong, dan `status`. `status` SHALL berupa `aktif` atau `nonaktif` dengan nilai awal `nonaktif`. `available_rooms` SHALL NOT melebihi `total_rooms`, dan `total_rooms` SHALL lebih besar dari nol. Tipe kamar yang sudah dihapus secara lunak SHALL NOT menahan pemakaian namanya kembali.

#### Scenario: Tipe kamar baru berstatus nonaktif

- **WHEN** sebuah tipe kamar dibuat tanpa menyebut status
- **THEN** `status` bernilai `nonaktif`

#### Scenario: Nama tipe kamar boleh sama pada kost berbeda

- **WHEN** dua kost berbeda masing-masing memiliki tipe kamar bernama "AC"
- **THEN** kedua baris tersimpan tanpa melanggar aturan unik

#### Scenario: Nama tipe kamar terhapus dapat dipakai ulang

- **WHEN** tipe kamar bernama "AC" pada sebuah kost dihapus secara lunak lalu dibuat tipe kamar baru bernama "AC" pada kost yang sama
- **THEN** tipe kamar baru tersimpan tanpa melanggar aturan unik

#### Scenario: Ketersediaan melebihi jumlah kamar ditolak

- **WHEN** sebuah tipe kamar disimpan dengan `available_rooms` lebih besar dari `total_rooms`
- **THEN** basis data menolak penyisipan

#### Scenario: Harga tidak valid ditolak

- **WHEN** sebuah tipe kamar disimpan dengan `price_per_month` nol atau negatif
- **THEN** basis data menolak penyisipan