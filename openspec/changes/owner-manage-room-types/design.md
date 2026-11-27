# Design

## Context

Lihat `proposal.md` untuk alasan perubahan. Keadaan yang membentuk rancangan:

- `room_types` sudah ada lengkap dengan CHECK constraint (`price_per_month > 0`, `total_rooms > 0`, `available_rooms between 0 and total_rooms`), index `(kost_id, status)`, `deleted_at`, dan `unique (kost_id, name)`.
- `photos` sudah dijaga CHECK yang memaksa kategori `kamar` dan `kamar_mandi` memiliki `room_type_id`, dan dua partial unique index untuk satu foto utama per kost dan per tipe kamar.
- Master `facilities`, `rules`, `specifications` hanya tersedia lewat seeder; belum ada layar Admin (FR-ADM-MST-03 belum dikerjakan).
- `SubmitKost` sudah memakai pola berkas-dulu-baris-kemudian dengan pembersihan berkas saat gagal. Pola ini dipakai ulang.
- `KostPolicy` hanya punya `viewAny` dan `create` dengan penjaga `is_active`, `isPemilikKost()`, `hasVerifiedIdentity()`.
- Grup `owner.` di `routes/web.php` sudah memuat `auth`, `verified`, `role:pemilik_kost`.
- Belum ada halaman `GET /owner/kosts/{kost}`; daftar kost belum punya tujuan tautan selain halaman tipe kamar.

## Goals / Non-Goals

**Goals:**

- CRUD tipe kamar berjalan penuh lewat Inertia tanpa endpoint JSON terpisah.
- Kepemilikan diperiksa di server pada setiap aksi, bukan hanya di antarmuka.
- Penghapusan lunak tetap memampukan nama dipakai ulang tanpa mengubah tipe kolom.
- Penyimpanan tipe kamar beserta pivot dan fotonya bersifat atomik terhadap berkas dan basis data.

**Non-Goals:**

- Tidak menambah master data atau layar Admin.
- Tidak mengubah alur pengajuan kost, `SubmitKost`, maupun `StoreKostRequest`.
- Tidak menambah endpoint publik, pencarian, atau halaman detail kost.
- Tidak menangani foto `bangunan`/`fasilitas_umum` dari formulir tipe kamar.

## Decisions

### Route bersarang di bawah kost, bukan halaman detail kost baru

Empat jalur di grup `owner.` yang sudah ada:

```
GET    /owner/kosts/{kost}/types                    owner.kosts.types.index
GET    /owner/kosts/{kost}/types/create             owner.kosts.types.create
POST   /owner/kosts/{kost}/types                    owner.kosts.types.store
GET    /owner/kosts/{kost}/types/{roomType}/edit    owner.kosts.types.edit
PUT    /owner/kosts/{kost}/types/{roomType}         owner.kosts.types.update
PATCH  /owner/kosts/{kost}/types/{roomType}/status  owner.kosts.types.status
DELETE /owner/kosts/{kost}/types/{roomType}         owner.kosts.types.destroy
```

`{kost}` memakai route model binding biasa (kost terhapus lunak otomatis 404), `{roomType}` memakai `RoomType` juga. Model binding tidak memeriksa kepemilikan, jadi `RoomTypePolicy` tetap dipanggil di setiap method.

**Alternatif yang ditolak**: menaruh CRUD kamar di halaman detail kost `/owner/kosts/{kost}`. Halaman itu belum ada dan belongs to FR-KOS-03/04/05 yang di luar cakupan; menambahnya sekarang memperbesar perubahan sekaligus menyerempet change Admin yang akan membuat `/admin/kosts/{kost}`.

### Otorisasi lewat policy yang menelusuri rantai kepemilikan

`RoomTypePolicy` dengan `viewAny(User, Kost)`, `create(User, Kost)`, `update(User, RoomType)`, `delete(User, RoomType)`, dan `changeStatus(User, RoomType)` — semuanya memeriksa: akun aktif, berperan `pemilik_kost`, identitas terverifikasi, dan `kost.owner_id === $user->id`. Pemeriksaan metode yang menerima `Kost` memakai `$kost->owner_id`; yang menerima `RoomType` memakai `$roomType->kost->owner_id`.

