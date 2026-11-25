# Tasks

## 1. Prasyarat

- [ ] 1.1 Pastikan change `admin-access-gate` sudah diimplementasikan: middleware `EnsureRole` terdaftar, grup route `/admin` memakai `role:admin`, dan `AdminLayout` tersedia. Verifikasi: `grep -rn "EnsureRole" app bootstrap routes` memuat middleware dan grup route `/admin`.

## 2. Integritas data

- [ ] 2.1 Tambah relasi `roomTypes()` pada `App\Models\Facility` (belongsToMany lewat `room_type_facilities`) beserta blok `@return` untuk PHPStan. Verifikasi: `vendor/bin/phpstan analyse app/Models/Facility.php` lulus.
- [ ] 2.2 Buat migrasi yang membuang unique lama pada `facilities.name` dan menambahkan unique index pada `lower(name)`, dengan `down()` yang mengembalikan keduanya. Verifikasi: `php artisan migrate` lalu `php artisan migrate:rollback --step=1` pada `aygkos_testing` berjalan tanpa galat.
- [ ] 2.3 Tambah tes skema di `tests/Feature/Schema/DomainModelTest.php`: menyimpan "wifi" saat "WiFi" ada ditolak basis data. Verifikasi: `php artisan test --filter="huruf besar"` lulus.

## 3. Backend pengelolaan fasilitas

- [ ] 3.1 Buat `app/Http/Requests/Admin/StoreFacilityRequest.php` dan `UpdateFacilityRequest.php`: `name` wajib, maksimal 100, dinormalkan (`trim` + spasi beruntun dirapatkan); `scope` wajib `in:umum,kamar`; `icon` wajib `FacilityIcons::isAllowed()`; aturan unik `lower(name)` mengabaikan baris sendiri saat ubah, dengan pesan galat berbahasa Indonesia. Verifikasi: tes validasi pada 4.1 lulus.
- [ ] 3.2 Buat `app/Actions/MasterData/ManageFacility.php` dengan `create()`, `update()`, dan `delete()`: `update()` menolak perubahan `scope` saat fasilitas dipakai dan `delete()` menolak penghapusan saat dipakai, keduanya melempar `ValidationException` berisi nama kost dan tipe kamar pemakainya. Verifikasi: tes pada 4.2 lulus.
- [ ] 3.3 Buat `app/Http/Controllers/Admin/FacilityController.php` (`index`, `store`, `update`, `destroy`) dan daftarkan route `/admin/facilities` pada grup `role:admin` di `routes/web.php`. `index` mengirim `facilities` dengan `kost_count`, `roomType_count`, dan `iconNames` dari `FacilityIcons::names()`; mutasi memakai `Inertia::flash('toast', ...)` seperti `ProfileController`. Verifikasi: `php artisan route:list --path=admin/facilities` menampilkan empat route dan `php artisan wayfinder:generate` lulus.
- [ ] 3.4 Tulis `tests/Feature/Admin/FacilityManagementTest.php`: Customer dan Pemilik Kost menerima 403 pada keempat route; Admin dapat menambah, mengubah nama dan ikon, menghapus fasilitas tanpa pemakai; penghapusan fasilitas yang dipakai ditolak dengan galat pada kolom `facility`; perubahan `scope` pada fasilitas yang dipakai ditolak; "wifi" ditolak saat "WiFi" ada; dan halaman daftar memuat `kost_count` serta `roomType_count` yang benar. Verifikasi: `php artisan test --filter=FacilityManagementTest` lulus.

## 4. Antarmuka Admin

- [ ] 4.1 Buat `resources/js/components/facility-icon.tsx` berisi `Record` bertipe union nama ikon ke komponen lucide untuk seluruh 21 nama di `FacilityIcons::ALL`, plus komponen `<FacilityIcon name={...} />` yang merender ikon atau `null` bila tidak dikenal. Verifikasi: `npm run types:check` lulus dan menghapus satu entri dari peta memunculkan galat tipe.
- [ ] 4.2 Buat `resources/js/pages/admin/facilities/index.tsx`: tabel nama, lingkup, ikon, jumlah pemakai, dan aksi; dialog tambah/ubah dengan grid ikon dari prop `iconNames`, pilihan ruang lingkup, dan `InputError` per kolom; kendali hapus nonaktif dengan `title` alasan saat pemakai bukan nol; dialog konfirmasi hapus untuk baris tanpa pemakai. Verifikasi: `npm run check` dan `npm run build` lulus; halaman terbuka di browser tanpa galat konsol.

## 5. Verifikasi akhir

- [ ] 5.1 Jalankan gate penuh: `composer test` (pint, phpstan level 7, pest) dan `npm run ci:check`. Verifikasi: seluruh gate hijau; kegagalan yang tersisa dilaporkan beserta alasannya.
- [ ] 5.2 Tinjau diff akhir: tidak ada import rusak, kode unused, atau perubahan di luar `proposal.md`. Verifikasi: `git status` hanya memuat berkas yang disebut pada bagian Impact proposal.

## Workflow follow-up

- Arsipkan change setelah review terpenuhi.
- Jalankan `openspec validate admin-manage-facilities --strict` sebelum arsip.