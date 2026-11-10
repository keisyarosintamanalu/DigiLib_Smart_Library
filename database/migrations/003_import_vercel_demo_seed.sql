START TRANSACTION;

INSERT INTO `kategori` (`nama_kategori`, `deskripsi`)
VALUES
  ('Fiksi', 'Novel, cerita pendek, dan karya sastra.'),
  ('Pengembangan Diri', 'Keterampilan pribadi dan pengembangan karier.'),
  ('Filsafat', 'Buku filsafat dan pemikiran.'),
  ('Sejarah', 'Sejarah Indonesia dan dunia.'),
  ('Teknologi', 'Komputer, teknologi informasi, dan sains terapan.')
ON DUPLICATE KEY UPDATE `nama_kategori` = VALUES(`nama_kategori`);

INSERT INTO `users` (`nama_lengkap`, `email`, `password_hash`, `role`, `status`)
SELECT seed.`nama_lengkap`, seed.`email`, seed.`password_hash`, seed.`role`, 'aktif'
FROM (
  SELECT 'Nadia Pratama' AS `nama_lengkap`, 'admin@digilib.id' AS `email`, '$2y$10$cAd6ItfevdiqOUaLJeVJd.nbZ0q1dwZE5jXPalCNMo4ABY8tmETK6' AS `password_hash`, 'admin' AS `role`
  UNION ALL SELECT 'Sinta Maharani', 'sinta@digilib.id', '$2y$10$k71AhIGj93PJOY6fq1LyNOGnpDOXyC1kL6z6MRSo1tBjcYqrpSO1u', 'anggota'
  UNION ALL SELECT 'Bima Saputra', 'bima@digilib.id', '$2y$10$gjIrzBIrc/FNjqpfzhvI.OWMXCk9EI06C2l5Utswz1PNs5siwKpjy', 'anggota'
  UNION ALL SELECT 'Alya Permata', 'alya@digilib.id', '$2y$10$ueuDViG9FQpekvr2ePwkI.dPx65GFr.9JI4dcIIys4c2YBqU3lTC.', 'anggota'
) seed
WHERE NOT EXISTS (SELECT 1 FROM `users` u WHERE u.`email` = seed.`email`);

UPDATE `users`
SET `nama_lengkap` = 'Raka Putra'
WHERE `email` = 'anggota@digilib.id' AND `nama_lengkap` = 'Anggota Demo';

UPDATE `anggota` a
INNER JOIN `users` u ON u.`user_id` = a.`user_id`
SET a.`nomor_anggota` = 'AGT-001',
    a.`nomor_telepon` = '0812-3456-7890',
    a.`alamat` = 'Jl. Melati No. 12, Bandung',
    a.`tanggal_daftar` = '2025-01-15'
WHERE u.`email` = 'anggota@digilib.id' AND a.`nomor_anggota` = 'DEMO-0001';

INSERT INTO `anggota` (`user_id`, `nomor_anggota`, `nomor_telepon`, `alamat`, `tanggal_daftar`, `status`)
SELECT u.`user_id`, seed.`nomor_anggota`, seed.`nomor_telepon`, seed.`alamat`, seed.`tanggal_daftar`, 'aktif'
FROM (
  SELECT 'sinta@digilib.id' AS `email`, 'AGT-002' AS `nomor_anggota`, '0813-2222-4567' AS `nomor_telepon`, 'Jl. Cendana No. 8, Bandung' AS `alamat`, '2025-03-02' AS `tanggal_daftar`
  UNION ALL SELECT 'bima@digilib.id', 'AGT-003', '0821-7788-9911', 'Jl. Kenanga No. 21, Cimahi', '2025-04-18'
  UNION ALL SELECT 'alya@digilib.id', 'AGT-004', '0857-1234-8866', 'Jl. Anggrek No. 5, Bandung', '2025-06-11'
) seed
INNER JOIN `users` u ON u.`email` = seed.`email` AND u.`role` = 'anggota'
WHERE NOT EXISTS (
  SELECT 1 FROM `anggota` a WHERE a.`user_id` = u.`user_id` OR a.`nomor_anggota` = seed.`nomor_anggota`
);

INSERT INTO `buku` (`kategori_id`, `judul`, `penulis`, `penerbit`, `tahun_terbit`, `rak`, `stok`, `status`)
SELECT k.`kategori_id`, seed.`judul`, seed.`penulis`, seed.`penerbit`, seed.`tahun_terbit`, seed.`rak`, seed.`stok`, 'aktif'
FROM (
  SELECT 'Laut Bercerita' AS `judul`, 'Leila S. Chudori' AS `penulis`, 'Kepustakaan Populer Gramedia' AS `penerbit`, 2017 AS `tahun_terbit`, 'A-01' AS `rak`, 4 AS `stok`, 'Fiksi' AS `kategori`
  UNION ALL SELECT 'Atomic Habits', 'James Clear', 'Gramedia Pustaka Utama', 2019, 'B-03', 5, 'Pengembangan Diri'
  UNION ALL SELECT 'Filosofi Teras', 'Henry Manampiring', 'Kompas', 2018, 'C-02', 3, 'Filsafat'
  UNION ALL SELECT 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Lentera Dipantara', 1980, 'A-02', 2, 'Fiksi'
  UNION ALL SELECT 'Sapiens', 'Yuval Noah Harari', 'Kepustakaan Populer Gramedia', 2017, 'D-01', 4, 'Sejarah'
  UNION ALL SELECT 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, 'A-03', 0, 'Fiksi'
  UNION ALL SELECT 'Dasar-Dasar Pemrograman Web', 'R. S. Pressman', 'Informatika', 2021, 'E-01', 3, 'Teknologi'
  UNION ALL SELECT 'Filosofi untuk Hidup dan Bertahan', 'Massimo Pigliucci', 'Kepustakaan Populer Gramedia', 2020, 'C-03', 2, 'Filsafat'
) seed
INNER JOIN `kategori` k ON k.`nama_kategori` = seed.`kategori`
WHERE NOT EXISTS (
  SELECT 1
  FROM `buku` b
  WHERE b.`judul` = seed.`judul` AND b.`penulis` = seed.`penulis`
);

