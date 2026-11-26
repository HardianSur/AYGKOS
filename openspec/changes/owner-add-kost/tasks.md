# Tasks

## 1. Prasyarat lingkungan

- [ ] 1.1 Naikkan batas unggahan PHP pada lingkungan pengembangan agar berkas 3 MB dan 5 MB lolos sebelum validasi Laravel; verifikasi dengan `lerd exec` menjalankan `php artisan tinker --execute="echo ini_get('upload_max_filesize');"` yang menghasilkan minimal `8M` dan `post_max_size` minimal `64M`
- [ ] 1.2 Pastikan `.env.example` tidak perlu kunci baru (tidak ada variabel baru pada change ini); verifikasi dengan `git diff .env.example` yang kosong

## 2. Refaktor konversi gambar

- [ ] 2.1 Buat `app/Actions/StoreImage.php` dengan `square()` (potong 400×400, kualitas 85) dan `scaled()` (proporsional, sisi terpanjang 1600, kualitas 80) yang berbagi satu metode penyimpanan WebP; verifikasi dengan `composer lint:check` dan `phpstan analyse` lolos
- [ ] 2.2 Hapus `app/Actions/StoreAvatar.php` dan ubah `ProfileController@update` memakai `StoreImage::square()`; verifikasi `php artisan test --filter=ProfileUpdateTest` hijau tanpa perubahan perilaku
- [ ] 2.3 Sesuaikan `tests/Feature/Settings/ProfileUpdateTest.php` ke `StoreImage` dan tambahkan asersi path avatar berakhiran `.webp`; verifikasi tes tersebut hijau

## 3. Otorisasi dan rute

- [ ] 3.1 Buat `app/Policies/KostPolicy.php` dengan `viewAny` dan `create` yang memeriksa peran `pemilik_kost`, `is_active`, dan identitas terverifikasi; verifikasi lewat tes grup 7 yang mencakup Customer, Admin, dan akun nonaktif mendapat 403
- [ ] 3.2 Tambahkan grup route `['auth', 'verified', 'role:pemilik_kost']` dengan prefix `owner` dan nama `owner.` berisi `owner.kosts.index`, `owner.kosts.create`, `owner.kosts.store`; verifikasi dengan `php artisan route:list --name=owner`
- [ ] 3.3 Jalankan `php artisan wayfinder:generate` dan verifikasi `resources/js/routes/owner/kosts/index.ts` terbentuk

## 4. Komponen peta dan autocomplete

- [ ] 4.1 Pisahkan effect pemasangan dan effect pusat pada `resources/js/components/map/leaflet-map.tsx` sehingga perubahan `center` memakai `flyTo()` alih-alih membangun ulang peta; verifikasi manual: memilih titik tidak memicu peta berkedip dan zoom tetap
- [ ] 4.2 Tambahkan prop `onPick` dan marker yang dapat digeser pada `leaflet-map.tsx`; verifikasi manual: klik dan geser marker memperbarui lintang/bujur pada formulir
- [ ] 4.3 Tambahkan `city` dan `address` pada `LocationSuggestion` di `resources/js/components/map/location-autocomplete.tsx`, isi dari `properties.city ?? county ?? state` dan `properties.formatted`, serta tambahkan `filter=countrycode:id` pada query; verifikasi manual: saran hanya memuat hasil Indonesia dan memilih saran mengisi alamat serta kota
- [ ] 4.4 Jalankan `npm run check` dan `npm run types:check`; verifikasi keduanya hijau

## 5. Validasi dan penyimpanan pengajuan kost

