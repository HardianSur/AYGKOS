# Design

## Context

Lihat `proposal.md - Why` untuk motivasi. Keadaan yang membentuk rancangan ini:

- Model dan migrasi domain sudah lengkap: `kosts`, `photos`, `kost_documents`, `kost_facilities`, `kost_rules`. Tidak ada kolom baru yang dibutuhkan.
- `photos` memiliki constraint `photos_category_room_type`: kategori `kamar` dan `kamar_mandi` wajib menyertakan `room_type_id`, sedangkan tipe kamar baru dapat dibuat setelah kost ada. Constraint `photos_one_primary_kost` memastikan hanya satu foto utama per kost.
- `App\Actions\StoreAvatar` sudah menangani konversi WebP foto profil (400×400, `cover()`), dipakai `ProfileController@update` dan dimock di `tests/Feature/Settings/ProfileUpdateTest.php`.
- Laravel 13 menyediakan facade `Illuminate\Support\Facades\Image` dengan driver GD; container PHP sudah memiliki dukungan WebP (`gd_info()` → `WebP Support => 1`).
- `resources/js/components/map/leaflet-map.tsx` saat ini membangun ulang peta setiap kali `center` berubah, dan belum mendukung pemilihan titik.
- Route pengguna hanya memiliki grup `role:admin`; belum ada halaman Pemilik Kost sama sekali.
- `KostPolicy` belum ada; `app/Policies` belum dibuat.

## Goals / Non-Goals

**Goals:**

- Satu jalur konversi gambar untuk foto profil dan foto kost, tanpa menduplikasi logika WebP.
- Titik peta sebagai satu-satunya sumber koordinat, dengan kolom kota yang tetap dapat dikoreksi manual.
- Penyimpanan berkas dan basis data yang bersifat semua-atau-tidak-sama-sekali.
- Formulir tetap dapat diselesaikan untuk kost di dalam gang yang tidak dikenali geocoder.

**Non-Goals:**

- Pemilih kategori foto per foto dan pengelolaan tipe kamar (halaman ubah kost, fase berikutnya).
- Pengeditan atau penghapusan kost.
- Penyimpanan gambar ke CDN atau pengubahan driver penyimpanan.
- Peta interaktif pada halaman daftar kost.

## Decisions

### Titik peta otoritatif, alamat deskriptif

`latitude`/`longitude` diisi dari titik yang dipilih atau digeser pada peta. Kolom `address` adalah teks bebas yang tidak pernah divalidasi ke geocoder dan tidak pernah ditimpa hasil reverse geocode. Reverse geocode hanya mengisi `city`.

Alternatif yang ditolak: menjadikan `address` sebagai sumber koordinat. Alamat di Indonesia sering tidak terpetakan (gang tanpa nama, nomor rumah tidak terdaftar di OSM), sehingga geocoding alamat akan gagal pada kasus yang justru harus didukung.

Konsekuensi: validasi koordinat memakai kotak batas Indonesia (`latitude` -11..6,1; `longitude` 94,9..141,1), lebih ketat daripada check constraint basis data (-90..90 / -180..180) tetapi tidak memerlukan migrasi.

### `StoreAvatar` digeneralisasi menjadi `StoreImage`

`StoreAvatar` diganti `App\Actions\StoreImage` dengan satu metode privat yang menyimpan hasil konversi sebagai WebP dan dua metode publik: `square()` (potong persegi + `cover()`, untuk foto profil) dan `scaled()` (`scale()` proporsional, untuk foto kost).

Alternatif yang ditolak:

- Helper fungsi global di `app/Support` — tidak dapat dimock dengan pola tes yang sudah ada, dan PHPStan level 7 lebih sulit diberi tipe kembalian eksplisit.
- Action terpisah `StoreKostPhoto` — menduplikasi rantai `orient() → toWebp() → quality() → store()` dan penanganan kegagalan.
- Menambah parameter `crop` pada `StoreAvatar` — nama kelas menjadi menyesatkan.

`scale()` pada Laravel 13 memetakan ke `scaleDown` Intervention, sehingga foto 800 px tidak diperbesar menjadi 1600 px. Kualitas WebP diturunkan dari 85 (foto profil, tidak berubah) menjadi 80 (foto kost, ukuran berkas lebih penting untuk galeri). Foto kost tidak dipotong karena rasio asli bangunan perlu dipertahankan.

### Semua foto pada pengajuan berkategori `bangunan`

Constraint `photos_category_room_type` membuat kategori `kamar`/`kamar_mandi` tidak dapat disimpan pada pengajuan awal. Pemilih kategori ditunda ke halaman ubah kost (lihat proposal `Non-Goals`). Foto pertama ditandai `is_primary`, sisanya tidak; `room_type_id` selalu `null`.

### Berkas lebih dulu, baris basis data di dalam transaksi

Berkas ditulis sebelum transaksi dimulai, lalu baris basis data disimpan di dalam `DB::transaction`. Pada kegagalan, path yang sudah tertulis dihapus dan galat diteruskan. Urutan ini menghindari baris basis data yang menunjuk berkas tidak ada, yang lebih sulit dipulihkan daripada berkas yatim yang dapat dihapus ulang.

