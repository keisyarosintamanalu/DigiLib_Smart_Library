# DIGILIB SMART LIBRARY

Project perpustakaan yang mulai memakai PHP dan MySQL untuk login. Jalankan melalui Apache Laragon, bukan dengan membuka file HTML langsung.

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
│   ├── index.php
│   └── peminjaman.php
├── assets/
│   ├── app.js
│   ├── styles.css
│   └── uploads/
│       └── covers/
├── config/
│   ├── .htaccess
│   ├── auth.php
│   └── database.php
├── database/
│   ├── .htaccess
│   ├── digilib_smart_library.sql
│   └── migrations/
│       └── 001_add_book_isbn_cover.sql
├── includes/
│   ├── portal_footer.php
│   └── portal_header.php
├── .htaccess
├── index.html
├── index.php
├── logout.php
└── README.md
```

## Cara menjalankan

1. Pastikan folder ada di `C:\laragon\www\DigiLib_Smart_Library`.
2. Klik **Start All** di Laragon, termasuk Apache dan MySQL.
3. Impor `database/digilib_smart_library.sql` satu kali melalui HeidiSQL. Skrip tersebut membuat database dan tabel sekaligus memasukkan akun demo.
4. Buka `http://localhost/DigiLib_Smart_Library/`.
5. Login dengan salah satu akun di bawah.

Konfigurasi default di [config/database.php](./config/database.php) menggunakan host `127.0.0.1`, port `3306`, user `root`, kata sandi kosong, dan database `digilib_smart_library`. Ubah melalui variabel lingkungan `DIGILIB_DB_HOST`, `DIGILIB_DB_PORT`, `DIGILIB_DB_NAME`, `DIGILIB_DB_USER`, dan `DIGILIB_DB_PASS` bila pengaturan MySQL Anda berbeda. Ekstensi PHP `pdo_mysql` diperlukan.

## Akun demo

| Jenis akun | Email | Kata sandi |
|---|---|---|
| Admin / Pustakawan | `admin@digilib.id` | `admin123` |
| Anggota | `anggota@digilib.id` | `anggota123` |

Kata sandi disimpan sebagai hash `password_hash()` di database; SQL tidak menyimpan kata sandi dalam teks biasa. Jika database sebelumnya sudah diimpor sebelum fitur login dibuat, jalankan kembali file `database/digilib_smart_library.sql` di HeidiSQL untuk membuat akun demo dengan aman.

## Login dan pembatasan akses

- `index.php` adalah halaman login dan memeriksa akun di MySQL menggunakan PDO serta `password_verify()`.
- Peran admin/pustakawan diarahkan ke `admin/index.php`; peran anggota diarahkan ke `anggota/index.php`.
- Setiap halaman portal memeriksa sesi dan peran sebelum menampilkan konten.
- Logout memakai permintaan POST dengan token CSRF, lalu menghapus sesi PHP.
- `.htaccess` memilih `index.php` sebagai halaman awal, mengalihkan `index.html` lama ke halaman login, dan menolak akses web langsung ke folder konfigurasi serta SQL.

## Landing page dan deployment Vercel

Pada deployment Vercel `key.my.id`, entry point yang digunakan adalah `index.html`. Landing page, login demo, dan dashboard SPA dirender oleh `assets/app.js`; perpindahan Landing → Login → Dashboard terjadi dalam aplikasi tersebut tanpa mengganti URL.

Jalur SPA ini memakai `localStorage` untuk menyimpan data dan sesi demo di browser, bukan MySQL atau session PHP. Data tidak dibagikan antarperangkat dan login tersebut bukan autentikasi server-side untuk penggunaan produksi. PHP/MySQL di `index.php`, `admin/`, dan `anggota/` adalah jalur terpisah yang berjalan di Apache seperti Laragon/cPanel; Vercel hanya menjalankannya jika deployment dikonfigurasi dengan runtime/container PHP dan koneksi database yang sesuai.

## Dashboard Admin

Dashboard di `admin/index.php` mengambil jumlah buku, anggota, pinjaman aktif, dan total denda langsung dari MySQL. Dashboard juga menampilkan daftar buku aktif dengan stok rendah (1–3 eksemplar), grafik koleksi per kategori, serta grafik statistik transaksi menurut status termasuk pinjaman yang terlambat. Angka dan grafik dimuat ulang dari database setiap kali dashboard dibuka, sehingga mengikuti perubahan transaksi peminjaman dan pengembalian tanpa data statistik manual.

## Modul Data Buku

