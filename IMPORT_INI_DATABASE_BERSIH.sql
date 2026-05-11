-- ============================================================
-- NAMA FILE: IMPORT_INI_DATABASE_BERSIH.sql
-- DESKRIPSI: Mereset seluruh isi database (Wipe Data)
-- INSTRUKSI: Import file ini langsung ke MySQL / phpMyAdmin.
-- PERINGATAN: Semua data tamu & event lama AKAN DIHAPUS!
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. PILIH ATAU BUAT DATABASE (Dikommentari agar aman di Hosting)
-- CREATE DATABASE IF NOT EXISTS `buktam`;
-- USE `buktam`;

-- 2. HAPUS TABEL YANG ADA (RESET TOTAL)
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `pengaturan`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `tamu`;
DROP TABLE IF EXISTS `social_media`;
DROP TABLE IF EXISTS `kategori_tamu`;
DROP TABLE IF EXISTS `master_broadcast_params`;

-- 3. BUAT ULANG STRUKTUR TABEL (VERSI TERBARU)

-- Tabel: users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','mempelai','receptionist') NOT NULL DEFAULT 'mempelai',
  `parent_id` int(11) DEFAULT 0,
  `event_limit` int(11) DEFAULT 1,
  `post_id` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel: pengaturan
CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_name` varchar(100) DEFAULT 'Buku Tamu Wedding',
  `logo_text` varchar(20) DEFAULT 'BT',
  `logo_dashboard` varchar(255) DEFAULT '',
  `copyright` varchar(255) DEFAULT '© 2026',
  `hero_title` varchar(255) DEFAULT 'The Wedding Of',
  `hero_desc` text DEFAULT NULL,
  `hero_img` varchar(255) DEFAULT '',
  `btn_text` varchar(50) DEFAULT 'Info Acara',
  `btn_link` varchar(255) DEFAULT '#',
  `bg_mode` varchar(20) DEFAULT 'image',
  `bg_youtube_url` varchar(255) DEFAULT '',
  `bg_img` varchar(255) DEFAULT '',
  `timezone` varchar(50) DEFAULT 'Asia/Jakarta',
  `language` varchar(10) DEFAULT 'id',
  `speed_timer` int(11) DEFAULT 10,
  `color_start` varchar(20) DEFAULT '#4F46E5',
  `color_end` varchar(20) DEFAULT '#000000',
  `color_text` varchar(20) DEFAULT '#FFFFFF',
  `delay_welcome` int(11) DEFAULT 5,
  `delay_gathering` int(11) DEFAULT 5,
  `looping_overlay` tinyint(1) DEFAULT 0,
  `looping_overlay_timer` int(11) DEFAULT 10,
  `animasi_out` varchar(50) DEFAULT 'Fade',
  `animasi_duration` int(11) DEFAULT 1000,
  `welcome_text` text DEFAULT NULL,
  `welcome_bg_color` varchar(20) DEFAULT '#ffffff',
  `welcome_font_color` varchar(20) DEFAULT '#000000',
  `welcome_font` varchar(50) DEFAULT 'Poppins',
  `size_acara` int(11) DEFAULT 20,
  `size_welcome` int(11) DEFAULT 40,
  `size_tamu` int(11) DEFAULT 60,
  `size_tanggal` int(11) DEFAULT 16,
  `size_lokasi` int(11) DEFAULT 16,
  `size_waktu` int(11) DEFAULT 16,
  `show_acara` tinyint(1) DEFAULT 1,
  `show_kategori` tinyint(1) DEFAULT 1,
  `show_tanggal` tinyint(1) DEFAULT 1,
  `show_lokasi` tinyint(1) DEFAULT 1,
  `show_waktu` tinyint(1) DEFAULT 1,
  `wa_template` text DEFAULT NULL,
  `broadcast_link` varchar(255) DEFAULT '',
  `broadcast_param_id` int(11) DEFAULT NULL,
  `import_info_text` text DEFAULT NULL,
  `show_frame` tinyint(1) DEFAULT 0,
  `frame_img` varchar(255) DEFAULT '',
  `show_logo` tinyint(1) DEFAULT 1,
  `show_running_text` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel: events
CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `event_location` varchar(255) NOT NULL,
  `deskripsi_acara` text DEFAULT NULL,
  `event_logo` varchar(255) DEFAULT '',
  `event_photo` varchar(255) DEFAULT '',
  `status` enum('active','inactive') DEFAULT 'inactive',
  `bg_mode` varchar(20) DEFAULT NULL,
  `bg_youtube` varchar(255) DEFAULT NULL,
  `bg_image` varchar(255) DEFAULT NULL,
  `show_frame` tinyint(1) DEFAULT NULL,
  `frame_img` varchar(255) DEFAULT NULL,
  `broadcast_link` varchar(255) DEFAULT NULL,
  `broadcast_param_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel: tamu
CREATE TABLE `tamu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `nama_tamu` varchar(255) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `nomor_wa` varchar(20) DEFAULT '',
  `kategori` varchar(50) DEFAULT NULL,
  `jumlah_orang` int(11) DEFAULT 1,
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `checkin_at` timestamp NULL DEFAULT NULL,
  `is_manual` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel: social_media
CREATE TABLE `social_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `link_url` varchar(255) DEFAULT NULL,
  `icon_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel: kategori_tamu
CREATE TABLE `kategori_tamu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel: master_broadcast_params
CREATE TABLE `master_broadcast_params` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `param_key` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. ISI DATA DEFAULT (BERSIH)

-- Admin Account (User: admin | Pass: admin123)
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `role`, `event_limit`) VALUES
('admin', '0192023a7bbd73250516f069df18b500', 'Administrator', 'admin', 999);

-- Default Settings
INSERT INTO `pengaturan` (`app_name`, `hero_title`, `hero_desc`, `logo_text`, `copyright`, `timezone`) VALUES
('BUKU TAMU DIGITAL Eksklusif', 'The Wedding Of', 'Selamat datang di perayaan kebahagiaan kami.', 'BT', '© 2026 BUKU TAMU DIGITAL Eksklusif', 'Asia/Jakarta');

-- Default Categories
INSERT INTO `kategori_tamu` (`nama_kategori`) VALUES 
('VIP'), 
('Keluarga'), 
('Teman'), 
('Umum');

-- Default Broadcast Params
INSERT INTO `master_broadcast_params` (`param_key`) VALUES 
('to'), 
('name'), 
('tamu');

SET FOREIGN_KEY_CHECKS = 1;
