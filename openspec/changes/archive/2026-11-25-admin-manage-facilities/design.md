# Design

## Context

Lihat `proposal.md` untuk motivasi. Keadaan kode yang membentuk rancangan ini:

- Tabel `facilities` sudah ada dengan `name` (unique, case-sensitive), `scope` (check `umum`/`kamar`), dan `icon` (check lewat aplikasi, bukan DB). `Facility` memakai `HasUuids` dan `$timestamps = false`.
- `kost_facilities.facility_id` dan `room_type_facilities.facility_id` sudah memakai `restrictOnDelete`, jadi basis data sudah menahan penghapusan. `Facility` hanya punya relasi `kosts()`; relasi ke `RoomType` belum ada.
- `App\Support\FacilityIcons::ALL` sudah menjadi daftar otoritatif 21 nama ikon lucide, dan `FacilitySeeder` memvalidasi terhadapnya. Belum ada pemetaan nama ke komponen di frontend.
- Data saat ini: 15 fasilitas, tidak ada duplikat `lower(name)`, `kost_facilities` dan `room_type_facilities` kosong.
- Belum ada controller, request, action, route, atau halaman Admin. `EnsureRole`, grup route `/admin`, dan `AdminLayout` menjadi prasyarat terpisah (`admin-access-gate`).
- Konvensi layer `docs/ARCHITECTURE.md`: Controller tipis → Form Request → Action → Model, tanpa layer repository. Pola feedback sukses memakai `Inertia::flash('toast', ...)` seperti `ProfileController`.

## Goals / Non-Goals

**Goals:**

- Satu halaman Admin yang menampilkan seluruh master fasilitas beserta dampak pemakaiannya, tanpa perlu membuka halaman lain.
- Aturan integritas (nama unik case-insensitive, ruang lingkup terkunci, hapus ditolak) ditegakkan di server dan dicerminkan di UI, bukan hanya disembunyikan.
- Satu peta nama ikon → komponen yang dapat dipakai ulang oleh form kost dan kartu katalog pada M2.2/M3.2.

**Non-Goals:**

- Pagination dan pencarian: 15 baris sekarang, dan halaman ini hanya dibuka Admin. Ditambahkan bila melewati beberapa ratus baris.
- CRUD master peraturan dan spesifikasi.
- Pemilih fasilitas pada form kost atau tipe kamar.
- Normalisasi nama massal atau penggabungan fasilitas duplikat yang sudah ada (tidak ada duplikat).

## Decisions

### Keunikan nama lewat unique index `lower(name)`, bukan kolom baru

Migrasi menambahkan `create unique index facilities_name_lower_unique on facilities (lower(name))` dan membuang unique lama pada `name` agar tidak ada dua aturan yang bersaing. Alternatif: kolom `name_normalized` terisi — ditolak karena menambah kolom dan sinkronisasi yang harus dijaga di model. Alternatif: `citext` — ditolak karena mengubah tipe kolom dan bergantung pada extension.

Nama dinormalkan di Form Request (`trim`, spasi beruntun dirapatkan) sebelum validasi `unique`, dengan `Rule::unique('facilities')->whereRaw('lower(name) = lower(?)')` yang mengabaikan baris sendiri saat ubah. Aturan `unique` di aplikasi memberi pesan galat yang menempel pada kolom; unique index menjadi lapis terakhir jika ada dua permintaan bersamaan.

### Ruang lingkup dikunci di Action, bukan di Form Request

`ManageFacility::update()` memeriksa apakah `scope` berubah dan baris dipakai; bila ya, ia melempar `ValidationException` pada kunci `scope` agar galat muncul di kolom yang tepat. Pemeriksaan ada di Action karena membutuhkan query pemakaian yang sama dengan yang dipakai `delete()`, dan karena Form Request tidak boleh menulis.

Alternatif: `Rule::prohibitedIf` di Form Request — ditolak karena memisahkan aturan dari Action yang menegakkannya dan menggandakan query pemakaian.

### Penghitungan pemakai: dua `withCount`, bukan view atau kolom cache

`FacilityController@index` memakai `Facility::withCount(['kosts', 'roomTypes'])` lalu mengirim `kost_count` dan `roomType_count`. Query `withCount` memakai subquery `count(*)` per relasi, aman untuk 15–1000 baris.

Alternatif: kolom `usage_count` yang diperbarui trigger — ditolak karena menyimpan turunan yang bisa melenceng dan menambah trigger di luar konvensi proyek. Alternatif: satu query group-by ke dua pivot lalu digabung di PHP — ditolak karena dua peta harus digabung manual untuk hasil yang sama.

