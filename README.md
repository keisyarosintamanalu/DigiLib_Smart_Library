# DIGILIB SMART LIBRARY

Project perpustakaan memakai PHP dan MySQL untuk autentikasi dan data produksi. Landing page dan portal PHP dijalankan melalui Apache, termasuk hosting InfinityFree; jangan membuka file HTML langsung.

## Struktur project

```text
DigiLib_Smart_Library/
├── admin/
│   ├── anggota.php
│   ├── buku.php
│   ├── denda.php
│   ├── index.php
│   ├── laporan.php
│   ├── pengembalian.php
│   └── peminjaman.php
├── anggota/
│   ├── buku.php
│   ├── detail_buku.php
│   ├── index.php
│   └── peminjaman.php
├── assets/
│   ├── app.js
│   ├── landing.js
│   ├── styles.css
│   └── uploads/
│       └── covers/
├── config/
│   ├── .htaccess
│   ├── auth.php
│   ├── database.php
│   ├── database.local.example.php
│   ├── supabase_storage.php
│   └── supabase.local.example.php
├── database/
│   ├── .htaccess
│   ├── digilib_smart_library.sql
│   └── migrations/
│       ├── 001_add_book_isbn_cover.sql
│       ├── 002_update_admin_email.sql
│       └── 003_import_vercel_demo_seed.sql
├── includes/
│   ├── landing_page.php
│   ├── portal_footer.php
│   └── portal_header.php
├── .htaccess
├── .gitignore
├── index.html
├── index.php
├── logout.php
└── README.md
```

## Cara menjalankan

1. Pastikan folder ada di `C:\laragon\www\DigiLib_Smart_Library`.
2. Klik **Start All** di Laragon, termasuk Apache dan MySQL.
3. Buat/pilih database `digilib_smart_library`, lalu impor `database/digilib_smart_library.sql` melalui HeidiSQL/phpMyAdmin.
4. Buka `http://localhost/DigiLib_Smart_Library/` untuk landing page atau tambahkan `?login=1` untuk form login.
5. Login dengan salah satu akun demo di bawah.

Konfigurasi lokal default di [config/database.php](./config/database.php) memakai host `127.0.0.1`, port `3306`, user `root`, kata sandi kosong, dan database `digilib_smart_library`. Untuk hosting, gunakan environment `DIGILIB_DB_*` atau salin `config/database.local.example.php` menjadi `config/database.local.php` dan isi detail database hosting. File lokal tersebut diabaikan Git dan folder `config/` ditolak akses langsung oleh `.htaccess`. Ekstensi PHP `pdo_mysql` diperlukan.

## Deploy ke InfinityFree

1. Buat hosting dan database MySQL dari panel InfinityFree. Catat hostname MySQL, nama database lengkap, username, dan password yang diberikan panel.
2. Buka phpMyAdmin untuk database yang baru dibuat dan impor `database/digilib_smart_library.sql` ke database tersebut. SQL ini membuat tabel di database yang sedang dipilih; database fisik tidak disimpan di folder website.
3. Salin seluruh file aplikasi ke folder `htdocs`, kecuali `.git/`. Pastikan `.htaccess`, `index.php`, `includes/`, `config/`, dan `assets/` ikut terunggah.
4. Buat `config/database.local.php` di hosting dengan menyalin format dari `config/database.local.example.php`, lalu isi kredensial MySQL InfinityFree. Jangan commit atau membagikan file lokal ini.
5. Siapkan Supabase Storage sesuai bagian **Penyimpanan cover di Supabase** di bawah. Cover baru akan disimpan di Supabase; cover yang sudah tersimpan lokal tetap bisa ditampilkan.
6. Arahkan domain ke nameserver/DNS yang ditampilkan panel InfinityFree. Ini dilakukan di pengelola domain, tidak melalui push GitHub.
7. Uji root domain (landing), tombol Login, akun admin/anggota, logout, tambah buku/anggota, peminjaman, pengembalian, denda, dan laporan.

Di Apache InfinityFree, `.htaccess` memilih `index.php` sebagai entry point; root menampilkan landing dan `index.php?login=1` menampilkan form login PHP/MySQL. `index.html` lama diarahkan ke entry point PHP. Deployment Vercel sebelumnya tidak menjalankan autentikasi PHP dan tidak lagi menjadi hosting utama setelah DNS domain dipindahkan.

## Akun demo

| Jenis akun | Email | Kata sandi |
|---|---|---|
| Admin / Pustakawan | `keibuildsbiztech@gmail.com` | Password yang Anda tetapkan; hash tersimpan di MySQL |
| Anggota | `anggota@digilib.id` | `anggota123` |

Kata sandi disimpan sebagai hash `password_hash()` di database; SQL tidak menyimpan kata sandi dalam teks biasa. Impor `database/digilib_smart_library.sql` hanya saat menyiapkan database baru. Jangan impor ulang ke database yang berisi data produksi.

