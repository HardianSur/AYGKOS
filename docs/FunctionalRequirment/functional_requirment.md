# Functional Requirement AYGKOS

## Versi V001

Dibuat: 17-11-2026:17:45 WIB
Dipensiunkan: -

| Kode | Aktor | Fitur | Requirement | Prioritas |
|---|---|---|---|---|
| FR-AUT-01 | Guest | Registrasi | Sistem harus menyediakan formulir registrasi dengan pilihan peran Customer atau Pemilik Kost. Registrasi mandiri sebagai Admin tidak tersedia, dan akun Admin dibuat melalui seeder atau langsung di database. | Tinggi |
| FR-AUT-02 | Guest | Registrasi | Sistem harus meminta nama lengkap, email, password, dan konfirmasi password pada registrasi Customer. | Tinggi |
| FR-AUT-03 | Guest | Registrasi | Sistem harus meminta nama lengkap, email, nomor WhatsApp aktif, password, dan konfirmasi password pada registrasi Pemilik Kost. | Tinggi |
| FR-AUT-04 | Guest | Registrasi | Sistem harus memvalidasi format email, keunikan email, kesesuaian password dengan konfirmasinya, kepatuhan password terhadap aturan kekuatan password (NFR), serta format dan keunikan nomor WhatsApp pada registrasi Pemilik Kost, dan menampilkan pesan kesalahan pada setiap kolom yang tidak valid. | Tinggi |
| FR-AUT-05 | Customer | Verifikasi Email | Sistem harus mengirim email berisi tautan verifikasi setelah registrasi Customer berhasil. Akun berstatus belum terverifikasi hingga tautan dibuka. | Tinggi |
| FR-AUT-06 | Pemilik Kost | Verifikasi WhatsApp | Sistem harus mengirim kode OTP ke nomor WhatsApp Pemilik Kost setelah registrasi berhasil, dan menetapkan akun sebagai terverifikasi apabila kode yang dimasukkan benar, belum kedaluwarsa, dan belum melampaui batas percobaan. | Tinggi |
| FR-AUT-07 | Customer, Pemilik Kost | Kirim Ulang Verifikasi | Sistem harus menyediakan kirim ulang tautan verifikasi email (Customer) atau kode OTP WhatsApp (Pemilik Kost) dengan pembatasan frekuensi pengiriman. | Sedang |
| FR-AUT-08 | Customer, Pemilik Kost, Admin | Login | Sistem harus menyediakan login dengan email dan password serta menampilkan pesan kesalahan yang tidak menyebutkan secara spesifik data yang salah. | Tinggi |
| FR-AUT-09 | Customer, Pemilik Kost | Login | Sistem harus menolak login akun yang belum terverifikasi (disertai arahan verifikasi atau kirim ulang) dan akun yang dinonaktifkan Admin (disertai pesan akun nonaktif). | Tinggi |
| FR-AUT-10 | Customer, Pemilik Kost, Admin | Login | Sistem harus mengarahkan pengguna setelah login berhasil sesuai perannya, yaitu Customer ke halaman pencarian kost, Pemilik Kost ke dashboard Pemilik Kost, dan Admin ke dashboard Admin. | Tinggi |
| FR-AUT-11 | Customer, Pemilik Kost, Admin | Lupa Password | Sistem harus menyediakan fitur lupa password melalui tautan reset yang dikirim ke email dan berlaku terbatas waktu. | Sedang |
| FR-AUT-12 | Customer, Pemilik Kost, Admin | Logout | Sistem harus menyediakan logout yang mengakhiri sesi pengguna dan mengarahkan pengguna ke halaman login. | Tinggi |
| FR-AUT-13 | Customer, Pemilik Kost, Admin | Kontrol Akses | Sistem harus membatasi akses halaman dan aksi sesuai peran, dan menolak akses oleh peran yang tidak berwenang. | Tinggi |
| FR-PRF-01 | Customer, Pemilik Kost | Profil | Sistem harus memungkinkan pengguna melihat dan mengubah nama lengkap. | Sedang |
| FR-PRF-02 | Customer, Pemilik Kost, Admin | Ubah Password | Sistem harus memungkinkan pengguna mengubah password dengan memasukkan password lama dan password baru. | Rendah |
| FR-PRF-03 | Pemilik Kost | Nomor WhatsApp | Sistem harus memungkinkan Pemilik Kost mengubah nomor WhatsApp, dan nomor baru berlaku setelah diverifikasi melalui kode OTP. | Rendah |
| FR-PRF-04 | Customer, Pemilik Kost, Admin | Foto Profil | Sistem harus memungkinkan pengguna mengunggah foto profil (JPG, PNG, atau WebP maksimal 3 MB), menyimpannya sebagai berkas 400×400 piksel, dan menampilkan foto itu pada halaman profil, header, dan menu pengguna. | Rendah |
| FR-DSH-01 | Pemilik Kost | Dashboard Pemilik Kost | Sistem harus menampilkan pada dashboard Pemilik Kost jumlah kost per status verifikasi dan jumlah kamar per status. | Sedang |
| FR-DSH-02 | Admin | Dashboard Admin | Sistem harus menampilkan pada dashboard Admin jumlah kost menunggu verifikasi dan jumlah aduan baru. | Sedang |
| FR-KOS-01 | Pemilik Kost | Pengajuan Kost | Sistem harus memungkinkan Pemilik Kost menambahkan kost dengan data nama, alamat lengkap, kota, titik lokasi (latitude dan longitude), tipe kost (Putra, Putri, atau Campur), deskripsi, fasilitas, peraturan, minimal satu foto, dan dokumen verifikasi (KTP atau bukti kepemilikan). | Tinggi |
| FR-KOS-02 | Pemilik Kost | Pengajuan Kost | Sistem harus menetapkan kost baru berstatus Menunggu Verifikasi dan tidak menampilkannya kepada publik hingga disetujui Admin. | Tinggi |
| FR-KOS-03 | Pemilik Kost | Pengelolaan Kost | Sistem harus memungkinkan Pemilik Kost mengubah informasi kost, dan perubahan berlaku segera setelah disimpan. | Tinggi |
| FR-KOS-04 | Pemilik Kost | Pengelolaan Kost | Sistem harus memungkinkan Pemilik Kost menonaktifkan dan mengaktifkan kembali kost miliknya. Kost nonaktif tidak ditampilkan kepada publik. | Sedang |
| FR-KOS-05 | Pemilik Kost | Pengelolaan Kost | Sistem harus memungkinkan Pemilik Kost menghapus kost miliknya beserta seluruh kamarnya. | Rendah |
| FR-KOS-06 | Pemilik Kost | Pengelolaan Kamar | Sistem harus memungkinkan Pemilik Kost menambahkan kamar pada kost dengan data nomor atau nama kamar dan harga sewa per bulan. | Tinggi |
| FR-KOS-07 | Pemilik Kost | Pengelolaan Kamar | Sistem harus memungkinkan Pemilik Kost mengubah data kamar dan mengatur status kamar menjadi Tersedia, Terisi, atau Nonaktif. | Tinggi |
| FR-KOS-08 | Pemilik Kost | Pengelolaan Kamar | Sistem harus memungkinkan Pemilik Kost menghapus kamar. | Rendah |
| FR-KOS-09 | Pemilik Kost | Status Verifikasi Kost | Sistem harus menampilkan daftar kost milik Pemilik beserta status verifikasi dan alasan penolakan atau penangguhan apabila ada. | Tinggi |
| FR-KOS-10 | Pemilik Kost | Pengajuan Kost | Sistem harus memungkinkan Pemilik Kost memperbaiki dan mengajukan ulang kost yang ditolak. | Sedang |
| FR-SRC-01 | Guest, Customer | Pencarian Kost | Sistem harus mengizinkan penelusuran daftar kost dan pencarian tanpa login. | Tinggi |
| FR-SRC-02 | Guest, Customer | Pencarian Kost | Sistem harus memungkinkan pengguna memasukkan lokasi atau tempat sebagai dasar pencarian, mengonversinya menjadi koordinat, dan menampilkan kost yang berada dalam radius 10 km dari lokasi tersebut. | Tinggi |
| FR-SRC-03 | Guest, Customer | Filter Kost | Sistem harus memungkinkan filter berdasarkan rentang harga, tipe kost, dan ketersediaan kamar. | Tinggi |
| FR-SRC-04 | Guest, Customer | Filter Kost | Sistem harus memungkinkan filter berdasarkan fasilitas. | Rendah |
| FR-SRC-05 | Guest, Customer | Urutan Hasil | Sistem harus memungkinkan pengurutan hasil berdasarkan jarak terdekat (apabila pencarian lokasi digunakan), harga terendah, harga tertinggi, dan terbaru. | Rendah |
| FR-SRC-06 | Guest, Customer | Daftar Hasil Pencarian | Sistem harus menampilkan hasil pencarian per halaman (pagination) dalam bentuk kartu berisi foto utama, nama, kota, tipe, harga mulai dari, dan jumlah kamar tersedia, serta pesan informatif apabila hasil kosong. | Tinggi |
| FR-SRC-07 | Guest, Customer | Peta Hasil Pencarian | Sistem harus menampilkan kost hasil pencarian sebagai titik (marker) pada peta, dan menampilkan ringkasan kost beserta tautan ke halaman detail ketika titik dipilih. | Sedang |
| FR-SRC-08 | Guest, Customer | Informasi Kost | Sistem harus menampilkan halaman detail kost berisi nama, alamat, lokasi pada peta, tipe, deskripsi, fasilitas, galeri foto, peraturan, daftar kamar beserta harga dan statusnya, dan nama Pemilik Kost. | Tinggi |
| FR-SRC-09 | Guest, Customer | Informasi Kost | Sistem harus menampilkan hanya kost berstatus Disetujui dan aktif milik Pemilik Kost yang akunnya aktif, dengan informasi terbaru sesuai perubahan terakhir Pemilik Kost. | Tinggi |
| FR-KTK-01 | Customer | Kontak Pemilik | Sistem harus menyediakan tombol Hubungi Pemilik pada halaman detail kost yang membuka percakapan WhatsApp dengan nomor WhatsApp Pemilik Kost. | Tinggi |
| FR-KTK-02 | Customer | Template Pesan | Sistem harus menyediakan dua pilihan pada tombol Hubungi Pemilik, yaitu Tanya Kost dan Ajukan Sewa, masing-masing dengan template pesan yang disediakan sistem. | Tinggi |
| FR-KTK-03 | Customer | Template Pesan | Sistem harus mengisi template secara otomatis dengan nama kost, nama atau nomor kamar yang dipilih (untuk Ajukan Sewa), dan nama lengkap Customer. | Tinggi |
| FR-KTK-04 | Customer | Template Pesan | Sistem harus menyediakan pilihan Ajukan Sewa hanya pada kamar berstatus Tersedia. | Sedang |
| FR-KTK-05 | Customer | Kontak Pemilik | Sistem harus hanya mengisi draf pesan pada WhatsApp tanpa mengirimkannya, sehingga pesan dikirim dan dapat diubah oleh Customer sebelum dikirim. | Sedang |
| FR-KTK-06 | Guest | Akses Kontak | Sistem harus mengarahkan Guest ke halaman login ketika memilih tombol Hubungi Pemilik, dan mengembalikan Guest ke halaman detail kost semula setelah login berhasil. | Sedang |
| FR-ADU-01 | Customer, Pemilik Kost | Aduan | Sistem harus memungkinkan pengguna mengajukan aduan dengan kategori (Laporan Kost, Laporan Pengguna, Kendala Sistem, atau Lainnya), judul, dan deskripsi, serta pilihan kost yang dilaporkan untuk kategori Laporan Kost. | Tinggi |
| FR-ADU-02 | Customer, Pemilik Kost | Aduan | Sistem harus memungkinkan pengguna melampirkan maksimal 3 foto atau dokumen pendukung pada aduan. | Sedang |
| FR-ADU-03 | Customer, Pemilik Kost | Riwayat Aduan | Sistem harus menampilkan riwayat aduan beserta status (Baru, Diproses, Selesai) dan tanggapan Admin kepada pengguna. | Tinggi |
| FR-ADU-04 | Admin | Penanganan Aduan | Sistem harus menampilkan daftar aduan dengan filter status dan kategori, serta detail aduan beserta lampirannya. | Tinggi |
| FR-ADU-05 | Admin | Penanganan Aduan | Sistem harus memungkinkan Admin mengubah status aduan dan menulis tanggapan. | Tinggi |
| FR-ADM-VER-01 | Admin | Verifikasi Kost | Sistem harus menampilkan daftar kost berstatus Menunggu Verifikasi beserta detail kost dan dokumen verifikasinya. | Tinggi |
| FR-ADM-VER-02 | Admin | Verifikasi Kost | Sistem harus memungkinkan Admin menyetujui kost sehingga berstatus Disetujui dan tampil kepada publik. | Tinggi |
| FR-ADM-VER-03 | Admin | Verifikasi Kost | Sistem harus memungkinkan Admin menolak kost dengan menyertakan alasan penolakan. | Tinggi |
| FR-ADM-USR-01 | Admin | Pengelolaan Pengguna | Sistem harus memungkinkan Admin melihat daftar pengguna serta menonaktifkan dan mengaktifkan akun. Akun nonaktif tidak dapat login, dan kost milik Pemilik Kost nonaktif tidak ditampilkan kepada publik. | Sedang |
| FR-ADM-KOS-01 | Admin | Pengelolaan Kost | Sistem harus memungkinkan Admin menangguhkan kost yang melanggar ketentuan dengan menyertakan alasan, dan kost berstatus Ditangguhkan tidak dapat diaktifkan kembali oleh Pemilik Kost. | Sedang |
| FR-ADM-REP-01 | Admin | Laporan | Sistem harus menampilkan ringkasan statistik berupa jumlah kost per status, jumlah kamar tersedia, jumlah pengguna per peran, dan jumlah aduan per status. | Sedang |
| FR-ADM-REP-02 | Admin | Laporan | Sistem harus memungkinkan ringkasan statistik difilter berdasarkan periode waktu tertentu. | Rendah |
| FR-NTF-01 | Customer, Pemilik Kost, Admin | Notifikasi | Sistem harus menampilkan daftar notifikasi in-app dengan penanda belum dibaca. | Rendah |
| FR-NTF-02 | Customer, Pemilik Kost | Notifikasi | Sistem harus membuat notifikasi in-app bagi Pemilik Kost ketika kostnya disetujui, ditolak, atau ditangguhkan, dan bagi pengadu ketika aduannya ditanggapi atau berubah status. | Rendah |
