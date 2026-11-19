# Design — auth-login

## Context

Motivasi ada di `proposal.md`; kebutuhan perilaku ada di `specs/auth/login/spec.md`. Yang membentuk pendekatan ini:

- `resources/js/pages/auth/login.tsx` masih scaffold bawaan: teks Inggris, memakai `<Head title>` + `InputError`, tanpa komponen merek.
- `resources/js/layouts/auth-layout.tsx` selalu merender `auth/auth-simple-layout` untuk semua halaman `auth/*`. Registrasi, lupa password, reset, verifikasi email, dan konfirmasi password memakai layout yang sama, jadi layout itu tidak boleh diubah.
- `resources/js/app.tsx` meresolusi layout dari nama halaman: `welcome` tanpa layout, `auth/*` ke `AuthLayout`, `settings/*` ke `AppLayout` + `SettingsLayout`, sisanya `AppLayout`. Nama halaman boleh berupa komponen (`Component.layout`) atau objek props.
- `resources/css/app.css` memakai token OKLCH shadcn di `:root`/`.dark` yang dipakai seluruh aplikasi; `docs/DESIGN.md` mendefinisikan palet navy/jingga dan huruf sendiri, tetapi penerapannya belum dimulai.
- `vite.config.ts` memuat Instrument Sans lewat `bunny()` dari `laravel-vite-plugin/fonts`.
- Fortify menangani `POST /login` lewat pipeline `EnsureLoginIsNotThrottled` → `CanonicalizeUsername` → `AttemptToAuthenticate` → `PrepareAuthenticatedSession`, lalu `LoginResponse`. Callback `Fortify::authenticateUsing()` belum dipasang, sehingga satu-satunya penolakan adalah kredensial salah.
- `config/fortify.php` `home = /dashboard`; `LoginResponse` bawaan memakai `redirect()->intended()` sehingga tujuan semula tetap dihormati.
- `App\Models\User` punya `is_active` dan `whatsapp_verified_at` serta relasi `role`; belum ada helper yang menyatukan bukti verifikasi per peran.
- Belum ada `lang/`. Framework memuat `vendor/.../Translation/lang` lebih dulu, lalu `lang/` aplikasi; kunci yang tidak ada di `lang/id` jatuh ke fallback `en`.
- `APP_LOCALE=en` di `.env.example` dan `phpunit.xml`.
- `Role::where('name', 'customer')` dipakai `CreateNewUser`, jadi pendaftaran selalu menghasilkan Customer; akun Pemilik Kost belum dapat dibuat lewat aplikasi.

## Goals / Non-Goals

**Goals:**

- Halaman `/login` punya tampilan dua panel sendiri tanpa menggeser halaman `auth/*` lainnya.
- FR-AUT-09 dijalankan pada satu titik pemeriksaan yang juga bisa dipakai ulang saat middleware verifikasi per peran dibuat nanti.
- Pesan galat masuk dan validasi dasar berbahasa Indonesia dengan istilah dokumen proyek.
- Semua peran mendarat di `/dashboard`, dengan tujuan semula tetap diutamakan.

**Non-Goals:**

- Menerapkan seluruh `docs/DESIGN.md` ke halaman lain; token yang ditambah hanya dipakai layout login.
- Pengarahan per peran (FR-AUT-10), lupa password, verifikasi email/WhatsApp, kirim ulang verifikasi, dan registrasi.
- Middleware `EnsureAccountVerified` untuk route terlindung: penolakan hanya terjadi di titik masuk.
- Social login, karena tidak ada di daftar FR.

## Decisions

### 1. Layout login berdiri sendiri, dipilih per halaman

Buat `resources/js/layouts/auth/auth-login-layout.tsx` dan pilih lewat `Component.layout` pada `pages/auth/login.tsx`, bukan cabang baru di `auth-layout.tsx`.

Alternatif yang ditolak: menambah cabang `name === 'auth/login'` di `auth-layout.tsx`. Itu menaruh pengetahuan tentang satu halaman di dalam layout bersama dan membuat `auth-layout.tsx` bergantung pada halaman yang dirender. Dengan `Component.layout`, `app.tsx` tidak perlu diubah sama sekali dan halaman lain tidak tersentuh; resolver `auth/*` tetap jalan untuk halaman `auth/*` yang tidak menyetel layout sendiri. `Page.layout` untuk login cukup memuat `title`/`description` bila masih diperlukan.

