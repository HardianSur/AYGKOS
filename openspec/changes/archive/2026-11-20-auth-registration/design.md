# Design — auth-registration

## Context

Motivasi ada di `proposal.md`; kebutuhan perilaku ada di `specs/auth/registration/spec.md` dan `specs/auth/verification/spec.md`. Yang membentuk pendekatan ini:

- `app/Actions/Fortify/CreateNewUser.php` memvalidasi lewat `ProfileValidationRules` + `PasswordValidationRules` dan selalu menyimpan `Role::where('name', 'customer')`. Tidak ada input peran maupun nomor WhatsApp.
- Fortify menjalankan `RegisteredUserController::store` → `CreatesNewUsers` → `event(new Registered($user))` → `guard->login($user)` → `RegisterResponse`. Listener `SendEmailVerificationNotification` sudah terdaftar otomatis oleh framework, jadi email verifikasi Customer tidak perlu ditulis ulang.
- `Laravel\Fortify\Http\Responses\RegisterResponse` mengembalikan `redirect()->intended(Fortify::redirects('register'))`, yaitu `config('fortify.redirects.register')` lalu `config('fortify.home')` = `/dashboard`.
- `routes/web.php` memakai grup `['auth','verified']` untuk `/dashboard`. `Illuminate\Auth\Middleware\EnsureEmailIsVerified` memeriksa `hasVerifiedEmail()`, yang pada `User` masih bawaan Laravel: hanya `email_verified_at`.
- `User` sudah punya `whatsapp_number`, `whatsapp_verified_at`, relasi `role()` dan `whatsappVerifications()`; `WhatsappVerification` sudah punya `code_hash`, `expires_at`, `attempt_count`, `resend_count`, `last_sent_at`, `consumed_at`, dengan `UPDATED_AT = null` sehingga setiap penerbitan kode adalah baris baru.
- `config/otp.php` sudah menetapkan `expiry_minutes=5`, `max_attempts=5`, `resend_cooldown_seconds=60`, `max_sends_per_hour=3`.
- `OtpSender` hanya `send(string $number, string $code): void`; `FonnteOtpSender` menelan kegagalan penyedia dan mencatatnya ke log. Tidak ada kode yang membuat atau memeriksa OTP.
- `VerifyEmail` framework bukan `ShouldQueue`, jadi tautan email terkirim sinkron tanpa worker; `phpunit.xml` memakai `MAIL_MAILER=array` dan `QUEUE_CONNECTION=sync`.
- Change `auth-login` menyediakan `resources/js/layouts/auth/auth-login-layout.tsx` (dua panel merek), token brand di `resources/css/app.css`, `lang/id/auth.php`, `lang/id/validation.php`, dan `APP_LOCALE=id`. Change ini memakai ulang semuanya.
- Tidak ada komponen `radio-group` di `resources/js/components/ui/`; yang tersedia `toggle-group` dan `checkbox`.

## Goals / Non-Goals

**Goals:**

- Satu aturan verifikasi per peran yang dipakai bersama oleh middleware `verified`, penolakan login, dan pengarahan setelah registrasi.
- Penerbitan dan pemeriksaan OTP berada di satu tempat yang dapat diuji tanpa jaringan dan tanpa membaca tabel secara tersebar.
- Halaman register dan verify-whatsapp memakai layout merek yang sama dengan login tanpa mengubah halaman `auth/*` lain.

**Non-Goals:**

- Middleware `EnsureRole`/`EnsureAccountVerified` dan grup route per peran (FR-AUT-13).
- Pengarahan setelah login per peran (FR-AUT-10).
- Pengubahan nomor WhatsApp dari halaman profil (FR-PRF-03) dan lupa password (FR-AUT-11).
- Pemberitahuan in-app (FR-NTF) — halaman verifikasi memakai flash session Inertia.

## Decisions

### 1. Penerbitan dan pemeriksaan OTP di satu layanan domain

