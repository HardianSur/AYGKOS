# Design

## Context

Lihat `proposal.md`. Keadaan yang membentuk rancangan:

- `rules`: `id` uuid, `name` varchar(100) unik, `scope` dengan check constraint, tanpa kolom `icon`, tanpa timestamp. `kost_rules` dan `room_type_rules` memakai `restrictOnDelete` pada `rule_id`, jadi penghapusan yang masih dipakai sudah ditolak basis data; aplikasi harus menerjemahkannya menjadi pesan yang menyebut pemakainya.
- Pola master fasilitas sudah ada dan dipakai sebagai acuan bentuk: `FacilityController`, trait `ValidatesFacility`, `ManageFacility`, `FacilityIcons` (allowlist 21 nama), `facility-icon.tsx` (peta statis), dan halaman `admin/facilities/index.tsx` setebal 485 baris.
- `FacilityIcons` tidak dapat dipakai ulang untuk aturan: 21 ikonnya (wifi, kasur, lemari) tidak mewakili "dilarang merokok" atau "tamu dilarang menginap".
- `lucide-react` 0.475 memasang 1760 ikon dengan `DynamicIcon` dan `dynamicIconImports`. Metadata tag/kata kunci tidak disertakan; satu-satunya data pencarian adalah slug nama ikon, semuanya tanpa spasi.
- `config/inertia.php` menyalakan SSR. `DynamicIcon` memakai `useEffect`, sehingga render server menghasilkan `null`.
- `Rule` belum punya relasi `roomTypes()`, sementara penghitungan pemakai dan pesan penolakan membutuhkannya.

## Goals / Non-Goals

**Goals:**

- Halaman `/admin/rules` dengan bentuk tabel, dialog, dan alur yang sejalan dengan halaman fasilitas, tetapi pemilih ikonnya berbeda: berbasis katalog dinamis dengan pencarian.
- Pencarian ikon deterministik dan mudah diuji: satu fungsi murni, tanpa dependensi baru.
- Berkas halaman tetap ringkas; logika pemilih, tabel, formulir, dan ikon tinggal di komponen masing-masing.
- Baris aturan lama ikut terisi ikon tanpa migrasi data manual.

**Non-Goals:**

- Tidak menyentuh `FacilityIcons`, `facility-icon.tsx`, atau dialog ikon fasilitas. Skema fasilitas dibiarkan apa adanya.
- Tidak menambah daftar 1760 nama ikon ke PHP, tidak membuat skrip generate, dan tidak menambah dependensi npm/composer.
- Tidak mengubah master spesifikasi, tipe kamar, atau alur publikasi kost.
- Tidak menambah dukungan pencarian bahasa Indonesia; UI memberi petunjuk bahasa Inggris.

## Decisions

### Katalog ikon dinamis, bukan allowlist PHP

`rule-icon.tsx` memakai `DynamicIcon` dari `lucide-react/dynamic`, memuat chunk per ikon saat dipakai. Alternatif allowlist statis seperti `FacilityIcons` ditolak karena memaksa memilih ±20 ikon yang tidak mewakili aturan, dan bertentangan dengan permintaan Admin agar seluruh katalog tersedia.

`dynamicIconImports` berukuran ~101 KB mentah / 12 KB gzip, dan hanya ikon yang benar-benar dirender yang mengunduh chunk (~1 KB per ikon). Modul itu tetap ikut bundel halaman Admin, bukan bundel global, karena hanya halaman aturan yang mengimpornya.

### Validasi ikon berupa bentuk slug, bukan daftar nama

Validasi: `required|string|max:64|regex:/^[a-z0-9]+(-[a-z0-9]+)*$/`. Alternatif `Rule::in(<1760 nama>)` ditolak karena butuh file daftar hasil generate dari `node_modules`, langkah regenerasi setiap kali lucide naik versi, dan `npm install` menjadi prasyarat `composer test`. Konsekuensi diterima: slug yang salah ketik tersimpan, tetapi komponen ikon mengembalikan `null` sehingga halaman tetap tampil. RegEx yang sama dipakai sebagai satu sumber kebenaran di trait `ValidatesRule`.

### Pencarian awalan-kata pada slug dalam satu fungsi murni

`resources/js/components/icon-picker/search.ts` mengekspor `searchIcons(names, query, limit)`. Aturan:

1. `query` di-`toLowerCase`, dipecah pada spasi dan tanda hubung, token kosong dibuang.
2. Slug dipecah pada `-`; token query cocok bila menjadi awalan salah satu kata slug.
3. Semua token harus cocok (AND).
4. Hasil dipotong `limit` (20).

Alternatif substring bebas (`includes`) ditolak: query satu huruf seperti `"x"` memunculkan terlalu banyak ikon tidak relevan. Awalan-kata tetap menerima `"paw print"`, `"paw-print"`, dan `"PAW"`.

Tanpa query, pemilih menampilkan 20 ikon kurasi tetap (bukan 20 pertama alfabet, yang berisi `a-arrow-down` dan sejenisnya):

```
paw-print      cigarette-off   clock           calendar-clock
user           users           bed-single      bed-double
cooking-pot    door-closed     volume-off      moon
baby           dog             cat             wine
music          party-popper    smartphone      spray-can
```

Daftar 20 ini tinggal di `resources/js/components/icon-picker/featured-icons.ts` dan hanya berlaku untuk tampilan awal; seluruh 1760 nama tetap dapat dicari. Karena slug berasal dari `dynamicIconImports`, nama kurasi yang hilang setelah upgrade lucide cukup menghasilkan ikon kosong, bukan galat build.

