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
| V001 | functional_requirment.md | 17-11-2026:17:45 WIB | - | minor | Versi awal; 58 FR dalam 12 kelompok, termasuk `FR-PRF-04` (foto profil) | Baseline dokumen | `profile-avatar-and-role-home` |