Setelah login sebagai admin, buka **Data Buku** pada sidebar atau `admin/buku.php`. Modul ini menyediakan tambah, daftar/lihat, edit, hapus, pencarian ISBN/judul/penulis/penerbit, dan filter kategori. Data tersimpan langsung ke MySQL; cover menerima JPG, PNG, atau WebP maksimal 3 MB dan disimpan di `assets/uploads/covers/`.

Jika database awal sudah pernah dibuat sebelum kolom ISBN dan cover tersedia, jalankan **sekali** `database/migrations/001_add_book_isbn_cover.sql` melalui HeidiSQL. Migrasi tersebut menambahkan kolom dan indeks ISBN lalu menyediakan kategori pilihan awal. Jika database dibuat baru, jalankan `database/digilib_smart_library.sql` saja; jangan jalankan migrasi setelahnya karena kolom sudah termasuk dalam skema utama.

Buku dengan histori peminjaman yang mereferensikannya tidak dapat dihapus karena foreign key database melindungi riwayat tersebut.

## Modul Data Anggota

Setelah login admin, buka **Data Anggota** pada sidebar atau `admin/anggota.php`. Modul ini menyediakan tambah, lihat, edit, hapus, pencarian nama/email/nomor anggota/telepon/alamat, dan filter status Aktif/Nonaktif. Setiap profil anggota dibuat bersama akun `users` ber-role `anggota`; perubahan nama, email, dan status diperbarui pada kedua tabel dalam transaksi database. Kata sandi baru di-hash menggunakan `password_hash()` dan saat edit dapat dikosongkan agar kata sandi tidak berubah.

Anggota nonaktif disimpan dengan status nonaktif pada profil dan akun, sehingga tidak dapat login atau meminjam. Pemeriksaan akses anggota juga membaca status terbaru dari database pada setiap halaman PHP yang memakai `digilibRequireRole(['anggota'])`; sesi anggota yang dinonaktifkan akan dibatalkan pada permintaan berikutnya.

Penghapusan anggota sekaligus menghapus akun login terkait dalam satu transaksi. Jika anggota sudah memiliki riwayat peminjaman, foreign key akan menolak penghapusan agar riwayat transaksi tidak rusak; ubah statusnya menjadi Nonaktif sebagai gantinya.

## Modul Peminjaman Buku

Anggota aktif dapat membuka menu **Peminjaman** untuk melihat buku dengan stok tersedia, meminjam buku, dan melihat transaksi miliknya. Setiap peminjaman mencatat tanggal hari ini dan tanggal jatuh tempo 7 hari kemudian. Stok buku berkurang satu dalam transaksi database yang sama. Batas pinjaman aktif adalah 3 buku per anggota; anggota nonaktif tidak dapat mengakses modul. Admin dapat melihat semua transaksi pada menu **Peminjaman** di `admin/peminjaman.php`.

Pengecekan status anggota, batas pinjaman, penguncian stok, pengurangan stok, dan pembuatan transaksi dilakukan di server dalam transaksi MySQL. Database tidak diberi data buku dummy; tambahkan buku melalui modul Data Buku sebelum mencoba peminjaman.

## Modul Pengembalian dan Denda

Admin memproses transaksi yang masih berstatus `dipinjam` pada menu **Pengembalian** (`admin/pengembalian.php`). Proses menggunakan transaksi dan penguncian baris, mencatat tanggal kembali memakai `CURRENT_DATE` MySQL, mengubah status peminjaman, dan menambah stok satu. Keterlambatan dihitung MySQL dengan `DATEDIFF(tanggal_kembali, tanggal_jatuh_tempo)` dari tanggal yang tersimpan; denda dihitung sebagai hari terlambat dikali Rp2.000. Pengembalian tepat waktu menghasilkan Rp0 dan status denda `lunas` tanpa pembayaran.

Menu **Denda** (`admin/denda.php`) menampilkan detail anggota, buku, tanggal jatuh tempo/kembali, hari terlambat, tarif, jumlah denda, serta status pembayaran. Admin dapat menandai denda positif sebagai lunas; tanggal pembayaran juga dicatat oleh MySQL. Tidak diperlukan migrasi database tambahan untuk modul ini karena tabel `pengembalian` dan `denda` sudah ada dalam skema.

## Modul Laporan

Admin/pustakawan dapat membuka menu **Laporan** (`admin/laporan.php`) untuk melihat laporan data buku, anggota, peminjaman, pengembalian, dan denda. Filter tanggal tersedia; tanggal yang digunakan mengikuti jenis laporan (tanggal buku ditambahkan, tanggal anggota daftar, tanggal peminjaman, atau tanggal pengembalian). Tombol **Cetak laporan** menjalankan `window.print()`. Tampilan cetak menyembunyikan navigasi, filter, dan kontrol sehingga hanya bagian laporan yang dicetak.
