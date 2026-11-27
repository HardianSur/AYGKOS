# Proposal

## Why

Pemilik Kost sudah dapat mengajukan kost berstatus `menunggu_verifikasi` beserta foto dan lima jenis dokumen izinnya, tetapi Admin belum punya halaman untuk melihat antrean itu sama sekali. Akibatnya FR-ADM-VER-01 belum terpenuhi: tidak ada satu pun tempat bagi Admin untuk menelusuri pengajuan, memeriksa kelengkapan dokumen wajib, dan membuka detail kost sebelum memutuskan. Tanpa daftar dan detail ini, seluruh alur M3.1 (setujui, tolak, tangguhkan) tidak punya titik masuk.

## What Changes

- Halaman master `/admin/kosts` berisi tabel kost: nomor, nama kost, nama pemilik, status, dan tanggal diajukan, dengan pagination 10 baris per halaman.
- Filter pada halaman master: status pengajuan (lima nilai) dan rentang tanggal pengajuan (`created_at`), keduanya bertahan di URL sehingga ikut terbawa saat berpindah halaman.
- Halaman master memuat kost dari semua status; filter status yang menyempitkan, bukan daftar yang hanya berisi satu status.
- Halaman detail `/admin/kosts/{kost}` berisi data yang diinput Pemilik Kost: identitas kost, data pemilik, peta titik lokasi, deskripsi, fasilitas umum, peraturan kost, foto bangunan, lima slot dokumen izin dengan penanda dokumen wajib yang belum diunggah, serta riwayat tinjauan bila kost sudah pernah diproses.
- Dokumen izin disajikan lewat tautan bertanda tangan berumur 10 menit (`PrivateFileUrl`), bukan URL penyimpanan publik.
- Halaman bersifat baca-saja. Tombol Setujui, Tolak, dan Tangguhkan beserta Action `ApproveKost`, `RejectKost`, `SuspendKost`, notifikasi in-app, dan dashboard hitungan menjadi change terpisah.

## Capabilities

### New Capabilities

- `admin/kost-verification`: Admin dapat menelusuri pengajuan kost lewat tabel berpaginasi dengan filter status dan rentang tanggal pengajuan, lalu membuka satu kost untuk memeriksa seluruh data dan dokumen izinnya, dengan dua halaman itu hanya dapat diakses Admin terverifikasi.

### Modified Capabilities

Tidak ada. Perilaku penyimpanan, status, dan tampilan publik kost tidak berubah.

## Impact

- **Dokumen**: tidak ada perubahan skema maupun FR, sehingga `docs/ERD/erd.md` tetap V005 dan `docs/FunctionalRequirment/functional_requirment.md` tetap V002. Change ini hanya merealisasikan FR-ADM-VER-01.
- **Backend baru**: `app/Http/Controllers/Admin/KostController.php` (`index`, `show`), dua route `/admin/kosts` di `routes/web.php` dalam grup `role:admin`, dan dua ability baru pada `app/Policies/KostPolicy.php` (`viewAnyAsAdmin`, `viewAsAdmin`) untuk membedakan jalur Admin dari jalur Pemilik Kost.
- **Tanpa Action dan tanpa mutasi**: kedua action controller hanya membaca. Tidak ada Form Request, tidak ada migrasi, tidak ada perubahan model.
- **Frontend**: halaman tipis `resources/js/pages/admin/kosts/index.tsx` dan `show.tsx`, ditambah komponen terpisah `resources/js/components/admin/kost/` (filter bar, tabel, pagination, badge status, ringkasan identitas, kartu pemilik, panel lokasi, panel fasilitas & peraturan, galeri foto, daftar dokumen, panel riwayat tinjauan) agar tidak ada berkas halaman yang membengkak.
- **Navigasi**: satu tautan "Verifikasi Kost" pada sidebar Admin di `resources/js/components/app-sidebar.tsx`.
- **Tes**: `tests/Feature/Admin/KostVerificationTest.php` untuk otorisasi peran, isi tabel, pagination 10, filter status, filter rentang tanggal, isi detail, penanda dokumen wajib yang kosong, dan URL dokumen bertanda tangan.
- **Di luar cakupan**: FR-ADM-VER-02/03 (setujui/tolak), FR-ADM-KOS-01 (tangguhkan), FR-NTF-02 (notifikasi hasil verifikasi), FR-DSH-02 (hitungan dashboard), daftar dan pengelolaan tipe kamar (M2.2 belum dibangun), dan pengeditan kost oleh Admin.