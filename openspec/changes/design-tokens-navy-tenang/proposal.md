# Proposal

## Why

Mode gelap AYGKOS memakai navy dengan chroma tinggi (`#202748`, chroma 0,061) pada area permukaan yang luas, ditambah garis `#414A7C` (chroma 0,084) dan teks `13,2 : 1`. Kombinasi itu membuat mata cepat lelah, terutama di ponsel pada cahaya redup. Selain itu `docs/DESIGN.md` dan `resources/css/app.css` sudah tidak sinkron: permukaan terangkat dan warna status hanya hidup di dokumen atau di-hardcode per halaman, dan palet chart masih bawaan shadcn.

## What Changes

- Palet gelap diganti ke navy tenang: chroma diturunkan sekitar lima kali, hue navy dipertahankan.
- **BREAKING (token CSS)**: `--brand-night` dihapus. Latar memakai `--brand-canvas`, overlay memakai token baru `--brand-scrim`.
- Batas kontras teks isi mode gelap diturunkan dari `13,2 : 1` ke `11,2 : 1`, dan dokumen mendapat batas atas `12 : 1` agar tidak hanya mengejar minimum WCAG.
- Permukaan terangkat diimplementasikan sebagai token (`--brand-surface-raised`), tidak hanya tertulis di dokumen.
- Warna status dipindah dari hex hardcoded per halaman ke token CSS; latar chip disesuaikan agar terpisah dari permukaan baru.
- Token shadcn `.dark` dipetakan ke token merek, sehingga hanya ada satu latar gelap, bukan dua.
- Palet chart `--chart-1..5` diganti turunan jingga, kuning, dan netral; ungu dan pink bawaan dihapus.
- Aturan bayangan diperjelas: mode gelap memakai permukaan terangkat + Garis, tanpa bayangan; warna bayangan mode terang diperbarui.
- `docs/DESIGN.md` §1, §2, §3, §5, dan §8 diperbarui mengikuti nilai baru.

Di luar scope change ini: dial ENERGY/RHYTHM/MOTION, spek motion, spek state empty/loading/error, aturan ikon, spek foto dan peta, serta aturan copy/CTA. Temuan itu dikerjakan sebagai change terpisah.

## Capabilities

### New Capabilities

- `ui/design-tokens`: nilai token warna mode terang dan gelap, batas kontras teks, pemetaan token komponen (shadcn dan merek), palet chart, serta aturan elevasi yang wajib dipakai setiap halaman.

### Modified Capabilities

Tidak ada. Change ini tidak mengubah requirement fungsional (`FR-*`) maupun skema data.

## Impact

- `resources/css/app.css`: blok `.dark` merek, blok `.dark` shadcn, token status, token chart, token scrim, dan permukaan terangkat.
- `docs/DESIGN.md`: §1 alasan navy, §2 tabel token, §3 warna status, §5 bayangan, §8 catatan revisi.
- `resources/js/pages/owner/kosts/index.tsx`: `statusStyles` memakai kelas token, bukan hex.
- `resources/js/components/logout-dialog.tsx`, `resources/js/pages/owner/kosts/create.tsx`: overlay pindah dari `bg-night/60` ke `bg-scrim`.
- `resources/js/layouts/auth/auth-login-layout.tsx`: `bg-night` menjadi `bg-canvas`.
- Tidak ada perubahan pada `docs/ERD/erd.md` (tetap V003) dan `docs/FunctionalRequirment/functional_requirment.md`.
- Tidak ada dependency baru. Mode terang tidak berubah selain warna bayangan.