Daftar nama pemakai (bukan hanya jumlah) dibutuhkan saat penolakan; `ManageFacility` mengambil `kosts()->pluck('name')` dan `roomTypes()->pluck('name')` hanya pada jalur penolakan, sehingga halaman daftar tetap murah.

### Kendali hapus nonaktif di UI, 409 di server

UI menonaktifkan tombol hapus saat `kost_count + roomType_count > 0` dengan `title` berisi alasannya. Server tetap memeriksa ulang dan mengembalikan galat validasi pada kunci `facility`, sehingga status balasan konsisten dengan form lain di aplikasi (419/422 lewat Inertia) dan tidak menambah jalur 409 terpisah yang belum dipakai di proyek ini.

Konsekuensi: klien yang memanggil `DELETE` langsung menerima 302 kembali dengan galat sesi, bukan 409. Ini diterima karena satu-satunya pemanggil adalah halaman Inertia.

### Peta ikon di `facility-icon.tsx`, statis dan eksplisit

Satu objek `Record<FacilityIconName, LucideIcon>` mengimpor 21 komponen lucide secara eksplisit, dengan `FacilityIconName` diturunkan dari daftar yang dikirim server (`iconNames` prop) atau dari union literal yang disalin dari `FacilityIcons::ALL`.

Alternatif: `import * as icons from 'lucide-react'` + indeks dinamis — ditolak karena menggagalkan tree-shaking dan mengirim seluruh set ikon ke bundel. Alternatif: nama dinamis ke komponen generic — ditolak karena ikon yang tidak dikenal akan tampil kosong tanpa galat.

Untuk mencegah daftar PHP dan TS melenceng, `FacilityController@index` mengirim `iconNames` dari `FacilityIcons::names()`, dan formulir merender pilihan dari prop itu. Peta TS tetap harus memuat setiap nama; `npm run types:check` gagal bila ada nama yang tidak dipetakan, karena tipe peta adalah `Record<FacilityIconName, ...>` dengan union dari daftar yang sama.

### Form Request terpisah untuk simpan dan ubah

`StoreFacilityRequest` dan `UpdateFacilityRequest` berbagi aturan di satu trait privat atau metode statis, dengan perbedaan pada aturan `unique` yang mengabaikan ID sendiri. Alternatif: satu request dengan `sometimes` — ditolak karena mengaburkan aturan wajib dan membuat pesan galat sulit dibedakan.

### Otorisasi di route, bukan Policy

Tidak ada `FacilityPolicy`: master data tidak dimiliki per pengguna, dan satu-satunya aturan adalah peran. Gerbang `role:admin` pada grup route (dari `admin-access-gate`) adalah batasnya, sesuai `docs/ARCHITECTURE.md` yang menyebut Policy untuk kepemilikan dan peran per model — model master tidak punya kepemilikan.

## Risks / Trade-offs

- **Gerbang admin belum ada** → change ini tidak dapat diimplementasikan sampai `admin-access-gate` selesai. Task pertama menandai prasyaratnya, dan tes otorisasi peran bergantung padanya.
- **Dua sumber daftar ikon (PHP dan TS)** → `iconNames` prop + `Record` bertipe union membuat ketidakcocokan terdeteksi saat `types:check`, bukan saat runtime. Penambahan ikon baru tetap memerlukan dua suntingan; ini diterima karena daftar berubah jarang.
- **Unique index pada `lower(name)` tidak dapat dipakai langsung oleh `Rule::unique` bawaan** → dipakai `whereRaw`, sehingga pesan galat default Laravel tidak otomatis menyebut nama yang bentrok. Pesan kustom ditulis di request.
- **Migrasi unique index gagal bila ternyata ada duplikat** → sudah diperiksa: nol duplikat pada data saat ini. Bila lingkungan lain berbeda, migrasi gagal dengan galat Postgres yang menyebut pasangan duplikatnya, dan pembersihan menjadi langkah manual.
- **Daftar tanpa pagination** → masih nyaman pada 15–100 baris. Bila master tumbuh karena impor massal, pagination ditambahkan di change terpisah.

## Migration Plan

1. Jalankan migrasi unique index; rollback membuang index dan mengembalikan unique lama pada `name`.
2. Deploy route, controller, action, dan halaman. Tidak ada perubahan data dan tidak ada backfill.
3. Rollback aplikasi cukup mengembalikan kode; index `lower(name)` tetap valid untuk kode lama karena lebih ketat, bukan lebih longgar.

## Open Questions

Tidak ada.