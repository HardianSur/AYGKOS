# Tasks

## 1. Prasyarat dari change `auth-login`

- [ ] 1.1 Pastikan `resources/js/layouts/auth/auth-login-layout.tsx`, token brand di `resources/css/app.css`, `lang/id/auth.php`, `lang/id/validation.php` ada, dan `APP_LOCALE=id` terpasang pada `.env.example` serta `phpunit.xml`. Verifikasi: `ls resources/js/layouts/auth/auth-login-layout.tsx lang/id && grep -n 'APP_LOCALE' .env.example phpunit.xml` menunjukkan semuanya ada dengan nilai `id`. Bila belum, terapkan change `auth-login` lebih dahulu.

## 2. Aturan verifikasi per peran

- [ ] 2.1 Tambah `User::hasVerifiedIdentity()` bila belum ada (Pemilik Kost → `whatsapp_verified_at`, Customer dan Admin → `email_verified_at`) dan override `hasVerifiedEmail()` agar memanggilnya. Verifikasi: tes membuktikan Pemilik Kost dengan `whatsapp_verified_at` terisi lolos route `['auth','verified']` walau `email_verified_at` kosong, sedangkan Customer tanpa `email_verified_at` tetap dialihkan ke `verification.notice`.
- [ ] 2.2 Tambah tes kelompok ini di `tests/Feature/Auth/EmailVerificationTest.php` (atau berkas tes verifikasi baru) untuk kedua arah di atas, memakai `forceFill`/state factory karena `#[Fillable]` tidak memuat kolom verifikasi. Verifikasi: `php artisan test --filter=EmailVerificationTest` hijau dan tes lama tetap lolos.

## 3. Layanan OTP

- [ ] 3.1 Buat `app/Services/Otp/OtpResult.php` (enum: `verified`, `invalid`, `expired`, `too_many_attempts`, `already_verified`) dan `app/Services/Otp/OtpService.php` dengan `issue(User $user, string $number): void`, `verify(User $user, string $code): OtpResult`, dan `canResend(User $user): bool`. `issue` membuat kode 6 digit acak, menyimpan `Hash::make($code)` + `expires_at` + `last_sent_at`, membatalkan (`consumed_at`) baris aktif sebelumnya, menegakkan jeda `resend_cooldown_seconds` serta batas `max_sends_per_hour`, lalu memanggil `OtpSender::send`. Verifikasi: `tests/Feature/Platform/OtpServiceTest.php` hijau untuk kode benar, salah, kedaluwarsa, batas percobaan, kode terpakai, dan kirim ulang terlalu cepat.
- [ ] 3.2 Tambah tes yang membuktikan kode tidak tersimpan terbaca: tangkap kode dari `OtpSender` yang di-mock, lalu pastikan `Hash::check($code, $row->code_hash)` benar dan `$row->code_hash !== $code`, dengan `expires_at` tidak lebih dari `config('otp.expiry_minutes')` menit. Verifikasi: tes tersebut hijau dan `WhatsappVerification::sole()` hanya berisi satu baris aktif setelah dua kali `issue`.
- [ ] 3.3 Tambah tes kirim ulang: sebelum jeda ditolak tanpa menambah `resend_count`, setelah jeda mengirim kode baru dan membatalkan yang lama, dan melewati `max_sends_per_hour` ditolak. Verifikasi: `php artisan test --filter=OtpServiceTest` hijau.

## 4. Registrasi per peran

- [ ] 4.1 Perbarui `app/Actions/Fortify/CreateNewUser.php`: validasi `role` (`required`, `in:customer,pemilik_kost`), validasi `whatsapp_number` (`required_if:role,pemilik_kost`, format nomor Indonesia, `Rule::unique(User::class)`), normalisasi nomor ke awalan `62`, simpan `role_id` sesuai peran, dan panggil `OtpService::issue` setelah akun Pemilik Kost dibuat. Verifikasi: `tests/Feature/Auth/RegistrationTest.php` membuktikan akun Customer dibuat tanpa nomor WhatsApp, akun Pemilik Kost menyimpan nomor ternormalisasi, dan `role=admin` ditolak tanpa membuat akun.
- [ ] 4.2 Tambah implementasi `Laravel\Fortify\Contracts\RegisterResponse` sendiri dan daftarkan di `FortifyServiceProvider`: Pemilik Kost → `route('verification.whatsapp.notice')`, lainnya → `route('verification.notice')`. Verifikasi: tes registrasi memeriksa `assertRedirect` tujuan yang benar untuk kedua peran.
- [ ] 4.3 Tambah tes bahwa OTP benar-benar terbit dan terkirim saat registrasi Pemilik Kost, memakai `$this->mock(OtpSender::class)` untuk menangkap kode, lalu memeriksa baris `whatsapp_verifications` (hash cocok, `expires_at` dalam 5 menit, `consumed_at` kosong) dan `OtpSender::send` dipanggil sekali dengan nomor ternormalisasi. Verifikasi: tes hijau dan tes registrasi Customer tidak memanggil `OtpSender`.
- [ ] 4.4 Tambah tes galat per kolom: email tidak valid, email duplikat, konfirmasi kata sandi tidak cocok, kata sandi lemah, nomor WhatsApp tidak valid, nomor WhatsApp duplikat, dan nomor WhatsApp kosong untuk peran Pemilik Kost. Verifikasi: `php artisan test --filter=RegistrationTest` hijau dan setiap tes memeriksa `assertSessionHasErrors` untuk kolom yang tepat.

## 5. Verifikasi WhatsApp

