# Proposal

## Why

ERD V003 memodelkan kamar sebagai unit fisik bernomor (`rooms.room_number`) dengan satu harga, sementara fasilitas, peraturan, dan foto hanya berada di level kost. Model ini gagal mewakili cara kost sungguhan diiklankan: satu bangunan hampir selalu memiliki beberapa tipe kamar (AC dan non-AC, kamar mandi dalam dan luar) yang berbeda harga, fasilitas, dan peraturannya. Akibatnya Pemilik Kost tidak bisa mengiklankan harga dan fasilitas per tipe, dan pencari kost tidak bisa membandingkan tipe dalam satu bangunan.

Sekaligus, kualitas isi katalog bergantung pada data yang diketik bebas (peraturan sebagai teks, fasilitas tanpa pemisah ruang lingkup) dan pada satu kolom dokumen verifikasi yang tidak mewakili legalitas bangunan yang sebenarnya terdiri dari beberapa izin. Ketiganya perlu diperbaiki sebelum M2.2 (pengelolaan kost dan kamar) dan M3.2 (pencarian dan detail kost) dibangun, karena keduanya bergantung pada bentuk data ini.

## What Changes

- **BREAKING** `rooms` dihapus dan diganti `room_types`. Tipe kamar menyimpan `name` (diisi Pemilik Kost), `price_per_month`, `total_rooms`, `available_rooms`, `notes`, dan `status` (`aktif`, `nonaktif`). Tidak ada lagi nomor kamar maupun entitas unit fisik.
- **BREAKING** `kosts.rules` (teks bebas) dihapus; peraturan menjadi master data yang dipilih Pemilik Kost, dengan `kost_rules` untuk peraturan kost dan `room_type_rules` untuk peraturan khusus tipe kamar (opsional).
- **BREAKING** `kost_photos` diganti `photos` dengan kategori (`bangunan`, `kamar`, `kamar_mandi`, `fasilitas_umum`) dan `room_type_id` opsional: kategori bangunan dan fasilitas umum berada di level kost, kategori kamar dan kamar mandi berada di level tipe kamar.
- **BREAKING** `kosts.verification_document_path` diganti `kost_documents` (banyak baris, ber-`type`). Jenis dokumen: surat keterangan domisili usaha, izin lingkungan RT/RW, izin kelurahan/kecamatan, NIB, dan surat pernyataan pemilik bangunan. NIB dan surat pernyataan wajib; tiga lainnya opsional. KTP tidak lagi diminta karena identitas Pemilik Kost sudah diverifikasi melalui OTP WhatsApp.
- Master data baru dikelola Admin: `facilities` mendapat `scope` (`umum`/`kamar`) dan `icon` (nama ikon lucide), serta tabel baru `rules` (dengan `scope`) dan `specifications` (dengan `input_type` dan `unit`).
- Spesifikasi kamar dikelola sebagai pasangan master + nilai pada `room_type_specifications`.
- Pemilik Kost boleh memiliki lebih dari satu kost; dokumen izin bersifat per bangunan, sehingga tiap kost membawa dokumennya sendiri.
- Moderasi tetap di level kost: Admin hanya memverifikasi kost. Tipe kamar baru tidak melewati verifikasi, tetapi mulai berstatus `nonaktif` dan hanya tampil kepada publik setelah Pemilik Kost mengaktifkannya sendiri; Admin tetap dapat menangguhkan tipe kamar yang bermasalah.
- Hasil pencarian tetap satu kartu per kost (harga mulai dari tipe kamar aktif termurah, jumlah kamar tersedia sebagai penjumlahan `available_rooms` tipe aktif), bukan satu kartu per tipe kamar.
- Halaman detail kost menampilkan tipe kamar sebagai bagian berurutan: spesifikasi, fasilitas kamar, peraturan khusus, catatan Pemilik, lalu navigasi ke tipe kamar lain, diikuti fasilitas umum dan peraturan kost.
- Halaman Admin baru untuk CRUD master data: fasilitas (beserta ikon), aturan, dan spesifikasi.
- Filter ketersediaan (`FR-SRC-03`) menjadi keberadaan tipe kamar aktif dengan `available_rooms > 0`, bukan keberadaan baris kamar berstatus tersedia.

## Capabilities

### New Capabilities

- `domain/master-data`: master fasilitas (dengan ruang lingkup dan ikon), aturan, dan spesifikasi kamar, beserta pengelolaannya oleh Admin dan pemilihan oleh Pemilik Kost.

### Modified Capabilities

- `domain/kost-catalog`: tipe kamar menggantikan kamar bernomor, foto berkategori, dokumen izin per bangunan, peraturan dari master, visibilitas publik dengan status tipe kamar, serta aturan tampilan kartu dan halaman detail.
- `domain/persistence-conventions`: aturan timestamp, kunci unik, constraint check, dan penghapusan lunak yang sebelumnya menunjuk `rooms`/`kost_photos` dipindahkan ke `room_types`, `photos`, dan tabel master baru.

## Impact

- **Dokumen**: `docs/ERD/erd.md` naik V004 -> **V005** (V004 dibekukan ke `docs/ERD/logs/V004-erd.md`); `docs/FunctionalRequirment/functional_requirment.md` naik V001 -> **V002** (FR-KOS-01, FR-KOS-06, FR-KOS-07, FR-KOS-08, FR-SRC-03, FR-SRC-06, FR-SRC-08, FR-KTK-03, FR-KTK-04, FR-ADM-VER-01, FR-ADM-REP-01 berubah; FR-ADM baru untuk master data), baris `release.md` ditambahkan pada kedua dokumen; `docs/ARCHITECTURE.md` bagian 5.3, 5.4, 5.6, 6.1, 6.3, dan `docs/ROADMAP.md` M2.2/M3.2 ditulis ulang.
- **Model**: `app/Models/Room.php` dihapus; `RoomType`, `Photo`, `KostDocument`, `Rule`, `Specification` baru; `Kost` kehilangan `rules` dan `verification_document_path`, relasi `rooms()` diganti `roomTypes()`.
- **Migrasi**: satu migrasi baru membentuk tabel baru dan membuang `rooms`, `kost_photos`, `kosts.rules`, `kosts.verification_document_path`; belum ada data produksi sehingga tidak perlu migrasi data.
- **Tes**: `tests/Feature/Schema/DomainSchemaTest.php` dan `tests/Feature/Schema/DomainModelTest.php` ditulis ulang untuk tabel baru.
- **Route dan UI**: belum ada route/controller kost, jadi hanya kontrak yang ditetapkan di sini — `/owner/kosts/{kost}/rooms` menjadi `/owner/kosts/{kost}/types`, route admin master data baru, `KostCard` dan galeri detail kost berubah. Implementasinya menyusul di M2.2/M3.2.
- **Konfigurasi**: `config/uploads.php` mendapat kunci `kost_document` (5 MB, hanya PDF) terpisah dari `verification_document`, plus daftar dokumen wajib dan daftar nama ikon fasilitas; batas foto tetap 3 MB.
- **Kontak WhatsApp**: pilihan "Ajukan Sewa" berpindah dari kamar ke tipe kamar, sehingga pesan memuat nama tipe kamar alih-alih nomor kamar.