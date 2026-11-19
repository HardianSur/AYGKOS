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
| V001 | logs/V001-erd.md | 17-11-2026:17:45 WIB | 17-11-2026:23:43 WIB | minor | Baseline ERD: 11 entitas, relasi, enumerasi, constraint | Diganti V002 | - |
| V002 | logs/V002-erd.md | 17-11-2026:23:43 WIB | 18-11-2026:16:10 WIB | minor | Tabel `kost` menjadi `kosts`; kolom `users.remember_token`; `users.full_name` menjadi `users.name`; catatan tabel framework di luar cakupan | Diganti V003 | - |
| V003 | erd.md | 18-11-2026:16:10 WIB | - | minor | Seluruh nama tabel domain menjadi bentuk plural: `roles`, `whatsapp_verifications`, `kost_photos`, `facilities`, `kost_facilities`, `rooms`, `complaints`, `complaint_attachments`, `notifications`; tabel framework tetap di luar cakupan | Menyesuaikan konvensi plural snake_case agar migration dan model tidak menimpa nama tabel | `erd-schema-and-models` |