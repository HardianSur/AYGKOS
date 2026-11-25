# Design

## Context

Lihat `proposal.md` untuk alasan perubahan. Kondisi saat ini yang membentuk pendekatan:

- Skema domain ada di sembilan migrasi bertanggal `2026_11_17_00000*` (satu tabel per migrasi) dan belum ada data produksi, sehingga tabel bisa dibentuk ulang alih-alih dimigrasi.
- ERD sudah berada di V004 (perubahan `users.avatar_path`); versi dokumen naik per dokumen lewat `release.md` masing-masing.
- `tests/Feature/Schema/DomainSchemaTest.php` dan `DomainModelTest.php` menguji `rooms`, `room_number`, `kost_photos`, dan `facilities` tanpa `scope`; keduanya ikut berubah.
- `resources/js/components/ui/icon.tsx` sudah menerima komponen `LucideIcon`, jadi ikon fasilitas cukup nama ikon yang dipetakan di frontend.
- `config/uploads.php` menjadi satu-satunya sumber batas unggah; `verification_document` sudah 5 MB dengan `jpg/jpeg/png/pdf`.
- Layer wajib: Controller -> FormRequest -> Policy -> Action -> Model. Tidak ada service/repository layer.
- PostgreSQL 16, tes berjalan pada `aygkos_testing`.

## Goals / Non-Goals

**Goals:**

- Bentuk tabel yang mendukung tipe kamar sebagai unit yang diiklankan tanpa membawa entitas unit fisik.
- Aturan visibilitas publik, keunikan, dan konstrain terekam di basis data sehingga jalur seeder dan konsol ikut tertahan.
- Master data terkurasi dengan satu titik pengelolaan (Admin) dan satu titik pemakaian (pemilih di formulir pemilik).

**Non-Goals:**

- Alur reservasi, pembayaran, atau pembaruan otomatis `available_rooms`.
- Halaman publik per tipe kamar (route tetap satu per kost; lihat "Keputusan: kartu per kost").
- Migrasi data lama (belum ada data produksi).
- Penyunting ikon berupa unggah SVG atau pembuat ikon di panel Admin.
- Controller, Action, Policy, dan halaman Inertia untuk master data, tipe kamar, dokumen, dan tampilan publik; semuanya masuk M2.2/M3.2 dan hanya bentuk datanya yang ditetapkan di sini.
- Perubahan pada Fortify, aduan, notifikasi, atau dashboard selain penyesuaian agregasi `rooms` -> `room_types`.

## Decisions

### Skema dibentuk ulang lewat migrasi baru, bukan menyunting migrasi lama

Tambah satu migrasi `2026_11_24_*_restructure_kost_catalog` yang membuang `kost_photos` dan `rooms`, menyunting `kosts` (buang `rules`, `verification_document_path`), dan membuat `room_types`, `photos`, `kost_documents`, `rules`, `specifications`, `room_type_specifications`, `kost_rules`, `room_type_rules`, `room_type_facilities`.

*Alternatif*: menyunting sembilan migrasi awal agar riwayat bersih. Ditolak karena migrasi awal sudah tercatat di git dan dijalankan di lingkungan anggota lain; menyuntingnya membuat `migrate:fresh` beda hasil dengan `migrate` pada salinan yang sudah jalan. `down()` tetap ditulis untuk mengembalikan bentuk V004, walau pemakaiannya hanya untuk pengembangan.

### `available_rooms` disimpan sebagai kolom, bukan dihitung dari tabel kamar

Karena tidak ada entitas kamar, ketersediaan adalah angka yang diisi pemilik. `total_rooms` disimpan terpisah agar dashboard bisa menampilkan terisi = `total_rooms - available_rooms` dan agar constraint `available_rooms <= total_rooms` bisa ditegakkan.

*Alternatif*: satu kolom `available_rooms` saja. Ditolak karena kehilangan total membuat "3 dari 8 kamar tersedia" tidak bisa ditampilkan dan tidak ada batas atas untuk divalidasi.

Risiko basi angka diterima; lihat Risks.

