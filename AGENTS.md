# AGENTS.md — AYGKOS

Marketplace kost berbasis Laravel 13 + Inertia v3 + React 19 + Tailwind v4 (PHP ^8.3, PostgreSQL 16). Sumber kebenaran: `docs/` dan `openspec/`. Scaffold + model/relasi domain sudah ada (10 model, 9 migrasi), tetapi fitur bisnis dan halaman kost belum dibangun.

## Batas kerja

- Kerjakan hanya yang masuk scope `docs/FunctionalRequirment/functional_requirment.md` (kode FR) dan fase di `docs/ROADMAP.md`. Jangan tambah fitur di luar kesepakatan tanpa izin.
- Fitur baru wajib punya change proposal di `openspec/` terlebih dahulu — jangan langsung menulis kode.
- Cek implementasi yang sudah ada sebelum membuat yang baru. Hormati kode anggota lain: jangan menimpa atau menulis ulang kode di luar task.
- Ikuti konvensi layer di `docs/ARCHITECTURE.md` (Controller -> FormRequest -> Policy -> Action -> Model).
- Kode harus sederhana, terbaca, dan dapat dipelihara. Jangan menambah abstraksi atau dependensi yang tidak perlu, dan jangan mengorbankan keterbacaan demi menyusutkan baris.

## Perintah

- `composer dev` -> `php artisan dev`: menjalankan `serve`, `queue:listen`, `pail`, dan `npm run dev` sekaligus lewat `@laravel/multiplex`. Butuh Postgres dan Node berjalan.
- `composer test` = `config:clear` -> `pint --test` -> `phpstan analyse` (level 7) -> `php artisan test`. Gate lokal utama.
- `composer ci:check` = `npm run check` -> `npm run types:check` -> `composer test`. Ini yang dijalankan CI.
- Satu test: `php artisan test --filter=<nama>` atau `./vendor/bin/pest tests/Feature/<file>.php`.
- Format PHP: `composer lint` (`pint --parallel`); cek saja: `composer lint:check`.
- Frontend: `npm run check` / `npm run check:fix` (`vp check`), `npm run types:check` (`tsc --noEmit`), `npm run build`.

## Lingkungan & data

- Basis data adalah **pgsql**, bukan sqlite. `.env.example` dan `phpunit.xml` memakai `pgsql`; tes membutuhkan database `aygkos_testing` (`DB_URL=""` di `phpunit.xml`).
- Pengembangan lewat lerd: `DB_HOST=lerd-postgres` (tersimpan di `.env.lerd_override`, gitignored). `127.0.0.1` hanya benar bila PHP berjalan langsung di host.
- Alternatif `compose.yaml`: service `pgsql` (postgres:16-alpine) dan `mailpit` pada profil `mail` (`docker compose --profile mail up -d`). Jangan menjalankan `lerd-postgres` dan container pgsql bersamaan — port 5432 bentrok.
- CI (`.github/workflows/tests.yml`): service postgres 16, PHP 8.5, `composer setup` lalu `composer ci:check`.
- Secret dan kredensial hanya di `.env` (gitignored); `.env.example` berisi placeholder. Jangan pernah commit atau hardcode API key, token, dan password. Jangan menghapus proteksi keamanan/aksesibilitas yang sudah ada.
- Konfigurasi: `config/uploads.php` (foto kost 3 MB, dokumen verifikasi & lampiran aduan 5 MB, maksimal 3 lampiran), `config/otp.php`, `config/services.php`. Variabel penting: `GEOAPIFY_API_KEY`, `OTP_DRIVER`, `FONNTE_TOKEN`, `MAIL_*`.
- Berkas privat (dokumen verifikasi, lampiran aduan) harus lewat `app/Support/PrivateFileUrl.php`, bukan diakses bebas dari `/storage/...`.
- Validasi input lewat Form Request, tangani galat dengan benar, dan jangan ekspos jejak internal ke pengguna.

## Backend

- Autentikasi dijalankan Fortify (`config/fortify.php`, `app/Providers/FortifyServiceProvider.php`, `app/Actions/Fortify/`). Tidak ada controller/komponen auth untuk login, register, reset, atau verifikasi — jangan dibuat ulang.
- Route terbagi di `routes/web.php` dan `routes/settings.php`. Halaman Inertia dirujuk lewat nama tanpa `.tsx` (`Route::inertia('/', 'welcome')`).
- Model domain memakai `id` UUID yang dibangkitkan aplikasi (`HasUuids`), bukan integer. Ikuti kebijakan timestamp per tabel dan aksi `on delete` foreign key di `openspec/specs/domain/persistence-conventions/spec.md` dan `docs/ERD/erd.md`.
- `phpstan.neon` menganalisis `app/`, `bootstrap/app.php`, `config/`, `database/`, `routes/` pada level 7 — tipe array dan docblock diperhatikan.
- `resources/js/{actions,routes,wayfinder}/` adalah hasil generate Wayfinder (gitignored). Import helper dari `@/routes/...`, jangan hardcode URL; regenerate dengan `php artisan wayfinder:generate`. Jangan mengeditnya manual.

## Frontend

