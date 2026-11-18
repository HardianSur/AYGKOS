# Design

## Context

Laravel 13 + Fortify, `HasUuids` tersedia di framework. Keadaan saat ini yang membentuk desain: `users` masih memakai `id` bigint dan satu-satunya tabel domain adalah `users`; `User` adalah `Authenticatable` dengan `MustVerifyEmail`; `UserFactory`, `CreateNewUser`, `ProfileValidationRules`, dan `resources/js/types/auth.ts` semuanya mengasumsikan `id` integer. `phpunit.xml` memakai `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, dan `RefreshDatabase` dipasang global untuk suite Feature lewat `tests/Pest.php`. Basis data `aygkos` dan `aygkos_testing` sudah ada di PostgreSQL 16 tetapi masih kosong. Lihat `proposal.md` untuk motivasi.

## Goals / Non-Goals

**Goals:**

- Satu berkas tes skema yang menegakkan keputusan ERD tanpa bergantung pada pembacaan kode sumber: PK uuid, PK komposit, aksi referensial, kebijakan timestamp, constraint check, keunikan.
- Model yang siap dipakai fitur tanpa penimpaan nama tabel, dengan relasi dan casting yang benar.
- Registrasi dan seluruh tes yang sudah ada tetap hijau setelah `users.id` berubah tipe.

**Non-Goals:**

- Kebijakan otorisasi, Form Request domain, controller, route, dan halaman Inertia.
- Logika bisnis FR: pengiriman OTP, persetujuan kost, penanganan aduan. Change ini hanya menyediakan tempat penyimpanannya.
- Enum PHP atau kolom enum basis data; nilai enumerasi ditegakkan pada validasi fitur.
- Index spasial atau PostGIS.

## Decisions

### 1. `HasUuids` pada setiap model, bukan default basis data

Seluruh model domain memakai trait `HasUuids`, kolom `id` dideklarasikan `uuid('id')->primary()`. Laravel 13 membangkitkan uuid7 (terurut waktu), sehingga PK tetap ramah indeks B-tree PostgreSQL.

**Alternatif ditolak:** `gen_random_uuid()` sebagai default kolom. Alasan: mengikat skema ke PostgreSQL dan tidak memberi `id` sebelum insert, sehingga relasi harus disimpan setelah pembuatan baris. `HasUuids` juga portabel bila proyek kembali ke SQLite.

### 2. `users` disunting di tempat, bukan migration baru

`0001_01_01_000000_create_users_table.php` diubah langsung: `id` menjadi `uuid`, `name` dipertahankan (menjadi nama lengkap), `role_id` ditambahkan, `sessions.user_id` menjadi `foreignUuid`. Migration terpisah `add_role_id_to_users` ditolak karena belum ada data dan urutan foreign key `users` → `roles` menjadi jelas dalam satu berkas: tabel `roles` dibuat lebih dulu dalam migration yang sama.

**Alternatif ditolak:** membiarkan `users` ber-id bigint dan hanya menambah kolom. Alasan: ERD menuntut seluruh identitas seragam, dan `sessions.user_id` akan tetap bigint sehingga relasi akun-sesi menjadi tidak konsisten.

`roles` dibuat di dalam migration `users` karena `users.role_id` NOT NULL membutuhkannya. `RoleSeeder` tetap terpisah dan bertanggung jawab atas isinya.

### 3. Urutan migration mengikuti arah foreign key

```
0001_01_01_000000_create_users_table          roles + users + password_reset_tokens + sessions
2026_11_17_000001_create_whatsapp_verifications_table
2026_11_17_000002_create_kosts_table
2026_11_17_000003_create_kost_photos_table
2026_11_17_000004_create_facilities_table
2026_11_17_000005_create_kost_facilities_table
2026_11_17_000006_create_rooms_table
2026_11_17_000007_create_complaints_table
2026_11_17_000008_create_complaint_attachments_table
2026_11_17_000009_create_notifications_table
```

Sepuluh berkas, satu tabel per berkas (kecuali berkas pertama yang menjadi milik framework). Satu berkas gabungan ditolak: pesan galat migrasi tidak menunjuk tabelnya, dan rollback parsial menjadi tidak mungkin.

### 4. Timestamp ditangani per model, bukan per migration

Tiga pola, masing-masing dengan pasangan migration dan model:

| Tabel | Migration | Model |
|---|---|---|
| `whatsapp_verifications`, `notifications` | `->timestamp('created_at')` tanpa `updated_at` | `const UPDATED_AT = null;` |
| `facilities`, `kost_photos` | tanpa kolom waktu | `public $timestamps = false;` |
| `kosts`, `rooms` | `->timestamps()` + `->softDeletes()` | `use SoftDeletes;` |
| sisanya | `->timestamps()` | bawaan |

`$timestamps = false` pada `facilities` dan `kost_photos` membuat Eloquent tidak menulis kolom yang tidak ada. Alternatif "biarkan Eloquent menulis lalu gagal" jelas tidak boleh. Konsekuensi yang diterima: `facility` adalah data master tanpa jejak siapa menambahkannya — sesuai keputusan pemilik dokumen, dan seeder fasilitas menjadi satu-satunya jalur penambahan pada praktiknya.

### 5. `kost_facilities` tanpa model, relasi langsung antar model

`$table->primary(['kost_id', 'facility_id'])`, keduanya `foreignUuid(...)->constrained()->cascadeOnDelete()`. Relasi dideklarasikan sebagai `Kost::belongsToMany(Facility::class)` dan sebaliknya; Laravel menemukan tabel pivotnya dari urutan abjad nama model (`Facility` sebelum `Kost`). Untuk menghindari ketergantungan pada tebakan itu, nama tabel pivot ditulis eksplisit: `belongsToMany(Facility::class, 'kost_facilities')`.

Model `KostFacility` ditolak karena tidak ada kolom di luar dua foreign key, sehingga tidak ada atribut yang perlu dibungkus objek.

### 6. Constraint check lewat `DB::statement` di akhir migration

Laravel 13 hanya menyediakan `rawIndex`, tidak ada `Blueprint::check()`. Pola yang dipakai, satu `up()` dua langkah:

```php
Schema::create('kosts', function (Blueprint $table) { /* ... */ });

