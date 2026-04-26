-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 26, 2026 at 09:44 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `uns_medicalcenterDB`
--

-- --------------------------------------------------------

--
-- Table structure for table `kunjungan`
--

CREATE TABLE `kunjungan` (
  `id` int(11) NOT NULL,
  `nik_pasien` varchar(16) NOT NULL,
  `token` varchar(20) DEFAULT NULL,
  `poli` varchar(100) DEFAULT NULL COMMENT 'Nama poli/unit layanan saat kunjungan',
  `tgl_kunjungan` date NOT NULL,
  `status_survei` enum('Belum','Sudah') DEFAULT 'Belum'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kunjungan`
--

INSERT INTO `kunjungan` (`id`, `nik_pasien`, `token`, `poli`, `tgl_kunjungan`, `status_survei`) VALUES
(1, '3514010101010001', 'TKN-001', 'Umum', '2026-04-13', 'Sudah'),
(2, '3514010101010001', 'TKN-002', 'Umum', '2026-04-10', 'Belum'),
(3, '3514010101010001', 'TKN-003', 'Gigi', '2026-04-06', 'Belum'),
(4, '3514010101010001', 'TKN-004', 'KIA', '2026-03-30', 'Sudah'),
(5, '3514010101010002', 'TKN-005', 'Umum', '2026-04-13', 'Belum'),
(6, '3514010101010002', 'TKN-006', 'Umum', '2026-04-08', 'Belum');

-- --------------------------------------------------------

--
-- Table structure for table `surveys`
--

CREATE TABLE `surveys` (
  `id` int(11) NOT NULL,
  `email` varchar(16) NOT NULL,
  `poli` varchar(20) NOT NULL DEFAULT 'Umum',
  `jalur` enum('kunjungan','umum') NOT NULL DEFAULT 'umum',
  `kategori` varchar(20) NOT NULL,
  `q1` tinyint(4) DEFAULT NULL,
  `q2` tinyint(4) DEFAULT NULL,
  `q3` tinyint(4) DEFAULT NULL,
  `q4` tinyint(4) DEFAULT NULL,
  `q5` tinyint(3) UNSIGNED DEFAULT NULL,
  `nps_score` tinyint(3) UNSIGNED DEFAULT NULL COMMENT '0-10 Net Promoter Score',
  `id_kunjungan` int(11) DEFAULT NULL,
  `saran` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `surveys`
--

INSERT INTO `surveys` (`id`, `email`, `poli`, `jalur`, `kategori`, `q1`, `q2`, `q3`, `q4`, `q5`, `nps_score`, `id_kunjungan`, `saran`, `created_at`) VALUES
(1, '3514010101010001', 'Umum', 'kunjungan', 'Mahasiswa', 5, 4, 5, 4, 4, 9, NULL, 'Dokter sangat informatif dan ramah!', '2026-04-13 11:12:55'),
(2, '3514010101010002', 'Gigi', 'kunjungan', 'Karyawan', 4, 4, 3, 5, 4, 8, NULL, 'Ruang tunggu bisa lebih nyaman.', '2026-04-13 11:12:55'),
(3, '3514010101010001', 'KIA', 'umum', 'Mahasiswa', 3, 4, 4, 4, 3, 7, NULL, 'Pelayanan cukup baik.', '2026-04-13 11:12:55'),
(4, '3514010101010002', 'Umum', 'umum', 'Dosen', 5, 5, 5, 5, 5, 10, NULL, 'Luar biasa, tidak ada yang perlu diperbaiki!', '2026-04-13 11:12:55'),
(5, '3514010101010001', 'Umum', 'kunjungan', 'Umum', 5, 5, 5, 5, 4, 5, 1, 'nice', '2026-04-13 14:33:46');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nik` varchar(16) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kategori` enum('Mahasiswa','Dosen','Karyawan','Umum') NOT NULL DEFAULT 'Umum',
  `no_hp` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nik`, `nama`, `kategori`, `no_hp`, `password`, `role`) VALUES
(1, '1111111111111111', 'Admin UNS Medical Center', 'Umum', '081111111111', '$2y$12$k9Fd51eQYX47oUlEBxublO2RYfigo4JpJrIcZOezT80du/ylHVqO6', 'admin'),
(2, '3514010101010001', 'Budi Santoso', 'Mahasiswa', '082222222222', '$2y$12$mx7WSkhRv7fEzECwHisVT.ZA3WKu/AVhnobpC2VnjHh3POTkL7X/S', 'user'),
(3, '3514010101010002', 'Siti Rahayu', 'Karyawan', '083333333333', '$2y$12$aS.HdBDsBxHO4xg7wVhRZeDH5zVMvp6LzLF7oWs7pCVY9JA/dLzHu', 'user'),
(4, '3121231236943914', 'Moh. Syaeful Effendi', 'Umum', '0987654321', '$2y$12$nNsiz9tb9aJlgqeUFEDB9ei3Qn.sWcCFw7axrOW/y1snP8KKbQgii', 'user'),
(5, '8235781248012491', 'Muhammad Akyas', 'Mahasiswa', '085876163333', '$2y$12$oBqA9i76TfFQpBSQcccVNun7NcQtk6HXGYheEiNTB56f/JnE5rGRS', 'user'),
(6, '2810462189468021', 'Ardianhan', 'Dosen', '081276281643', '$2y$12$.kGU1a4bmJc0E5arhK3egOY8/XgPnroEQxhPMj4aiUU5CHeUETbWe', 'user'),
(7, '2137964012094721', 'Akyas Febryansah', 'Mahasiswa', '085876163554', '$2y$12$r45xCvgyTQjFguejYcDveOz1vDV/dyZciJ5QJbZOgOHHdhI9tDOUG', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kunjungan`
--
ALTER TABLE `kunjungan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `surveys`
--
ALTER TABLE `surveys`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD UNIQUE KEY `no_hp` (`no_hp`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kunjungan`
--
ALTER TABLE `kunjungan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
