# Proposal

## Why

Fase M2.2 belum punya satu pun halaman Pemilik Kost, sehingga kost tidak dapat masuk ke sistem sama sekali; seluruh alur publik (M3.2) dan verifikasi Admin (M3.1) bergantung pada data yang belum bisa dibuat. Formulir pengajuan kost adalah pintu masuk data itu, dan titik lokasi adalah bagian yang paling rawan salah karena alamat teks tidak selalu dapat dipetakan (kost di dalam gang, nomor rumah tidak terdaftar di OSM).

## What Changes

- Halaman daftar kost milik Pemilik Kost (`/owner/kosts`) berisi foto utama, nama, kota, tipe, status verifikasi, dan alasan penolakan atau penangguhan bila ada.
- Halaman pengajuan kost (`/owner/kosts/create`) dengan data dasar, titik lokasi pada peta, fasilitas umum dan peraturan dari master, foto bangunan, serta dokumen izin.
- Titik peta menjadi sumber kebenaran koordinat; `address` tetap teks bebas yang tidak divalidasi ke geocoder. `city` diisi dari reverse geocode titik peta, dapat dikoreksi manual, dan tidak ditimpa setelah pengguna menyuntingnya.
- Kost baru selalu berstatus `menunggu_verifikasi`; status tidak pernah diterima dari request.
- Foto kost dikonversi ke WebP dengan sisi terpanjang maksimal 1600 px sebelum disimpan; berkas asli tidak disimpan.
- `App\Actions\StoreAvatar` digeneralisasi menjadi `App\Actions\StoreImage` (`square()` untuk foto profil, `scaled()` untuk foto kost) sehingga konversi gambar tidak terduplikasi. Perilaku foto profil tidak berubah.
- Navigasi sidebar mendapat satu item "Kost Saya" untuk peran Pemilik Kost.

## Capabilities

### New Capabilities

- `owner/kost-submission`: pengajuan kost oleh Pemilik Kost — validasi data wajib, penurunan kota dari titik peta, status awal menunggu verifikasi, penyimpanan foto dan dokumen izin, serta daftar kost milik sendiri beserta statusnya.

### Modified Capabilities

- `platform/file-storage`: ditambahkan persyaratan bahwa foto kost dikonversi ke WebP berukuran maksimal sebelum disimpan, dan berkas asli tidak disimpan.
- `platform/map-geocoding`: ditambahkan persyaratan pengisian `city` dari reverse geocode titik peta pada formulir kost, koreksi manual yang diizinkan, dan larangan menimpa kota yang sudah disunting.

## Impact

- Kode baru: `app/Http/Controllers/Owner/KostController.php`, `app/Http/Requests/Owner/StoreKostRequest.php`, `app/Actions/Kost/SubmitKost.php`, `app/Policies/KostPolicy.php`, `resources/js/pages/owner/kosts/{index,create}.tsx`.
- Kode diubah: `routes/web.php`, `resources/js/components/map/leaflet-map.tsx` (marker dapat digeser, `onPick`, perbaikan effect `center`), `resources/js/components/map/location-autocomplete.tsx` (kota dan alamat pada saran, filter negara), `resources/js/components/app-sidebar.tsx`.
- Refaktor: `app/Actions/StoreAvatar.php` → `app/Actions/StoreImage.php`, dengan penyesuaian `app/Http/Controllers/Settings/ProfileController.php` dan `tests/Feature/Settings/ProfileUpdateTest.php`.
- Tanpa migrasi: tabel `kosts`, `photos`, `kost_documents`, `kost_facilities`, dan `kost_rules` sudah memadai. Tidak ada perubahan `docs/ERD/erd.md` maupun `docs/FunctionalRequirment/functional_requirment.md`, jadi versi dokumen tetap ERD V003 dan FR V001.
- Lingkungan: unggahan melebihi 2 MB ditolak PHP sebelum Laravel sempat memvalidasi, karena `upload_max_filesize` bernilai 2M dan `post_max_size` 8M di container. Formulir ini memerlukan `upload_max_filesize` minimal 8M dan `post_max_size` 64M.
- Otorisasi: `KostPolicy` menutup celah `is_active` yang saat ini hanya diperiksa pada saat login.