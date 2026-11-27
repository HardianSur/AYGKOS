# Proposal

## Why

`admin-kost-verification-list` memberi Admin tempat menelusuri dan memeriksa pengajuan kost, tetapi kedua halamannya baca-saja: tidak ada satu pun jalur yang mengubah status. Akibatnya FR-ADM-VER-02, FR-ADM-VER-03, dan FR-ADM-KOS-01 masih belum terpenuhi, dan antrean verifikasi hanya bisa dilihat, tidak bisa diputuskan. Kost yang sudah diajukan akan menumpuk berstatus `menunggu_verifikasi` selamanya.

## What Changes

- Tiga aksi Admin pada halaman detail kost `/admin/kosts/{kost}`:
  - **Setujui** — kost `menunggu_verifikasi` menjadi `disetujui` sehingga tampil kepada publik.
  - **Tolak** — kost `menunggu_verifikasi` menjadi `ditolak` dengan alasan wajib.
  - **Tangguhkan** — kost `disetujui` menjadi `ditangguhkan` dengan alasan wajib; status ini tidak dapat diaktifkan kembali oleh Pemilik Kost.
- Tiga Action baru `ApproveKost`, `RejectKost`, `SuspendKost`. Masing-masing mengisi `status`, `reviewed_by`, `reviewed_at`, dan `status_reason` (kosong untuk Setujui), lalu menulis satu baris `notifications` bagi Pemilik Kost.
- Setiap Action menolak perubahan atas kost yang statusnya sudah tidak sesuai, sehingga dua Admin yang memutuskan kost yang sama tidak saling menimpa.
- Dua Form Request baru `RejectKostRequest` dan `SuspendKostRequest` dengan alasan wajib 10–500 karakter.
- Ability `reviewAsAdmin` pada `KostPolicy` dan tiga route `POST` di grup `role:admin`.
- Kendali aksi pada halaman detail: tombol Setujui langsung, dialog beralasan untuk Tolak dan Tangguhkan. Tabel master tetap baca-saja.
- Requirement `Halaman verifikasi bersifat baca-saja` pada capability `admin/kost-verification` diganti dengan requirement halaman master baca-saja plus requirement keputusan verifikasi.

## Capabilities

### New Capabilities

- `admin/kost-verification`: capability yang sama dengan change `admin-kost-verification-list`, ditambah perilaku keputusan verifikasi — route `POST` bertiga yang hanya dapat dipakai Admin, guard status asal, alasan wajib, dan umpan balik di halaman detail.

### Modified Capabilities

- `domain/kost-catalog`: transisi status verifikasi kost dibatasi ke jalur yang sah (`menunggu_verifikasi` → `disetujui` | `ditolak`, `disetujui` → `ditangguhkan`), dan setiap keputusan mencatat peninjau beserta waktunya.
- `domain/complaint-and-notification`: keputusan verifikasi kost (disetujui, ditolak, ditangguhkan) meninggalkan satu baris notifikasi in-app bagi Pemilik Kost, dengan rujukan polimorfik ke kost.

## Impact

- **Dokumen**: tidak ada perubahan skema maupun FR, sehingga `docs/ERD/erd.md` tetap V005 dan `docs/FunctionalRequirment/functional_requirment.md` tetap V002. Change ini merealisasikan FR-ADM-VER-02, FR-ADM-VER-03, FR-ADM-KOS-01, dan bagian kost pada FR-NTF-02.
- **Urutan arsip**: delta ini menghapus requirement yang lahir di `admin-kost-verification-list` dan menambah method pada controller yang dibangun change itu. `admin-kost-verification-list` harus diarsipkan lebih dulu.
- **Backend baru**: `app/Http/Controllers/Admin/KostReviewController.php` (tiga action), `app/Http/Requests/Admin/RejectKostRequest.php`, `app/Http/Requests/Admin/SuspendKostRequest.php`, dan Action `ApproveKost`, `RejectKost`, `SuspendKost` di `app/Actions/Kost/`.
- **Backend berubah**: `app/Policies/KostPolicy.php` (ability `reviewAsAdmin`), `routes/web.php` (tiga route `POST` di grup `role:admin`).
- **Frontend**: komponen baru `resources/js/components/admin/kost/detail-actions.tsx` disisipkan ke `resources/js/pages/admin/kosts/show.tsx`.
- **Tes**: `tests/Feature/Admin/KostVerificationActionTest.php` untuk otorisasi, ketiga transisi, guard status asal, alasan wajib, penghapusan alasan lama saat disetujui, dan baris notifikasi.
- **Tanpa migrasi**: `kosts.status`, `status_reason`, `reviewed_by`, `reviewed_at`, dan tabel `notifications` sudah ada.
- **Di luar cakupan**: UI daftar dan penanda notifikasi (FR-NTF-01), hitungan dashboard Admin (FR-DSH-02), ajukan ulang kost ditolak (FR-KOS-10), tangguhkan dari halaman lain, dan aktivasi kembali kost `nonaktif`.
