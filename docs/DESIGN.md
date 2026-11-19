# Arah Desain AYGKOS

Jingga menjadi warna utama, dipadukan dengan kuning mentari dan dasar biru malam (navy), bukan hitam. Seluruh rasio kontras dihitung dengan rumus WCAG 2.x. Semua pasangan teks memenuhi 4,5 : 1, batas kolom form dan cincin fokus memenuhi 3 : 1, serta pemisahan permukaan mode gelap memenuhi ambang persepsi.

## 1. Dasar Pemilihan

- Jingga dan kuning memberi kesan hangat, menyambut, dan bersemangat. Kesan ini sesuai dengan pengguna yang sedang mencari tempat tinggal di lingkungan baru.
- Biru malam memberi rasa aman dan tepercaya. Jingga di atas biru malam merupakan kontras komplementer yang tegas, sehingga mode gelap tidak terkesan murung.
- Latar biru malam dipilih agar tidak jatuh ke pola umum desain AI, yaitu latar hitam dengan satu aksen neon.
- Audiens utama adalah mahasiswa yang mengakses dari ponsel. Prioritasnya keterbacaan pada layar kecil dan cahaya luar, sehingga teks kecil harus memakai keluarga huruf ber-x-height tinggi.

## 2. Token Warna

Rasio pada kolom terakhir adalah teks di atas Permukaan, kecuali dinyatakan lain.

| Token | Mode terang | Mode gelap | Peran | Kontras (terang / gelap) |
|---|---|---|---|---|
| Latar | `#F7F8FC` | `#12152A` | Latar halaman | |
| Permukaan | `#FFFFFF` | `#202748` | Kartu, form, panel | |
| Permukaan terangkat | `#FFFFFF` | `#2A3157` | Menu dropdown dan popover | |
| Teks utama | `#1B2038` | `#F3F4FA` | Teks dan judul | 16,0 : 1 / 13,2 : 1 |
| Teks redup | `#5E6580` | `#A4ABC8` | Keterangan dan teks sekunder | 5,8 : 1 / 6,4 : 1 |
| Garis | `#D8DCE8` | `#414A7C` | Pembatas dekoratif dan tepi kartu | 1,4 : 1 / 1,7 : 1 |
| Garis input | `#7D879F` | `#7A83A8` | Batas kolom form | 3,6 : 1 / 3,9 : 1 |
| Jingga utama | `#FF7A1A` | `#FF8A2B` | Isian tombol, sorotan, marker peta | Teks `#1B2038` di atasnya: 6,1 : 1 / teks `#12152A`: 7,7 : 1 |
| Jingga teks | `#B54708` | `#FF8A2B` | Tautan dan ikon | 5,4 : 1 / 6,2 : 1 |
| Jingga tipis | `#FFF1E6` | `#3B2A20` | Latar chip filter terpilih | Teks `#B54708` / `#FFB366`: 4,9 : 1 / 7,7 : 1 |
| Kuning Mentari | `#FFC933` | `#FFC933` | Sorotan ceria, misalnya penanda "Baru" | Teks `#1B2038`: 10,4 : 1 / teks `#12152A`: 11,7 : 1 |
| Hijau WhatsApp | `#25D366` | `#25D366` | Khusus tombol Hubungi Pemilik | Teks `#1B2038`: 8,1 : 1 / teks `#12152A`: 9,1 : 1 |

**Pemisahan permukaan mode gelap.** Kedalaman dibangun dari tiga tingkat permukaan, bukan bayangan, sehingga selisihnya harus terukur: Permukaan terhadap Latar 1,24 : 1, Permukaan terangkat terhadap Permukaan 1,16 : 1, dan kedua tingkat terhadap Latar 1,44 : 1. Pada mode terang Permukaan terhadap Latar hanya 1,06 : 1, sehingga tepi kartu **wajib** memakai warna Garis; kartu tanpa tepi pada mode terang tidak terlihat sama sekali.

**Warna terlarang.** Putih murni di atas Jingga utama hanya 2,6 : 1. Teks utama mode gelap di atas Jingga utama hanya 2,1 : 1. Keduanya tidak boleh dipakai.

## 3. Warna Status

