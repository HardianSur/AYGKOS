# Tasks

## 1. Token inti mode gelap

- [ ] 1.1 Tulis ulang blok `.dark` merek di `resources/css/app.css` sesuai D1: `--brand-canvas #101317`, `--brand-surface #24282E`, `--brand-surface-raised #2F333B`, `--brand-hairline #3D424D`, `--brand-field #6E7687`, `--brand-ink #DDE0E4`, `--brand-ink-soft #989FAD`, `--brand-ember #FF8A2B`. Verifikasi: `grep -n "brand-surface-raised" resources/css/app.css` menemukan token, dan `npm run build` sukses.
- [ ] 1.2 Petakan blok `.dark` shadcn ke token merek sesuai D3 (`--background`, `--card`, `--popover`, `--foreground`, `--muted-foreground`, `--secondary`, `--muted`, `--accent`, `--border`, `--input`, `--ring`). Verifikasi: `grep -n "oklch(0.145 0 0)" resources/css/app.css` tidak lagi muncul di blok `.dark`.
- [ ] 1.3 Tambah token `--brand-scrim` (mode terang `rgb(27 32 56 / 0.55)`, mode gelap `rgb(16 19 23 / 0.6)`) dan daftarkan di `@theme` sebagai `--color-scrim`. Verifikasi: kelas `bg-scrim` terbentuk dan `npm run build` sukses.
- [ ] 1.4 Perbarui satu tingkat bayangan mode terang ke `0 4px 16px rgb(27 32 56 / 0.12)` dan pastikan mode gelap tidak memakai bayangan. Verifikasi: `grep -n "18 21 42" resources/css/app.css` kosong.
- [ ] 1.5 Hitung ulang rasio token D1 (Permukaan ke Latar, terangkat ke Permukaan, terangkat ke Latar, Garis input ke Permukaan, Teks utama dan Teks redup ke Permukaan). Verifikasi: hasil cocok dengan tabel D1 pada selisih maksimal 0,05.

## 2. Token status dan pemakaian komponen

- [ ] 2.1 Tambah token `--status-*` untuk teks dan latar chip, mode terang tidak berubah, mode gelap sesuai D4, lalu daftarkan di `@theme` (`--color-status-*`). Verifikasi: `npm run build` sukses dan kelas `bg-status-*` serta `text-status-*` tersedia.
- [ ] 2.2 Ganti `statusStyles` di `resources/js/pages/owner/kosts/index.tsx:34-45` menjadi kelas token. Verifikasi: `grep -n "#[0-9A-Fa-f]\{6\}" resources/js/pages/owner/kosts/index.tsx` kosong, dan chip tampil benar di kedua mode.
- [ ] 2.3 Ganti `bg-night/60` menjadi `bg-scrim` di `resources/js/components/logout-dialog.tsx` dan `resources/js/pages/owner/kosts/create.tsx`, serta `bg-night` menjadi `bg-canvas` dengan `text-night-ink` menjadi `text-ink` dan `text-night-soft` menjadi `text-ink-soft` di `resources/js/layouts/auth/auth-login-layout.tsx`. Verifikasi: `grep -rn "night" resources/js` kosong.
- [ ] 2.4 Hapus token `--brand-night`, `--brand-night-ink`, dan `--brand-night-soft` dari `resources/css/app.css` beserta entri `@theme`-nya. Verifikasi: `grep -rn "brand-night" resources` kosong dan `npm run build` sukses.
- [ ] 2.5 Pastikan tidak ada hex status lain yang tersisa di halaman lain. Verifikasi: `grep -rn "E8F0FB\|E6F4EC\|FDECEA\|EDEFF5\|1B2B4F\|14352A\|3D1E24\|2A3050" resources/js` kosong.

## 3. Palet chart

- [ ] 3.1 Ganti `--chart-1` sampai `--chart-5` di `:root` dan `.dark` sesuai D5. Verifikasi: `grep -n "303.9\|16.439\|264.376" resources/css/app.css` kosong.
- [ ] 3.2 Hitung rasio setiap seri terhadap latar grafiknya (putih mode terang, `#24282E` mode gelap). Verifikasi: semua minimal `3 : 1`.

## 4. Dokumen desain

- [ ] 4.1 Perbarui `docs/DESIGN.md` §2 (tabel token dan rasio baru), §3 (latar chip status gelap D4), §5 (bayangan dan aturan tanpa bayangan di mode gelap), §6 (rasio marker peta `7,9 : 1`). Verifikasi: setiap nilai di dokumen sama persis dengan `resources/css/app.css`.
- [ ] 4.2 Tambahkan aturan batas atas kontras teks isi mode gelap `12 : 1` di §2, dan catatan bahwa chip status mode terang belum disesuaikan di §8. Verifikasi: kedua kalimat ada dan tidak bertentangan dengan `specs/ui/design-tokens/spec.md`.
- [ ] 4.3 Perbarui §1: ganti alasan "agar tidak jatuh ke pola desain AI" dengan alasan produk (audiens mahasiswa, foto kost, jingga komplementer). Verifikasi: §1 tidak lagi memuat alasan berbasis menghindari gaya AI.
- [ ] 4.4 Catat item di luar scope (dial ENERGY/RHYTHM/MOTION, motion, state, ikon, foto, peta, copy) di §7. Verifikasi: daftar tersebut ada di §7.

## 5. Verifikasi integrasi

- [ ] 5.1 Jalankan `npm run check`, `npm run types:check`, dan `npm run build`. Verifikasi: ketiganya hijau.
- [ ] 5.2 Cek manual mode terang dan gelap pada halaman login (aside), daftar kost (chip status), dialog logout, dan dropdown: tidak ada dua latar gelap, chip terpisah dari kartu, cincin fokus terlihat. Verifikasi: catat hasil per halaman.
- [ ] 5.3 Jalankan `openspec validate design-tokens-navy-tenang --strict`. Verifikasi: valid.

## Workflow follow-up

- Arsipkan change setelah review pemilik proyek selesai.
- Jalankan `openspec archive design-tokens-navy-tenang` dan pastikan `openspec/specs/ui/design-tokens/spec.md` terbentuk.