Dependency `{roomType}` juga divalidasi milik `{kost}` pada jalur: bila `$roomType->kost_id !== $kost->id`, dijawab 404, bukan 403, supaya tidak membocorkan keberadaan tipe kamar kost lain.

**Alternatif yang ditolak**: middleware atau scope query di controller. Policy menempatkan aturan di satu tempat dan sudah menjadi konvensi proyek (`KostPolicy`).

### Status diubah oleh Action terpisah, bukan dari formulir

`UpdateRoomTypeStatus(user, RoomType, string $status)` menerima `aktif` atau `nonaktif` dan hanya menulis kolom `status`. `CreateRoomType` dan `UpdateRoomType` mengabaikan kunci `status` dari data masukan (form request tidak memvalidasinya sebagai field, dan Action menetapkan `nonaktif` pada pembuatan).

**Alternatif yang ditolak**: satu kolom `status` di formulir ubah. Bertentangan dengan requirement "status diubah terpisah" dan membuat penyuntingan data diam-diam mempublikasikan kamar.

### Empat Action: Create, Update, Status, Delete

| Action | Tanggung jawab |
|---|---|
| `CreateRoomType` | tulis berkas foto, lalu dalam transaksi: buat `room_types` berstatus `nonaktif`, `sync` fasilitas & peraturan, tulis `room_type_specifications`, buat baris `photos` dengan penandaan utama |
| `UpdateRoomType` | hitung berkas baru, lalu dalam transaksi: perbarui kolom, `sync` pivot, `sync` spesifikasi, tambah/hapus foto, tetapkan foto utama; sesudah commit hapus berkas yang dilepas |
| `UpdateRoomTypeStatus` | satu `update(['status' => ...])` dengan penjagaan status awal |
| `DeleteRoomType` | baca path foto, `delete()` (soft delete), hapus berkas; baris `photos` tetap tersimpan |

Semua Action memakai `DB::transaction` dan `try/catch` dengan pembersihan berkas tertulis, meniru `SubmitKost`. Penggantian pivot memakai `sync()` sehingga pasangan yang dilepas benar-benar hilang (bukan hanya bertambah).

### Penandaan foto utama diselesaikan di dalam transaksi

Karena `photos_one_primary_room_type` adalah unique partial index, menandai utama baru sambil melepas yang lama harus berurutan: `update(['is_primary' => false])` untuk seluruh foto tipe kamar itu, lalu set satu baris. Pola ini sama dengan yang dibutuhkan `SubmitKost` bila nanti menandai foto utama. Foto tanpa penanda utama sah (tidak ada constraint yang mewajibkan).

### Migrasi index unik menjadi parsial

Satu migrasi `replace_room_types_unique_name_with_partial_index`:

```sql
alter table room_types drop constraint room_types_kost_id_name_unique;
create unique index room_types_kost_id_name_active_unique
  on room_types (kost_id, name) where deleted_at is null;
```

Memakai `DB::statement` seperti migrasi `photos` yang sudah memakai partial index. PostgreSQL mendukungnya dan seluruh tes berjalan di pgsql, jadi tidak ada cabang sqlite.

`down()` mengembalikan index penuh; migrasi gagal bila ada nama ganda di antara baris terhapus, dan itu memang diinginkan (rollback menuntut data bersih).

### Berkas foto memakai `StoreImage::scaled` yang sudah ada

`StoreImage::scaled()` menyimpan WebP maksimal 1600 px di direktori `kosts/` pada disk `public`, tepat seperti foto kost. Tidak ada parameter direktori baru: foto kamar dan foto kost boleh berbagi folder karena path selalu disimpan per baris. Batas unggahan mengikuti `config/uploads.php` `kost_photo` (3 MB, jpg/jpeg/png/webp), sama seperti pengajuan kost.

### Frontend: tiga halaman, satu formulir bersama

```
resources/js/pages/owner/kosts/types/index.tsx   daftar + tombol aktif/nonaktif/hapus
resources/js/pages/owner/kosts/types/create.tsx
resources/js/pages/owner/kosts/types/edit.tsx
resources/js/components/owner/room-type-form/    formulir bersama + bagian foto/spesifikasi
```