INSERT INTO `peminjaman` (`anggota_id`, `buku_id`, `tanggal_pinjam`, `tanggal_jatuh_tempo`, `status`)
SELECT a.`anggota_id`, b.`buku_id`, DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 4 DAY), 'dipinjam'
FROM `anggota` a
INNER JOIN `users` u ON u.`user_id` = a.`user_id` AND u.`email` = 'sinta@digilib.id'
INNER JOIN `buku` b ON b.`judul` = 'Laut Bercerita'
WHERE NOT EXISTS (
  SELECT 1 FROM `peminjaman` p WHERE p.`anggota_id` = a.`anggota_id` AND p.`buku_id` = b.`buku_id` AND p.`status` = 'dipinjam'
);

INSERT INTO `peminjaman` (`anggota_id`, `buku_id`, `tanggal_pinjam`, `tanggal_jatuh_tempo`, `status`)
SELECT a.`anggota_id`, b.`buku_id`, DATE_SUB(CURRENT_DATE, INTERVAL 12 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), 'dipinjam'
FROM `anggota` a
INNER JOIN `users` u ON u.`user_id` = a.`user_id` AND u.`email` = 'bima@digilib.id'
INNER JOIN `buku` b ON b.`judul` = 'Filosofi Teras'
WHERE NOT EXISTS (
  SELECT 1 FROM `peminjaman` p WHERE p.`anggota_id` = a.`anggota_id` AND p.`buku_id` = b.`buku_id` AND p.`status` = 'dipinjam'
);

INSERT INTO `peminjaman` (`anggota_id`, `buku_id`, `tanggal_pinjam`, `tanggal_jatuh_tempo`, `status`)
SELECT a.`anggota_id`, b.`buku_id`, DATE_SUB(CURRENT_DATE, INTERVAL 18 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 11 DAY), 'dikembalikan'
FROM `anggota` a
INNER JOIN `users` u ON u.`user_id` = a.`user_id` AND u.`email` = 'anggota@digilib.id'
INNER JOIN `buku` b ON b.`judul` = 'Sapiens'
WHERE NOT EXISTS (
  SELECT 1
  FROM `peminjaman` p
  INNER JOIN `pengembalian` r ON r.`peminjaman_id` = p.`peminjaman_id`
  WHERE p.`anggota_id` = a.`anggota_id` AND p.`buku_id` = b.`buku_id` AND r.`catatan` = 'Seed data Vercel DIGILIB'
);

INSERT INTO `pengembalian` (`peminjaman_id`, `tanggal_kembali`, `catatan`)
SELECT p.`peminjaman_id`, DATE_SUB(CURRENT_DATE, INTERVAL 9 DAY), 'Seed data Vercel DIGILIB'
FROM `peminjaman` p
INNER JOIN `anggota` a ON a.`anggota_id` = p.`anggota_id`
INNER JOIN `users` u ON u.`user_id` = a.`user_id` AND u.`email` = 'anggota@digilib.id'
INNER JOIN `buku` b ON b.`buku_id` = p.`buku_id` AND b.`judul` = 'Sapiens'
WHERE p.`status` = 'dikembalikan'
  AND p.`tanggal_pinjam` = DATE_SUB(CURRENT_DATE, INTERVAL 18 DAY)
  AND NOT EXISTS (SELECT 1 FROM `pengembalian` r WHERE r.`peminjaman_id` = p.`peminjaman_id`);

INSERT INTO `denda` (`pengembalian_id`, `jumlah_hari_terlambat`, `tarif_per_hari`, `jumlah_denda`, `status_pembayaran`)
SELECT r.`pengembalian_id`, 2, 2000.00, 4000.00, 'belum_dibayar'
FROM `pengembalian` r
INNER JOIN `peminjaman` p ON p.`peminjaman_id` = r.`peminjaman_id`
INNER JOIN `anggota` a ON a.`anggota_id` = p.`anggota_id`
INNER JOIN `users` u ON u.`user_id` = a.`user_id` AND u.`email` = 'anggota@digilib.id'
INNER JOIN `buku` b ON b.`buku_id` = p.`buku_id` AND b.`judul` = 'Sapiens'
WHERE r.`catatan` = 'Seed data Vercel DIGILIB'
  AND NOT EXISTS (SELECT 1 FROM `denda` d WHERE d.`pengembalian_id` = r.`pengembalian_id`);

COMMIT;