### Kartu hasil pencarian per kost, tipe kamar hanya bagian halaman detail

Query daftar publik memakai `EXISTS`/subquery agregat pada `room_types` aktif alih-alih join yang menghasilkan baris ganda: harga mulai = `MIN(price_per_month)`, kamar tersedia = `SUM(available_rooms)`, foto kartu = foto utama tipe kamar termurah (fallback foto utama bangunan). Pagination dan filter harga memakai `EXISTS` sehingga satu kost tetap satu baris.

*Alternatif*: join + `GROUP BY kosts.id`. Bisa, tetapi `EXISTS` lebih mudah dipadukan dengan filter fasilitas bertingkat dan tidak mengubah jumlah baris saat filter ditambah.

Ordering "harga terendah" memakai agregat yang sama (`MIN`) sehingga urutan konsisten dengan label harga di kartu.

### `photos` satu tabel dengan kategori, ditegakkan lewat check constraint

```
category in (bangunan, fasilitas_umum)  -> room_type_id IS NULL
category in (kamar, kamar_mandi)        -> room_type_id IS NOT NULL
```

Ditegakkan `CHECK`, bukan hanya FormRequest, karena seeder dan perintah konsol juga menulis foto. Kepemilikan `room_types.kost_id = photos.kost_id` tidak bisa dinyatakan CHECK sederhana (butuh subquery) sehingga ditegakkan validasi + Action; alternatifnya trigger, ditolak karena menambah objek basis data yang tidak tercatat di ERD.

Keunikan foto utama memakai indeks unik parsial Postgres:

```sql
create unique index photos_one_primary_kost on photos (kost_id) where is_primary and room_type_id is null;
create unique index photos_one_primary_room_type on photos (room_type_id) where is_primary and room_type_id is not null;
```

*Alternatif*: menandai "foto utama" sebagai kolom di `kosts`/`room_types`. Ditolak karena menambah kolom yang harus dijaga sinkron dengan `photos` dan merusak `on delete cascade` saat foto dihapus. Indeks parsial adalah satu-satunya cara menyatakan "paling banyak satu benar per grup" tanpa trigger.

### Master data: tiga tabel, bukan satu tabel `attributes` dengan tipe

`facilities` (scope + icon), `rules` (scope), `specifications` (input_type + unit) berbeda bentuk dan berbeda relasi, jadi tabel terpisah lebih jujur daripada satu tabel dengan kolom nullable bersyarat.

*Alternatif*: satu tabel `master_data` dengan `kind`. Ditolak karena `specifications` butuh `input_type`/`unit` yang tidak bermakna untuk fasilitas, menghasilkan kolom yang selalu kosong dan validasi bersyarat di semua jalur.

Empat pivot (kost/tipe x fasilitas/aturan) tetap terpisah, bukan polimorfik: konvensi persistence menuntut FK uuid dengan `on delete` yang dinyatakan ERD, dan pivot polimorfik tidak bisa punya FK.

### `icon` menyimpan nama ikon, bukan berkas atau SVG

Validasi ke daftar nama ikon lucide yang diizinkan (satu konstanta array di `config/uploads.php` atau config khusus ikon), frontend memetakan nama ke komponen lewat `icon.tsx`, dengan ikon cadangan bila nama tidak dikenal.

*Alternatif*: unggah SVG. Ditolak karena SVG adalah vektor serangan (script di dalam SVG) dan menambah jalur unggah baru yang harus disanitasi.

### Dokumen izin: `type` sebagai string dengan konfigurasi wajib/opsional

`kost_documents.type` varchar dengan CHECK pada lima nilai; `config/uploads.php` memuat daftar `wajib` (`nib`, `surat_pernyataan`) sehingga kebijakan bisa berubah tanpa migrasi. Keunikan `(kost_id, type)` menjamin satu berkas per jenis. Berkas dokumen izin SHALL hanya menerima PDF (`mimes:pdf`), karena formatnya dokumen legal hasil pindai/terbit sistem, bukan foto; batas 5 MB di kunci `kost_document`.

