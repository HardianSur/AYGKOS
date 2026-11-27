# Design

## Context

Lihat `proposal.md` untuk motivasi dan `specs/` untuk kontrak perilaku. Keadaan kode yang membentuk pendekatan ini:

- `admin-kost-verification-list` membangun halaman `/admin/kosts` dan `/admin/kosts/{kost}` beserta `Admin/KostController`, dan menambahkan `viewAnyAsAdmin`/`viewAsAdmin` pada `app/Policies/KostPolicy.php`. Change ini menyusul dan menumpang keduanya.
- `app/Actions/Kost/SubmitKost.php` sudah menjadi contoh konvensi Action: satu kelas per use case, `handle()` dengan parameter bertipe, `DB::transaction` untuk mutasi multi-tabel. Notifikasi belum pernah ditulis di mana pun.
- `app/Models/Notification.php` sudah ada dengan `HasUuids`, `UPDATED_AT = null`, dan kolom `type`, `message`, `reference_type`, `reference_id`, `read_at`. Tabel `notifications` punya indeks `(user_id, read_at)`.
- `app/Actions/MasterData/ManageFacility.php` memakai `ValidationException::withMessages()` untuk penolakan aturan bisnis, sehingga pesan muncul pada kolom formulir, bukan halaman galat 500. Pola ini dipakai ulang untuk guard status.
- `app/Http/Controllers/Admin/FacilityController.php` memanggil Action lewat injeksi parameter method lalu `to_route()` + `Inertia::flash('toast', ...)`.
- Guard peran sudah dipasang middleware `role:admin` pada grup route; `EnsureRole` mengembalikan 403 untuk peran lain dan meneruskan tamu ke middleware `auth`.
- `tests/Feature/Admin/AccessGateTest.php` memakai helper `userWithRole()` yang dideklarasikan di berkas itu; berkas tes baru perlu helper serupa sendiri.

## Goals / Non-Goals

**Goals:**

- Satu Action per keputusan, semuanya memakai guard status asal yang sama sehingga tidak ada jalur status liar.
- Notifikasi ditulis di dalam transaksi yang sama dengan perubahan status: tidak ada status berubah tanpa notifikasi, atau sebaliknya.
- Kendali UI mengikuti status kost, bukan peran saja, agar Admin tidak melihat tombol yang pasti gagal.
- Controller terpisah dari `Admin/KostController` milik change sebelumnya supaya dua change tidak berebut satu berkas.

**Non-Goals:**

- Tidak menambah kolom, tabel, migrasi, atau model.
- Tidak membangun halaman daftar notifikasi, penanda belum dibaca, atau hitungan dashboard.
- Tidak menyentuh halaman Pemilik Kost maupun katalog publik.
- Tidak menyediakan aktivasi kembali kost `nonaktif` atau `ditangguhkan`; itu di luar FR yang dikerjakan.

## Decisions

### 1. Tiga Action tipis, bukan satu Action bercabang

`ApproveKost`, `RejectKost`, `SuspendKost` masing-masing satu kelas dengan `handle(Kost $kost, User $admin, ?string $reason = null): Kost`.

Alasan: nama ketiganya sudah tertulis di `docs/ARCHITECTURE.md` §6.1 dan `docs/ROADMAP.md` M3.1, dan tiap keputusan punya status asal, kolom alasan, jenis notifikasi, dan pesan yang berbeda. Satu kelas `ReviewKost` berswitch akan menyatukan empat sumbu perbedaan itu dalam satu method panjang dan menyimpang dari dokumen arsitektur.

Alternatif: satu `ReviewKost` dengan enum keputusan. Ditolak karena keuntungannya hanya satu berkas lebih sedikit, sementara biayanya adalah kontroler yang harus memetakan enum ke aturan validasi dan pesan.

Tubuh yang identik (guard status, isi `reviewed_by`/`reviewed_at`, tulis notifikasi) dapat disatukan lewat trait privat kecil `App\Actions\Kost\Concerns\ReviewsKost` bila saat implementasi terlihat benar-benar kembar tiga kali. Keputusan detail itu ditunda ke implementasi; spec tidak bergantung padanya.

### 2. Guard status memakai update bersyarat, bukan pemeriksaan baca-lalu-tulis

```php
return DB::transaction(function () use ($kost, $admin, $reason): Kost {
    $updated = Kost::query()
        ->whereKey($kost->getKey())
        ->where('status', 'menunggu_verifikasi')
        ->update([...]);

    if ($updated === 0) {
        throw ValidationException::withMessages([
            'status' => 'Status kost sudah berubah. Muat ulang halaman lalu putuskan kembali.',
        ]);
    }
    ...
});
```

