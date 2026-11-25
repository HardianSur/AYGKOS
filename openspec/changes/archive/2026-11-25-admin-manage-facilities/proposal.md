# Proposal

## Why

Skema fasilitas dan spec `domain/master-data` sudah menetapkan bahwa Admin mengelola master fasilitas dan Pemilik Kost hanya memilih dari daftar terkurasi, tetapi belum ada satu pun halaman, controller, maupun route untuk melakukannya. Hari ini master fasilitas hanya bisa diubah lewat seeder, sehingga Admin tidak dapat menambah fasilitas baru, memperbaiki salah ketik nama, atau menyesuaikan ikon tanpa deploy. Fitur ini juga menjadi sumber data form kost dan tipe kamar (M2.2), sehingga harus ada sebelum pengelolaan kost dibangun.

## What Changes

- Halaman `/admin/facilities` berisi tabel master fasilitas: nama, ruang lingkup, ikon, jumlah pemakai, dan aksi ubah serta hapus.
- Admin dapat menambah fasilitas dengan nama, ruang lingkup (`umum` atau `kamar`), dan ikon yang dipilih dari grid 21 ikon yang sudah didefinisikan `App\Support\FacilityIcons`.
- Admin dapat mengubah nama dan ikon fasilitas. Ruang lingkup dikunci selama fasilitas masih dipakai kost atau tipe kamar, karena perubahan itu akan meninggalkan relasi yang tidak sesuai.
- Pengahapusan fasilitas yang masih dirujuk `kost_facilities` atau `room_type_facilities` ditolak dan menyebut pemakainya.
- Keunikan nama fasilitas ditegakkan tanpa membedakan huruf besar/kecil, sehingga "WiFi" dan "wifi" tidak dapat menjadi dua baris berbeda.
- Server menolak setiap permintaan dari peran selain Admin (FR-ADM-MST-01, FR-AUT-13).

## Capabilities

### New Capabilities

Tidak ada. Perilaku yang diubah sudah dimiliki `domain/master-data`.

### Modified Capabilities

- `domain/master-data`: keunikan nama fasilitas menjadi tanpa beda huruf besar/kecil, ruang lingkup fasilitas terkunci selama dipakai, dan penghapusan yang ditolak menampilkan daftar pemakainya.

## Impact

- **Prasyarat**: change `admin-access-gate` (middleware `EnsureRole`, grup route `/admin`, `AdminLayout`) belum ada di kode. Artefak perencanaan ini dapat ditulis sekarang, tetapi tugas implementasi baru dapat dijalankan setelah prasyarat itu selesai. Delta spec di sini tidak menduplikasi gerbang akses; ia hanya menyatakan perilaku yang harus berlaku.
- **Dokumen**: tidak ada perubahan pada `docs/ERD/erd.md` (tetap V005) maupun `docs/FunctionalRequirment/functional_requirment.md` (tetap V002). Aturan baru dicatat pada delta spec change ini, karena FR-ADM-MST-01 sudah mencakup CRUD master fasilitas beserta penolakan penghapusan.
- **Model**: `app/Models/Facility.php` mendapat relasi `roomTypes()` agar daftar pemakai dapat dihitung dan ditampilkan.
- **Migrasi**: satu migrasi baru menambahkan unique index pada `lower(name)` untuk `facilities`. Data saat ini 15 baris, tidak ada duplikat, dan `kost_facilities` serta `room_type_facilities` masih kosong, sehingga migrasi aman tanpa langkah pembersihan.
- **Route dan controller baru**: `app/Http/Controllers/Admin/FacilityController.php`, `app/Http/Requests/Admin/StoreFacilityRequest.php`, `app/Http/Requests/Admin/UpdateFacilityRequest.php`, `app/Actions/MasterData/ManageFacility.php` sesuai pembagian layer `docs/ARCHITECTURE.md`.
- **Frontend**: `resources/js/components/facility-icon.tsx` (peta nama ikon ke komponen lucide, dipakai ulang form kost dan kartu katalog nanti) dan `resources/js/pages/admin/facilities/index.tsx`.
- **Tes**: `tests/Feature/Admin/FacilityManagementTest.php` untuk otorisasi peran, validasi, penolakan hapus, dan kunci ruang lingkup.
- **Di luar cakupan**: master peraturan dan master spesifikasi (FR-ADM-MST-02, FR-ADM-MST-03), serta pemilih fasilitas pada form kost dan tipe kamar (M2.2).
