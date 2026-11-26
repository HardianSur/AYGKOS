# Spec Delta

## ADDED Requirements

### Requirement: Kota diisi dari titik peta pada formulir kost

Sistem SHALL mengisi kolom kota pada formulir pengajuan kost dari hasil reverse geocode titik yang dipilih pada peta, dengan urutan sumber `city`, lalu `county`, lalu `state`. Hasil reverse geocode SHALL NOT mengubah alamat lengkap yang diketik Pemilik Kost.

#### Scenario: Kota terisi setelah titik dipilih

- **WHEN** Pemilik Kost memilih titik pada peta di wilayah Kota Yogyakarta
- **THEN** kolom kota terisi dari hasil reverse geocode titik tersebut

#### Scenario: Alamat tidak ditimpa

- **WHEN** Pemilik Kost sudah mengetik alamat lengkap lalu menggeser titik pada peta
- **THEN** teks alamat lengkap tetap seperti yang diketik dan hanya kolom kota yang diperbarui

#### Scenario: Bagian wilayah tidak lengkap

- **WHEN** hasil reverse geocode tidak memuat `city` namun memuat `county`
- **THEN** kolom kota terisi dari nilai `county`

#### Scenario: Reverse geocode gagal

- **WHEN** permintaan reverse geocode gagal atau kuota penyedia habis
- **THEN** kolom kota tetap dapat diisi manual dan pengajuan tidak terhalang

### Requirement: Kota yang disunting manual tidak ditimpa

Setelah Pemilik Kost menyunting kolom kota secara manual, hasil reverse geocode berikutnya SHALL NOT menimpa nilai tersebut.

#### Scenario: Suntingan manual dipertahankan

- **WHEN** Pemilik Kost menyunting kolom kota lalu menggeser titik pada peta
- **THEN** kolom kota tetap bernilai seperti hasil suntingan

#### Scenario: Pemilik mengosongkan kota

- **WHEN** Pemilik Kost mengosongkan kolom kota setelah sebelumnya terisi otomatis
- **THEN** kolom kota tetap kosong sampai Pemilik Kost mengisinya kembali

### Requirement: Saran lokasi dibatasi pada wilayah Indonesia

Permintaan saran lokasi pada formulir kost SHALL membatasi hasil ke negara Indonesia.

#### Scenario: Saran di luar Indonesia tidak muncul

- **WHEN** Pemilik Kost mengetik nama tempat yang juga ada di negara lain
- **THEN** daftar saran hanya memuat hasil di Indonesia