*Alternatif*: tabel master jenis dokumen. Ditolak karena daftar ini regulatif dan tidak dikelola pengguna; tabel hanya menambah satu layar CRUD tanpa manfaat.

Berkas disimpan di disk privat dan dibaca lewat `app/Support/PrivateFileUrl.php`, sama seperti lampiran aduan.

### Tipe kamar baru `nonaktif`, pengaktifan oleh pemilik

Karena Admin hanya memverifikasi kost, tipe kamar baru tidak melewati Admin. Nilai awal `nonaktif` membuat penambahan tipe tidak langsung tampil, sehingga kesalahan isi tidak bocor ke publik sebelum pemilik meninjaunya. Admin tetap memegang aksi penonaktifan untuk penindakan.

*Alternatif*: tipe baru langsung `aktif`. Ditolak karena kost yang sudah disetujui bisa dipakai menyisipkan konten apa pun tanpa peninjauan; `nonaktif` memberi satu langkah sengaja.

### Scope penegakan di aplikasi, bukan constraint lintas tabel

`facilities.scope` vs tabel pivot yang dipakai, dan `rules.scope` vs `kost_rules`/`room_type_rules`, ditegakkan FormRequest + Action. CHECK lintas tabel butuh trigger; menambah trigger berarti objek basis data di luar ERD yang harus dipelihara pada setiap perubahan skema.

## Risks / Trade-offs

- `available_rooms` basi karena tidak ada alur reservasi -> pemilik memperbarui manual dan dashboard menandai tipe yang belum diperbarui; bila nanti ada booking, kolom ini digantikan hitungan dari tabel pemesanan.
- Penyuntingan migrasi lama dilarang tetapi `down()` tetap ditulis -> `down()` bisa tidak persis mengembalikan V004 bila constraint baru ditambahkan setelahnya; mitigasi: `down()` hanya untuk pengembangan dan diuji dengan `migrate:fresh` di suite tes.
- `room_type_id` yang tidak konsisten dengan `kost_id` bisa lolos bila Action dilewati (impor langsung) -> validasi ganda di FormRequest dan Action, plus query publik yang selalu memfilter `kost_id`; risiko diterima karena jalur tulis hanya lewat formulir.
- Hapus master yang masih dipakai ditolak basis data -> pesan galat harus menyebut pemakai; UI menampilkan daftar pemakai sebelum tombol hapus aktif.
- Halaman detail kost bisa jadi panjang dengan banyak tipe -> tipe kamar dirender berurutan dengan jangkar dan navigasi ringkas; tanpa paginasi karena jumlah tipe realistis kecil.
- Ikon tidak dikenal (nama dihapus dari daftar) -> frontend memakai ikon cadangan dan `site_doctor` tidak menangkapnya; mitigasi: validasi saat simpan dan seeder memakai nama dari konstanta yang sama.
- Rename tabel memutus kode yang masih menyebut `rooms` (`Kost::rooms()`, dashboard, `DomainSchemaTest`) -> seluruh pemakaian dicari lebih dulu (`grep -rn "rooms\b"`) dan diperbarui dalam satu change.

## Migration Plan

1. Buang `kost_photos` dan `rooms`, sunting `kosts`, buat tabel baru, tambahkan indeks unik parsial dan check constraint.
2. Sesuaikan model (`RoomType`, `Photo`, `KostDocument`, `Rule`, `Specification`) dan relasi `Kost`.
3. Sesuaikan seeder master data (`facilities`, `rules`, `specifications`) sebagai data awal; tidak ada data pemilik yang perlu dipindahkan.
4. Perbarui tes skema dan model, lalu jalankan `composer test`.
5. Perbarui dokumen (ERD V005, FR V002, ARCHITECTURE, ROADMAP); belum ada controller/FormRequest/Action kosts yang perlu disesuaikan, sehingga perubahan tampilan menyusul di M2.2/M3.2.

Rollback: `php artisan migrate:rollback --step=1` mengembalikan bentuk V004 (data tipe kamar hilang, sesuai karena belum ada produksi).
