# Tasks

## 1. Token warna dan primitive dialog

- [ ] 1.1 Tambahkan `--brand-danger` / `--brand-danger-ink` di `:root` (`#B42318` / `#FDECEA`) dan `.dark` (`#FF8A80` / `#12152A`), lalu daftarkan `--color-danger` / `--color-danger-ink` di blok `@theme` dari `resources/css/app.css`; verifikasi dengan `npm run check` bahwa tidak ada kelas atau token yang ditolak
- [ ] 1.2 Tambahkan prop opsional `showCloseButton` (default `true`) pada `DialogContent` di `resources/js/components/ui/dialog.tsx` dan render tombol tutup hanya ketika prop bernilai benar; verifikasi `delete-user.tsx` masih menampilkan tombol tutup dan lolos `npm run types:check`

## 2. Komponen dialog konfirmasi logout

- [ ] 2.1 Buat `resources/js/components/logout-dialog.tsx` dengan prop `open` dan `onOpenChange`, judul "Keluar dari akun?", kalimat penjelas bahasa Indonesia, `DialogFooter` berisi `Batal` (`DialogClose`) dan `Keluar`; verifikasi `data-test="confirm-logout-button"` melekat pada tombol konfirmasi
- [ ] 2.2 Terapkan gaya pada `DialogContent`: `bg-surface border-hairline rounded-xl shadow-[0_4px_16px_rgb(18_21_42/0.12)]`, overlay `bg-night/60`, `showCloseButton={false}`, judul `text-lg font-bold`, isi `text-sm text-ink-soft`, tombol `rounded-lg`; verifikasi secara manual di mode terang dan gelap bahwa merah isian memakai token baru dan cincin fokus `ring-ink` dengan `ring-offset-2` terlihat
- [ ] 2.3 Hubungkan tombol "Keluar" ke `router.post(logout.url())` dengan state `processing` yang menonaktifkan tombol, memanggil `cleanup()` dari `useMobileNavigation` dan `router.flushAll()` sebelum navigasi, lalu menutup dialog; verifikasi klik ganda tidak mengirim lebih dari satu permintaan logout lewat tab Network

## 3. Sambungkan ke menu pengguna

- [ ] 3.1 Ubah `resources/js/components/user-menu-content.tsx`: terima prop wajib `onRequestLogout: () => void`, ganti `Link` logout dengan item yang memanggil prop itu, dan pertahankan `data-test="logout-button"`; verifikasi import `logout` dan `router` yang tidak terpakai dihapus
- [ ] 3.2 Tambahkan state `useState<boolean>` dan render `LogoutDialog` di `resources/js/components/nav-user.tsx`, teruskan `onRequestLogout` ke `UserMenuContent`; verifikasi di browser: klik item logout dari sidebar menampilkan dialog dan sesi tetap aktif
- [ ] 3.3 Lakukan hal yang sama di `resources/js/components/app-header.tsx` agar prop wajib terpenuhi; verifikasi `npm run types:check` lolos tanpa kesalahan

## 4. Verifikasi akhir

- [ ] 4.1 Uji interaksi manual di browser pada sidebar: "Batal", Escape, dan klik overlay menutup dialog tanpa logout; "Keluar" mengakhiri sesi dan mengarahkan ke `/login`; verifikasi setiap jalur secara berurutan
- [ ] 4.2 Uji navigasi keyboard (Tab dan Shift+Tab) saat dialog terbuka, pastikan fokus terjebak di dalam dialog dan kembali ke pemicu setelah ditutup; verifikasi tanpa error di konsol browser
- [ ] 4.3 Jalankan `npm run types:check`, `npm run check`, `npm run build`, dan `composer test`; verifikasi semuanya keluar dengan kode 0 dan `php artisan test --filter=AuthenticationTest` tetap hijau
- [ ] 4.4 Tinjau diff akhir: hanya enam berkas yang tercantum di `proposal.md` yang berubah, tanpa import mati, tanpa perubahan pada `verify-email.tsx`, dan tanpa perubahan pada `docs/ERD/erd.md` maupun `docs/FunctionalRequirment/functional_requirment.md` sehingga tidak ada versi dokumen lama yang perlu dimensiunkan ke `logs/` atau baris baru di `release.md`

## Workflow follow-up

- Arsipkan change setelah review dan pastikan requirement dari `specs/auth/logout/spec.md` masuk ke `openspec/specs/auth/logout/spec.md`.
