# Design

## Context

Lihat `proposal.md` untuk motivasi. Kondisi teknis yang membentuk pendekatan:

- `resources/css/app.css` memuat dua lapisan token: `.dark` bawaan shadcn (netral `oklch(...)`) dan `.dark` merek (`--brand-*`). `body` memakai lapisan pertama lewat `bg-background`, kartu memakai lapisan kedua lewat `bg-surface`.
- Token merek yang tertulis di `docs/DESIGN.md` tapi tidak ada di CSS: Permukaan terangkat, warna status.
- Warna status di-hardcode sebagai arbitrary Tailwind di `resources/js/pages/owner/kosts/index.tsx:34-45`.
- `--chart-1..5` masih nilai bawaan shadcn, termasuk ungu dan pink. Belum dipakai halaman mana pun.
- `--brand-night` dan `--brand-canvas` bernilai sama, dipakai terpisah untuk latar aside dan overlay.
- Seluruh 32 pasangan kontras di `docs/DESIGN.md` sudah dihitung ulang dan benar; yang salah bukan angkanya, melainkan pilihan chroma dan tidak adanya batas atas.

## Goals / Non-Goals

**Goals:**

- Satu set token gelap dengan chroma rendah, semua ambang `docs/DESIGN.md` tetap lolos.
- Satu latar gelap per halaman, tanpa dua sistem warna.
- Semua warna peran (status, chart, scrim, permukaan terangkat) menjadi token CSS.
- Dokumen dan kode kembali sinkron, dengan rasio terhitung untuk setiap pasangan.

**Non-Goals:**

- Tidak mengubah mode terang selain warna bayangan dan pemetaan token.
- Tidak menambah dial ENERGY/RHYTHM/MOTION, spek motion, state, ikon, foto, peta, atau copy.
- Tidak menyesuaikan latar chip status mode terang (kontras sekarang `1,05` sampai `1,13 : 1` terhadap putih). Itu tercatat sebagai temuan, bukan bagian change ini.
- Tidak menyentuh komponen shadcn di `resources/js/components/ui/*`; hanya nilai tokennya.

## Decisions

### D1. Nilai token gelap baru

Hue navy dipertahankan (`hue 265` di OKLCH), chroma diturunkan sekitar lima kali, luminance disusun ulang agar pemisahan permukaan tetap terukur.

| Token | Lama | Baru | Rasio |
|---|---|---|---|
| Latar | `#12152A` | `#101317` | |
| Permukaan | `#202748` | `#24282E` | 1,26 : 1 ke Latar |
| Permukaan terangkat | `#2A3157` (tidak di CSS) | `#2F333B` | 1,17 : 1 ke Permukaan, 1,47 : 1 ke Latar |
| Garis | `#414A7C` | `#3D424D` | 1,47 : 1 ke Permukaan |
| Garis input | `#7A83A8` | `#6E7687` | 3,25 : 1 ke Permukaan |
| Teks utama | `#F3F4FA` | `#DDE0E4` | 11,2 : 1 ke Permukaan |
| Teks redup | `#A4ABC8` | `#989FAD` | 5,6 : 1 ke Permukaan |
| Jingga utama | `#FF8A2B` | `#FF8A2B` | 6,3 : 1 ke Permukaan |

Alasan: chroma `0,061` pada permukaan seluas kartu adalah penyebab keluhan, bukan hue-nya. Alternatif yang ditolak: mempertahankan chroma dan hanya menggelapkan latar (tidak menyelesaikan halation), dan membuang biru total ke grafit hangat (membalik keputusan `docs/DESIGN.md` §8 dan menghapus identitas navy).

### D2. Batas atas kontras teks

Teks isi mode gelap diturunkan dari `13,2 : 1` ke `11,2 : 1`, dan dokumen mendapat aturan baru: teks isi MUST NOT melebihi `12 : 1` terhadap Permukaan. `11,2` dipilih agar masih ada ruang bila permukaan digeser sedikit.

Alasan: WCAG hanya mengatur minimum. Di layar OLED pada ruang gelap, teks hampir putih di atas permukaan gelap menambah halo di tepi huruf. Alternatif yang ditolak: menurunkan sampai `8 : 1`, karena teks redup akan kehilangan hierarki terhadap teks utama.

### D3. Pemetaan token shadcn ke token merek

Blok `.dark` shadcn diisi nilai merek, bukan nilai netral:

| Token shadcn | Nilai |
|---|---|
| `--background` | `#101317` |
| `--card`, `--popover` | `#24282E` |
| `--foreground` | `#DDE0E4` |
| `--muted-foreground` | `#989FAD` |
| `--secondary`, `--muted`, `--accent` | `#2F333B` |
| `--border`, `--input` | `#3D424D` |
| `--ring` | `#DDE0E4` |

Alasan: `body` memakai `bg-background`, jadi tanpa pemetaan ini halaman punya latar abu netral sementara kartu navy, dan perbedaan itu terlihat sebagai pita di tepi viewport. Alternatif yang ditolak: memindahkan `body` ke `bg-canvas` dan membiarkan token shadcn netral, karena dialog, sheet, dan sidebar tetap memakai token shadcn.

