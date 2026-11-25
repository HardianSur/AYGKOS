# Spec Delta

## ADDED Requirements

### Requirement: Admin memiliki halaman dashboard di area Admin

Sistem SHALL menyediakan halaman `/admin/dashboard` yang hanya dapat dibuka Admin terverifikasi, dan halaman itu SHALL menyajikan dashboard aplikasi yang sama dengan yang dilihat Pemilik Kost. Dashboard Admin terpisah dengan statistik sendiri SHALL NOT dibuat selama FR-ADM-DSB belum dibangun.

#### Scenario: Admin membuka dashboard Admin

- **WHEN** akun Admin terverifikasi membuka `/admin/dashboard`
- **THEN** dashboard aplikasi yang sama dengan dashboard Pemilik Kost ditampilkan

#### Scenario: Peran lain ditolak di area Admin

- **WHEN** akun Customer atau Pemilik Kost membuka `/admin/dashboard`
- **THEN** permintaan ditolak dengan status 403

### Requirement: Navigasi menampilkan menu sesuai peran

Sidebar SHALL menampilkan menu area Admin hanya kepada pengguna berperan Admin, dan SHALL tetap menampilkan menu yang sudah ada untuk peran lain.

#### Scenario: Admin melihat menu Admin

- **WHEN** akun Admin membuka halaman aplikasi
- **THEN** sidebar memuat menu menuju `/admin/dashboard`

#### Scenario: Pemilik Kost tidak melihat menu Admin

- **WHEN** akun Pemilik Kost membuka halaman aplikasi
- **THEN** sidebar tidak memuat menu menuju `/admin/dashboard`