Pada database lama yang sudah mempunyai admin `admin@digilib.id`, jalankan satu kali `database/migrations/002_update_admin_email.sql` melalui phpMyAdmin setelah memilih database dan sebelum menambahkan akun dengan email baru. Jika schema lama belum memiliki kolom ISBN/cover, jalankan migrasi `001` terlebih dahulu. Ganti password admin setelah login pertama karena password awal sebaiknya dianggap sementara untuk deployment.

## Login dan pembatasan akses

- `index.php` menampilkan landing page pada root; form login PHP tersedia melalui `index.php?login=1` dan memeriksa akun MySQL memakai PDO serta `password_verify()`.
- Peran admin/pustakawan diarahkan ke `admin/index.php`; peran anggota diarahkan ke `anggota/index.php`.
- Setiap halaman portal memeriksa sesi dan peran sebelum menampilkan konten.
- Logout memakai permintaan POST dengan token CSRF, lalu menghapus sesi PHP.
- `.htaccess` memilih `index.php` sebagai halaman awal, mengalihkan `index.html` lama ke entry point PHP, dan menolak akses web langsung ke folder konfigurasi serta SQL.

## Landing page dan deployment Vercel

File `index.html` dan `assets/app.js` menyimpan SPA demo lama untuk pengembangan lokal/arsip. Deployment InfinityFree yang didokumentasikan di atas menggunakan `index.php`, landing PHP, autentikasi MySQL, dan session server-side.

Jika masih mengakses deployment Vercel lama, aplikasi itu menjalankan SPA berbasis `localStorage`, bukan backend PHP/MySQL. Untuk produksi InfinityFree, arahkan domain ke hosting InfinityFree dan gunakan URL login `index.php?login=1`.

Untuk menambahkan data contoh awal dari SPA Vercel ke database PHP, jalankan `database/migrations/003_import_vercel_demo_seed.sql` satu kali setelah skema dibuat. Migrasi ini menambahkan data yang belum ada dan hanya mengganti profil anggota `DEMO-0001` bila masih memakai nama placeholder `Anggota Demo`; data lokal lainnya tidak dihapus. Akun demo yang ditambahkan: `admin@digilib.id` / `admin123`, `sinta@digilib.id` / `sinta123`, `bima@digilib.id` / `bima123`, dan `alya@digilib.id` / `alya123`. Ganti kata sandi akun demo sebelum dipakai di hosting publik.

Vercel menyimpan perubahan data pada `localStorage` browser masing-masing. Migrasi ini hanya menyalin seed awal yang tercatat di `assets/app.js`, bukan transaksi atau perubahan yang pernah tersimpan di browser Vercel.

## Dashboard Admin

Dashboard di `admin/index.php` mengambil jumlah buku, anggota, pinjaman aktif, dan total denda langsung dari MySQL. Dashboard juga menampilkan daftar buku aktif dengan stok rendah (1–3 eksemplar), grafik koleksi per kategori, serta grafik statistik transaksi menurut status termasuk pinjaman yang terlambat. Angka dan grafik dimuat ulang dari database setiap kali dashboard dibuka, sehingga mengikuti perubahan transaksi peminjaman dan pengembalian tanpa data statistik manual.

## Modul Data Buku

Setelah login sebagai admin, buka **Data Buku** pada sidebar atau `admin/buku.php`. Modul ini menyediakan tambah, daftar/lihat, edit, hapus, pencarian ISBN/judul/penulis/penerbit, dan filter kategori. Metadata buku tersimpan di MySQL; cover JPG, PNG, atau WebP maksimal 3 MB diunggah ke Supabase Storage.

### Penyimpanan cover di Supabase

Database aplikasi saat ini tetap MySQL; file gambar disimpan di Supabase Storage, sedangkan kolom `buku.cover_path` menyimpan kunci objeknya. Cover lama yang berupa nama file lokal tetap ditampilkan.

1. Di project Supabase, buat bucket Storage bernama `book-covers` dan atur sebagai **Public bucket** agar gambar katalog dapat ditampilkan kepada anggota.
2. Salin `config/supabase.local.example.php` menjadi `config/supabase.local.php` di server, lalu isi URL project dan **service_role key** dari Supabase. Alternatifnya, atur environment `SUPABASE_URL`, `SUPABASE_STORAGE_BUCKET`, dan `SUPABASE_SERVICE_ROLE_KEY`.
3. Jangan pernah mengirim, memasukkan ke Git, atau menaruh service-role key di browser. Kunci ini hanya digunakan oleh PHP server; `.gitignore` mengecualikan `config/supabase.local.php`.
4. Pastikan ekstensi PHP cURL aktif. URL project default mengikuti project Supabase yang tersedia pada konfigurasi ini; ganti jika memakai project berbeda.

Upload dan penghapusan objek dilakukan oleh PHP server menggunakan service-role key. Jika key/bucket belum dikonfigurasi dengan benar, form buku menampilkan kegagalan simpan cover; metadata buku tidak disimpan dengan cover yang gagal diunggah.

