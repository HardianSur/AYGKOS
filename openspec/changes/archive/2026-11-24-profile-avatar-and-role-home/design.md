# Design

## Context

Lihat `proposal.md` untuk alasan perubahan, `specs/profile/account-profile/spec.md`, `specs/dashboard/role-home/spec.md`, `specs/domain/identity-accounts/spec.md`, dan `specs/platform/file-storage/spec.md` untuk kontrak perilaku.

Keadaan kode yang membentuk rancangan ini:

- `resources/js/types/auth.ts` sudah mendeklarasikan `avatar?: string`, dan `app-header.tsx` serta `user-info.tsx` sudah merender `<AvatarImage src={user.avatar}>` dengan `AvatarFallback` inisial. Frontend tidak perlu komponen baru untuk menampilkan foto.
- `HandleInertiaRequests::share()` membagikan model `User` mentah, jadi kolom baru otomatis sampai ke frontend; yang belum ada adalah URL-nya.
- `ProfileUpdateRequest` memakai trait `ProfileValidationRules` yang juga dipakai `CreateNewUser`; aturan khusus unggahan tidak boleh masuk trait itu.
- `ProfileController::update()` menjalankan `fill($request->validated())`; `UploadedFile` di dalam array itu akan masuk ke kolom string.
- `routes/web.php` saat ini `Route::inertia('dashboard', 'dashboard')` tanpa controller, di dalam middleware `auth` + `verified`.
- `config/uploads.php` sudah menjadi sumber batas ukuran/mime per jenis berkas (pola `kost_photo`).
- Laravel 13.42 menyediakan facade `Illuminate\Image` beserta driver Intervention, tetapi paket `intervention/image` belum terpasang; tanpa paket itu facade melempar `ImageException`. Container PHP 8.5 sudah punya `gd` (WebP) dan `exif`.
- `public/storage` sudah tertaut ke `storage/app/public`, dan disk `public` sudah dikonfigurasi. `FILESYSTEM_DISK=local`, jadi disk wajib disebut eksplisit.

## Goals / Non-Goals

**Goals:**

- Satu jalur unggah, satu jalur simpan, satu jalur tampil untuk foto profil, tanpa menambah endpoint.
- Konversi WebP terjadi sekali saat unggah, bukan setiap penyajian.
- Perubahan frontend seminimal mungkin: hanya halaman profil (pemilih berkas + pratinjau lingkaran) dan satu halaman baru untuk Customer.
- Kegagalan penyimpanan berkas tidak boleh menghilangkan foto lama maupun menggagalkan penyimpanan nama/email yang sudah sah.

**Non-Goals:**

- Tidak ada pemotongan (crop) interaktif oleh pengguna; pemotongan dilakukan otomatis di tengah.
- Tidak ada penghapusan foto tanpa penggantian (tidak ada tombol "hapus foto").
- Tidak membangun alur OTP perubahan nomor WhatsApp, halaman `settings/whatsapp`, atau dashboard Customer sebenarnya; keduanya tetap di roadmap.
- Tidak mengubah aturan verifikasi per peran, guard login, atau middleware `verified`.
- Tidak mengganti token warna, radius, atau komponen `ui/avatar.tsx`.

## Decisions

**1. Satu kolom `avatar_path`, URL dibangkitkan accessor `avatar`.**
Migrasi menambah `users.avatar_path varchar(500) nullable`. `User` mendapat `#[Hidden([..., 'avatar_path'])]` digabung dengan daftar yang ada, `#[Appends(['avatar'])]`, dan accessor `avatar()` yang mengembalikan `Storage::disk('public')->url($this->avatar_path)` atau `null` bila kosong. Dengan begitu `types/auth.ts` yang sudah ada langsung terisi tanpa mengubah `HandleInertiaRequests`, `app-header.tsx`, maupun `user-info.tsx`.

Alternatif menyimpan URL lengkap di basis data ditolak: spec `platform/file-storage` melarang menyimpan URL, dan URL berubah bila domain atau skema berganti. Alternatif menambah `avatar` lewat `share()` ditolak karena menduplikasi sumber data dan tetap butuh kolom path.

**2. Konversi lewat facade `Illuminate\Image` dengan paket `intervention/image`.**
Satu Action `app/Actions/StoreAvatar.php` menerima `UploadedFile` dan mengembalikan path:

```php
Image::fromUpload($file)
    ->orient()
    ->cover(400, 400)
    ->toWebp()
    ->quality(85)
    ->storeAs('avatars', $name, 'public');
```

`orient()` mendahului `cover()` supaya EXIF rotasi diterapkan sebelum pemotongan, bukan sesudahnya. Kualitas 85 dipilih di atas bawaan `ImageOutputOptions::DEFAULT_QUALITY` (70) karena wajah adalah isi utama dan ukuran berkas 400×400 WebP tetap kecil pada 85. Disk `public` disebut eksplisit karena `FILESYSTEM_DISK=local`.

