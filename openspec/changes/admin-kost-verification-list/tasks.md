# Tasks

## 1. Backend route dan otorisasi

- [ ] 1.1 Tambah ability `viewAnyAsAdmin(User $user): bool` dan `viewAsAdmin(User $user): bool` pada `app/Policies/KostPolicy.php`; keduanya hanya memeriksa `role?->name === 'admin'` dan `is_active`, dengan docblock yang menyebut jalur Admin terpisah dari jalur Pemilik Kost; verifikasi `php artisan tinker --execute` bahwa `Gate::allows('viewAnyAsAdmin', Kost::class)` bernilai `true` untuk Admin dan `false` untuk Pemilik Kost.
- [ ] 1.2 Tambah dua route `GET` di grup `role:admin` pada `routes/web.php`: `admin.kosts.index` (`/admin/kosts`) dan `admin.kosts.show` (`/admin/kosts/{kost}`), tanpa route mutasi; verifikasi `php artisan route:list --name=admin.kosts` menampilkan dua route dengan middleware `auth`, `verified`, `role:admin`.
- [ ] 1.3 Buat `app/Http/Controllers/Admin/KostController.php` dengan `index(Request $request)` membaca `status`, `dari`, `sampai` (nilai status tak dikenal diabaikan, bukan melempar galat), menyaring `whereDate('created_at', '>=', $dari)` / `'<='`, memanggil `->paginate(10)->withQueryString()`, dan memakai `->through()` untuk membentuk baris tabel; verifikasi dengan `php artisan tinker` bahwa query status dan rentang tanggal menghasilkan baris yang diharapkan.
- [ ] 1.4 Isi `show(Kost $kost)` dengan eager loading `owner:id,name,email,whatsapp_number`, `reviewer:id,name`, `photos`, `documents`, `facilities:id,name,icon`, `kostRules:id,name,icon`, `roomTypes:id,name,status,price_per_month`, lalu bangun lima slot dokumen dari `config('uploads.kost_documents_wajib')` dan `PrivateFileUrl::for()`; otorisasi lewat `Gate::authorize('viewAsAdmin', Kost::class)`; verifikasi prop yang dikirim tidak memuat kolom sensitif pengguna.

## 2. Halaman master dan komponen index

- [ ] 2.1 Buat `resources/js/components/admin/kost/status-badge.tsx` berisi peta status ke label Indonesia dan kelas token (memuat peta yang sudah ada di `resources/js/pages/owner/kosts/index.tsx`); verifikasi kelima nilai `menunggu_verifikasi|disetujui|ditolak|ditangguhkan|nonaktif` punya label dan kelas, dengan nilai tak dikenal jatuh ke gaya netral.
- [ ] 2.2 Buat `resources/js/components/admin/kost/index-filter-bar.tsx` berisi input status, tanggal awal, dan tanggal akhir yang mengirim `router.get(index.url(), values, { preserveState: true, replace: true })` dan mereset `page` ke 1; verifikasi mengubah filter benar-benar mengubah URL dan hasil, serta mengosongkan filter mengembalikan seluruh status.
- [ ] 2.3 Buat `resources/js/components/admin/kost/index-table.tsx` dengan lima kolom (nomor urut, nama kost, pemilik, status lewat `status-badge`, tanggal diajukan) dan tautan tiap baris ke `admin/kosts/show`; verifikasi tabel menampilkan keadaan kosong yang jelas saat hasil saringan nol.
- [ ] 2.4 Buat `resources/js/components/admin/kost/index-pagination.tsx` memakai `prev_page_url`/`next_page_url` dan menampilkan posisi halaman; verifikasi tombol nonaktif saat tidak ada halaman sebelumnya/berikutnya.
- [ ] 2.5 Buat `resources/js/pages/admin/kosts/index.tsx` sebagai halaman tipis yang menyusun `index-filter-bar`, `index-table`, dan `index-pagination` dari prop `kosts: Paginated<KostRow>` serta `filters`; tetapkan judul lewat `<Head>` dan pertahankan pola token `surface`/`hairline`/`ink`/`ink-soft`/`ember` dari `admin/facilities/index.tsx`.

## 3. Halaman detail dan komponen show

- [ ] 3.1 Buat `resources/js/components/admin/kost/detail-identity.tsx` (nama, tipe, kota, alamat, tanggal pengajuan, deskripsi yang hanya dirender bila terisi) dan `detail-owner.tsx` (nama, email, WhatsApp); verifikasi tidak ada elemen berisi teks kosong saat deskripsi null.
- [ ] 3.2 Buat `resources/js/components/admin/kost/detail-location.tsx` yang membungkus `resources/js/components/map/leaflet-map.tsx` dengan satu marker non-interaktif dari `latitude`/`longitude`; verifikasi halaman tidak jatuh bila koordinat null (bagian disembunyikan).
- [ ] 3.3 Buat `resources/js/components/admin/kost/detail-facilities.tsx` (dua kelompok: fasilitas umum dan peraturan kost) dan `detail-photos.tsx` (grid foto bangunan dengan keadaan kosong); verifikasi keduanya menerima array kosong tanpa galat.
- [ ] 3.4 Buat `resources/js/components/admin/kost/detail-documents.tsx` yang merender lima slot dari prop: label jenis, penanda wajib, keadaan belum terunggah, tautan bertanda tangan bila ada, dan catatan masa berlaku 10 menit; slot wajib yang kosong ditandai sebagai kekurangan.
- [ ] 3.5 Buat `resources/js/components/admin/kost/detail-review.tsx` yang merender peninjau, waktu tinjauan, dan alasan hanya bila `reviewed_at` terisi; verifikasi kost `menunggu_verifikasi` tanpa tinjauan tidak menampilkan bagian ini.
- [ ] 3.6 Buat `resources/js/pages/admin/kosts/show.tsx` sebagai halaman tipis yang menyusun seluruh komponen detail di atas, memakai `status-badge` untuk status, dan menyediakan tautan kembali ke master; verifikasi tidak ada tombol atau formulir yang mengubah data kost.

## 4. Navigasi, tes, dan gerbang mutu

- [ ] 4.1 Tambah tautan "Verifikasi Kost" ke `resources/js/components/app-sidebar.tsx` untuk peran Admin, memakai helper route hasil Wayfinder; jalankan `php artisan wayfinder:generate`; verifikasi tautan hanya muncul di sidebar Admin.
- [ ] 4.2 Tulis `tests/Feature/Admin/KostVerificationTest.php` yang membuktikan: Admin melihat tabel, pagination 10 baris, filter status menyempitkan hasil, nilai status tak dikenal diabaikan, filter tanggal awal/akhir bekerja, filter bertahan di URL halaman kedua, detail memuat identitas/pemilik/fasilitas/peraturan/foto, lima slot dokumen dengan penanda wajib kosong, URL dokumen memakai tanda tangan, riwayat tinjauan hanya muncul saat sudah diproses, kost terhapus lunak tidak tampil, dan Customer/Pemilik Kost maupun tamu ditolak dari kedua route.
- [ ] 4.3 Perbarui `app/Http/Controllers/Admin/KostController.php` atau tes bila `phpstan analyse` (level 7) mengeluhkan tipe array prop; verifikasi `composer test` hijau.
- [ ] 4.4 Jalankan `npm run check`, `npm run types:check`, dan `npm run build`; perbaiki seluruh kegagalan sebelum melaporkan selesai.