Jika database awal sudah pernah dibuat sebelum kolom ISBN dan cover tersedia, pilih database target di HeidiSQL/phpMyAdmin lalu jalankan **sekali** `database/migrations/001_add_book_isbn_cover.sql`. Migrasi tersebut menambahkan kolom dan indeks ISBN lalu menyediakan kategori pilihan awal. Jika database dibuat baru, jalankan `database/digilib_smart_library.sql` saja; jangan jalankan migrasi setelahnya karena kolom sudah termasuk dalam skema utama.

Buku dengan histori peminjaman yang mereferensikannya tidak dapat dihapus karena foreign key database melindungi riwayat tersebut.

## Modul Data Anggota

Setelah login admin, buka **Data Anggota** pada sidebar atau `admin/anggota.php`. Modul ini menyediakan tambah, lihat, edit, hapus, pencarian nama/email/nomor anggota/telepon/alamat, dan filter status Aktif/Nonaktif. Setiap profil anggota dibuat bersama akun `users` ber-role `anggota`; perubahan nama, email, dan status diperbarui pada kedua tabel dalam transaksi database. Kata sandi baru di-hash menggunakan `password_hash()` dan saat edit dapat dikosongkan agar kata sandi tidak berubah.

Anggota nonaktif disimpan dengan status nonaktif pada profil dan akun, sehingga tidak dapat login atau meminjam. Pemeriksaan akses anggota juga membaca status terbaru dari database pada setiap halaman PHP yang memakai `digilibRequireRole(['anggota'])`; sesi anggota yang dinonaktifkan akan dibatalkan pada permintaan berikutnya.

Penghapusan anggota sekaligus menghapus akun login terkait dalam satu transaksi. Jika anggota sudah memiliki riwayat peminjaman, foreign key akan menolak penghapusan agar riwayat transaksi tidak rusak; ubah statusnya menjadi Nonaktif sebagai gantinya.

## Modul Peminjaman Buku

Anggota aktif dapat membuka menu **Peminjaman** untuk melihat buku dengan stok tersedia, meminjam buku, dan melihat transaksi miliknya. Setiap peminjaman mencatat tanggal hari ini dan tanggal jatuh tempo 7 hari kemudian. Stok buku berkurang satu dalam transaksi database yang sama. Batas pinjaman aktif adalah 3 buku per anggota; anggota nonaktif tidak dapat mengakses modul. Admin dapat melihat semua transaksi pada menu **Peminjaman** di `admin/peminjaman.php`.

Dashboard anggota menampilkan pinjaman aktif, jumlah buku tersedia, denda yang belum dibayar, pinjaman yang perlu dikembalikan, dan rekomendasi bacaan. Menu **Data Buku** pada akun anggota membuka katalog dengan pencarian, filter kategori, stok/status, serta peminjaman langsung. Menu **Peminjaman** menampilkan riwayat anggota tersebut. Dashboard admin menampilkan ringkasan koleksi, anggota, pinjaman, denda, grafik kategori dan transaksi, akses cepat, serta aktivitas peminjaman terbaru.

Pengecekan status anggota, batas pinjaman, penguncian stok, pengurangan stok, dan pembuatan transaksi dilakukan di server dalam transaksi MySQL. Database tidak diberi data buku dummy; tambahkan buku melalui modul Data Buku sebelum mencoba peminjaman.

## Modul Pengembalian dan Denda

Admin memproses transaksi yang masih berstatus `dipinjam` pada menu **Pengembalian** (`admin/pengembalian.php`). Proses menggunakan transaksi dan penguncian baris, mencatat tanggal kembali memakai `CURRENT_DATE` MySQL, mengubah status peminjaman, dan menambah stok satu. Keterlambatan dihitung MySQL dengan `DATEDIFF(tanggal_kembali, tanggal_jatuh_tempo)` dari tanggal yang tersimpan; denda dihitung sebagai hari terlambat dikali Rp2.000. Pengembalian tepat waktu menghasilkan Rp0 dan status denda `lunas` tanpa pembayaran.

Menu **Denda** (`admin/denda.php`) menampilkan detail anggota, buku, tanggal jatuh tempo/kembali, hari terlambat, tarif, jumlah denda, serta status pembayaran. Admin dapat menandai denda positif sebagai lunas; tanggal pembayaran juga dicatat oleh MySQL. Tidak diperlukan migrasi database tambahan untuk modul ini karena tabel `pengembalian` dan `denda` sudah ada dalam skema.

## Modul Laporan

Admin/pustakawan dapat membuka menu **Laporan** (`admin/laporan.php`) untuk melihat laporan data buku, anggota, peminjaman, pengembalian, dan denda. Filter tanggal tersedia; tanggal yang digunakan mengikuti jenis laporan (tanggal buku ditambahkan, tanggal anggota daftar, tanggal peminjaman, atau tanggal pengembalian). Tombol **Cetak laporan** menjalankan `window.print()`. Tampilan cetak menyembunyikan navigasi, filter, dan kontrol sehingga hanya bagian laporan yang dicetak.