### 2. Token brand additive, bukan penggantian `:root`

Tambah blok token brand terpisah di `resources/css/app.css` (`--color-night`, `--color-canvas`, `--color-surface`, `--color-ink`, `--color-ink-soft`, `--color-hairline`, `--color-field`, `--color-ember`, `--color-ember-ink`, `--color-ember-wash`, `--font-brand`) beserta pasangan `.dark`-nya, dan pakai hanya di layout login. `:root`/`.dark` shadcn serta `--font-sans` tidak diubah.

Alternatif yang ditolak: mengganti `:root` ke palet DESIGN.md sekarang. Akibatnya dashboard, settings, welcome, dan halaman `auth/*` lain berubah visual setengah jadi, sementara layoutnya belum disentuh; itu di luar cakupan dan sulit ditinjau.

Huruf: `bunny('Plus Jakarta Sans', { weights: [400, 500, 700, 800] })` ditambahkan berdampingan dengan Instrument Sans, diekspos sebagai `--font-brand`. Instrument Sans tetap dipakai halaman lain.

### 3. Komponen yang ada dipakai ulang lewat `className`

`Input`, `PasswordInput`, `Checkbox`, `Button`, dan `InputError` dipakai apa adanya dengan override `className`. Tidak ada varian baru, tidak ada komponen baru di `components/`.

Alternatif yang ditolak: menambah varian `brand` pada `Button`/`Input`. Varian dengan satu pemakai menambah permukaan API tanpa manfaat; bila nanti halaman lain memakai palet yang sama, varian bisa diekstrak saat itu.

Cincin fokus memakai `ring-2 ring-ink ring-offset-2 ring-offset-surface` sesuai aturan offset di `docs/DESIGN.md` §6.

### 4. FR-AUT-09 lewat `Fortify::authenticateUsing()`

Satu callback di `FortifyServiceProvider` memeriksa tiga hal berurutan: kredensial (`Auth::validate`), `is_active`, lalu bukti verifikasi per peran. Kredensial salah dan akun tidak dikenal sama-sama mengembalikan `null` sehingga Fortify melempar `auth.failed`; dua penolakan lain melempar `ValidationException` dengan pesan `auth.inactive` dan `auth.unverified`.

Alasan memilih callback dan bukan middleware atau pengecekan di controller: Fortify tidak mengekspos controller-nya untuk diubah, pipeline-nya berjalan sebelum sesi dibuat sehingga penolakan tidak meninggalkan sesi setengah jadi, dan `ValidationException` dari callback sudah otomatis mengembalikan galat ke formulir (`errors.email`) tanpa perlu response kustom. Alternatif "cek setelah login lalu logout" ditolak karena membuat sesi sempat aktif dan memperumit pengujian.

Bukti verifikasi per peran disatukan sebagai `User::hasVerifiedIdentity()`:

- Pemilik Kost: `whatsapp_verified_at !== null`
- Customer dan Admin: `email_verified_at !== null`

Helper ini dipakai callback sekarang dan middleware verifikasi nanti, sehingga aturannya tidak terduplikasi. Saat ini `CreateNewUser` selalu membuat Customer, jadi jalur Pemilik Kost bersifat defensif dan hanya terbukti lewat tes; itu dicatat sebagai risiko, bukan alasan menunda.

### 5. Pesan lewat `lang/id`, bukan string di kode

Tambah `lang/id/auth.php` (`failed`, `inactive`, `unverified`, `throttle`) dan `lang/id/validation.php` minimal (`required`, `string`, `email`, `unique`, `confirmed`, `min`, plus `attributes` untuk email, password, dan nama). Set `APP_LOCALE=id` di `.env.example` dan `phpunit.xml`; `.env` lokal tidak di-commit sehingga pemilik mesin mengubahnya sendiri.

