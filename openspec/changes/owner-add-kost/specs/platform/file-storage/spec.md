# Spec Delta

## ADDED Requirements

### Requirement: Foto kost dikonversi ke WebP sebelum disimpan

Sistem SHALL mengonversi setiap foto kost yang lolos validasi menjadi WebP dengan sisi terpanjang maksimal 1600 piksel, tanpa memperbesar gambar yang lebih kecil, dan tanpa memotong rasionya. Berkas asli SHALL NOT disimpan. Nilai yang dicatat pada basis data SHALL berupa path pada penyimpanan publik, bukan URL.

#### Scenario: Foto berukuran besar dikonversi

- **WHEN** Pemilik Kost mengunggah foto kost berukuran 4000×3000 piksel
- **THEN** berkas yang tersimpan berformat WebP dengan sisi terpanjang 1600 piksel dan rasionya tetap

#### Scenario: Foto kecil tidak diperbesar

- **WHEN** Pemilik Kost mengunggah foto kost berukuran 800×600 piksel
- **THEN** berkas yang tersimpan berformat WebP dengan ukuran tidak melebihi 800×600 piksel

#### Scenario: Orientasi foto dihormati

- **WHEN** Pemilik Kost mengunggah foto kost yang memuat metadata orientasi kamera
- **THEN** foto tersimpan dengan orientasi tegak sesuai metadata tersebut

#### Scenario: Berkas asli tidak tersimpan

- **WHEN** foto kost berhasil disimpan
- **THEN** hanya berkas WebP hasil konversi yang ada pada penyimpanan, dan kolom basis data memuat path berkas tersebut

### Requirement: Foto profil dan foto kost memakai satu jalur konversi gambar

Konversi gambar untuk foto profil dan foto kost SHALL memakai mekanisme yang sama, dengan perbedaan hanya pada bentuk akhir: foto profil dipotong persegi dan diperkecil ke 400×400, sedangkan foto kost diperkecil proporsional dengan batas 1600 piksel.

#### Scenario: Perilaku foto profil tidak berubah

- **WHEN** pengguna mengganti foto profil
- **THEN** berkas yang tersimpan tetap berformat WebP berukuran 400×400 dan path lama terhapus setelah berkas baru berhasil

#### Scenario: Konversi gagal

- **WHEN** penyimpanan gambar hasil konversi gagal
- **THEN** galat dilaporkan ke pengguna dan tidak ada path yang tercatat pada basis data