### Ikon disimpan sebagai slug pada `rules.icon`

Migrasi menambah `icon` varchar(64) NOT NULL dengan nilai awal `circle-alert` agar baris lama langsung sah, lalu **tidak** memasang unique index pada `icon` (banyak aturan boleh berbagi ikon). Unique index yang ditambahkan adalah `lower(name)`, menyamakan penegakan keunikan dengan master fasilitas dan menggantikan constraint `name` unik yang peka huruf besar/kecil. Migrasi harus menghapus indeks unik lama `rules_name_unique` sebelum membuat indeks `lower(name)`.

### Seeder memakai `updateOrCreate`

`RuleSeeder` diubah dari `firstOrCreate` ke `updateOrCreate` berkunci `name`, dengan `icon` pada tiap baris, sehingga basis data yang sudah berisi tujuh aturan ikut terisi tanpa duplikasi:

| Aturan | Ikon |
|---|---|
| Tidak boleh membawa hewan peliharaan | `paw-print` |
| Dilarang merokok di dalam kamar | `cigarette-off` |
| Jam tamu maksimal 22.00 | `clock` |
| Pembayaran sewa paling lambat tanggal 5 | `calendar-clock` |
| Hanya untuk satu orang | `user` |
| Dilarang memasak di kamar | `cooking-pot` |
| Tamu dilarang menginap | `moon` |

### `Rule` mendapat `roomTypes()` dan `icon` fillable

Relasi `belongsToMany(RoomType::class, 'room_type_rules')` ditambahkan agar `withCount(['kosts','roomTypes'])` dan pesan pemakai bekerja seperti `Facility`. `ManageRule` menyalin bentuk `ManageFacility`, hanya mengganti label dan pesan.

### Halaman dipecah menjadi komponen kecil

`admin/facilities/index.tsx` setebal 485 baris dijadikan pelajaran; halaman aturan dibagi:

```
resources/js/pages/admin/rules/index.tsx          <-- tipis: props + susun bagian
resources/js/components/admin/rule-table.tsx      <-- tabel, badge ruang lingkup, tombol
resources/js/components/admin/rule-form-dialog.tsx<-- dialog tambah/ubah, pakai IconPicker
resources/js/components/rule-icon.tsx             <-- DynamicIcon + fallback null
resources/js/components/icon-picker/index.tsx     <-- dialog: input cari + grid
resources/js/components/icon-picker/icon-grid.tsx <-- grid 20 sel
resources/js/components/icon-picker/search.ts     <-- fungsi murni (diuji)
resources/js/components/icon-picker/featured-icons.ts
```

Setiap berkas ditargetkan di bawah ~150 baris. `IconPicker` adalah komponen generik (menerima `value`, `onChange`, `open`), bukan khusus aturan, sehingga nanti dapat dipakai master lain tanpa menyentuh kode fasilitas.

### SSR: grid kosong sekejap diterima

`DynamicIcon` mengisi setelah hydrate. Karena halaman ini hanya untuk Admin dan muncul sekali per kunjungan, grid kosong sekejap diterima. Alternatif (mematikan SSR untuk halaman ini lewat `Inertia::render(...)->withSSR(false)` atau preload lambat) tidak diambil agar tidak menambah jalur khusus.

### Tata letak dialog mengikuti pola fasilitas, tetapi berbeda isi

Struktur: input cari di atas grid 20 sel, tiap sel tombol dengan ikon dan label slug, state terpilih ditandai cincin. Fasilitas tidak punya input cari dan menampilkan seluruh allowlist; versi aturan menampilkan maksimal 20 dan menambahkan petunjuk "Cari dalam bahasa Inggris (mis. cigarette, clock)" saat hasil kosong.

## Risks / Trade-offs

- `dynamicIconImports` (~101 KB mentah) masuk bundel halaman Admin → hanya satu route yang mengimpor; dipantau lewat `npm run build`.
- Grid kosong pada render server → diterima; hanya halaman Admin.
- Slug salah ketik tersimpan tanpa peringatan → ikon kosong, halaman tetap tampil; pesan bantuan di dialog menjelaskan pencarian.
- Unique `lower(name)` gagal bila sudah ada dua baris berbeda kapitalisasi → seeder hari ini unik; migrasi memakai indeks ekspresi setelah memastikan tidak ada duplikat, dan test menutup kasus ini.
- Nama ikon kurasi dapat hilang saat lucide di-upgrade → ikon kosong, bukan galat; daftar kurasi dikurasi ulang manual bila perlu.
- `Rule::roomTypes()` baru, sementara `room_type_rules` sudah ada → tidak ada perubahan skema pivot.

## Migration Plan

Satu migrasi dalam satu arah; rollback hanya `dropColumn('icon')` dan `dropUnique` indeks `lower(name)`, lalu mengembalikan `rules_name_unique`.

1. `php artisan migrate` menambah `icon` (default `circle-alert`), membuang `rules_name_unique`, membuat unique index `lower(name)`.
2. `php artisan db:seed --class=RuleSeeder` mengisi ikon tujuh aturan.
3. Rilis: tidak ada data yang dihapus; aplikasi lama tetap berjalan karena kolom baru punya default.
4. Rollback: batalkan migrasi sebelum mengembalikan kode lama, agar `Rule` tidak membaca kolom yang hilang.

## Open Questions

Tidak ada.