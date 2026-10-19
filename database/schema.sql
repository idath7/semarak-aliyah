-- ==========================================================================
-- DATABASE SCHEMA: SEMARAK ALIYAH XII 2026
-- Siap di-import langsung melalui phpMyAdmin (WampServer)
-- ==========================================================================

CREATE DATABASE IF NOT EXISTS `semarak_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `semarak_db`;

-- 1. TABEL CABANG KOMPETISI (Dinamic Quota & Settings)
CREATE TABLE IF NOT EXISTS `competitions` (
  `id` VARCHAR(50) NOT NULL PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `category` ENUM('akademik', 'seni', 'olahraga') NOT NULL DEFAULT 'akademik',
  `category_label` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(20) NOT NULL DEFAULT '🎯',
  `fee` INT NOT NULL DEFAULT 50000,
  `max_quota` INT NOT NULL DEFAULT 30,
  `current_quota` INT NOT NULL DEFAULT 0,
  `status` ENUM('open', 'closed') NOT NULL DEFAULT 'open',
  `participants_rule` VARCHAR(255) NOT NULL,
  `tm_date` VARCHAR(150) NOT NULL,
  `contact_person` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. TABEL PENDAFTARAN (Registrations)
CREATE TABLE IF NOT EXISTS `registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reg_code` VARCHAR(30) NOT NULL UNIQUE,
  `school_name` VARCHAR(200) NOT NULL,
  `mentor_name` VARCHAR(150) NOT NULL,
  `mentor_wa` VARCHAR(30) NOT NULL,
  `package_type` VARCHAR(100) NOT NULL,
  `competition_id` VARCHAR(50) NOT NULL,
  `competition_title` VARCHAR(150) NOT NULL,
  `participants_names` TEXT NOT NULL,
  `notes` TEXT NULL,
  `total_fee` INT NOT NULL DEFAULT 50000,
  `payment_status` ENUM('Menunggu Verifikasi', 'Terverifikasi', 'Dibatalkan') NOT NULL DEFAULT 'Menunggu Verifikasi',
  `payment_method` VARCHAR(50) DEFAULT 'Transfer Bank / Tunai',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`reg_code`),
  INDEX (`competition_id`),
  INDEX (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. TABEL ADMIN PANITIA (Admin Users)
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `fullname` VARCHAR(100) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'panitia',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================================
-- DATA AWAL (SEED DATA)
-- ==========================================================================

-- Data 8 Cabang Lomba Resmi Semarak Aliyah XII 2026
INSERT INTO `competitions` (`id`, `title`, `category`, `category_label`, `icon`, `fee`, `max_quota`, `current_quota`, `status`, `participants_rule`, `tm_date`, `contact_person`, `description`) VALUES
('mipa', 'Olimpiade MIPA', 'akademik', 'Akademik & Sains', '🔬', 50000, 40, 14, 'open', 'Maksimal 3 peserta per sekolah/madrasah', '07 Januari 2026 • 08:00 WIB', '0838 5007 0767', 'Ajang uji kemampuan sains dan penalaran logika Matematika serta Ilmu Pengetahuan Alam SMP/MTs se-Kabupaten Jember.'),
('ips', 'Olimpiade IPS', 'akademik', 'Akademik & Sains', '🌍', 50000, 40, 12, 'open', 'Maksimal 3 peserta per sekolah/madrasah', '07 Januari 2026 • 08:00 WIB', '0838 5007 0767', 'Kompetisi wawasan sosial, geografi, sejarah, sosiologi, dan ekonomi terpadu tingkat SMP/MTs.'),
('storytelling', 'Storytelling (Bahasa Inggris)', 'seni', 'Bahasa & Seni', '📖', 50000, 30, 9, 'open', 'Maksimal 2 peserta per sekolah/madrasah', '07 Januari 2026 • 08:00 WIB', '0838 5007 0767', 'Lomba bercerita dalam bahasa Inggris untuk mengasah bakat public speaking, ekspresi, dan intonasi.'),
('albanjari', 'Festival Albanjari', 'seni', 'Religi & Seni', '🥁', 50000, 25, 11, 'open', '1 grup terdiri dari maksimal 10 personel (Vokal + Terbang)', '07 Januari 2026 • 08:00 WIB', '0838 5007 0767', 'Festival shalawat hadrah Al-Banjari tingkat pelajar se-Kabupaten Jember dengan syiar seni Islami.'),
('singer', 'Singer Lagu Islami', 'seni', 'Bahasa & Seni', '🎤', 50000, 30, 8, 'open', 'Maksimal 2 peserta (Putra/Putri) per sekolah', '07 Januari 2026 • 08:00 WIB', '0838 5007 0767', 'Kompetisi vokal solo lagu religi/Islami untuk menyalurkan bakat bernyanyi dengan nada merdu dan penghayatan.'),
('volleyball-putra', 'Tournament Volleyball (Putra)', 'olahraga', 'Olahraga', '🏐', 50000, 16, 16, 'closed', '1 Tim (6 pemain inti + 4 cadangan + official)', 'Technical Meeting: 08:00 WIB - Selesai', '0838 5007 0767', 'Turnamen bola voli putra bergengsi antar SMP/MTs. Kuota peserta utama telah terpenuhi (waiting list).'),
('volleyball-putri', 'Tournament Volleyball (Putri)', 'olahraga', 'Olahraga', '🏐', 50000, 16, 10, 'open', '1 Tim (6 pemain inti + 4 cadangan + official)', 'Technical Meeting: 08:00 WIB - Selesai', '0838 5007 0767', 'Turnamen bola voli putri untuk mengasah ketangkasan, kekompakan tim, dan sportivitas pelajar putri.'),
('mobile-legends', 'Tournament Mobile Legends (MSA S4)', 'olahraga', 'E-Sport', '🎮', 50000, 32, 19, 'open', '1 Tim (5 pemain inti + 1 cadangan)', 'Technical Meeting: 14:00 WIB - Selesai', '0852 3582 8664 / 0813 3311 3204', 'Turnamen E-Sport MLBB (MSA Season 4) resmi Semarak Aliyah tingkat pelajar SMP/MTs.')
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- Data Akun Panitia Default (Username: admin, Password: admin123)
INSERT INTO `admins` (`username`, `password`, `fullname`, `role`) VALUES
('admin', 'admin123', 'Panitia Semarak Aliyah 2026', 'superadmin')
ON DUPLICATE KEY UPDATE `fullname`=VALUES(`fullname`);

-- Contoh Data Pendaftar Awal (Mock Registrants untuk simulasi dashboard)
INSERT INTO `registrations` (`reg_code`, `school_name`, `mentor_name`, `mentor_wa`, `package_type`, `competition_id`, `competition_title`, `participants_names`, `notes`, `total_fee`, `payment_status`) VALUES
('SA26-102941', 'MTs Negeri 1 Jember', 'Ustadz Abdullah, S.Pd', '081234567890', 'Paket Satuan (Rp 50.000 / Cabang)', 'mipa', 'Olimpiade MIPA', '1. Muhammad Rizky (Kls 9)\n2. Anisa Rahmawati (Kls 8)', 'Pembayaran transfer bank BSI', 50000, 'Terverifikasi'),
('SA26-492018', 'SMP Plus Al-Azhar Balung', 'Ibu Nurul Hidayati, M.Pd', '085298765432', 'Paket All-In Komplit (Rp 200.000)', 'all-in', 'Semua Cabang Lomba (Paket All In)', 'Delegasi lengkap 8 cabang lomba kontingen SMP Plus Al-Azhar', 'Konfirmasi via WhatsApp Panitia', 200000, 'Terverifikasi'),
('SA26-847291', 'SMP Negeri 2 Puger', 'Bapak Hadi Susanto, S.Pd', '083812340987', 'Paket Satuan (Rp 50.000 / Cabang)', 'volleyball-putri', 'Tournament Volleyball (Putri)', 'Tim Voli Putri SMPN 2 Puger (10 siswi)', 'Menunggu verifikasi bukti transfer', 50000, 'Menunggu Verifikasi')
ON DUPLICATE KEY UPDATE `school_name`=VALUES(`school_name`);
