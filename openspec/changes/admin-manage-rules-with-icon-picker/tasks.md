# Tasks

## 1. Skema, model, dan seeder

- [ ] 1.1 Tambah migrasi `add_icon_to_rules_table` yang membuang indeks `rules_name_unique`, menambah kolom `icon` varchar(64) NOT NULL dengan default `circle-alert`, dan membuat unique index pada `lower(name)`; verifikasi `php artisan migrate` dan `php artisan migrate:rollback` berjalan bolak-balik.
- [ ] 1.2 Perbarui `app/Models/Rule.php`: `icon` masuk `Fillable`, tambah `@property string $icon`, dan tambah relasi `roomTypes(): BelongsToMany` ke `room_type_rules`; verifikasi dengan `php artisan tinker --execute` bahwa `Rule::first()->roomTypes` dapat dipanggil.
- [ ] 1.3 Ubah `database/seeders/RuleSeeder.php` memakai `updateOrCreate` berkunci `name` serta ikon tiap baris sesuai tabel di `design.md`; verifikasi dengan menjalankan seeder dua kali pada basis data yang sudah berisi tujuh aturan — tetap tujuh baris, semua `icon` terisi.
- [ ] 1.4 Tambah `tests/Feature/Admin/RuleModelTest.php` yang membuktikan nama berbeda kapitalisasi ditolak unique index, `icon` wajib tidak null, dan aturan `scope` `kost` tidak dapat disimpan ke `room_type_rules`; verifikasi `php artisan test --filter=RuleModelTest` hijau.

## 2. Backend pengelolaan aturan Admin

- [ ] 2.1 Tambah `app/Http/Requests/Admin/Concerns/ValidatesRule.php` (nama wajib + rapi spasi + unik tanpa beda kapitalisasi, `scope` in `kost`/`kamar`, `icon` wajib `max:64` + regex slug) beserta `messages()` berbahasa Indonesia, lalu `StoreRuleRequest` dan `UpdateRuleRequest` yang memakainya; verifikasi lewat test validasi di 2.5.
- [ ] 2.2 Tambah `app/Actions/MasterData/ManageRule.php` dengan `create`, `update`, `delete`, penolakan ubah `scope` saat masih dipakai, dan penolakan hapus dengan pesan menyebut nama kost serta tipe kamar pemakai; verifikasi lewat test di 2.5.
- [ ] 2.3 Tambah `app/Http/Controllers/Admin/RuleController.php` (`index` mengirim `rules` dengan `kosts`/`roomTypes` count beserta `icon`, `store`, `update`, `destroy` dengan toast) dan daftarkan empat route `/admin/rules` di `routes/web.php` dalam grup `role:admin`; verifikasi `php artisan route:list --name=admin.rules` menampilkan empat route.
- [ ] 2.4 Tambah tautan Peraturan pada sidebar Admin di `resources/js/components/app-sidebar.tsx` memakai helper route Wayfinder hasil `php artisan wayfinder:generate`; verifikasi tautan muncul dan menuju `/admin/rules`.
- [ ] 2.5 Tambah `tests/Feature/Admin/RuleManagementTest.php` yang menutup: Customer dan Pemilik Kost ditolak (403), Admin dapat menambah/mengubah/menghapus, nama ganda dan beda kapitalisasi ditolak dengan galat pada `name`, ikon hilang atau non-slug ditolak pada `icon`, ubah `scope` aturan terpakai ditolak, hapus aturan terpakai ditolak dengan nama pemakai, hapus aturan tanpa pemakai berhasil, dan halaman `index` memuat ikon serta jumlah pemakai; verifikasi `php artisan test --filter=RuleManagementTest` hijau.

## 3. Komponen pemilih ikon dan halaman Admin