DB::statement('alter table kosts add constraint kosts_latitude_range check (latitude between -90 and 90)');
DB::statement('alter table kosts add constraint kosts_longitude_range check (longitude between -180 and 180)');
```

Nama constraint ditulis eksplisit agar pesan galat PostgreSQL menyebut nama yang dapat dicari. `down()` cukup `Schema::dropIfExists('kosts')` karena constraint ikut terhapus bersama tabel.

**Alternatif ditolak:** validasi aplikasi saja. Alasan: seeder, impor, dan `artisan tinker` melewati Form Request, padahal data koordinat salah merusak pencarian radius.

`phpunit.xml` bergantung pada pilihan ini: check constraint tidak ada di SQLite, sehingga tes skema akan lolos secara palsu bila suite tetap di SQLite.

### 7. Tes pindah ke PostgreSQL dengan basis data terpisah

`phpunit.xml` menjadi:

```xml
<env name="DB_CONNECTION" value="pgsql"/>
<env name="DB_DATABASE" value="aygkos_testing"/>
```

`DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` tidak ditulis di `phpunit.xml` agar kredensial tetap di `.env` dan tidak ter-commit. `RefreshDatabase` yang sudah dipasang global akan memigrasi ulang `aygkos_testing` per proses tes.

Konsekuensi yang diterima: `php artisan test` menuntut PostgreSQL hidup. Bila mati, galatnya adalah `SQLSTATE[08006]` yang jelas, bukan kegagalan senyap. Paratest terpasang tetapi tetap tidak diaktifkan: setiap worker akan memakai basis data yang sama dan saling menghapus tabel.

`DB_DATABASE=aygkos_testing` berbeda dari `aygkos`, sesuai spec `domain/persistence-conventions`.

### 8. Peran diisi seeder, bukan enum

`RoleSeeder` memakai `Role::firstOrCreate(['name' => ...])` sehingga aman dijalankan berulang dan aman dipanggil dari `DatabaseSeeder` maupun dari `tests/Pest.php`. `DatabaseSeeder` memanggilnya lebih dulu, lalu membuat satu akun Admin.

`tests/Pest.php` mendapat `beforeEach(fn () => $this->seed(RoleSeeder::class))` hanya untuk suite Feature. Ini menambah satu query per tes, tetapi menghindari setiap tes menyeed sendiri dan menghindari `UserFactory` menyentuh basis data peran pada jalur yang tidak perlu: `UserFactory` tetap memakai `firstOrCreate` peran `customer` sebagai jaring pengaman bila sebuah tes membuat pengguna di luar `beforeEach`.

**Alternatif ditolak:** enum PHP `RoleName` dengan kolom string. Alasan: ERD menetapkan `roles` sebagai tabel, dan Admin kelak butuh daftar peran untuk penyaringan.

### 9. `role_id` diisi di `CreateNewUser`, bukan lewat event model

`CreateNewUser::create` menambahkan `'role_id' => Role::where('name', 'customer')->value('id')`. Registrasi mandiri selalu menjadi Customer sesuai FR-AUT-01.

**Alternatif ditolak:** `creating` event pada `User` yang mengisi peran bawaan. Alasan: event menyembunyikan keputusan penting dan membuat pembuatan akun Admin lewat seeder tidak dapat membedakan peran tanpa mematikan event.

Bila baris `customer` tidak ada, `role_id` menjadi null dan basis data menolak insert. Ini disengaja: kegagalan jelas lebih baik daripada akun tanpa peran.

### 10. Model menyatakan relasi, dan dokumen blok `@property`

Setiap model memakai atribut `#[Fillable([...])]` dan `#[Hidden([...])]` seperti `User` yang sudah ada, agar konsisten dengan scaffold. Relasi yang dideklarasikan:

```
Role      hasMany(User)
User      belongsTo(Role), hasMany(Kost, 'owner_id'), hasMany(Kost, 'reviewed_by'),
          hasMany(WhatsappVerification), hasMany(Complaint, 'user_id'),
          hasMany(Complaint, 'handled_by'), hasMany(Notification)
Kost      belongsTo(User, 'owner_id'), belongsTo(User, 'reviewed_by'),
          hasMany(KostPhoto), belongsToMany(Facility, 'kost_facilities'),
          hasMany(Room), hasMany(Complaint)
Facility  belongsToMany(Kost, 'kost_facilities')
Complaint belongsTo(User, 'user_id'), belongsTo(Kost), belongsTo(User, 'handled_by'),
          hasMany(ComplaintAttachment)
```

`complaints.kost_id` memakai `nullOnDelete`, jadi relasi `Complaint::kost()` harus `->withDefault()` atau nullable di dokumen blok, bukan diasumsikan selalu ada.

`phpstan` level 7 menganalisis `app/`, sehingga setiap relasi memerlukan blok `@return` dan setiap model blok `@property` untuk kolomnya. Tanpa itu `composer test` gagal pada tahap phpstan, bukan pada tahap tes.

### 11. `HasUuids` dan tipe `id` di luar PHP

`resources/js/types/auth.ts` mengubah `id: number` menjadi `id: string`. `ProfileValidationRules::profileRules(?int $userId)` menjadi `?string`, karena `Rule::unique(...)->ignore()` menerima nilai apa pun dan pemanggilnya `ProfileUpdateRequest` meneruskan `$this->user()->id` yang kini string.

Tidak ada perubahan kontrak lain: `User` tetap `MustVerifyEmail`, tetap `Authenticatable`, dan tetap menyimpan `remember_token` sehingga sesi yang sudah ada tidak terpengaruh.

## Risks / Trade-offs

- **[Suite tes menuntut PostgreSQL hidup]** → `php artisan test` gagal total bila `lerd-postgres`/container mati. Mitigasi: galat `SQLSTATE[08006]` muncul di baris pertama; dicatat pada `AGENTS.md` bagian perintah, dan `composer test` sudah menjalankan `config:clear` lebih dulu.
- **[`RefreshDatabase` memigrasi ulang seluruh skema tiap proses]** 10 tabel + constraint pada PostgreSQL sekitar satu kali per proses, bukan per tes. Terukur beberapa detik; bila suite membesar, `DatabaseMigrations` tidak akan menolong — jalur perbaikan adalah template basis data atau Paratest dengan DB per worker.
- **[`facilities` dan `kost_photos` tanpa timestamp]** tidak ada jejak siapa menambah fasilitas atau kapan foto diunggah. Mitigasi: kolom berkas menyimpan path yang sudah memuat `kosts/{id}/` sebagai penanda tidak langsung; bila audit dibutuhkan, tambah kolom pada perubahan terpisah dengan bump ERD.
- **[`users` disunting di tempat]** siapa pun yang punya basis data lama akan mengalami `migrate` yang gagal karena tabelnya tidak cocok. Mitigasi: tidak ada data nyata, dan satu-satunya jalur adalah `migrate:fresh`; dicatat di task dan di pesan commit.
- **[Constraint check tidak dapat diubah tanpa drop]** mengubah batas koordinat kelak butuh `alter table ... drop constraint`. Mitigasi: nama constraint eksplisit dan stabil, sehingga perubahannya satu `DB::statement`.
- **[`sessions.user_id` tidak punya foreign key]** migration bawaan hanya menambah kolom dan index. Mitigasi: dibiarkan, karena sesi kedaluwarsa dan foreign key akan menahan penghapusan akun dengan galat yang membingungkan pengguna.
- **[`HasUuids` uuid7 bukan uuid4]** id memuat waktu pembuatan baris. Mitigasi: diterima; tidak ada id yang diekspos sebagai rahasia, dan indeks lebih rapat.
- **[`firstOrCreate` peran di `UserFactory`]** menyisipkan baris `roles` di tengah `RefreshDatabase` bila sebuah tes membuat pengguna sebelum seeder berjalan. Mitigasi: memang disengaja, dan teksnya `customer` sehingga tidak menambah peran lain.

## Migration Plan

1. Ubah `phpunit.xml` ke `pgsql`/`aygkos_testing`, jalankan `php artisan config:clear`.
2. Tulis sepuluh migration, jalankan `php artisan migrate:fresh`.
3. Tulis sembilan model dan ubah `User`.
4. `php artisan db:seed --class=RoleSeeder`, lalu `php artisan db:seed`.
5. Jalankan `composer lint`, `npm run types:check`, `vendor/bin/phpstan analyse`, `php artisan test`.
6. Perbarui `docs/ERD/erd.md` ke V003, pensiunkan V002 ke `docs/ERD/logs/V002-erd.md`, tambah baris `docs/ERD/release.md`.

**Rollback:** `php artisan migrate:fresh` mengembalikan skema. Tidak ada data produksi. Bila suite harus kembali ke SQLite, ubah `phpunit.xml` dan lewati tes constraint check — tetapi cabang itu tidak disediakan di change ini.

## Open Questions

Tidak ada.