Buat `app/Services/Otp/OtpService.php` dengan tiga operasi: `issue(User $user, string $number)`, `verify(User $user, string $code)`, dan `canResend(User $user)`. Layanan ini memegang `OtpSender`, `config('otp.*')`, pembuatan kode 6 digit lewat `random_int`, hash lewat `Hash::make`, dan seluruh aturan kedaluwarsa/percobaan/konsumsi.

Alternatif yang ditolak: menaruh logika di controller verifikasi. Aturan kedaluwarsa dan batas percobaan juga dibutuhkan saat penerbitan ulang dan saat pengujian langsung, sehingga menaruhnya di controller menduplikasi aturan atau memaksa controller lain memanggil controller. Alternatif "satu Action class per operasi" ditolak karena ketiganya berbagi invarian yang sama (hanya satu kode aktif per akun) dan selalu dipakai bersamaan.

Invarian yang dijaga layanan: satu akun hanya punya satu kode aktif; `issue` membatalkan kode sebelumnya (`consumed_at` diisi tanpa menetapkan status terverifikasi); `verify` menaikkan `attempt_count` pada setiap kegagalan dan menandai `consumed_at` hanya pada keberhasilan.

### 2. `verify` mengembalikan hasil eksplisit, bukan melempar exception

`OtpService::verify` mengembalikan salah satu nilai `OtpResult` (`verified`, `invalid`, `expired`, `too_many_attempts`) sehingga controller memetakan masing-masing ke pesan `lang/id`. Alternatif "lempar `ValidationException` dari layanan" ditolak karena layanan yang sama akan dipakai pengujian langsung dan kemungkinan pemakaian CLI di masa depan; exception validasi mengikatnya ke lapisan HTTP.

### 3. Kode OTP hanya terkirim sekali, bukan lewat event

`CreateNewUser` memanggil `OtpService::issue` **setelah** `User::create` dan **sebelum** mengembalikan user, sehingga Fortify merespons setelah kode terkirim. Alternatif "listener pada `Registered`" ditolak karena listener berjalan untuk kedua peran dan harus memeriksa peran lagi; alur yang berbeda per peran lebih terbaca di satu tempat (`CreateNewUser`) yang memang sudah tahu peran yang dipilih. Email verifikasi tetap lewat listener bawaan — kode itu tidak ditulis di change ini dan tidak perlu diubah.

Konsekuensi yang diterima: bila pengiriman OTP gagal, akun tetap terbuat dan user tetap diarahkan ke `/verify-whatsapp`; halaman tersebut menampilkan tombol kirim ulang. Ini sesuai `platform/outbound-messaging`.

### 4. Peran divalidasi di `CreateNewUser`, bukan di controller baru

`CreateNewUser` menambah aturan `role` (`required`, `in:customer,pemilik_kost`) dan `whatsapp_number` (`required_if:role,pemilik_kost`, `nullable`, format nomor Indonesia, `Rule::unique(User::class)`). `role_id` diambil dari nama peran yang dipilih. Nilai `admin` tidak ada di daftar `in:` sehingga permintaan langsung pun ditolak sebelum menyentuh model.

Alternatif yang ditolak: mengganti route `POST /register` dengan controller sendiri. Fortify sudah memiliki throttle, pembatasan `guest`, dan event `Registered`; menggantinya berarti menyalin semuanya.

`whatsapp_number` dinormalisasi ke format `62…` sebelum disimpan agar keunikan kolom tidak bisa ditembus dua ejaan nomor yang sama (`08…` vs `628…`).

### 5. `hasVerifiedEmail()` diarahkan ke aturan per peran

`User::hasVerifiedEmail()` di-override menjadi `$this->hasVerifiedIdentity()`, dengan `hasVerifiedIdentity()` yang sudah diperkenalkan change `auth-login`. Tidak ada middleware baru. Dengan itu:

- `EnsureEmailIsVerified` pada grup `['auth','verified']` meloloskan Pemilik Kost yang WhatsApp-nya terbukti.
- Halaman pemberitahuan verifikasi email tidak lagi menahan Pemilik Kost.

Alternatif "middleware `EnsureAccountVerified` baru sekarang" ditolak: itu pekerjaan FR-AUT-13, dan satu override tiga baris menutup lubangnya tanpa menambah permukaan baru.

### 6. `RegisterResponse` diganti agar tujuan mengikuti peran

Daftarkan implementasi sendiri untuk `Laravel\Fortify\Contracts\RegisterResponse` di `FortifyServiceProvider`: `pemilik_kost` → `route('verification.whatsapp.notice')`, lainnya → `route('verification.notice')`. `redirect()->intended()` tidak dipakai karena pendaftaran bukan kasus "kembali ke halaman yang semula diminta"; bila ada intended URL dari kunjungan sebelumnya, tetap diabaikan agar pengguna tidak melewati verifikasi.

Alternatif yang ditolak: `config('fortify.redirects.register')`. Nilai itu satu untuk semua peran, dan memilih tujuan berdasarkan peran butuh kode.

### 7. Route verifikasi WhatsApp terpisah, tiga endpoint

`routes/web.php` menambah grup `['auth']` (bukan `verified`, karena justru sedang menyelesaikan verifikasi):

- `GET /verify-whatsapp` → `verification.whatsapp.notice`, menampilkan halaman dan status flash.
- `POST /verify-whatsapp` → `verification.whatsapp.store`, memeriksa kode; sukses mengisi `whatsapp_verified_at` dan mengarahkan ke `/dashboard`.
- `POST /verify-whatsapp/resend` → `verification.whatsapp.send`, memakai `throttle:6,1` seperti `verification.send` Fortify.

Pemeriksaan frekuensi kirim ulang berada di `OtpService` (jeda 60 detik, maksimum 3 per jam dari `last_sent_at` per akun), bukan hanya di rate limiter route, karena rate limiter menghitung per IP dan tidak dapat membedakan pengguna yang berpindah jaringan.

Pengguna yang `whatsapp_verified_at` sudah terisi dan membuka `/verify-whatsapp` dialihkan ke `/dashboard` — halaman ini tidak punya arti setelah verifikasi.

### 8. Halaman memakai layout merek yang sama, form tetap Inertia

`register.tsx` dan `verify-whatsapp.tsx` menyetel `Component.layout = AuthLoginLayout` dengan `title`/`description` Indonesia. `verify-email.tsx` yang ada tetap di `auth-simple-layout` (tidak diubah change `auth-login`) tetapi teksnya diterjemahkan; memindahkannya ke layout merek adalah perubahan tampilan yang tidak diminta.

Pilihan peran memakai `<input type="radio" name="role">` yang dibungkus label kartu dengan `has-checked:` Tailwind, tanpa komponen baru. Alternatif `ToggleGroup` shadcn ditolak karena tidak merender input tersembunyi, sehingga nilai tidak ikut terkirim dan harus disinkronkan manual.

Isian nomor WhatsApp dikondisikan dengan `useState` pada nilai radio. Karena React Compiler aktif, tidak ada `useMemo`/`useCallback` manual.

### 9. Tes sebagai bukti OTP benar-benar dibuat dan terkirim

- `OtpService` diuji langsung (tanpa HTTP): kode benar, kode salah menaikkan `attempt_count`, kedaluwarsa, batas percobaan, kode terpakai, kirim ulang terlalu cepat, kirim ulang setelah jeda, dan bahwa `code_hash` bukan plaintext (`Hash::check`).
- Pengiriman dibuktikan pada dua tingkat: `$this->mock(OtpSender::class)` untuk transport di tes registrasi, dan `Http::fake` pada `FonnteOtpSender` (sudah ada di `tests/Feature/Platform/OtpSenderTest.php`) untuk membuktikan isi permintaan keluar memuat kode.
- Email dibuktikan dengan `Notification::assertSentTo($user, VerifyEmail::class)`.
- Registrasi diuji untuk kedua peran, penolakan `role=admin`, keunikan email/nomor, dan pengarahan tujuan per peran.