Alternatif GD mentah ditolak: harus menangani transparansi PNG, orientasi EXIF, dan pemotongan sendiri, padahal facade sudah membungkusnya. Alternatif menyimpan berkas asli lalu mengonversi saat disajikan ditolak karena melanggar keputusan "konversi saat unggah" dan menambah jalur penyajian berkas.

Nama berkas memakai `hashName()` dari facade. Path tersimpan relatif (`avatars/<hash>.webp`), bukan URL.

**3. Tetap satu route `PATCH settings/profile`, berkas tidak masuk `fill()`.**
Inertia v3 mengubah permintaan menjadi `multipart/form-data` ketika mendeteksi `File` dan menyisipkan `_method=patch`, jadi route `profile.update` yang ada menerima berkas tanpa route baru. `ProfileUpdateRequest` menambahkan aturan `avatar`:

```php
'avatar' => ['nullable', 'image', 'mimes:'.implode(',', config('uploads.profile_photo.mimes')),
             'max:'.config('uploads.profile_photo.max_kb')],
```

Aturan itu ditulis langsung di `ProfileUpdateRequest::rules()`, digabung dengan hasil `profileRules()`; trait `ProfileValidationRules` tidak disentuh supaya registrasi tidak ikut meminta foto.

`ProfileController::update()`:

1. `$data = $request->safe()->except('avatar');` lalu `fill($data)` dan `save()`.
2. Bila `$request->hasFile('avatar')`, panggil `StoreAvatar`, simpan path baru, lalu hapus berkas lama.
3. `whatsapp_number` tidak ada di `#[Fillable]` saat ini, jadi mengirimnya tidak mengubah apa pun; itu sudah memenuhi skenario "nomor tidak berubah lewat formulir profil" tanpa kode tambahan.

Urutan ini menyimpan data akun lebih dulu, lalu berkas. Bila penyimpanan berkas gagal, nama/email tetap tersimpan dan foto lama utuh, sesuai skenario "penyimpanan foto baru gagal" — kegagalan berkas dilaporkan lewat `Inertia::flash('toast', ...)` bertipe error, bukan dengan membatalkan seluruh permintaan.

Alternatif memakai route unggah terpisah (`POST settings/profile/avatar`) ditolak: menambah dua jalur untuk satu formulir dan memaksa dua permintaan untuk satu kali simpan. Alternatif menyimpan nama/email dan berkas dalam satu transaksi basis data tidak menolong karena penulisan disk tidak ikut transaksi.

**4. Penghapusan berkas lewat event model, bukan di controller.**
`User::booted()` mendaftarkan listener `deleting` yang menghapus `Storage::disk('public')->delete($user->avatar_path)` bila path terisi. Dengan begitu penghapusan akun dari mana pun (termasuk `ProfileController::destroy` yang sudah ada) ikut membersihkan berkas tanpa mengubah controller itu. Penggantian foto tetap ditangani di `ProfileController::update()` karena butuh path lama sebelum ditimpa.

Alternatif `observe` class baru ditolak: satu listener lima baris tidak layak satu kelas dan satu pendaftaran provider.

**5. Nomor WhatsApp read-only, dirender berdasarkan data.**
Halaman profil menampilkan nomor bila `auth.user.whatsapp_number` terisi, beserta label "Terverifikasi"/"Belum terverifikasi" dari `whatsapp_verified_at`. Customer dan Admin selalu bernilai `null` karena `CreateNewUser` tidak mengisinya untuk peran itu, jadi tidak perlu mengirim `role` ke frontend dan tidak ada cabang per peran di tampilan.

`types/auth.ts` mendapat `whatsapp_number: string | null` dan `whatsapp_verified_at: string | null` agar TypeScript mengenal keduanya (saat ini hanya lewat index signature `[key: string]: unknown`).

Alternatif menampilkan tombol "Ubah nomor" ke halaman `settings/whatsapp` ditolak: halaman itu belum ada, sehingga tombolnya akan menjadi tautan mati.

**6. Dashboard per peran lewat closure route, bukan middleware atau dua route.**
`routes/web.php` mengganti `Route::inertia('dashboard', 'dashboard')` dengan closure yang merender `home` untuk Customer dan `dashboard` untuk peran lain:

```php
Route::get('dashboard', function (Request $request) {
    return Inertia::render($request->user()->role?->name === 'customer' ? 'home' : 'dashboard');
})->name('dashboard');
```