### D4. Token status

Warna status menjadi token `--status-*` untuk teks dan latar chip. Mode terang tidak berubah. Mode gelap: latar chip dinaikkan agar terpisah minimal `1,2 : 1` dari Permukaan baru (`#24282E`), teks tetap.

| Status | Teks | Latar chip lama | Latar chip baru | Rasio latar ke Permukaan |
|---|---|---|---|---|
| Tersedia, disetujui | `#5BD68F` | `#14352A` | `#0F3D2C` | 1,22 : 1 |
| Menunggu verifikasi | `#7DB2FF` | `#1B2B4F` | `#22375F` | 1,26 : 1 |
| Ditolak, ditangguhkan | `#FF8A80` | `#3D1E24` | `#522A31` | 1,22 : 1 |
| Terisi, nonaktif | `#A4ABC8` | `#2A3050` | `#303654` | 1,26 : 1 |

Teks terhadap latar chip baru: `6,65`, `5,44`, `5,30`, `5,19 : 1`. Alasan: nilai lama lolos terhadap Permukaan lama (`#202748`), tapi setelah Permukaan digeser ke `#24282E` chip-nya turun ke `1,01` sampai `1,11 : 1` dan menyatu dengan kartu.

### D5. Palet chart

Lima token, semua turunan jingga, kuning, dan netral. Tidak ada hue baru.

| Token | Mode terang | Mode gelap |
|---|---|---|
| `--chart-1` | `#B54708` | `#FF8A2B` |
| `--chart-2` | `#C2410C` | `#FFB366` |
| `--chart-3` | `#D97706` | `#FFC933` |
| `--chart-4` | `#5E6580` | `#989FAD` |
| `--chart-5` | `#1B2038` | `#6E7687` |

Semua seri mencapai minimal `3 : 1` terhadap latar grafiknya (putih mode terang, `#24282E` mode gelap). Alasan memakai ramp satu keluarga: kontras antar dua seri dari keluarga yang sama tidak akan mencapai `3 : 1` untuk lima seri, jadi pembeda utama adalah posisi dan label langsung, bukan warna. Alternatif yang ditolak: meminjam biru status sebagai seri chart, karena itu mencampur makna status dengan data.

### D6. Scrim dan penghapusan `--brand-night`

Token baru `--brand-scrim`: `rgb(27 32 56 / 0.55)` mode terang, `rgb(16 19 23 / 0.6)` mode gelap. `--brand-night` dihapus; aside login memakai `bg-canvas`, overlay memakai `bg-scrim`. Alasan: dua nama untuk satu nilai adalah sumber drift yang sudah terjadi sekali.

### D7. Bayangan

Satu tingkat, `0 4px 16px rgb(27 32 56 / 0.12)` di mode terang (tint Teks utama, bukan Latar lama). Mode gelap tidak memakai bayangan: popover memakai Permukaan terangkat plus Garis. Alasan: bayangan navy `0,12` tidak terlihat di atas `#101317`, jadi aturan lama hanya menambah ketidakpastian.

## Risks / Trade-offs

- [Kontras marker peta berubah] Jingga tetap, Latar menggelap, sehingga rasio marker terhadap latar naik dari `6,9` ke `7,9 : 1`; angka di `docs/DESIGN.md` §6 harus diperbarui agar tidak salah kutip → perbarui tabel peta pada task dokumen.
- [Status masih hardcoded di tempat lain] Baru satu halaman yang ditemukan (`owner/kosts/index.tsx`), bisa ada duplikat saat halaman admin bertambah → task memuat `grep` seluruh `resources/js` dan mengganti setiap hex status.
- [Chip mode terang tetap di bawah 1,2 : 1] Sengaja di luar scope; dicatat sebagai temuan agar tidak dianggap selesai → masukkan ke catatan `docs/DESIGN.md` §8.
- [Komponen shadcn bergantung pada `--ring` dan `--input`] Pemetaan bisa membuat cincin fokus kurang terlihat di atas tombol jingga → verifikasi keyboard pada dialog dan tombol jingga di kedua mode.
- [Grafik belum dipakai] Tidak ada regresi visual sekarang, tapi nilai token adalah kontrak → verifikasi rasio saat grafik pertama dibangun.

## Migration Plan

1. Ubah token di `resources/css/app.css` (merek dan shadcn, kedua mode untuk bayangan dan scrim).
2. Ganti pemakaian `bg-night` dan hex status di komponen.
3. Perbarui `docs/DESIGN.md`.
4. Verifikasi: `npm run check`, `npm run types:check`, `npm run build`, lalu cek manual kedua mode.

Rollback: satu commit token dan satu commit komponen; revert keduanya. Tidak ada perubahan data, jadi tidak ada langkah pemulihan khusus.

## Open Questions

Tidak ada. Penyesuaian chip mode terang dan spek motion/state sengaja ditunda ke change lain, bukan pertanyaan terbuka.
