# Tasks

Prasyarat: `admin-kost-verification-list` sudah diimplementasikan (halaman `/admin/kosts` dan `/admin/kosts/{kost}`, `Admin/KostController`, ability `viewAnyAsAdmin`/`viewAsAdmin`).

## 1. Otorisasi dan Route

- [ ] 1.1 Tambah method `reviewAsAdmin(User $user): bool` pada `app/Policies/KostPolicy.php` yang mengembalikan true hanya untuk `$user->is_active` dan peran `admin`; verifikasi dengan tes otorisasi Policy (admin true, customer/pemilik/akun nonaktif false) di `tests/Feature/Admin/KostVerificationActionTest.php`.
- [ ] 1.2 Daftarkan tiga route `POST` di grup `admin.` pada `routes/web.php`: `kosts/{kost}/approve` → `KostReviewController@approve`, `kosts/{kost}/reject` → `@reject`, `kosts/{kost}/suspend` → `@suspend`, masing-masing dengan nama `admin.kosts.approve|reject|suspend`; verifikasi dengan `php artisan route:list --name=admin.kosts` yang menampilkan ketiganya ber-middleware `role:admin`.
- [ ] 1.3 Verifikasi seluruh skenario "Keputusan verifikasi hanya dapat dilakukan Admin" lolos tes: admin 200/redirect sukses, customer dan pemilik 403, tamu dialihkan ke `route('login')`, admin nonaktif ditolak, dan status kost tidak berubah pada semua jalur yang ditolak.

## 2. Action dan Validasi

- [ ] 2.1 Buat `app/Actions/Kost/ApproveKost.php` (`handle(Kost $kost, User $admin): Kost`) yang di dalam `DB::transaction` melakukan update bersyarat `where('status', 'menunggu_verifikasi')` ke `disetujui` dengan `status_reason = null`, `reviewed_by = $admin->id`, `reviewed_at = now()`, lalu menulis baris notifikasi; verifikasi tes: kost `menunggu_verifikasi` menjadi `disetujui`, `status_reason` kosong, peninjau dan waktu terisi, satu baris `notifications` bertipe `kost_disetujui` dengan `reference_type = 'kost'` dan `reference_id` kost tersebut.
- [ ] 2.2 Buat `app/Actions/Kost/RejectKost.php` (`handle(Kost $kost, User $admin, string $reason): Kost`) yang di dalam `DB::transaction` mengubah `menunggu_verifikasi` → `ditolak` beserta `status_reason`, `reviewed_by`, `reviewed_at`, dan notifikasi `kost_ditolak`; verifikasi tes: status `ditolak`, alasan tersimpan, peninjau/waktu terisi, notifikasi tertulis dengan alasan pada `message`.
- [ ] 2.3 Buat `app/Actions/Kost/SuspendKost.php` (`handle(Kost $kost, User $admin, string $reason): Kost`) yang di dalam `DB::transaction` mengubah `disetujui` → `ditangguhkan` beserta alasan dan notifikasi `kost_ditangguhkan`; verifikasi tes: status `ditangguhkan`, alasan tersimpan, notifikasi tertulis, dan kost hilang dari hasil daftar publik (kueri publik yang sudah ada).
- [ ] 2.4 Pastikan setiap Action melempar `ValidationException::withMessages(['status' => 'Status kost sudah berubah. Muat ulang halaman lalu putuskan kembali.'])` ketika update bersyarat mengubah 0 baris, tanpa menulis notifikasi; verifikasi tes skenario "Keputusan ditolak bila status kost sudah berubah" dan "Keputusan gagal tidak menulis notifikasi" (hitung baris `notifications` sebelum dan sesudah).
- [ ] 2.5 Verifikasi skenario penolakan atas status salah untuk ketiga aksi: reject atas kost `disetujui`/`ditolak`, suspend atas `menunggu_verifikasi`/`ditolak`/`nonaktif`, approve atas kost `disetujui` — semuanya ditolak tanpa perubahan kolom.
- [ ] 2.6 Buat `app/Http/Requests/Admin/RejectKostRequest.php` dan `app/Http/Requests/Admin/SuspendKostRequest.php` dengan aturan `reason` `['required','string','min:10','max:500']`, `authorize(): true`, dan `messages()` berbahasa Indonesia; verifikasi tes: tanpa alasan, alasan 9 karakter, dan alasan 501 karakter masing-masing ditolak dengan galat pada kunci `reason` dan status kost tidak berubah, sedangkan alasan sah diterima.

