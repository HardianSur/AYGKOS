# Design

## Context

Lihat `proposal.md` untuk alasan perubahan dan `specs/auth/logout/spec.md` untuk kontrak perilaku.

Saat ini `resources/js/components/user-menu-content.tsx` merender item "Log out" sebagai `Link` langsung ke `logout()` (POST `/logout` dari Fortify). Komponen itu dipasang di dalam `DropdownMenuContent` oleh dua pemakai: `nav-user.tsx` (sidebar, jalur yang aktif) dan `app-header.tsx` (header; layoutnya belum direferensikan). Proyek memakai Inertia v3 + Wayfinder (`formVariants: true`), Radix Dialog dan Dropdown Menu yang sudah terpasang, serta token warna brand di `resources/css/app.css`.

Dua keterbatasan teknis membentuk rancangan ini:

- Radix membongkar `DropdownMenuContent` saat menu tertutup. Dialog yang ditempatkan di dalam item menu akan ikut hilang sebelum sempat tampil, jadi state dialog harus berada di pemakai menu, bukan di `UserMenuContent`.
- `resources/js/components/ui/dialog.tsx` adalah komponen turunan shadcn dengan tombol tutup (X) bawaan. Dialog konfirmasi hanya perlu dua pilihan eksplisit, jadi tombol itu disembunyikan lewat prop opsional tanpa mengubah perilaku `delete-user.tsx`.

## Goals / Non-Goals

**Goals:**

- Memisahkan "minta logout" dari "laksanakan logout" tanpa mengubah endpoint atau redirect.
- Menjaga konsistensi visual dengan `docs/DESIGN.md` pada kedua mode warna.
- Menjaga kompilasi TypeScript tetap valid di kedua pemakai `UserMenuContent`.

**Non-Goals:**

- Mengubah autentikasi sisi server, session driver, atau middleware.
- Menambahkan konfirmasi pada tautan "Keluar" di halaman verifikasi email.
- Mengganti seluruh komponen `ui/dialog.tsx` ke token brand; penyesuaian hanya lewat `className` pada pemakaian logout dan penambahan prop `showCloseButton`.
- Menambah runner tes frontend baru ke proyek.

## Decisions

**1. State dialog di pemakai menu, bukan di item menu.**
`UserMenuContent` menerima prop wajib `onRequestLogout: () => void`. `NavUser` dan `AppHeader` masing-masing menyimpan `useState<boolean>` dan merender `<LogoutDialog open onOpenChange />`. Prop wajib dipilih supaya TypeScript menandai pemakai mana pun yang lupa dihubungkan; tidak ada jalur diam-diam yang langsung logout. Alternatif `forceMount` pada `DropdownMenuContent` ditolak karena menyisahkan DOM menu yang tersembunyi dan justru menambah kompleksitas fokus.

**2. Dialog baru, bukan konfigurasi ulang `ui/dialog.tsx`.**
`resources/js/components/logout-dialog.tsx` membungkus `Dialog` dengan judul "Keluar dari akun?", satu kalimat penjelas, `Batal` (`DialogClose`), dan `Keluar`. `ui/dialog.tsx` hanya mendapat prop `showCloseButton = true`; pemakaian lain tidak berubah. Alternatif menambahkan `variant="destructive"` ke `button.tsx` ditolak karena varian itu memakai `--destructive` (oklch shadcn), bukan token merah status yang disyaratkan desain.

**3. Eksekusi logout lewat `router.post(logout.url())`.**
Route Wayfinder `logout` hanya menerima POST (Fortify). Tombol konfirmasi memanggil `router.post` lalu menutup dialog; `cleanup()` dari `useMobileNavigation` dan `router.flushAll()` tetap dijalankan seperti perilaku lama sebelum navigasi. Alternatif membungkus `DialogTrigger`/`Link` dengan method POST tidak dipilih karena navigasi terjadi sebelum konfirmasi selesai.

**4. Token warna merah status ditambahkan ke palet brand.**
`--brand-danger` dan `--brand-danger-ink` ditambahkan di `:root` (`#B42318` / `#FDECEA`) dan `.dark` (`#FF8A80` / `#12152A`), lalu didaftarkan sebagai `--color-danger` / `--color-danger-ink` di blok `@theme` supaya tersedia sebagai kelas Tailwind. Nama berbeda dari `--destructive` agar tidak menimpa tema shadcn. Kontras pasangan ini sesuai nilai status Ditolak/Ditangguhkan pada `docs/DESIGN.md` §3 dan diperiksa ulang saat implementasi.

**5. Gaya visual mengikuti §5 dan §6 DESIGN.md.**
Panel memakai `bg-surface border-hairline rounded-xl shadow-[0_4px_16px_rgb(18_21_42/0.12)]`; overlay `bg-night/60` (bukan hitam murni); judul `text-lg font-bold` (18/24), isi `text-sm text-ink-soft` (14/22); tombol `rounded-lg` (10 px). Cincin fokus `focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-surface`. Tidak ada bayangan kedua. Tombol tutup disembunyikan agar satu-satunya jalan keluar adalah "Batal", Escape, atau klik overlay.

## Risks / Trade-offs

- [Merah status dipakai sebagai isian tombol solid, padahal `docs/DESIGN.md` mendefinisikannya untuk label/status] → Keputusan eksplisit sesuai permintaan; kontras diperiksa terhadap ambang 4,5:1 dan dicatat di spec. Jika tinjauan desain menolak, ubah hanya kelas tombol dan spec tidak berubah.
- [Focus trap bertabrakan antara Radix Dropdown Menu dan Dialog] → Dialog dirender di luar `DropdownMenuContent` dan menu menutup sebelum dialog dibuka; diverifikasi manual dengan keyboard (Tab, Escape, klik luar).
- [Klik ganda mengirim dua permintaan logout] → Tombol dinonaktifkan setelah ditekan; handler Inertia dipanggil sekali.
- [`app-header.tsx` adalah jalur mati sehingga perubahan di sana sulit diverifikasi] → Prop wajib memaksa perubahan, dan verifikasi difokuskan pada jalur sidebar; header tetap di-check oleh `npm run types:check`.
- [Tidak ada tes frontend otomatis] → Daftar pemeriksaan manual tercantum di `tasks.md`; gate `npm run types:check`, `npm run check`, `npm run build`, dan `composer test` tetap dijalankan.

## Migration Plan

Tidak ada migrasi data, route, atau env. Implementasi dapat dibatalkan dengan mengembalikan berkas yang tercantum di `proposal.md` dan menghapus `logout-dialog.tsx` beserta spec delta ini. Tidak ada urutan deploy khusus karena perubahan murni frontend dan token CSS.

## Open Questions

Tidak ada. Pilihan warna tombol, cakupan titik logout, dan wadah spec sudah diputuskan sebelum proposal dibuat.
