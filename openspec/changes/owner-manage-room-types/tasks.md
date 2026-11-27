# Tasks

## 1. Skema

- [ ] 1.1 Buat migrasi `replace_room_types_unique_name_with_partial_index` yang men-drop constraint `room_types_kost_id_name_unique` dan membuat unique index parsial `room_types (kost_id, name) where deleted_at is null`, lalu jalankan `php artisan migrate:fresh --seed` dan pastikan selesai tanpa galat
- [ ] 1.2 Tulis `tests/Feature/Schema/RoomTypeUniquenessTest.php`: tipe kamar terhapus lunak membebaskan namanya untuk dipakai ulang, sementara nama duplikat pada baris hidup tetap ditolak basis data — jalankan `php artisan test --filter=RoomTypeUniqueness`
- [ ] 1.3 Perbarui dokumen ERD: pindahkan V005 ke `docs/ERD/logs/V005-erd.md`, naikkan `docs/ERD/erd.md` ke V006 dengan keterangan `name` tipe kamar unik per kost di antara baris belum dihapus, dan tambahkan satu baris riwayat di `docs/ERD/release.md`

## 2. Otorisasi

- [ ] 2.1 Buat `app/Policies/RoomTypePolicy.php` dengan `viewAny`/`create` (menerima `Kost`) dan `update`/`delete`/`changeStatus` (menerima `RoomType`) yang memeriksa akun aktif, peran `pemilik_kost`, identitas terverifikasi, dan kepemilikan lewat `kost.owner_id`
- [ ] 2.2 Tulis tes policy di `tests/Feature/Owner/RoomTypePolicyTest.php`: pemilik sah diizinkan, pemilik lain ditolak, customer ditolak, akun nonaktif ditolak, akun belum terverifikasi ditolak — jalankan `php artisan test --filter=RoomTypePolicyTest`

## 3. Action

- [ ] 3.1 Buat `app/Actions/RoomType/CreateRoomType.php`: tulis foto lebih dulu, lalu dalam satu transaksi buat tipe kamar berstatus `nonaktif`, `sync` fasilitas dan peraturan, tulis nilai spesifikasi, dan buat baris `photos` dengan foto pertama bertanda utama; bersihkan berkas tertulis bila transaksi gagal
- [ ] 3.2 Buat `app/Actions/RoomType/UpdateRoomType.php`: perbarui kolom, `sync` pivot fasilitas/peraturan, `sync` nilai spesifikasi, tambah foto baru, hapus foto yang dilepas beserta berkasnya, dan tetapkan foto utama setelah melepas penanda lain; bersihkan berkas baru bila transaksi gagal
- [ ] 3.3 Buat `app/Actions/RoomType/UpdateRoomTypeStatus.php` yang hanya menulis kolom `status` dengan nilai `aktif` atau `nonaktif` dan menolak nilai lain
- [ ] 3.4 Buat `app/Actions/RoomType/DeleteRoomType.php` yang menghapus berkas seluruh foto lalu melakukan `delete()` (soft delete) pada tipe kamar, tanpa menghapus baris `photos`
- [ ] 3.5 Uji Action lewat tes feature CRUD (grup 6): pastikan pembuatan menghasilkan status `nonaktif`, pivot yang dilepas benar-benar hilang, dan berkas foto terhapus dari disk — diverifikasi pada `tests/Feature/Owner/RoomTypeManagementTest.php`

## 4. Validasi dan Controller

- [ ] 4.1 Buat `app/Http/Requests/RoomType/StoreRoomTypeRequest.php` dan `UpdateRoomTypeRequest.php`: nama wajib dan unik di antara baris belum terhapus, harga > 0, total > 0, tersedia 0..total, fasilitas/peraturan hanya dari master dengan `scope` `kamar`, nilai spesifikasi mengikuti `input_type`, foto maksimal 10 berkas memakai batas `config('uploads.kost_photo')`
- [ ] 4.2 Buat `app/Http/Controllers/Owner/RoomTypeController.php` dengan `index`, `create`, `store`, `edit`, `update`, `changeStatus`, `destroy`; setiap method memanggil `Gate::authorize`, dan jalur `{roomType}` yang bukan milik `{kost}` dijawab 404
- [ ] 4.3 Tambahkan tujuh route di grup `owner.` pada `routes/web.php` dan jalankan `php artisan route:list --name=owner.kosts.types` untuk memastikan semuanya terdaftar dengan middleware `auth`, `verified`, `role:pemilik_kost`
- [ ] 4.4 Uji validasi lewat tes feature: kamar tersedia melebihi jumlah kamar ditolak, nama ganda ditolak, harga nol ditolak, fasilitas ber-`scope` `umum` ditolak, nilai spesifikasi `number` berisi teks ditolak, sebelas foto ditolak — diverifikasi pada `tests/Feature/Owner/RoomTypeManagementTest.php`

## 5. Frontend

- [ ] 5.1 Jalankan `php artisan wayfinder:generate` lalu verifikasi `resources/js/routes/owner/kosts/types/index.ts` berisi helper route tipe kamar
- [ ] 5.2 Buat `resources/js/pages/owner/kosts/types/index.tsx`: daftar tipe kamar dengan nama, harga, jumlah/tersedia, status, tombol ubah, tombol aktif/nonaktif, dan hapus dengan dialog konfirmasi; reuse token dan komponen dari `resources/js/pages/owner/kosts/index.tsx`
- [ ] 5.3 Buat `resources/js/components/owner/room-type-form/` (formulir bersama, bagian foto dengan pemilihan foto utama, bagian spesifikasi ber-editor per `input_type`, bagian fasilitas dan peraturan) lalu `resources/js/pages/owner/kosts/types/create.tsx` dan `edit.tsx` yang memakainya
- [ ] 5.4 Tambahkan tautan "Kelola tipe kamar" pada kartu kost di `resources/js/pages/owner/kosts/index.tsx` dan verifikasi `npm run types:check` serta `npm run check` lolos

## 6. Seeder

- [ ] 6.1 Buat `database/seeders/DemoKostSeeder.php`: satu kost `disetujui` milik `pemilik@example.com` dengan `reviewed_by` akun admin dan `reviewed_at` terisi, dua tipe kamar (satu `aktif`, satu `nonaktif`) lengkap dengan fasilitas `kamar`, peraturan `kamar`, dan nilai spesifikasi; panggil dari `DatabaseSeeder`
- [ ] 6.2 Jalankan `php artisan migrate:fresh --seed` dan verifikasi lewat `php artisan tinker --execute` bahwa kost berstatus `disetujui` memiliki tepat dua tipe kamar dan hanya satu berstatus `aktif`

## 7. Integrasi

- [ ] 7.1 Tulis `tests/Feature/Owner/RoomTypeManagementTest.php` sebagai tes feature menyeluruh: halaman 403 untuk pemilik lain/customer/akun nonaktif, siklus buat → ubah → aktifkan → nonaktifkan → hapus, foto pertama menjadi utama, dan soft delete membebaskan nama
- [ ] 7.2 Jalankan `composer test` (pint, phpstan level 7, pest) sampai hijau dan `npm run build` berhasil

## Workflow follow-up

- Arsipkan change setelah review terpenuhi: `openspec archive owner-manage-room-types`.
- Verifikasi hasil arsip: capability `owner/room-type-management` muncul di `openspec/specs/` dan requirement `room_types` pada `domain/kost-catalog` memuat aturan keunikan baru.