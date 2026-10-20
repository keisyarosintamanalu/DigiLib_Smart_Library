CREATE DATABASE IF NOT EXISTS `digilib_smart_library`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `digilib_smart_library`;

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_lengkap` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'pustakawan', 'anggota') NOT NULL,
  `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kategori` (
  `kategori_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_kategori` VARCHAR(80) NOT NULL,
  `deskripsi` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`kategori_id`),
  UNIQUE KEY `uq_kategori_nama` (`nama_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `anggota` (
  `anggota_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `nomor_anggota` VARCHAR(30) NOT NULL,
  `nomor_telepon` VARCHAR(30) NULL,
  `alamat` TEXT NULL,
  `tanggal_daftar` DATE NOT NULL,
  `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`anggota_id`),
  UNIQUE KEY `uq_anggota_user` (`user_id`),
  UNIQUE KEY `uq_anggota_nomor` (`nomor_anggota`),
  CONSTRAINT `fk_anggota_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `buku` (
  `buku_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kategori_id` BIGINT UNSIGNED NOT NULL,
  `isbn` VARCHAR(20) NULL,
  `judul` VARCHAR(200) NOT NULL,
  `penulis` VARCHAR(150) NOT NULL,
  `penerbit` VARCHAR(150) NOT NULL,
  `tahun_terbit` SMALLINT UNSIGNED NOT NULL,
  `rak` VARCHAR(30) NOT NULL,
  `stok` INT UNSIGNED NOT NULL DEFAULT 0,
  `cover_path` VARCHAR(255) NULL,
  `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`buku_id`),
  UNIQUE KEY `uq_buku_isbn` (`isbn`),
  KEY `idx_buku_kategori` (`kategori_id`),
  KEY `idx_buku_judul` (`judul`),
  CONSTRAINT `fk_buku_kategori`
    FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`kategori_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `kategori` (`nama_kategori`, `deskripsi`)
VALUES
  ('Fiksi', 'Novel, cerita pendek, dan karya sastra.'),
  ('Teknologi', 'Komputer, teknologi informasi, dan sains terapan.'),
  ('Pendidikan', 'Buku pelajaran dan materi pembelajaran.'),
  ('Sejarah', 'Sejarah Indonesia dan dunia.'),
  ('Pengembangan Diri', 'Keterampilan pribadi dan pengembangan karier.')
ON DUPLICATE KEY UPDATE `nama_kategori` = VALUES(`nama_kategori`);

CREATE TABLE IF NOT EXISTS `peminjaman` (
  `peminjaman_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `anggota_id` BIGINT UNSIGNED NOT NULL,
  `buku_id` BIGINT UNSIGNED NOT NULL,
  `tanggal_pinjam` DATE NOT NULL,
  `tanggal_jatuh_tempo` DATE NOT NULL,
  `status` ENUM('dipinjam', 'dikembalikan', 'dibatalkan') NOT NULL DEFAULT 'dipinjam',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`peminjaman_id`),
  KEY `idx_peminjaman_anggota_status` (`anggota_id`, `status`),
  KEY `idx_peminjaman_buku_status` (`buku_id`, `status`),
  KEY `idx_peminjaman_jatuh_tempo` (`tanggal_jatuh_tempo`),
  CONSTRAINT `fk_peminjaman_anggota`
    FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`anggota_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_peminjaman_buku`
    FOREIGN KEY (`buku_id`) REFERENCES `buku` (`buku_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pengembalian` (
  `pengembalian_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `peminjaman_id` BIGINT UNSIGNED NOT NULL,
  `tanggal_kembali` DATE NOT NULL,
  `catatan` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pengembalian_id`),
  UNIQUE KEY `uq_pengembalian_peminjaman` (`peminjaman_id`),
  CONSTRAINT `fk_pengembalian_peminjaman`
    FOREIGN KEY (`peminjaman_id`) REFERENCES `peminjaman` (`peminjaman_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `denda` (
  `denda_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pengembalian_id` BIGINT UNSIGNED NOT NULL,
  `jumlah_hari_terlambat` INT UNSIGNED NOT NULL,
  `tarif_per_hari` DECIMAL(12,2) NOT NULL DEFAULT 2000.00,
  `jumlah_denda` DECIMAL(12,2) NOT NULL,
  `status_pembayaran` ENUM('belum_dibayar', 'lunas') NOT NULL DEFAULT 'belum_dibayar',
  `tanggal_pembayaran` DATE NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`denda_id`),
  UNIQUE KEY `uq_denda_pengembalian` (`pengembalian_id`),
  CONSTRAINT `fk_denda_pengembalian`
    FOREIGN KEY (`pengembalian_id`) REFERENCES `pengembalian` (`pengembalian_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`nama_lengkap`, `email`, `password_hash`, `role`, `status`)
VALUES
  ('Admin DIGILIB', 'admin@digilib.id', '$2y$10$BVBjSweZ2cBE2dmHWAM9X.yfCormLOHn.oacvbzFE8uZq.rddogWi', 'admin', 'aktif'),
  ('Anggota Demo', 'anggota@digilib.id', '$2y$10$Udzd3p4p4I9yEoujkDFHiupT7Y0jNoVvioBT8n9P2WWitZBktAn5S', 'anggota', 'aktif')
ON DUPLICATE KEY UPDATE
  `nama_lengkap` = VALUES(`nama_lengkap`),
  `password_hash` = VALUES(`password_hash`),
  `role` = VALUES(`role`),
  `status` = VALUES(`status`);

INSERT INTO `anggota` (`user_id`, `nomor_anggota`, `nomor_telepon`, `alamat`, `tanggal_daftar`, `status`)
SELECT `user_id`, 'DEMO-0001', '0812-0000-0001', 'Anggota demo DIGILIB', CURRENT_DATE, 'aktif'
FROM `users`
WHERE `email` = 'anggota@digilib.id'
ON DUPLICATE KEY UPDATE
  `nomor_telepon` = VALUES(`nomor_telepon`),
  `alamat` = VALUES(`alamat`),
  `status` = VALUES(`status`);