Middleware `auth` + `verified` tetap sama, jadi tujuan pengarahan setelah masuk tidak berubah dan spec `auth/login` tidak perlu diubah. Halaman `resources/js/pages/home.tsx` berisi satu `<h1>Hai, {auth.user.name}</h1>` terpusat dan satu tombol `Keluar` (POST `/logout` Fortify) tanpa layout aplikasi: `app.tsx` meresolusi `home` ke `null` seperti `welcome`, sehingga Customer tidak melihat sidebar maupun header milik Pemilik Kost dan Admin — satu-satunya jalan keluar dari halaman itu adalah tombol tersebut. Halaman Customer akan mendapat UI sendiri pada change terpisah.

Alternatif middleware pengarah per peran ditolak: mengubah tujuan pengarahan, memaksa middleware baru, dan bertabrakan dengan spec `auth/login` yang menyatakan semua peran diarahkan ke `/dashboard`. Alternatif dua route berbeda (`/beranda`, `/dashboard`) ditolak karena alasan yang sama.

**7. Gaya mengikuti `docs/DESIGN.md`.**
Foto profil memakai `rounded-full` (lingkaran), bukan radius 10/14 px, karena aturan radius pada DESIGN.md berlaku untuk kartu, tombol, dan panel; avatar bulat adalah bentuk, bukan skala radius. Pratinjau foto di halaman profil memakai `size-20` dengan `object-cover`. Label "Terverifikasi" memakai `text-ink-soft` dan ikon/label status tanpa menjadikan Jingga sebagai warna teks (DESIGN.md melarang Jingga untuk teks/ikon). Cincin fokus pemilih berkas memakai `focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-surface`.

**8. Versi dokumen naik di change ini, bukan change terpisah.**
`docs/ERD/erd.md` V003 dibekukan ke `docs/ERD/logs/V003-erd.md` lalu diedit menjadi V004 dengan kolom `users.avatar_path`, disertai baris baru di `docs/ERD/release.md`. `docs/FunctionalRequirment/functional_requirment.md` mendapat header `## Versi V001` dan baris `FR-PRF-04`, dicatat di `docs/FunctionalRequirment/release.md` sebagai versi awal (aturan 3 `openspec/config.yaml`: belum ada versi yang dipensiunkan, jadi tidak ada berkas beku).

## Risks / Trade-offs

- [`intervention/image` belum ada di `vendor/`, sehingga tes apa pun yang memakai facade gagal sebelum `composer require`] → Pasang paket di tugas pertama change dan jalankan `composer test` sesudahnya.
- [Kualitas 85 menghasilkan berkas lebih besar daripada bawaan 70] → Batas 400×400 dan WebP menjaga ukuran di kisaran puluhan kilobyte; bila terlalu besar, turunkan ke 80 tanpa mengubah spec.
- [Kegagalan disk setelah akun tersimpan menyisakan ketidakcocokan sementara: nama/email berubah, foto lama tetap] → Disengaja sesuai spec; dilaporkan lewat toast error agar pengguna tahu foto belum berganti.
- [Path lama bisa yatim bila `delete` disk gagal] → Penghapusan hanya best-effort dan tidak membatalkan permintaan; berkas yatim tidak merusak tampilan karena path di akun sudah menunjuk berkas baru.
- [`role` adalah relasi lazy-load; closure dashboard memicunya untuk setiap permintaan dashboard] → Satu query tambahan per permintaan dashboard, dapat diterima, dan tidak perlu `with('role')` karena dashboard bukan jalur panas.
- [Peran yang namanya berubah menjadi `customer` atau sebaliknya akan mengubah halaman yang dirender] → Nama peran adalah data tetap hasil seeder dan dirujuk spec `domain/identity-accounts`; risiko diterima.
- [Avatar berukuran 400×400 di-cache peramban, sehingga penggantian foto dengan nama hash baru selalu memuat ulang] → Nama hash baru justru menghindari cache basi; tidak ada risiko.
- [Tidak ada tes frontend otomatis] → Daftar pemeriksaan manual ada di `tasks.md`, dan gate `npm run check`, `npm run types:check`, `npm run build`, serta `composer test` tetap dijalankan.

## Migration Plan

1. `composer require intervention/image` (memperbarui `composer.json` dan `composer.lock`).
2. Migrasi `add_avatar_path_to_users_table`; dapat dibatalkan dengan `migrate:rollback` pada environment lokal karena kolom nullable tanpa indeks.
3. Deploy kode; tidak ada data lama yang perlu diisi karena `avatar_path` kosong berarti "belum ada foto".
4. Rollback: kembalikan berkas yang tercantum di `proposal.md`, jalankan rollback migrasi, dan hapus direktori `storage/app/public/avatars` bila perlu. Tidak ada perubahan env atau route yang perlu dibatalkan di luar itu.

## Open Questions

Tidak ada. Sumber foto lama untuk penghapusan, penempatan aturan validasi, bentuk halaman Customer, dan kepemilikan versi dokumen sudah diputuskan sebelum proposal dibuat.