# Proposal

## Why

Master aturan (`rules`) sudah punya tabel, seeder, relasi `kost_rules` dan `room_type_rules`, serta dipakai formulir kost, tetapi Admin belum punya halaman untuk mengelolanya: hari ini aturan hanya bisa diubah lewat seeder, sehingga FR-ADM-MST-02 belum terpenuhi sama sekali. Aturan juga belum punya ikon, padahal tampilan peraturan pada katalog kost (FR-SRC-08) dan formulir Pemilik Kost sebaiknya seragam dengan fasilitas yang sudah berikon.

## What Changes

- Halaman `/admin/rules` berisi tabel master aturan: ikon, nama, ruang lingkup (`kost` atau `kamar`), jumlah pemakai, dan aksi ubah serta hapus.
- Admin dapat menambah aturan dengan nama, ruang lingkup, dan ikon yang dipilih lewat dialog pemilih ikon.
- Pemilih ikon memakai katalog ikon lucide yang sudah terpasang, bukan daftar kurasi statis seperti fasilitas: ada kolom pencarian, hasil dan tampilan awal dibatasi maksimal 20 ikon, dan saat pencarian kosong ditampilkan 20 ikon kurasi yang relevan dengan aturan.
- Pencarian mencocokkan awalan kata pada nama ikon, menerima spasi, tanda hubung, dan huruf besar/kecil (`"paw print"`, `"paw-print"`, dan `"PAW"` sama-sama menemukan `paw-print`).
- Ruang lingkup aturan terkunci selama aturan masih dipakai kost atau tipe kamar, dan penghapusan aturan yang masih dipakai ditolak dengan menyebut pemakainya — pola yang sama dengan master fasilitas.
- Keunikan nama aturan ditegakkan tanpa membedakan huruf besar/kecil.
- `rules` mendapat kolom `icon`, dan `RuleSeeder` mengisi ikon untuk tujuh aturan bawaan.
- Formulir kost milik Pemilik Kost menampilkan ikon pada tiap aturan, konsisten dengan bagian fasilitas.
- Dialog pemilih ikon dan tabel master aturan dipecah menjadi komponen terpisah agar berkas halaman tetap ringkas.

## Capabilities

### New Capabilities

Tidak ada. Perilaku yang diubah sudah dimiliki `domain/master-data`.

### Modified Capabilities

- `domain/master-data`: master aturan menyimpan ikon; Admin mengelola master aturan lewat halaman `/admin/rules` dengan aturan kunci ruang lingkup dan penolakan hapus yang sama seperti fasilitas; formulir master aturan menawarkan ikon dari katalog aplikasi dengan pencarian dan batas 20 hasil.

## Impact

- **Dokumen**: `docs/ERD/erd.md` naik dari V005 ke **V006** (kolom `rules.icon`), dan `docs/FunctionalRequirment/functional_requirment.md` naik dari V002 ke **V003** (FR-ADM-MST-02 mencakup ikon). Versi lama dipensiunkan ke `docs/ERD/logs/V005-erd.md` dan `docs/FunctionalRequirment/logs/V002-functional_requirment.md`, dengan baris baru pada masing-masing `release.md`, dalam change yang sama.
- **Basis data**: satu migrasi menambahkan `rules.icon` (`varchar`, tidak null, nilai awal `circle-alert`) dan unique index pada `lower(name)`. Seeder ikut diperbarui memakai `updateOrCreate` agar baris yang sudah ada terisi ikon.
- **Model**: `app/Models/Rule.php` menerima `icon` pada `Fillable` dan relasi `roomTypes()` untuk menghitung pemakai.
- **Backend baru**: `app/Http/Controllers/Admin/RuleController.php`, `app/Http/Requests/Admin/StoreRuleRequest.php`, `app/Http/Requests/Admin/UpdateRuleRequest.php`, `app/Http/Requests/Admin/Concerns/ValidatesRule.php`, `app/Actions/MasterData/ManageRule.php`, route `/admin/rules` di `routes/web.php`, dan tautan sidebar Admin.
- **Validasi ikon**: format slug lucide (`/^[a-z0-9]+(-[a-z0-9]+)*$/`, maksimal 64 karakter) tanpa daftar 1760 nama di PHP; ikon yang tidak dikenal gagal dirender menjadi kosong, bukan menimbulkan galat.
- **Frontend**: `resources/js/components/rule-icon.tsx` (pemuat ikon dinamis), `resources/js/components/icon-picker.tsx` (dialog cari + grid 20), `resources/js/components/admin/rule-form-dialog.tsx`, `resources/js/components/admin/rule-table.tsx`, dan halaman tipis `resources/js/pages/admin/rules/index.tsx`. Bagian aturan pada form kost memakai komponen ikon yang sama.
- **Konsekuensi SSR**: `config/inertia.php` menyalakan SSR, sedangkan pemuat ikon dinamis mengisi setelah hydrate, sehingga grid ikon tampil kosong sekejap pada kunjungan pertama halaman Admin. Diterima; halaman ini hanya untuk Admin.
- **Tes**: `tests/Feature/Admin/RuleManagementTest.php` untuk otorisasi peran, validasi nama/ruang lingkup/ikon, kunci ruang lingkup, penolakan hapus, dan isi halaman.
- **Di luar cakupan**: master spesifikasi (FR-ADM-MST-03), perubahan bagian spesifikasi tipe kamar, dan penggantian skema pemilih ikon master fasilitas.