- Package manager npm dengan `package-lock.json`. `pnpm-workspace.yaml` ada tetapi tidak ada `pnpm-lock.yaml` — jangan berganti package manager.
- Layout diresolversi dari nama halaman di `resources/js/app.tsx` (`welcome` -> tanpa layout, `auth/*` -> AuthLayout, `settings/*` -> AppLayout + SettingsLayout, selainnya -> AppLayout). Jangan menambah wrapper layout per halaman; gunakan `Page.layout = { breadcrumbs: [...] }` seperti di `pages/dashboard.tsx`.
- Tailwind v4: tanpa `tailwind.config.js`. Token tema di `resources/css/app.css`. Konfigurasi shadcn di `components.json`; alias `@/*` -> `resources/js/*` (`tsconfig.json`).
- React Compiler aktif (`reactCompilerPreset()` di `vite.config.ts`) — jangan menambah `useMemo`/`useCallback` manual tanpa alasan.
- Script Node lewat `vp` (vite-plus). `vite` di `package.json` adalah alias `@voidzero-dev/vite-plus-core`; konfigurasi lint/fmt ada di `vite.config.ts` bagian `lint`/`fmt`, bukan file ESLint/Prettier terpisah. `vp check` menolak warning.
- `resources/js/components/ui/*` adalah hasil generate shadcn dan dikecualikan dari `vp` lint/fmt. File lain wajib lolos `vp check`.
- Wayfinder memakai `formVariants: true`, sehingga helper `.form()` tersedia untuk form Inertia.
- Jaga konsistensi komponen, token, dan pola desain antarhalaman. Layout harus responsif di mobile dan desktop, dengan perhatian pada aksesibilitas dan keterbacaan; hindari styling ad-hoc per halaman.

## Dokumen & konvensi

- `docs/OVERVIEW.md` (tujuan), `docs/ARCHITECTURE.md` (arsitektur target; §12 mencatat status implementasi, bisa tertinggal dari kode), `docs/ROADMAP.md` (fase M0-M10), `docs/FunctionalRequirment/functional_requirment.md` (57 FR, 12 kelompok), `docs/ERD/erd.md` (V003).
- Peran pengguna: Customer, Pemilik Kost, Admin. Kode FR antara lain `FR-AUT-xx`, `FR-KOS-xx`, `FR-ADM-xx`, `FR-SRC-xx`, `FR-ADU-xx`, `FR-NTF-xx`. Pertahankan istilah ini.
- Versi dokumen ERD dan FR: versi terbaru di berkas utama, versi lama dibekukan di `logs/` sebagai `V<3 digit>-<dokumen>.md`, dan ditambahkan ke `release.md`. Aturan lengkap di `openspec/config.yaml`.
- Dokumen proyek ditulis dalam bahasa Indonesia. Gaya pesan commit mengikuti `git log` yang ada (mis. `ADD :`, `FIX :`, `feat(scope):`).
- File lokal yang tetap gitignored: `/opencode.json`, `/boost.json`, `/CLAUDE.md`, `.env.lerd_override`. `AGENTS.md` dan `.agents/` sudah ikut di-commit, jangan menganggapnya lokal.

## Sebelum selesai

- Tinjau diff: pastikan tidak ada import rusak, kode mati/unused, atau perubahan di luar task.
- Pastikan input divalidasi, galat ditangani, dan tidak ada secret di kode atau log.
- Jalankan gate yang tersedia: `composer test`, `npm run check`, `npm run types:check`, dan `npm run build`. Perbaiki kegagalan sebelum melaporkan selesai.
- Laporkan masalah yang belum terselesaikan beserta alasannya. Jangan mengklaim selesai bila ada gate yang merah.

<!-- antislop:start -->
## antislop
For UI, copy, people, mobile layout, or code comments work, read these installed skill files directly (use these paths even if a same-named global skill exists):
- Core filter, always on: `antislop`: `.agents/skills/antislop/SKILL.md`
- UI / visual: `antislop-ui`: `.agents/skills/antislop-ui/SKILL.md`
- Copy & text: `antislop-copywriting`: `.agents/skills/antislop-copywriting/SKILL.md`
- People: `antislop-human`: `.agents/skills/antislop-human/SKILL.md`
- Mobile / responsive: `antislop-layoutmobile`: `.agents/skills/antislop-layoutmobile/SKILL.md`
Before starting, follow the core's "Two Usage Modes" section in strict order: explicit session instruction first, then global preference, then ask. A session instruction always wins. For a resolved mode, say `antislop active: <mode> (session override).` or `antislop active: <mode> (global preference).` once before presenting findings or making edits, using the actual mode and source. Acknowledging the user's request without naming the source does not replace this notice.
Only an explicit choice of antislop during or after selects a session mode. A request to review, audit, or avoid file edits does not select a mode; read the global preference in that case. Another skill's mode does not select antislop's mode.
If the mode is unresolved, ask during/after and end the response; wait for the answer before any UI review, planning, or concept. For read-only tasks, put the active-mode notice only at the start of the final answer, never in progress messages. For editing tasks, announce before the first edit and omit it from the final answer.
To update antislop later: `npx antislop-ai --update`, or run `npx antislop-ai` and pick Overwrite them.
<!-- antislop:end -->