Alasan `lang/id/validation.php` parsial aman: framework memuat `vendor/.../lang` lebih dulu lalu `lang/` aplikasi, dan kunci yang tidak ada jatuh ke `APP_FALLBACK_LOCALE=en`. Jadi file tidak perlu menyalin seluruh kamus validasi. Alternatif "hanya `auth.php`" ditolak karena galat kolom wajib akan tetap Inggris di halaman yang justru sedang didesain ulang.

### 6. Tes sebagai bukti perilaku

`tests/Feature/Auth/AuthenticationTest.php` diperluas dengan: akun nonaktif ditolak, Customer belum terverifikasi ditolak, Pemilik Kost dengan email terbukti tetapi WhatsApp belum terbukti ditolak, Pemilik Kost dengan WhatsApp terbukti diterima, dan halaman login merender komponen `auth/login`. Tes memakai `User::factory()->create()` dengan `forceFill`/state untuk `is_active`, `email_verified_at`, dan `whatsapp_verified_at`, karena `#[Fillable]` pada `User` tidak memuat `is_active`.

Tes lama wajib tetap hijau: halaman login dapat dirender, masuk berhasil mengalihkan ke `route('dashboard', absolute: false)`, dan batas percobaan menghasilkan 429.

## Risks / Trade-offs

- **Pemilik Kost belum punya jalan keluar** — akun owner tanpa `whatsapp_verified_at` ditolak, tetapi halaman verifikasi WhatsApp baru ada di M2. Mitigasi: pendaftaran owner juga belum ada (`CreateNewUser` selalu Customer), jadi keadaan ini belum dapat terjadi di produksi; pesannya menyebut verifikasi WhatsApp tanpa menjanjikan tautan kirim ulang.
- **`APP_LOCALE=id` berlaku global** — halaman yang belum diterjemahkan menampilkan campuran Indonesia–Inggris lewat fallback `en`. Mitigasi: diterima sebagai efek samping yang diketahui, dicatat di proposal; terjemahan penuh dikerjakan per fase.
- **Penolakan hanya di titik masuk** — akun yang dinonaktifkan setelah login tetap memegang sesi sampai keluar. Mitigasi: middleware status akun dan verifikasi untuk route terlindung dicatat sebagai pekerjaan M2; spec change ini hanya mengikat perilaku `POST /login`.
- **`authenticateUsing` menggantikan pemeriksaan bawaan** — callback harus tetap memanggil `Auth::validate` sendiri, kalau tidak kata sandi tidak diperiksa. Mitigasi: satu tes kredensial salah sudah ada dan wajib tetap hijau.
- **Token baru bisa dipakai tanpa sengaja di halaman lain** — palet brand jadi tersedia global di CSS. Mitigasi: hanya layout login yang mengimpor/memakainya, dan `docs/DESIGN.md` sudah menjadi acuan sehingga pemakaian berikutnya sejalan.
- **Layout terpilih lewat `Component.layout`** — halaman login tidak lagi mengikuti perubahan `auth-layout.tsx` di masa depan. Mitigasi: disengaja; login punya arah desain sendiri.

## Migration Plan

Tidak ada migrasi data maupun perubahan skema; kolom yang dipakai (`is_active`, `email_verified_at`, `whatsapp_verified_at`) sudah ada. Urutan penerapan:

1. Token brand + huruf → halaman lain tidak berubah.
2. `lang/id/*` + `APP_LOCALE=id`.
3. `User::hasVerifiedIdentity()` + `Fortify::authenticateUsing()`.
4. Layout dan halaman login.
5. Tes baru.

Rollback: kembalikan `APP_LOCALE` ke `en` dan hapus callback `authenticateUsing`; keduanya mengembalikan perilaku masuk seperti semula tanpa menyentuh data. Perubahan tampilan sudah terisolasi di satu halaman.

## Open Questions

Tidak ada yang perlu diputuskan sebelum implementasi. Dua hal yang sengaja ditunda dan tidak mengubah spec: middleware status/verifikasi untuk route terlindung (M2) dan pengarahan per peran FR-AUT-10 setelah route `/kosts`, `/owner/dashboard`, dan `/admin/dashboard` ada.