| Status | Mode terang (teks / latar) | Mode gelap (teks / latar) |
|---|---|---|
| Tersedia, Disetujui | `#0C7A45` / `#E6F4EC` (4,8 : 1) | `#5BD68F` / `#14352A` (7,3 : 1) |
| Menunggu Verifikasi | `#1D4F91` / `#E8F0FB` (7,1 : 1) | `#7DB2FF` / `#1B2B4F` (6,4 : 1) |
| Ditolak, Ditangguhkan | `#B42318` / `#FDECEA` (5,7 : 1) | `#FF8A80` / `#3D1E24` (6,5 : 1) |
| Terisi, Nonaktif | `#5E6580` / `#EDEFF5` (5,0 : 1) | `#A4ABC8` / `#2A3050` (5,6 : 1) |

Teks status juga tetap terbaca saat label ditampilkan tanpa latar chip: pada mode terang 5,1 s.d. 7,7 : 1 di atas Latar, pada mode gelap 7,9 s.d. 9,8 : 1. Label teks status selalu ditampilkan, sehingga status tidak hanya dibedakan melalui warna.

## 4. Tipografi

Satu keluarga huruf: **Plus Jakarta Sans**, dimuat lewat plugin `bunny()` di `vite.config.ts` dengan bobot 400, 500, 700, dan 800. Tidak ada keluarga kedua; aksen tambahan tidak diperlukan karena kontras warna sudah membawa karakter.

Alasannya: x-height 0,536 em, tertinggi di antara kandidat sans yang diuji, sehingga teks 13–14 px di ponsel tetap terbaca; bentuk humanis-geometris dengan counter terbuka; memiliki angka tabular (`tnum`) untuk kolom harga; dan berasal dari ekosistem huruf Indonesia, sesuai konteks produk.

| Peran | Bobot | Ukuran / tinggi baris (px) | Catatan |
|---|---|---|---|
| Hero beranda | 800 | 30 / 38, lalu 38 / 46 pada `lg` | Satu per halaman |
| Judul halaman | 700 | 24 / 30 | |
| Judul kartu kost | 700 | 20 / 26 | |
| Harga | 700 | 22 / 28 | `font-feature-settings: "tnum"` |
| Judul bagian | 700 | 18 / 24 | |
| Teks isi | 400 | 16 / 26 | Lebar baris maksimal 70 karakter |
| Teks isi kecil | 400 | 14 / 22 | Keterangan, alamat, tanggal |
| Label form dan tombol | 500 | 14 / 20 | Bukan huruf kapital semua |
| Label chip dan badge | 700 | 12 / 16 | Bukan huruf kapital semua |

Aturan tambahan:

- Huruf kapital semua hanya untuk singkatan baku (KTP, OTP, WA) dan tidak dipakai sebagai label antarmuka.
- Angka memakai `tnum` pada harga, jumlah kamar, dan tabel agar kolom rata.
- Bobot 800 hanya untuk hero; pemakaiannya di tempat lain menghapus penekanan yang seharusnya dimiliki hero.
- Jarak huruf bawaan keluarga huruf ini sudah cukup; jangan menambah `letter-spacing` negatif.

## 5. Bentuk dan Elevasi

- Radius mengikuti `--radius: 0.625rem` (10 px) di `resources/css/app.css`. Turunannya: `sm` 6 px, `md` 8 px, `lg` 10 px untuk kartu dan tombol, `xl` 14 px untuk panel besar, dan `9999px` untuk chip dan badge.
- Jangan memakai satu radius untuk semua elemen. Kartu, tombol, dan chip harus berbeda agar hierarki terbaca.
- Mode terang memakai tepi Garis, bukan bayangan. Mode gelap memakai tingkat permukaan dan tepi Garis, bukan bayangan.
- Bayangan hanya untuk elemen melayang di atas konten: dropdown, popover, sheet, dan toast. Nilainya satu tingkat saja: `0 4px 16px rgb(18 21 42 / 0.12)`, tanpa varian kedua.

## 6. Aturan Pemakaian

