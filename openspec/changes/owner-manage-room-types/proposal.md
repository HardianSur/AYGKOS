# Proposal

## Why

Tipe kamar adalah unit yang diiklankan (`domain/kost-catalog` menyebut kartu pencarian, harga mulai, dan jumlah kamar tersedia diturunkan dari tipe kamar aktif), tetapi belum ada satu pun jalur bagi Pemilik Kost untuk membuatnya. Pemilik saat ini hanya dapat mengajukan kost kosong: `room_types` tidak pernah terisi, sehingga kost yang disetujui pun tidak akan tampil kepada publik karena spec mensyaratkan minimal satu tipe kamar aktif. FR-KOS-06, FR-KOS-07, dan FR-KOS-08 belum terpenuhi sama sekali, dan halaman `GET /owner/kosts/{kost}/types` yang sudah direncanakan di ARCHITECTURE belum ada.

Fitur verifikasi Admin juga belum selesai, sehingga tidak ada satu pun kost berstatus `disetujui` di basis data pengembangan. Tanpa data itu, CRUD tipe kamar tidak dapat diuji secara manual pada kondisi yang sebenarnya dan alur publik tidak dapat diperiksa. Karena itu change ini menyertakan seeder kost terverifikasi.

## What Changes

- Halaman `GET /owner/kosts/{kost}/types` baru: daftar tipe kamar milik satu kost beserta status, harga, kamar tersedia, dan tombol ubah/hapus.
- Form tambah dan ubah tipe kamar memuat: nama, harga sewa per bulan, jumlah kamar, kamar tersedia, catatan, fasilitas kamar dari master ber-`scope` `kamar`, peraturan khusus dari master ber-`scope` `kamar`, nilai spesifikasi dengan editor mengikuti `input_type` (`number`, `boolean`, `text`), dan maksimal 10 foto berkategori `kamar` atau `kamar_mandi`.
- Tipe kamar baru selalu berstatus `nonaktif`; aktif dan nonaktif diubah lewat aksi terpisah pada daftar dan tidak dibaca dari masukan pembuatan.
- Hapus tipe kamar memakai penghapusan lunak, menghapus berkas fotonya dari disk, dan membebaskan namanya untuk dipakai ulang.
- **BREAKING (skema)**: indeks unik `room_types (kost_id, name)` diganti indeks unik parsial `WHERE deleted_at IS NULL`. Tanpa itu, nama tipe kamar yang sudah dihapus lunak tidak dapat dipakai lagi.
- `RoomTypePolicy` baru menegakkan rantai kepemilikan `room_type.kost.owner_id`, ditambah tiga Action `CreateRoomType`, `UpdateRoomType`, dan `DeleteRoomType`.
- Seeder `DemoKostSeeder` membuat satu kost berstatus `disetujui` milik `pemilik@example.com` (dengan `reviewed_by` dan `reviewed_at` terisi) beserta dua tipe kamar — satu `aktif` dan satu `nonaktif` — lengkap dengan fasilitas, peraturan, dan nilai spesifikasi.

## Capabilities

### New Capabilities

- `owner/room-type-management`: pengelolaan tipe kamar oleh Pemilik Kost — pembuatan dengan status awal `nonaktif`, perubahan data beserta fasilitas/peraturan/spesifikasi/foto, pengaktifan dan penonaktifan, penghapusan lunak, serta batas kepemilikan yang ditolak di server.

### Modified Capabilities

- `domain/kost-catalog`: aturan keunikan nama tipe kamar dalam satu kost dibatasi pada baris yang belum dihapus lunak, sehingga nama tipe kamar yang sudah dihapus dapat dipakai ulang. Requirement lain pada capability ini tidak berubah.

## Impact

- **Dokumen**: perubahan skema indeks unik menaikkan `docs/ERD/erd.md` ke **V006** (baris constraint `room_types`), disertai pemensiunan V005 ke `logs/V005-erd.md` dan satu baris riwayat di `docs/ERD/release.md`. FR tidak berubah — FR-KOS-06/07/08 sudah ada — sehingga `docs/FunctionalRequirment/functional_requirment.md` tetap **V002**.
- **Skema**: satu migrasi baru `replace_room_types_unique_name_with_partial_index`.
- **Backend baru**: `app/Http/Controllers/Owner/RoomTypeController.php`; `app/Http/Requests/RoomType/StoreRoomTypeRequest.php` dan `UpdateRoomTypeRequest.php`; `app/Actions/RoomType/{CreateRoomType,UpdateRoomType,DeleteRoomType}.php`; `app/Policies/RoomTypePolicy.php`.
- **Backend berubah**: `routes/web.php` (empat route di grup `role:pemilik_kost`) dan `database/seeders/DatabaseSeeder.php`. `RoomTypePolicy` ditemukan otomatis oleh konvensi nama Laravel seperti `KostPolicy`, jadi `AppServiceProvider` tidak disentuh.
- **Frontend baru**: `resources/js/pages/owner/kosts/types.tsx`, komponen `resources/js/components/owner/room-type-form/*`, dan tautan dari kartu kost di `resources/js/pages/owner/kosts/index.tsx`.
- **Tes**: `tests/Feature/Owner/RoomTypeManagementTest.php` dan `tests/Feature/Schema/RoomTypeUniquenessTest.php`.
- **Di luar cakupan**: FR-KOS-03/04/05 (ubah, nonaktifkan, hapus, ajukan ulang kost), halaman detail publik, verifikasi Admin, serta master spesifikasi Admin (FR-ADM-MST-03) yang masih memakai seeder.