- [ ] 3.1 Tambah `resources/js/components/icon-picker/search.ts` (fungsi murni `searchIcons`, aturan awalan-kata, batas 20) beserta `resources/js/components/icon-picker/featured-icons.ts` berisi 20 slug kurasi dari `design.md`; verifikasi `npm run types:check` hijau dan perilaku pencarian diperiksa di 3.6 (`"paw print"`, `"paw-print"`, `"PAW"` memberi hasil sama; `"clock"` dipotong 20).
- [ ] 3.2 Tambah `resources/js/components/rule-icon.tsx` memakai `DynamicIcon` dari `lucide-react/dynamic` yang mengembalikan `null` bila nama tidak dikenal; verifikasi `npm run types:check` hijau dan ikon tampil pada halaman aturan.
- [ ] 3.3 Tambah `resources/js/components/icon-picker/icon-grid.tsx` (grid sel tombol ikon + slug, penanda terpilih) dan `resources/js/components/icon-picker/index.tsx` (dialog dengan input cari, petunjuk bahasa Inggris, keadaan kosong, maksimal 20 sel); verifikasi tidak ada berkas melebihi ~150 baris dan `vp check` lolos.
- [ ] 3.4 Tambah `resources/js/components/admin/rule-table.tsx` (kolom ikon, nama, badge ruang lingkup, jumlah pemakai, tombol ubah/hapus dengan nonaktif saat terpakai) dan `resources/js/components/admin/rule-form-dialog.tsx` (nama, ruang lingkup, `IconPicker`); verifikasi `npm run types:check` hijau.
- [ ] 3.5 Tambah halaman tipis `resources/js/pages/admin/rules/index.tsx` yang hanya menerima props, menyusun `RuleTable` dan `RuleFormDialog`, serta mengirim POST/PUT/DELETE lewat helper Wayfinder; verifikasi halaman memuat tabel, dialog, dan toast sukses setelah simpan/hapus.
- [ ] 3.6 Jalankan halaman `/admin/rules` di browser sebagai Admin: tambah aturan dengan ikon hasil pencarian "paw print", ubah aturan tanpa pemakai beserta ruang lingkupnya, dan pastikan tombol hapus nonaktif pada aturan terpakai; verifikasi tidak ada galat konsol dan `diag browser_events` bersih.

## 4. Ikon aturan pada formulir Pemilik Kost

- [ ] 4.1 Ubah `app/Http/Controllers/Owner/KostController.php` agar `rules` yang dikirim ke `create` memuat `icon`, dan perbarui tipe `Rule` di `resources/js/components/owner/kost-form/types.ts`; verifikasi `npm run types:check` hijau.
- [ ] 4.2 Ubah `resources/js/components/owner/kost-form/rules-section.tsx` memakai `RuleIcon` di samping nama aturan, tetap kompatibel dengan aturan tanpa ikon dikenal; verifikasi formulir tambah kost menampilkan ikon tiap aturan.
- [ ] 4.3 Tambah test di `tests/Feature/Owner/KostFormTest.php` (atau berkas terkait yang sudah ada) yang memastikan props `rules` pada halaman `owner/kosts/create` memuat `name` dan `icon`; verifikasi `php artisan test --filter=Kost` hijau.

## 5. Dokumen versi

- [ ] 5.1 Salin `docs/ERD/erd.md` ke `docs/ERD/logs/V005-erd.md`, naikkan berkas utama ke V006 dengan baris `rules.icon` pada struktur tabel dan catatan unique `lower(name)`, lalu ubah baris V005 di `docs/ERD/release.md` menjadi dipensiunkan dan tambah baris V006 dengan rujukan change ini; verifikasi `grep -n "V006" docs/ERD/release.md` dan struktur tabel `rules` memuat `icon`.
- [ ] 5.2 Salin `docs/FunctionalRequirment/functional_requirment.md` ke `docs/FunctionalRequirment/logs/V002-functional_requirment.md`, naikkan berkas utama ke V003 dengan FR-ADM-MST-02 mencakup ikon, lalu perbarui baris V002 di `docs/FunctionalRequirment/release.md` dan tambah baris V003; verifikasi `grep -n "V003" docs/FunctionalRequirment/release.md` dan FR-ADM-MST-02 menyebut ikon.

## 6. Pemeriksaan integrasi

- [ ] 6.1 Jalankan `composer test`, `npm run check`, `npm run types:check`, dan `npm run build`; verifikasi keempatnya hijau dan perbaiki kegagalan sebelum melapor selesai.
- [ ] 6.2 Tinjau diff: tidak ada perubahan pada modul fasilitas (`FacilityIcons`, `facility-icon.tsx`, halaman fasilitas), tidak ada secret baru, tidak ada berkas halaman melebihi ~150 baris, dan `openspec validate admin-manage-rules-with-icon-picker --strict` lolos.

## Workflow follow-up

- Arsipkan change setelah gate di grup 6 hijau dan hasil ditinjau.
- Verifikasi hasil arsip: delta `domain/master-data` masuk ke `openspec/specs/domain/master-data/spec.md` dan direktori change pindah ke `openspec/changes/archive/`.