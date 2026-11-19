# Proposal

## Why

Repositori masih memakai skema bawaan Laravel: `users` dengan `id` bigint dan kolom `name`, belum ada tabel domain apa pun. Seluruh fitur di `docs/FunctionalRequirment/functional_requirment.md` bergantung pada ERD AYGKOS, sehingga tidak satu pun FR dapat dikerjakan sebelum skema dan model tersedia. Sekaligus, keputusan UUID sebagai primary key bertabrakan dengan `phpunit.xml` yang masih memakai SQLite `:memory:`: constraint PostgreSQL yang diminta ERD (check koordinat, check harga) tidak dapat diuji di sana.

## What Changes

- **BREAKING**: primary key seluruh tabel domain menjadi uuid7 (`HasUuids`), termasuk `users.id` dan `sessions.user_id`. Tipe `User::$id` berubah dari `int` menjadi `string` di PHP, TypeScript, dan seluruh validasi profil.
- Menambahkan 10 tabel domain sesuai ERD: `roles`, `users` (diubah), `whatsapp_verifications`, `kosts`, `kost_photos`, `facilities`, `kost_facilities`, `rooms`, `complaints`, `complaint_attachments`, `notifications`.
- Nama tabel domain mengikuti konvensi plural snake_case Laravel, sehingga tidak ada model yang butuh `protected $table` manual.
- Kebijakan timestamp per tabel: hanya `created_at` untuk `whatsapp_verifications` dan `notifications`; tanpa timestamp sama sekali untuk `facilities` dan `kost_photos`; `timestamps()` penuh untuk tabel lain; penghapusan lunak hanya pada `kosts` dan `rooms`.
- `kost_facilities` memakai primary key komposit (`kost_id`, `facility_id`) tanpa kolom `id` dan tanpa model pivot.
- Menambahkan constraint PostgreSQL yang tidak dapat diekspresikan Blueprint: check rentang `latitude`/`longitude` pada `kosts` dan check `price_per_month > 0` pada `rooms`.
- Menambahkan `RoleSeeder` (customer, pemilik_kost, admin) dan mengubah `DatabaseSeeder` agar pengguna contoh memakai peran admin.
- `CreateNewUser` menetapkan `role_id` dari peran `customer`; `UserFactory` membuat peran `customer` bila belum ada; `tests/Pest.php` menyeed peran sebelum setiap tes Feature.
- Memindahkan basis data pengujian ke PostgreSQL: `phpunit.xml` memakai `DB_CONNECTION=pgsql` dan basis data `aygkos_testing`.
- **Tidak berubah**: nama tabel `users`, kolom `users.name`, dan `remember_token` dipertahankan sehingga alur Fortify, halaman profil, dan factory tidak perlu ditulis ulang.
- Dokumen ERD naik ke **V003** (nama tabel plural), V002 dipensiunkan ke `docs/ERD/logs/V002-erd.md`. `docs/FunctionalRequirment/functional_requirment.md` tidak berubah.

## Capabilities

### New Capabilities

- `domain/persistence-conventions`: aturan yang berlaku pada seluruh tabel domain, yaitu identitas uuid7 yang dibangkitkan aplikasi, kebijakan timestamp per tabel, aksi referensial foreign key, penamaan tabel dan kolom, constraint check, penghapusan lunak, serta batas antara tabel domain dan tabel framework.
- `domain/identity-accounts`: peran pengguna, akun yang menyandang tepat satu peran, verifikasi email dan WhatsApp sebagai penentu status terverifikasi, serta kode OTP yang disimpan sebagai hash dengan batas kedaluwarsa dan percobaan.
- `domain/kost-catalog`: data kost, foto, fasilitas, relasi banyak ke banyak kost-fasilitas, dan kamar, termasuk syarat kost tampil kepada publik dan penghapusan lunak kost beserta kamarnya.
- `domain/complaint-and-notification`: aduan pengguna dengan lampiran dan penanganan Admin, serta notifikasi in-app dengan penanda sudah dibaca dan rujukan polimorfik ke kost atau aduan.

### Modified Capabilities

Tidak ada. `openspec/specs/` masih kosong, sehingga seluruh capability di atas dibuat baru.

## Impact

- **Migrasi**: 10 berkas migration baru di `database/migrations/` dan satu berkas lama `0001_01_01_000000_create_users_table.php` disunting di tempat (`users` menjadi uuid, `sessions.user_id` menjadi `foreignUuid`). Tidak ada data nyata; basis data `aygkos` dan `aygkos_testing` masih kosong.
- **Model**: `app/Models/` bertambah 9 model (`Role`, `WhatsappVerification`, `Kost`, `KostPhoto`, `Facility`, `Room`, `Complaint`, `ComplaintAttachment`, `Notification`); tidak ada model untuk `kost_facilities`. `User` diubah (uuid, relasi, casting).
- **Seeder**: `database/seeders/RoleSeeder.php` baru, `database/seeders/DatabaseSeeder.php` diubah.
- **Auth**: `app/Actions/Fortify/CreateNewUser.php` (penetapan peran), `app/Concerns/ProfileValidationRules.php` (parameter `?int` menjadi `?string`).
- **Frontend**: `resources/js/types/auth.ts` (`id: number` menjadi `string`).
- **Tes**: `phpunit.xml` (`DB_CONNECTION=pgsql`, `DB_DATABASE=aygkos_testing`), `tests/Pest.php` (seed peran), `tests/Feature/Schema/DomainSchemaTest.php` baru.
- **Dokumentasi**: `docs/ERD/erd.md` menjadi V003, `docs/ERD/logs/V002-erd.md`, satu baris baru di `docs/ERD/release.md`.
- **Dependensi**: tidak ada dependensi baru. `HasUuids` tersedia di Laravel 13.
- **Di luar cakupan**: controller, route, halaman Inertia, Form Request domain, kebijakan otorisasi, dan logika bisnis tiap FR. Change ini hanya menyediakan skema, model, relasi, dan seeder peran.
- **Risiko**: `users.role_id` NOT NULL membuat setiap `User::factory()` dan registrasi gagal bila peran belum ada. Karena itu seeding peran masuk ke change ini, bukan change FR-AUT.