Formulir tipe kamar cukup besar (foto, fasilitas, peraturan, spesifikasi bertipe) sehingga dialog dalam daftar akan terlalu padat. Halaman terpisah juga membuat `useForm` dan unggahan berkas mengikuti pola `owner/kosts/create.tsx` yang sudah ada. Formulir edit memakai helper `.form()` dari Wayfinder.

Daftar tipe kamar menampilkan nama, harga, jumlah/tersedia, status, dan tombol ubah, aktif/nonaktif, serta hapus dengan dialog konfirmasi (pola sama dengan dialog konfirmasi pengajuan kost). Tautan ke halaman ini ditambahkan pada kartu kost di `resources/js/pages/owner/kosts/index.tsx`.

### Seeder demo berdiri sendiri

`database/seeders/DemoKostSeeder.php` dipanggil dari `DatabaseSeeder`, memakai `pemilik@example.com` yang sudah dibuat, membuat satu kost `disetujui` dengan `reviewed_by` (akun admin hasil seeder) dan `reviewed_at` terisi, lalu dua tipe kamar: satu `aktif`, satu `nonaktif`, masing-masing dengan fasilitas `kamar`, peraturan `kamar`, dan nilai spesifikasi dari master. Tanpa foto karena berkas gambar nyata tidak tersedia di seeder.

Mengikuti pola `DatabaseSeeder` sekarang: tidak idempoten. Menjalankan `db:seed` dua kali menggandakan baris; itu batas yang diterima untuk data pengembangan.

### ERD naik ke V006

Indeks unik `room_types` berubah, jadi `docs/ERD/erd.md` naik versi: V005 dipindahkan ke `logs/V005-erd.md`, berkas utama menjadi V006 dengan baris constraint yang diperbarui, dan satu baris riwayat ditambahkan ke `docs/ERD/release.md`. FR tidak berubah.

## Risks / Trade-offs

- **Nama tipe kamar tetap bisa ganda antar-baris terhapus** → diinginkan: index parsial hanya menjaga baris aktif, dan pencarian nama selalu menyaring `deleted_at is null`.
- **Soft delete menyimpan baris `photos` yatim** → berkasnya dihapus dan baris tidak pernah dibaca karena relasi `photos` hanya diakses lewat `RoomType` yang sudah terhapus lunak. Alternatif hard delete ditolak karena melanggar `persistence-conventions` dan ARCHITECTURE.
- **Tidak ada constraint DB yang menjamin `room_type_id` berasal dari kost yang sama** → dijaga di lapisan aplikasi (route binding + policy) dan tes.
- **Unggahan berkas di dalam transaksi** → berkas tidak transaksional; mitigasi pembersihan di `catch` seperti `SubmitKost`, dengan risiko sisa berkas bila proses mati di tengah. Diterima karena pola yang sama sudah dipakai.
- **`Storage::fake('public')` di tes** → tes foto memakai fake disk; seeder tidak menulis berkas.
- **Route `/owner/kosts/{kost}/types` bertabrakan dengan rencana Admin** → tidak; Admin memakai prefix `/admin`.
- **Otorisasi foto lewat `PhotoPolicy` tidak ada** → foto hanya diubah lewat endpoint tipe kamar yang sudah dijaga policy-nya, jadi tidak ada jalur langsung.

## Migration Plan

1. Jalankan migrasi index parsial sebelum kode dirilis (tidak ada data lama yang terdampak: baris hidup tetap unik).
2. Deploy kode (policy, Action, controller, route, halaman).
3. `php artisan wayfinder:generate` untuk helper route frontend.
4. `php artisan db:seed --class=DemoKostSeeder` pada basis data pengembangan.
5. Rollback: `php artisan migrate:rollback` mengembalikan index penuh setelah hapus/pulihkan baris nama ganda; kode lama tidak menyentuh tabel baru.

## Open Questions

Tidak ada. Semua keputusan yang memengaruhi spec, pendekatan, dan daftar tugas sudah ditetapkan di atas.