## 3. Controller

- [ ] 3.1 Buat `app/Http/Controllers/Admin/KostReviewController.php` dengan `approve(Kost $kost)`, `reject(RejectKostRequest $request, Kost $kost)`, dan `suspend(SuspendKostRequest $request, Kost $kost)`; masing-masing `Gate::authorize('reviewAsAdmin', Kost::class)`, memanggil Action, lalu `to_route('admin.kosts.show', $kost)` dengan `Inertia::flash('toast', ...)`; verifikasi tes: respons redirect ke detail kost yang sama, toast terisi, dan `owner` menerima notifikasi tepat satu baris per permintaan (tidak berlipat saat dikirim dua kali).
- [ ] 3.2 Verifikasi kegagalan tulis notifikasi membatalkan perubahan status (skenario "Kegagalan menulis notifikasi membatalkan perubahan status"): simulasikan kegagalan dengan `Notification::creating(fn () => throw new RuntimeException)` di dalam tes, lalu pastikan status kost tetap nilai semula dan tidak ada baris notifikasi tersimpan.
- [ ] 3.3 Verifikasi tidak ada jalur tambahan: `GET` pada ketiga route keputusan mengembalikan 405, dan status kost tidak berubah bila route dipanggil dengan `PUT`/`DELETE`.

## 4. Antarmuka Halaman Detail

- [ ] 4.1 Buat `resources/js/components/admin/kost/detail-actions.tsx` yang menerima `kost: { id: string; name: string; status: KostStatus }`; render tombol Setujui dan Tolak (dialog alasan) untuk `menunggu_verifikasi`, tombol Tangguhkan (dialog alasan) untuk `disetujui`, dan tidak ada kendali untuk `ditolak`/`ditangguhkan`/`nonaktif`; verifikasi: `npm run types:check` lolos dan tes Inertia memastikan props `kost` pada halaman detail memuat `id`, `name`, `status`.
- [ ] 4.2 Sambungkan ketiga kendali ke helper Wayfinder hasil `php artisan wayfinder:generate` (`@/routes/admin/kosts` atau padanannya) memakai `router.post(url, { reason })`, tampilkan galat `reason`/`status` dari `useForm` di dalam dialog, tutup dialog hanya saat sukses, dan nonaktifkan tombol saat proses berjalan; verifikasi: `npm run check` dan `npm run build` lolos tanpa warning.
- [ ] 4.3 Sisipkan `detail-actions` ke `resources/js/pages/admin/kosts/show.tsx` (satu baris komponen di area aksi) tanpa mengubah tata letak lain; verifikasi: halaman detail untuk kost `menunggu_verifikasi` memuat tombol Setujui dan Tolak, dan daftar `/admin/kosts` tidak memuat tombol yang mengubah status (skenario "Halaman master verifikasi tetap baca-saja").

## 5. Verifikasi Akhir

- [ ] 5.1 Jalankan `composer test` (pint, phpstan level 7, seluruh tes) dan pastikan hijau tanpa galat tipe pada Action, Form Request, Policy, dan Controller baru.
- [ ] 5.2 Jalankan `npm run check`, `npm run types:check`, dan `npm run build`; pastikan tidak ada warning `vp`, tidak ada impor rusak, dan komponen baru tidak memakai `useMemo`/`useCallback` manual.
- [ ] 5.3 Tinjau diff akhir: tidak ada migrasi atau kolom baru, tidak ada perubahan pada halaman Pemilik Kost maupun katalog publik, dan `openspec validate admin-kost-verification-actions` tetap valid.

## Workflow follow-up

- Arsipkan `admin-kost-verification-list` terlebih dahulu, lalu `admin-kost-verification-actions`, karena delta ini menghapus requirement yang lahir di change pertama dan menambah method pada controller yang dibangunnya.
- Change ini tidak mengubah `docs/ERD/erd.md` maupun `docs/FunctionalRequirment/functional_requirment.md`, sehingga tidak ada versi dokumen yang perlu dipensiunkan ke `logs/` atau baris `release.md` baru.