- Hitam dan putih murni dihindari pada mode gelap. Kedalaman dibangun melalui tingkat permukaan (Latar, Permukaan, Permukaan terangkat), bukan bayangan.
- Hanya tiga warna cerah yang dipakai: jingga, kuning, dan hijau WhatsApp. Kuning cukup muncul pada satu sorotan per layar dan hanya sebagai latar berteks gelap.
- Satu blok solid per layar. Pada halaman detail kost, tombol Hubungi Pemilik berwarna hijau WhatsApp dan menjadi blok solid utama, sedangkan jingga muncul pada elemen merek, filter, dan marker.
- **Jingga utama tidak pernah menjadi warna teks atau ikon.** Pada mode terang kontrasnya terhadap Latar hanya 2,5 : 1. Teks dan ikon interaktif memakai Jingga teks (`#B54708`); Jingga utama hanya untuk isian solid yang berteks `#1B2038` (mode terang) atau `#12152A` (mode gelap).
- Cincin fokus memakai warna Teks utama, tebal 2 px, dengan `ring-offset` 2 px berwarna Latar. Offset itu bukan hiasan: cincin Teks utama berhadapan dengan tombol hijau WhatsApp hanya 1,8 : 1, sehingga tanpa celah latar cincinnya hilang.
- Mode mengikuti preferensi sistem pengguna, dilengkapi tombol pengalih manual.
- Foto kost ditampilkan apa adanya pada kedua mode, tanpa filter.

### Peta

- Mode terang memakai tile terang. Marker di atas tile terang **tidak boleh** memakai Jingga utama: kontrasnya terhadap contoh tile OpenStreetMap `#F2EFE9` hanya 2,3 : 1. Pakai isian `#FF7A1A` dengan cincin `#1B2038` setebal 2 px, atau isian `#C2410C` yang mencapai 4,5 : 1 tanpa cincin. Cincin `#1B2038` sendirian sudah mencapai 14 : 1 terhadap tile terang, sehingga marker tetap terbaca di atas foto satelit, taman, maupun jalan.
- Mode gelap memakai tile gelap. Terhadap contoh latar `#1B1F33`, Jingga utama mode gelap (`#FF8A2B`) mencapai 6,9 : 1. Kebijakan penggunaan tile gelap milik penyedia peta harus diperiksa sebelum dipakai.
- Rasio di atas dihitung terhadap contoh warna tile, bukan tile sebenarnya. Uji ulang pada tile yang benar-benar dipakai, dan pastikan marker tetap terbaca di atas bagian tile yang paling terang maupun paling gelap.

## 7. Cakupan

Dokumen ini mengatur warna, tipografi, bentuk, dan elevasi. Belum diatur di sini dan perlu dokumen terpisah: skala spasi, tata letak per halaman, inventaris komponen, pola filter ponsel (sheet), serta elemen ciri yang membedakan AYGKOS dari marketplace kost lain.

## 8. Catatan

- Nilai mode terang untuk Latar, Teks utama, Teks redup, Garis, dan Garis input berbeda dari dokumen `AYGKOS_Palet_Warna_Jingga.md`, karena netralnya dialihkan dari cokelat ke biru malam agar sekeluarga dengan mode gelap.
- Revisi ini mengubah Garis (`#E4E7F0` → `#D8DCE8`), Garis input terang (`#8790A8` → `#7D879F`), Permukaan gelap (`#1C2140` → `#202748`), Permukaan terangkat gelap (`#262C4D` → `#2A3157`), Garis gelap (`#333A5E` → `#414A7C`), dan Garis input gelap (`#6B7399` → `#7A83A8`). Nilai lama gagal ambang 3 : 1 untuk batas kolom form atau terlalu rapat sehingga permukaan mode gelap menyatu.
- Rasio marker peta pada versi sebelumnya juga keliru: 6,9 : 1 disebut untuk `#FF7A1A` di atas `#1B1F33`, padahal hasil hitungnya 6,2 : 1. Angka 6,9 : 1 hanya berlaku untuk `#FF8A2B` (Jingga utama mode gelap) di atas latar itu, sedangkan mode terang sebelumnya tidak dibahas sama sekali padahal justru di sanalah marker kehilangan kontras.
- Palet ini diterapkan sebagai satu paket dan tidak dicampur dengan palet sebelumnya.
- Palet ini bersifat usulan dan mengasumsikan audiens mahasiswa dengan akses terutama dari ponsel.
