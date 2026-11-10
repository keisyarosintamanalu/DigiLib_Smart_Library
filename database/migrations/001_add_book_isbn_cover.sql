ALTER TABLE `buku`
  ADD COLUMN `isbn` VARCHAR(20) NULL AFTER `kategori_id`,
  ADD COLUMN `cover_path` VARCHAR(255) NULL AFTER `stok`,
  ADD UNIQUE KEY `uq_buku_isbn` (`isbn`);

INSERT INTO `kategori` (`nama_kategori`, `deskripsi`)
VALUES
  ('Fiksi', 'Novel, cerita pendek, dan karya sastra.'),
  ('Teknologi', 'Komputer, teknologi informasi, dan sains terapan.'),
  ('Pendidikan', 'Buku pelajaran dan materi pembelajaran.'),
  ('Sejarah', 'Sejarah Indonesia dan dunia.'),
  ('Pengembangan Diri', 'Keterampilan pribadi dan pengembangan karier.')
ON DUPLICATE KEY UPDATE `nama_kategori` = VALUES(`nama_kategori`);
