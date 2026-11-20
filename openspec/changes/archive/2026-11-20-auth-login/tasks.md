# Tasks

## 1. Token brand dan huruf

- [x] 1.1 Tambah token brand additive di `resources/css/app.css` (`--color-night`, `--color-canvas`, `--color-surface`, `--color-ink`, `--color-ink-soft`, `--color-hairline`, `--color-field`, `--color-ember`, `--color-ember-ink`, `--color-ember-wash`, `--font-brand`) beserta pasangan `.dark`-nya, tanpa menyentuh `:root`/`.dark` shadcn. Verifikasi: `git diff resources/css/app.css` hanya menambah baris, dan `npm run build` lolos.
- [x] 1.2 Tambah `bunny('Plus Jakarta Sans', { weights: [400, 500, 700, 800] })` di `vite.config.ts` berdampingan dengan Instrument Sans. Verifikasi: `npm run build` lolos dan berkas CSS hasil build memuat `font-family` Plus Jakarta Sans.

## 2. Bahasa Indonesia

- [x] 2.1 Buat `lang/id/auth.php` (`failed`, `inactive`, `unverified`, `throttle`) dan `lang/id/validation.php` minimal (`required`, `string`, `email`, `unique`, `confirmed`, `min`, `attributes` untuk email/password/name). Verifikasi: `php artisan tinker --execute="echo __('auth.failed');"` menghasilkan kalimat Indonesia, dan kunci yang sengaja tidak diterjemahkan (mis. `validation.numeric`) tetap jatuh ke teks Inggris.
- [x] 2.2 Set `APP_LOCALE=id` pada `.env.example` dan `phpunit.xml`. Verifikasi: `php artisan test --filter=AuthenticationTest` lolos dan galat validasi formulir tampil berbahasa Indonesia; catat bahwa `.env` lokal harus diubah manual.

## 3. FR-AUT-08 dan FR-AUT-09 di backend

- [x] 3.1 Tambah `User::hasVerifiedIdentity()`: Pemilik Kost memakai `whatsapp_verified_at`, Customer dan Admin memakai `email_verified_at`. Verifikasi: tes di grup ini membuktikan keempat kombinasi peran/bukti berperilaku sesuai.
- [x] 3.2 Pasang `Fortify::authenticateUsing()` di `FortifyServiceProvider`: kredensial salah mengembalikan `null` (pesan `auth.failed`), akun nonaktif melempar `auth.inactive`, akun belum terverifikasi melempar `auth.unverified`. Verifikasi: tes kredensial salah, batas percobaan 429, dan pengalihan `route('dashboard', absolute: false)` yang sudah ada tetap lolos.
- [x] 3.3 Tambah tes di `tests/Feature/Auth/AuthenticationTest.php`: akun nonaktif ditolak dan tetap tamu, Customer belum verifikasi email ditolak, Pemilik Kost dengan email terbukti tetapi WhatsApp belum terbukti ditolak, Pemilik Kost dengan `whatsapp_verified_at` terisi diterima, masing-masing memeriksa pesan galatnya. Verifikasi: `php artisan test --filter=AuthenticationTest` hijau.

## 4. Tampilan halaman masuk

- [x] 4.1 Buat `resources/js/layouts/auth/auth-login-layout.tsx`: dua panel (navy `night` + radial jingga tipis di kiri, `surface` berbingkai `hairline` di kanan), panel kiri menciut jadi strip merek di bawah `lg`, cincin fokus `ring-2 ring-ink ring-offset-2 ring-offset-surface`. Verifikasi: halaman `/login` tampil dua panel pada lebar desktop dan satu kolom pada lebar ponsel, tanpa mengubah `auth-simple-layout`.
- [x] 4.2 Tulis ulang `resources/js/pages/auth/login.tsx`: judul dan label Indonesia, isian email, `PasswordInput`, `Checkbox` "Ingat saya", tombol masuk, tautan "Lupa sandi?" (bila `canResetPassword`) dan tautan registrasi; pakai komponen yang ada lewat `className`, lalu set `Login.layout = AuthLoginLayout` dengan props `title`/`description`. Verifikasi: tes baru memastikan halaman merender komponen `auth/login`, dan `npm run check` serta `npm run types:check` lolos.
- [x] 4.3 Periksa aksesibilitas dan keadaan galat: setiap isian punya `Label` ber-`htmlFor`, galat muncul lewat `InputError` di bawah isian yang bersangkutan, pesan galat generik tidak menyebut bagian kredensial mana yang salah, dan navigasi papan ketik mengikuti urutan tabIndex. Verifikasi: tinjau `/login` dengan galat kredensial salah dan dengan isian kosong; pesan tampil Indonesia dan terbaca pembaca layar.

## 5. Pemeriksaan integrasi

- [x] 5.1 Jalankan gate proyek: `composer test`, `npm run check`, `npm run types:check`, `npm run build`. Verifikasi: keempat perintah hijau; bila ada yang merah, perbaiki sebelum melaporkan selesai.
- [x] 5.2 Pastikan cakupan tidak meluas: `git diff --stat` tidak menyentuh `pages/auth/{register,forgot-password,reset-password,verify-email,confirm-password}.tsx`, `auth-simple-layout.tsx`, `:root`/`.dark` shadcn, atau route di luar `/login`. Verifikasi: diff hanya memuat berkas yang tercantum di `proposal.md` bagian Impact.

## Workflow follow-up

- Arsipkan change setelah hasil tinjauan disetujui.
- Tawarkan pembaruan `docs/ARCHITECTURE.md` §7.4/§12 agar status FR-AUT-09 (sebagian, penolakan hanya di titik masuk) tercatat; belum diputuskan pemilik proyek.