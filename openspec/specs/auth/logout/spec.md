# auth/logout Specification

## Purpose

Mengatur konfirmasi sebelum sesi pengguna AYGKOS diakhiri dari menu pengguna, termasuk pembatalan, kelanjutan logout, dan tampilan dialog yang mematuhi `docs/DESIGN.md`.

## Requirements

### Requirement: Menu logout meminta konfirmasi sebelum mengakhiri sesi

Sistem SHALL menampilkan dialog konfirmasi ketika pengguna memilih "Log out" pada menu pengguna, dan SHALL tidak mengakhiri sesi sebelum pengguna mengonfirmasi.

#### Scenario: Item logout dipilih

- **WHEN** pengguna masuk dan memilih item "Log out" pada menu pengguna
- **THEN** dialog konfirmasi tampil dan sesi pengguna tetap aktif

#### Scenario: Dialog ditutup tanpa konfirmasi

- **WHEN** pengguna memilih "Batal", menekan Escape, atau mengklik luar dialog
- **THEN** dialog ditutup, sesi tetap aktif, dan pengguna tetap berada di halaman semula

### Requirement: Konfirmasi mengakhiri sesi dan mengarahkan ke halaman masuk

Sistem SHALL mengakhiri sesi ketika pengguna mengonfirmasi logout, lalu SHALL mengarahkan pengguna ke halaman masuk sesuai FR-AUT-12.

#### Scenario: Pengguna mengonfirmasi logout

- **WHEN** pengguna memilih "Keluar" pada dialog konfirmasi
- **THEN** sistem mengakhiri sesi dan mengarahkan pengguna ke halaman masuk

### Requirement: Dialog logout memakai kosakata bahasa Indonesia

Sistem SHALL menampilkan judul, penjelasan, dan label tombol dialog logout dalam bahasa Indonesia.

#### Scenario: Dialog logout ditampilkan

- **WHEN** dialog konfirmasi logout tampil
- **THEN** judul "Keluar dari akun?", penjelasan singkat, tombol "Batal", dan tombol "Keluar" terbaca dalam bahasa Indonesia

### Requirement: Tampilan dialog logout mengikuti token desain

Sistem SHALL merender dialog logout memakai permukaan, tepi, radius, bayangan, warna teks, warna tombol konfirmasi, dan cincin fokus yang ditetapkan `docs/DESIGN.md` untuk mode terang maupun mode gelap.

#### Scenario: Dialog logout pada mode terang

- **WHEN** preferensi warna pengguna adalah terang dan dialog logout tampil
- **THEN** panel memakai warna Permukaan dengan tepi Garis dan radius 14 px, overlay memakai biru malam, tombol "Keluar" berisian `#B42318` berteks `#FDECEA`, dan cincin fokus memakai Teks utama dengan offset dua piksel

#### Scenario: Dialog logout pada mode gelap

- **WHEN** preferensi warna pengguna adalah gelap dan dialog logout tampil
- **THEN** panel memakai Permukaan mode gelap dengan tepi Garis mode gelap, dan tombol "Keluar" berisian `#FF8A80` berteks `#12152A`