- [ ] 5.1 Tambah `app/Http/Controllers/Auth/WhatsappVerificationController.php` dengan `show`, `store`, dan `resend`, memakai `OtpService`; `store` memetakan `OtpResult` ke pesan `lang/id` dan mengisi `whatsapp_verified_at` dengan `forceFill` pada keberhasilan, lalu mengarahkan ke `/dashboard`. Pengguna yang sudah terverifikasi dialihkan ke `/dashboard`. Verifikasi: tes fitur membuktikan kode benar mengisi `whatsapp_verified_at` dan mengarahkan ke `dashboard`, sedangkan kode salah menampilkan galat dan tidak mengubah status.
- [ ] 5.2 Daftarkan route di `routes/web.php` dalam grup `['auth']`: `GET /verify-whatsapp` (`verification.whatsapp.notice`), `POST /verify-whatsapp` (`verification.whatsapp.store`), `POST /verify-whatsapp/resend` (`verification.whatsapp.send`, `throttle:6,1`). Verifikasi: `php artisan route:list --name=verification.whatsapp` menampilkan ketiganya dan `php artisan test --filter=WhatsappVerificationTest` hijau.
- [ ] 5.3 Tambah tes penolakan: kode kedaluwarsa, melebihi batas percobaan, kode terpakai, dan kirim ulang terlalu cepat — masing-masing memeriksa pesan galat yang tampil dan `whatsapp_verified_at` tetap kosong. Verifikasi: semua tes di grup ini hijau.
- [ ] 5.4 Tambah galat berbahasa Indonesia: tambahkan kunci yang dipakai (mis. `verification.invalid_code`, `verification.expired_code`, `verification.too_many_attempts`, `verification.resend_cooldown`) ke `lang/id/`, lalu pastikan tidak ada string Indonesia yang ditulis langsung di controller. Verifikasi: `grep -rn "Kode OTP" app/Http/Controllers` kosong dan `php artisan tinker --execute="echo __('verification.invalid_code');"` menghasilkan kalimat Indonesia.

## 6. Tampilan registrasi dan verifikasi

- [ ] 6.1 Tulis ulang `resources/js/pages/auth/register.tsx`: pilihan peran sebagai isian paling atas (dua kartu `input[type=radio]` ber-`name="role"` dengan `has-checked:`), isian nama/email/kata sandi/konfirmasi berlabel Indonesia, isian nomor WhatsApp yang hanya tampil saat peran `pemilik_kost` (`useState`, tanpa `useMemo`/`useCallback` manual), `InputError` per kolom, lalu set `Register.layout = AuthLoginLayout` dengan `title`/`description` Indonesia. Verifikasi: `npm run check` dan `npm run types:check` lolos, dan pada `/register` memilih peran Pemilik Kost menampilkan isian nomor WhatsApp tanpa reload.
- [ ] 6.2 Buat `resources/js/pages/auth/verify-whatsapp.tsx`: isian kode 6 digit, tombol kirim, tombol kirim ulang dengan sisa waktu tunggu dari props, pesan status/gagal berbahasa Indonesia, dan layout `AuthLoginLayout`. Verifikasi: halaman tampil pada `/verify-whatsapp` untuk pengguna yang belum terverifikasi WhatsApp, dan `npm run types:check` lolos.
- [ ] 6.3 Terjemahkan teks `resources/js/pages/auth/verify-email.tsx` ke bahasa Indonesia (judul, pemberitahuan tautan terkirim, tombol kirim ulang, tombol keluar) tanpa memindahkannya dari `auth-simple-layout`. Verifikasi: halaman `/email/verify` tidak memuat teks Inggris pada elemen yang terlihat, dan `npm run check` lolos.
- [ ] 6.4 Periksa aksesibilitas: setiap isian punya `Label` ber-`htmlFor`, grup radio memakai `fieldset`/`legend`, galat muncul lewat `InputError` di bawah isian terkait, dan urutan tab mengikuti urutan visual. Verifikasi: tinjau `/register` dan `/verify-whatsapp` dengan navigasi papan ketik dan pembaca layar, termasuk keadaan galat validasi.

## 7. Pemeriksaan integrasi

- [ ] 7.1 Buktikan jalur pengiriman OTP ke penyedia nyata tanpa jaringan: perluas `tests/Feature/Platform/OtpSenderTest.php` bila perlu sehingga `Http::assertSent` memeriksa `target` sama dengan nomor ternormalisasi dan `message` memuat kode. Verifikasi: `php artisan test --filter=OtpSenderTest` hijau.
- [ ] 7.2 Buktikan email verifikasi Customer terkirim setelah registrasi: tes memakai `Notification::fake()` lalu `Notification::assertSentTo($user, VerifyEmail::class)` pada alur `POST /register` peran customer. Verifikasi: tes hijau dan `email_verified_at` masih kosong setelah registrasi.
- [ ] 7.3 Jalankan gate proyek: `composer test`, `npm run check`, `npm run types:check`, `npm run build`. Verifikasi: keempat perintah hijau; bila ada yang merah, perbaiki sebelum melaporkan selesai.
- [ ] 7.4 Pastikan cakupan tidak meluas: `git diff --stat` tidak menyentuh `resources/js/pages/auth/{login,forgot-password,reset-password,confirm-password}.tsx`, `auth-simple-layout.tsx`, `auth-login-layout.tsx`, atau route login. Verifikasi: diff hanya memuat berkas yang tercantum di `proposal.md` bagian Impact.

## Workflow follow-up

- Perbarui `docs/ROADMAP.md` M2: tandai deliverable registrasi per peran, verifikasi dua jalur, kirim ulang, dan halaman `verify-whatsapp` sebagai selesai setelah change diarsipkan.
- Arsipkan change setelah hasil tinjauan disetujui.
- Putuskan apakah `EnsureRole`/`EnsureAccountVerified` (FR-AUT-13) menjadi change berikutnya di M2.