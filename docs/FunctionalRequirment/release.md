# Release Catatan Perubahan Dokumen

Berkas ini mencatat setiap versi dokumen yang dipensiunkan ke `logs/`. Satu dokumen satu `release.md` (ERD terpisah dari Functional Requirement), karena penomoran versi berjalan per dokumen.

## Aturan

1. Satu versi = satu perubahan dokumen yang diterima. Bukan per commit.
2. Berkas di `logs/` bersifat beku (immutable). Salah isi diperbaiki pada versi berikutnya, tidak pernah menyunting atau menghapus berkas lama.
3. `V001` adalah versi awal. Tidak ada versi yang dipensiunkan sebelum `V002` dibuat.
4. Format tanggal: `dd-mm-yyyy:HH:MM WIB`, jam 24 jam, zona Asia/Jakarta.
5. Koreksi editorial (typo, format tabel, tautan) dicatat sebagai baris `editorial` tanpa menambah versi dan tanpa memensiunkan berkas.
6. Dokumen di `logs/` hanya berpindah tempat saat versi baru diterima. Waktu `Dibuat` = waktu git mencatat isi versi itu pertama kali masuk repositori.

## Riwayat

| Versi | Berkas | Dibuat | Dipensiunkan | Jenis | Ringkasan | Alasan | Rujukan |
|---|---|---|---|---|---|---|---|
| V001 | logs/V001-functional_requirment.md | 17-11-2026:17:45 WIB | 25-11-2026:09:28 WIB | minor | Versi awal; 58 FR dalam 12 kelompok, termasuk `FR-PRF-04` (foto profil) | Baseline dokumen | `profile-avatar-and-role-home` |
| V002 | functional_requirment.md | 25-11-2026:09:28 WIB | - | major | FR-KOS-01/05/06/07/08, FR-SRC-03/06/08, FR-KTK-03/04, FR-ADM-VER-01, FR-ADM-REP-01, dan FR-DSH-01 disesuaikan dengan tipe kamar, dokumen izin per bangunan, master data, dan kartu per kost; FR-ADM-MST-01/02/03 baru untuk CRUD master data | Tipe kamar sebagai unit yang diiklankan dan katalog terkurasi | `restructure-kost-catalog` |