## Risks / Trade-offs

- **Ketergantungan pada change `auth-login`** — layout, token brand, dan `lang/id` berasal dari sana; bila belum diterapkan, halaman register tidak bisa memakai `AuthLoginLayout` dan pesan tetap Inggris. Mitigasi: `proposal.md` mencatat prasyaratnya; `auth-login` sudah selesai dan tervalidasi, tinggal diterapkan lebih dahulu.
- **Override `hasVerifiedEmail()` mengubah perilaku bawaan framework** — paket atau kode lain yang memanggil `hasVerifiedEmail()` kini mendapat jawaban berbeda untuk Pemilik Kost. Mitigasi: itu memang tujuannya, dan aturannya sudah tertulis di `domain/identity-accounts`; satu tes menutup kedua arah (owner lolos, customer tetap dialihkan).
- **`CreateNewUser` mengirim OTP di dalam transaksi request** — bila penyedia WhatsApp lambat, pendaftaran ikut lambat (maksimum 10 detik dari timeout `FonnteOtpSender`). Mitigasi: timeout sudah ditetapkan dan kegagalan ditelan; memindahkan ke queue akan membuat tes sinkron lebih rumit dan menunda umpan balik ke pengguna, jadi ditolak untuk sekarang. Dicatat sebagai titik optimasi bila waktu respons pendaftaran menjadi keluhan.
- **Kode OTP terbaca di log saat `OTP_DRIVER=log`** — disengaja untuk pengembangan, tetapi berbahaya bila dipakai di produksi. Mitigasi: `platform/outbound-messaging` sudah mensyaratkan driver log hanya sebagai saklar darurat, dan `AppServiceProvider` mencatat peringatan saat token Fonnte kosong.
- **Rate limiter route per IP, bukan per akun** — dua pengguna di balik NAT berbagi jatah. Mitigasi: batas sebenarnya ada di `OtpService` per akun; throttle route hanya lapisan tambahan.
- **Email verifikasi juga terkirim ke Pemilik Kost** — listener framework berjalan untuk kedua peran. Mitigasi: diterima; menyaringnya menambah kode hanya untuk menghilangkan satu email, dan email tersebut tidak memblokir apa pun setelah `hasVerifiedEmail()` di-override.
- **Nomor WhatsApp unik di tingkat basis data** — dua pendaftaran bersamaan dengan nomor sama akan gagal pada constraint, bukan pada validasi. Mitigasi: constraint tetap menjadi penjaga terakhir; galat constraint dipetakan ke galat kolom `whatsapp_number` bila terjadi saat pengujian.

## Migration Plan

Tidak ada migrasi skema; seluruh kolom dan tabel sudah ada. Urutan penerapan:

1. `User::hasVerifiedEmail()` → `hasVerifiedIdentity()` (tanpa ini, owner terlempar ke verifikasi email).
2. `OtpService` + hasil `OtpResult` + tes layanan.
3. `CreateNewUser` (peran, nomor WhatsApp, penerbitan OTP) + `RegisterResponse` kustom.
4. Route dan controller verifikasi WhatsApp + halaman `verify-whatsapp`.
5. Halaman register dan terjemahan `verify-email`.
6. Tes registrasi dan verifikasi.

Rollback: hapus route dan halaman `verify-whatsapp`, kembalikan `CreateNewUser` ke `customer` saja, dan hapus override `hasVerifiedEmail()`. Data `whatsapp_verifications` boleh dibiarkan; baris tanpa kode aktif tidak berpengaruh.

## Open Questions

Tidak ada. Dua hal sengaja ditunda dan tidak mengubah spec: halaman verifikasi WhatsApp untuk pengubahan nomor dari profil (FR-PRF-03) dan pengarahan per peran setelah login (FR-AUT-10).