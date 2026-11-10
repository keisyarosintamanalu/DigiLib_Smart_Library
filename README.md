# DIGILIB SMART LIBRARY

Project perpustakaan berbasis PHP. Seluruh akun dan data perpustakaan disimpan di file JSON lokal; cover buku disimpan sebagai file gambar di `assets/uploads/covers/` dan nama file-nya dicatat di JSON. Tidak memerlukan database layanan eksternal untuk menjalankan aplikasi.

## Menjalankan di Laragon

1. Letakkan project di `C:\laragon\www\DigiLib_Smart_Library`.
2. Nyalakan Apache dari Laragon. MySQL tidak diperlukan.
3. Pastikan PHP dapat menulis ke folder `database/` dan `assets/uploads/covers/`.
4. Buka `http://localhost/DigiLib_Smart_Library/`; login tersedia di `?login=1`.

Saat pertama dibuka, aplikasi membuat `database/data.json` dari `database/data.example.json`. Akun awal:

| Peran | Email | Kata sandi |
|---|---|---|
| Admin | `admin@digilib.id` | `admin123` |
| Anggota | `anggota@digilib.id` | `anggota123` |

Ganti kata sandi awal sebelum aplikasi digunakan secara publik.

## Database JSON dan foto

- `database/data.json` adalah database aktif berisi `users`, `kategori`, `anggota`, `buku`, `peminjaman`, `pengembalian`, dan `denda`.
- `database/data.example.json` hanya seed awal; jangan menaruh data pengguna atau data produksi di file contoh.
- Aplikasi memakai penguncian file dan penulisan atomik untuk mencegah dua permintaan web menimpa perubahan satu sama lain.
- Folder `database/` ditolak akses langsung dari web oleh `.htaccess`. Backup `data.json` secara berkala dan simpan backup di tempat privat.
- `data.json` sengaja dikecualikan dari Git agar kata sandi hash, email, alamat, dan data peminjaman anggota tidak terpublikasikan. Di hosting, unggah file JSON secara privat atau jalankan migrasi seperti dijelaskan berikutnya.
- Cover JPG, PNG, dan WebP maksimal 3 MB disimpan di `assets/uploads/covers/`. Database JSON hanya mencatat nama file cover.

## Memindahkan data MySQL lama

Jika project lama Anda memiliki database MySQL berisi data yang ingin dipertahankan, jalankan migrasi **sebelum menghapus database lama** dari komputer yang dapat mengaksesnya:

```powershell
$env:DIGILIB_DB_HOST = "127.0.0.1"
$env:DIGILIB_DB_PORT = "3306"
$env:DIGILIB_DB_NAME = "digilib_smart_library"
$env:DIGILIB_DB_USER = "root"
$env:DIGILIB_DB_PASS = ""
php database\migrate_mysql_to_json.php
```

Migrasi hanya dapat dijalankan melalui CLI, berhenti jika `database/data.json` sudah ada, dan tidak mengubah database MySQL sumber. Setelah berhasil, pindahkan `database/data.json` ke server secara privat (jangan commit atau unggah melalui URL publik). Foto cover yang telah ada di folder upload tetap dipakai selama folder gambarnya ikut dipindahkan.

Untuk instalasi baru tanpa database lama, aplikasi membuat akun dan kategori contoh dari `database/data.example.json`; Anda dapat mulai menambah buku dan anggota dari halaman admin.

## Login dan fitur

- Login dan pembatasan peran untuk admin, pustakawan, dan anggota.
- Admin mengelola buku, cover, kategori awal, anggota, peminjaman, pengembalian, denda, dan laporan.
- Anggota dapat mencari katalog, melihat detail buku, meminjam, dan memeriksa riwayat.
- Setiap perubahan database JSON dilakukan di server dengan token CSRF; password disimpan sebagai hash.

Landing page dan portal PHP tersedia melalui `index.php` pada hosting yang menjalankan PHP. Untuk deployment statis, entry point `index.html` menjalankan aplikasi browser dari `assets/app.js`.

## Deployment Vercel (website statis)

Vercel menyajikan `index.html`, yang memuat landing page, login, dashboard admin/anggota, dan fitur SPA di `assets/app.js`. `index.php` tetap dipakai di server PHP seperti Laragon, tetapi Vercel tidak mengeksekusi file PHP.

Pada deployment statis, data SPA hanya tersimpan di `localStorage` browser yang sedang dipakai dan tidak membaca atau memperbarui `database/data.json`. Artinya, perubahan data tidak tersinkron antarperangkat dan akan hilang jika data situs browser dihapus. Database JSON server di `database/data.json` hanya berlaku untuk hosting yang menjalankan PHP dan memiliki disk persisten.