Alasan: `where('status', ...)` menutup celah dua Admin memutuskan kost yang sama. Pola baca-lalu-cek-lalu-simpan (`if ($kost->status !== ...) abort(409)`) rentan terhadap permintaan yang datang bersamaan, dan spek meminta keputusan kedua ditolak, bukan ditimpa.

Alternatif: `$kost->update()` dengan pemeriksaan `$kost->status` di awal. Ditolak karena memindahkan jaminan ke timing, bukan ke basis data.

Pesan galat memakai `ValidationException` agar muncul sebagai pesan di formulir keputusan, bukan halaman 409 kosong.

### 3. Notifikasi ditulis inline di dalam transaksi Action

```php
$kost->owner->notifications()->create([
    'type' => 'kost_ditolak',
    'message' => "Kost \"{$kost->name}\" ditolak. Alasan: {$reason}",
    'reference_type' => 'kost',
    'reference_id' => $kost->getKey(),
]);
```

Alasan: tabel `notifications` dan modelnya sudah ada, transformasi ini hanya satu baris, dan spek `domain/complaint-and-notification` meminta penulisan notifikasi dan perubahan status terjadi bersama. Kelas helper `NotificationWriter` untuk satu `create()` di tiga tempat adalah abstraksi yang belum dibutuhkan.

Alternatif: `Notification::create([...])` dengan `user_id` eksplisit. Setara; versi relasi dipilih agar `user_id` tidak bisa salah.

Jenis notifikasi: `kost_disetujui`, `kost_ditolak`, `kost_ditangguhkan`. `reference_type` diisi `'kost'` (bukan nama kelas penuh) agar sesuai kolom `string(50)` dan tidak bergantung namespace model.

Pesan notifikasi tidak disimpan sebagai konstanta di config: tiga kalimat, satu tempat masing-masing.

### 4. Dua Form Request, bukan satu

`RejectKostRequest` dan `SuspendKostRequest`, keduanya rule `reason: ['required', 'string', 'min:10', 'max:500']` dan `authorize(): true` (otorisasi lewat route+Policy, konsisten dengan `StoreFacilityRequest`).

Alasan: `ApproveKost` tidak butuh payload, sehingga tidak butuh Form Request sama sekali. Memaksa satu `ReviewKostRequest` dengan aturan `required_if` membuat aturan approval (yang tidak punya kolom) ikut tercampur.

Pesan galat validasi ditulis di Form Request lewat `messages()` agar berbahasa Indonesia dan menyebut alasan, bukan kunci kolom mentah.

### 5. Ability tunggal `reviewAsAdmin` dan satu controller terpisah

```php
Route::post('kosts/{kost}/approve', [KostReviewController::class, 'approve'])->name('kosts.approve');
Route::post('kosts/{kost}/reject', [KostReviewController::class, 'reject'])->name('kosts.reject');
Route::post('kosts/{kost}/suspend', [KostReviewController::class, 'suspend'])->name('kosts.suspend');
```

Ketiganya memanggil `Gate::authorize('reviewAsAdmin', Kost::class)`, dan ability itu memeriksa `role?->name === 'admin'` serta `is_active`.

Alasan: tiga ability berbadan identik menambah permukaan tanpa membedakan apa pun. Satu ability bernama jelas lebih mudah diuji dan disebut di docblock.

Controller terpisah dari `Admin/KostController` karena `KostController` lahir di change lain dan tugas di sana sudah menetapkan isinya; menyisipkan tiga method mutasi ke berkasnya akan membuat dua change menulis berkas yang sama dan memaksa urutan penggabungan yang ketat.

Alternatif: method `approve`/`reject`/`suspend` di `Admin/KostController`. Ditolak karena alasan di atas; bila kelak controller itu memang tempat semua aksi kost Admin, penyatuan bisa dilakukan tanpa mengubah route maupun spec.

### 6. Kendali UI di halaman detail, tabel master dibiarkan

`resources/js/components/admin/kost/detail-actions.tsx` menerima `kost` (id, nama, status) dan merender:

| Status | Kendali |
|---|---|
| `menunggu_verifikasi` | tombol Setujui; tombol Tolak (membuka dialog alasan) |
| `disetujui` | tombol Tangguhkan (membuka dialog alasan) |
| lainnya | tidak ada |

Alasan: spek menuntut tabel master tetap baca-saja, dan halaman detail sudah memuat seluruh konteks yang dibutuhkan Admin untuk memutuskan. Menaruh aksi di baris tabel berarti Admin dapat menolak tanpa membaca dokumen.