- [ ] 5.1 Buat `app/Http/Requests/Owner/StoreKostRequest.php` dengan aturan data dasar, koordinat dalam kotak batas Indonesia, `photos` 1–10 berkas sesuai `config/uploads.php` `kost_photo`, dan dokumen sesuai `kost_documents_wajib` + `config/uploads.php` `kost_document`; tanpa aturan untuk `status`, `status_reason`, `reviewed_by`, `reviewed_at`; verifikasi lewat tes grup 7 untuk koordinat luar Indonesia, foto kosong, NIB kosong, dan berkas melebihi batas
- [ ] 5.2 Buat `app/Actions/Kost/SubmitKost.php`: tulis foto lewat `StoreImage::scaled()` dan dokumen ke disk `local`, lalu simpan `kosts` (status literal `menunggu_verifikasi`, `owner_id` dari pengguna terautentikasi), `photos` (semua `bangunan`, foto pertama `is_primary`), `kost_facilities`, `kost_rules`, dan `kost_documents` di dalam `DB::transaction`; pada kegagalan hapus path yang sudah tertulis lalu teruskan galat; verifikasi lewat tes grup 7 untuk penyimpanan lengkap dan pembersihan saat gagal
- [ ] 5.3 Tambahkan `laravel/framework` facade `Image` pemakaian melalui `StoreImage` diuji dengan `Storage::fake('public')`: verifikasi berkas tersimpan berformat WebP dan tidak ada berkas asli tersisa

## 6. Controller dan halaman

- [ ] 6.1 Buat `app/Http/Controllers/Owner/KostController.php` dengan `index` (kost milik pengguna, `with('photos')`, urut terbaru, `paginate(12)`, memetakan foto utama, status, dan `status_reason`), `create` (fasilitas scope `umum`, peraturan scope `kost`, kunci Geoapify sebagai prop), dan `store` (`SubmitKost` + flash toast + redirect ke daftar); verifikasi lewat tes grup 7 bahwa daftar tidak memuat kost pemilik lain
- [ ] 6.2 Buat `resources/js/pages/owner/kosts/index.tsx` dengan kartu per kost (foto utama atau penanda gambar kosong, nama, kota, tipe, badge status, alasan bila ada), paginasi, keadaan kosong, dan tombol menuju formulir; verifikasi manual di peramban untuk keadaan kosong dan berisi
- [ ] 6.3 Buat `resources/js/pages/owner/kosts/create.tsx` dengan `useForm({ forceFormData: true })` berisi bagian data dasar, lokasi (autocomplete + peta + alamat + kota), fasilitas, peraturan, foto, dan dokumen; reverse geocode hanya mengisi kota bila belum disunting manual; galat validasi menempel pada kolom masing-masing; verifikasi manual: pengajuan lengkap berhasil dan muncul pada daftar
- [ ] 6.4 Tambahkan item navigasi "Kost Saya" pada `resources/js/components/app-sidebar.tsx` yang hanya tampil untuk peran `pemilik_kost`; verifikasi manual: peran lain tidak melihat item tersebut
- [ ] 6.5 Jalankan `npm run check`, `npm run types:check`, dan `npm run build`; verifikasi ketiganya hijau

## 7. Tes fitur

- [ ] 7.1 Buat `tests/Feature/Owner/KostTest.php` mencakup: daftar hanya memuat kost sendiri dan menampilkan alasan penolakan; Pemilik Kost terverifikasi membuka formulir (fasilitas hanya scope `umum`); Customer, Admin, dan akun nonaktif mendapat 403
- [ ] 7.2 Tambahkan tes penyimpanan: pengajuan valid menghasilkan `status=menunggu_verifikasi`, `owner_id` pelaku, satu foto utama, pivot fasilitas dan peraturan, serta dua dokumen; koordinat di luar Indonesia, foto kosong, dan NIB kosong masing-masing menghasilkan galat pada kolomnya; nilai `status` kiriman diabaikan
- [ ] 7.3 Tambahkan tes pembersihan: kegagalan penyimpanan baris tidak meninggalkan baris `kosts` maupun berkas pada `Storage::fake`
- [ ] 7.4 Jalankan `composer test` dan verifikasi pint, PHPStan level 7, serta seluruh tes hijau

## Workflow follow-up

- Arsipkan change setelah tinjauan dan gate `composer test` + `composer ci:check` terpenuhi.
- Verifikasi hasil arsip pada `openspec/specs/owner/kost-submission/spec.md` serta delta yang masuk ke `platform/file-storage` dan `platform/map-geocoding`.