Alternatif yang ditolak: menyimpan berkas di dalam `DB::transaction` — transaksi basis data tidak dapat membatalkan penulisan disk, dan kegagalan setelah commit tetap meninggalkan berkas yatim tanpa jalur pembersihan yang jelas.

### Status tidak pernah dibaca dari request

`SubmitKost` menetapkan `status = 'menunggu_verifikasi'` secara literal dan tidak memakai `status`, `status_reason`, `reviewed_by`, maupun `reviewed_at` dari data tervalidasi. `StoreKostRequest` juga tidak mendeklarasikan aturan untuk kolom tersebut, sehingga nilai kiriman pengguna tidak pernah masuk ke `fill()`.

### Otorisasi lewat `KostPolicy`

`KostPolicy::viewAny` dan `create` memeriksa peran `pemilik_kost`, `is_active`, dan identitas terverifikasi. Dipanggil dengan `Gate::authorize()` karena base `Controller` tidak memakai trait `AuthorizesRequests`; memakai `Gate` menghindari perubahan di luar lingkup.

`is_active` hanya diperiksa saat login saat ini, sehingga akun yang dinonaktifkan setelah masuk tetap memiliki sesi. Policy menutup celah tersebut tanpa perubahan pada middleware.

### Peta: pemilihan titik dan `flyTo`

`leaflet-map.tsx` mendapat prop opsional `onPick` dan marker yang dapat digeser. Effect pemasangan peta tidak lagi bergantung pada `center`; perubahan `center` ditangani effect terpisah dengan `map.flyTo()`. Tanpa pemisahan ini, setiap pemilihan titik akan membuang dan membangun ulang instance peta, yang memicu kedip dan kehilangan posisi zoom.

Effect overlay dipertahankan karena menangani marker, lingkaran radius, dan pembersihan layer.

### Autocomplete: filter negara dan data kota

`LocationSuggestion` ditambah `city` (`city`, lalu `county`, lalu `state`) dan `address` (`formatted`) hanya untuk prefill alamat, bukan penimpaan. Query autocomplete ditambah `filter=countrycode:id`, dan reverse geocode ditambah `type=city` karena tanpa itu hasil terdekat bisa berupa kode pos desa (mis. "Baros") alih-alih kota administratifnya. Dengan `format=json` Geoapify mengembalikan field datar (`lat`, `lon`, `city`, `formatted`) di akar objek, bukan di dalam `properties` seperti format GeoJSON.

Kolom kota menyimpan penanda "sudah disunting manual" di state komponen; reverse geocode tidak menulis ke kota setelah penanda aktif. Penanda bukan state persisten: memuat ulang formulir mengosongkan semuanya.

### Halaman daftar memakai `AppLayout` yang ada

Sidebar mendapat satu item untuk peran Pemilik Kost. `OwnerLayout` terpisah belum dibuat karena baru ada satu tujuan navigasi; ditunda sampai navigasi pemilik mencapai tiga item.

## Risks / Trade-offs

- **Batas unggahan PHP lebih kecil dari batas aplikasi** → `upload_max_filesize` 2M dan `post_max_size` 8M saat ini membuat foto 3 MB ditolak PHP sebelum validasi berjalan, dengan galat yang membingungkan. Mitigasi: catat sebagai prasyarat lingkungan (minimal 8M/64M) pada tugas penyiapan; ini pengaturan container, bukan perubahan kode.
- **Kota hasil reverse geocode bisa salah wilayah** (mis. kecamatan vs kota) → kota dapat disunting manual, dan penanda suntingan mencegah hasil geocoder menimpanya.
- **Kuota Geoapify terbatas** → reverse geocode hanya dipanggil setelah titik berhenti bergerak (debounce), permintaan lama dibatalkan, dan kegagalan tidak menghalangi pengajuan.
- **Refaktor `StoreAvatar` menyentuh fitur profil (M2.1)** → perilaku foto profil dijaga identik (400×400, `cover()`, kualitas 85) dan tes profil yang ada tetap dipertahankan, hanya nama kelas yang berubah.
- **Foto kost tidak dipotong** → ukuran berkas lebih besar daripada galeri persegi, ditebus dengan batas 1600 px dan kualitas 80.
- **Pengajuan tanpa pilihan kategori foto** → semua foto masuk `bangunan`; pengguna tidak dapat menandai foto kamar sampai halaman ubah kost ada. Dicatat sebagai batasan yang disadari.

## Migration Plan

Tidak ada migrasi basis data. Urutan penerapan: konfigurasi batas unggahan lingkungan → refaktor `StoreImage` → route, policy, request, action → halaman React → `wayfinder:generate`.

Rollback: hapus route dan halaman, kembalikan `StoreAvatar` beserta pemanggilnya. Data yang sudah tersimpan (kost berstatus `menunggu_verifikasi`) tidak terpengaruh dan dapat diproses Admin secara manual.

## Open Questions

Tidak ada. Dua hal yang belum diputuskan sebelumnya — foto maksimal sepuluh berkas dan prasyarat `post_max_size=64M` — sudah dikunci.