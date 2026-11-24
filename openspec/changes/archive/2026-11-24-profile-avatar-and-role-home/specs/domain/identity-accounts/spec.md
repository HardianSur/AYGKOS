# Spec Delta

## MODIFIED Requirements

### Requirement: Akun menyimpan identitas, kontak, dan status aktif

Setiap akun SHALL menyimpan nama lengkap, email unik, password ter-hash, dan status aktif dengan nilai awal aktif. Nomor WhatsApp SHALL kosong untuk Customer dan Admin, dan SHALL terisi untuk Pemilik Kost. Setiap akun SHALL boleh menyimpan path foto profil, yang kosong selama pengguna belum mengunggah foto dan SHALL NOT berisi URL. Akun yang dinonaktifkan SHALL tetap tersimpan dan tetap dapat ditunjuk baris lain sebagai pemilik atau penanggung jawab.

#### Scenario: Akun baru aktif secara bawaan

- **WHEN** sebuah akun dibuat tanpa menyebut status aktif
- **THEN** `is_active` bernilai benar

#### Scenario: Akun baru belum memiliki foto profil

- **WHEN** sebuah akun dibuat tanpa menyertakan foto profil
- **THEN** `avatar_path` bernilai kosong dan akun tetap tersimpan

#### Scenario: Dua akun tidak boleh memakai email yang sama

- **WHEN** sebuah akun disimpan dengan email yang sudah dipakai akun lain
- **THEN** basis data menolak penyisipan

#### Scenario: Foto profil disimpan sebagai path

- **WHEN** sebuah akun menyimpan foto profil
- **THEN** `avatar_path` berisi path relatif pada penyimpanan, bukan URL lengkap

#### Scenario: Akun dinonaktifkan tetap tersimpan

- **WHEN** sebuah akun dinonaktifkan
- **THEN** baris akun tetap ada dengan `is_active` bernilai salah dan seluruh baris `kosts` miliknya tetap merujuk akun tersebut