Dialog memakai `resources/js/components/ui/dialog.tsx` yang sudah ada; tombol mengirim `router.post(route.url({ kost: id }), { reason })` dengan `onSuccess` menutup dialog. Galat validasi ditampilkan lewat `useForm().errors.reason` agar pesan dari `ValidationException` di Action (status sudah berubah) ikut tampil, karena keduanya memakai kunci `status`/`reason` pada props galat Inertia.

Ketiga aksi lewat dialog. Tolak dan Tangguhkan memakai dialog berisi kolom alasan; Setujui memakai dialog konfirmasi ringkas berisi nama kost. Alasan: persetujuan tidak dapat dibalik ke `menunggu_verifikasi`, jadi satu klik tak sengaja tidak boleh langsung menayangkan kost.

Alternatif: tombol Setujui langsung tanpa dialog. Ditolak karena tidak ada jalur pembatalan di change ini.

### 7. Setelah berhasil, kembali ke halaman detail yang sudah diperbarui

Controller `approve`/`reject`/`suspend` mengembalikan `to_route('admin.kosts.show', $kost)` dengan `Inertia::flash('toast', ...)` berisi hasil keputusan.

Alasan: Admin biasanya memeriksa beberapa pengajuan berurutan dan tetap ingin melihat hasil keputusan pada halaman yang sama. Redirect ke master akan menghilangkan konteks dan filter yang sedang aktif.

`to_route` dengan model terikat menghasilkan URL yang benar meski kost telah berpindah status.

## Risks / Trade-offs

- **[Dua change menyentuh halaman detail]** `show.tsx` ditulis change list lalu disunting change ini untuk menyisipkan `detail-actions`. → Mitigasi: sisipan hanya satu baris komponen; `admin-kost-verification-list` diarsipkan lebih dulu, dan rollback change ini adalah menghapus satu baris itu.
- **[Status guard mengunci retry]** Admin yang kalah balapan tidak bisa sekadar klik ulang; halaman harus dimuat ulang untuk melihat status baru. → Mitigasi: pesan galat menyuruh memuat ulang halaman, dan toast sukses hanya muncul pada keputusan yang benar-benar tersimpan.
- **[Notifikasi tanpa UI]** Baris `notifications` bertambah tanpa tempat membacanya sampai M4.2. → Mitigasi: data tidak hilang dan indeks `(user_id, read_at)` sudah siap; pemilik tetap melihat `status_reason` di halaman daftar kost miliknya.
- **[Penolakan bersifat jalan buntu]** FR-KOS-10 (ajukan ulang) belum dibangun, jadi kost `ditolak` tidak punya jalur pemulihan bagi pemilik. → Mitigasi: bukan akibat change ini, tetapi harus disebut di ringkasan; pemulihan menyusul di M2.2 dan tidak memerlukan perubahan skema.
- **[Pesan notifikasi memuat alasan bebas]** Alasan Admin masuk ke `message` tanpa sanitasi. → Mitigasi: `message` dirender sebagai teks oleh halaman notifikasi kelak, bukan HTML; tidak ada markup yang dieksekusi. Panjang dibatasi 500 karakter oleh validasi.
- **[Tiga Action nyaris kembar]** Duplikasi guard dan penulisan notifikasi di tiga kelas. → Mitigasi: trait `ReviewsKost` dipakai bila duplikasi terbukti; spec menguji perilaku, bukan struktur, sehingga penyatuan tidak memaksa perubahan kontrak.

## Migration Plan

1. Tambah ability `reviewAsAdmin` dan tesnya; route belum ada sehingga perilaku lama utuh.
2. Tambah Action, Form Request, lalu controller dan route `POST`.
3. Tambah `detail-actions.tsx` dan sisipkan ke halaman detail.
4. Jalankan `php artisan wayfinder:generate` agar helper route `admin/kosts/approve` dan seterusnya tersedia di frontend.
5. Jalankan tes, `npm run check`, `npm run types:check`, `npm run build`.

Rollback: hapus tiga route, controller, Action, dan Form Request, lalu hapus satu baris `detail-actions` dari `show.tsx`. Tidak ada migrasi maupun data yang perlu dibatalkan; baris `notifications` yang sudah tertulis dibiarkan karena kolomnya memang dirancang untuk rujukan yang boleh basi.

## Open Questions

Tidak ada. Bentuk route, nama Action, dan jenis notifikasi sudah diputuskan; pencarian teks, aktivasi kembali kost `nonaktif`, dan UI notifikasi berada di luar change ini.