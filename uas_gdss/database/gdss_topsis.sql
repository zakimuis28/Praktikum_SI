-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 08, 2025 at 06:49 AM
-- Server version: 9.0.1
-- PHP Version: 8.4.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gdss_topsis`
--

-- --------------------------------------------------------

--
-- Table structure for table `borda_results`
--

CREATE TABLE `borda_results` (
  `id` int NOT NULL,
  `project_id` int NOT NULL,
  `borda_score` int NOT NULL COMMENT 'Skor BORDA terbobot',
  `final_rank` int NOT NULL COMMENT 'Ranking final konsensus',
  `calculated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `borda_results`
--

INSERT INTO `borda_results` (`id`, `project_id`, `borda_score`, `final_rank`, `calculated_at`) VALUES
(1, 2, 65, 1, '2025-12-08 05:43:14'),
(2, 1, 40, 2, '2025-12-08 05:43:14'),
(3, 3, 35, 3, '2025-12-08 05:43:14'),
(4, 5, 30, 4, '2025-12-08 05:43:14'),
(5, 4, 25, 5, '2025-12-08 05:43:14');

-- --------------------------------------------------------

--
-- Table structure for table `criteria`
--

CREATE TABLE `criteria` (
  `id` int NOT NULL,
  `field` enum('supervisor','teknis','keuangan') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Bidang DM yang menilai',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nama kriteria',
  `weight` float NOT NULL DEFAULT '0' COMMENT 'Bobot kriteria (sudah dinormalisasi 0-1)',
  `type` enum('benefit','cost') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'benefit' COMMENT 'Benefit=max lebih baik, Cost=min lebih baik',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `criteria`
--

INSERT INTO `criteria` (`id`, `field`, `name`, `weight`, `type`, `created_at`) VALUES
(1, 'supervisor', 'Kesesuaian Strategis', 0.22, 'benefit', '2025-12-06 10:30:44'),
(2, 'supervisor', 'Dampak Organisasi', 0.22, 'benefit', '2025-12-06 10:30:44'),
(3, 'supervisor', 'Prioritas Manajemen', 0.17, 'benefit', '2025-12-06 10:30:44'),
(4, 'supervisor', 'Risiko Proyek', 0.22, 'cost', '2025-12-06 10:30:44'),
(5, 'supervisor', 'Keberlanjutan', 0.17, 'benefit', '2025-12-06 10:30:44'),
(6, 'teknis', 'Kemampuan Teknis', 0.28, 'benefit', '2025-12-06 10:30:44'),
(7, 'teknis', 'Ketersediaan SDM', 0.28, 'benefit', '2025-12-06 10:30:44'),
(8, 'teknis', 'Kompleksitas Implementasi', 0.22, 'cost', '2025-12-06 10:30:44'),
(9, 'teknis', 'Infrastruktur Pendukung', 0.22, 'benefit', '2025-12-06 10:30:44'),
(10, 'keuangan', 'Nilai Proyek', 0.36, 'benefit', '2025-12-06 10:30:44'),
(11, 'keuangan', 'Biaya Operasional', 0.28, 'cost', '2025-12-06 10:30:44'),
(12, 'keuangan', 'ROI Estimasi', 0.36, 'benefit', '2025-12-06 10:30:44');

-- --------------------------------------------------------

--
-- Table structure for table `part_weights`
--

CREATE TABLE `part_weights` (
  `id` int NOT NULL,
  `part` enum('supervisor','teknis','keuangan') COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` int NOT NULL COMMENT 'Bobot DM untuk konsensus BORDA',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `part_weights`
--

INSERT INTO `part_weights` (`id`, `part`, `weight`, `created_at`) VALUES
(1, 'supervisor', 7, '2025-12-06 10:30:44'),
(2, 'teknis', 4, '2025-12-06 10:30:44'),
(3, 'keuangan', 2, '2025-12-06 10:30:44');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int NOT NULL,
  `project_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `project_code`, `project_name`, `location`, `date`, `description`, `status`, `created_at`) VALUES
(1, '2501', 'SIPPK Kementerian Kesehatan RI', 'Kementerian Kesehatan RI Jl. HR Rasuna Said Jakarta', '2012-10-01', 'Proyek Sistem Informasi Pelayanan Publik Kementerian Kesehatan', 'active', '2025-12-06 10:30:44'),
(2, '2502', 'SMS Gateway PPK Kemenkes RI', 'Kementerian Kesehatan RI Jl. HR Rasuna Said Jakarta', '2012-10-01', 'Proyek SMS Gateway Puskesmas dan Posyandu Kementerian Kesehatan', 'active', '2025-12-06 10:30:44'),
(3, '2503', 'Sistem Data Center/Warehouse', 'Kementerian Perikanan dan Kelautan RI', '2012-11-01', 'Proyek Pengembangan Data Center dan Data Warehouse Kementerian Kelautan', 'active', '2025-12-06 10:30:44'),
(4, '2504', 'Sistem Informasi Akademik STIE', 'STIE BPD Semarang', '2012-12-30', 'Proyek Sistem Informasi Akademik STIE Bank Pembangunan Daerah', 'active', '2025-12-06 10:30:44'),
(5, '2505', 'Network Monitoring System', 'Pusat Pengolahan Data Kementerian Pekerjaan Umum', '2012-10-20', 'Proyek Network Monitoring System Kementerian Pekerjaan Umum', 'active', '2025-12-06 10:30:44');

-- --------------------------------------------------------

--
-- Table structure for table `scores`
--

CREATE TABLE `scores` (
  `id` int NOT NULL,
  `user_id` int NOT NULL COMMENT 'ID Decision Maker yang menilai',
  `project_id` int NOT NULL COMMENT 'ID Proyek yang dinilai',
  `criteria_id` int NOT NULL COMMENT 'ID Kriteria',
  `value` float NOT NULL COMMENT 'Nilai rating 1-5',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scores`
--

INSERT INTO `scores` (`id`, `user_id`, `project_id`, `criteria_id`, `value`, `created_at`) VALUES
(26, 3, 2, 1, 4, '2025-12-06 10:30:44'),
(27, 3, 2, 2, 5, '2025-12-06 10:30:44'),
(28, 3, 2, 3, 4, '2025-12-06 10:30:44'),
(29, 3, 2, 4, 1, '2025-12-06 10:30:44'),
(30, 3, 2, 5, 5, '2025-12-06 10:30:44'),
(31, 3, 3, 1, 5, '2025-12-06 10:30:44'),
(32, 3, 3, 2, 5, '2025-12-06 10:30:44'),
(33, 3, 3, 3, 5, '2025-12-06 10:30:44'),
(34, 3, 3, 4, 4, '2025-12-06 10:30:44'),
(35, 3, 3, 5, 4, '2025-12-06 10:30:44'),
(36, 3, 4, 1, 3, '2025-12-06 10:30:44'),
(37, 3, 4, 2, 3, '2025-12-06 10:30:44'),
(38, 3, 4, 3, 3, '2025-12-06 10:30:44'),
(39, 3, 4, 4, 4, '2025-12-06 10:30:44'),
(40, 3, 4, 5, 2, '2025-12-06 10:30:44'),
(41, 3, 5, 1, 3, '2025-12-06 10:30:44'),
(42, 3, 5, 2, 4, '2025-12-06 10:30:44'),
(43, 3, 5, 3, 4, '2025-12-06 10:30:44'),
(44, 3, 5, 4, 4, '2025-12-06 10:30:44'),
(45, 3, 5, 5, 5, '2025-12-06 10:30:44'),
(49, 4, 2, 10, 5, '2025-12-06 10:30:44'),
(50, 4, 2, 11, 1, '2025-12-06 10:30:44'),
(51, 4, 2, 12, 4, '2025-12-06 10:30:44'),
(52, 4, 3, 10, 4, '2025-12-06 10:30:44'),
(53, 4, 3, 11, 2, '2025-12-06 10:30:44'),
(54, 4, 3, 12, 5, '2025-12-06 10:30:44'),
(55, 4, 4, 10, 5, '2025-12-06 10:30:44'),
(56, 4, 4, 11, 3, '2025-12-06 10:30:44'),
(57, 4, 4, 12, 4, '2025-12-06 10:30:44'),
(58, 4, 5, 10, 4, '2025-12-06 10:30:44'),
(59, 4, 5, 11, 2, '2025-12-06 10:30:44'),
(60, 4, 5, 12, 4, '2025-12-06 10:30:44'),
(61, 3, 1, 2, 5, '2025-12-06 14:43:36'),
(62, 3, 1, 5, 3, '2025-12-06 14:43:36'),
(63, 3, 1, 1, 4, '2025-12-06 14:43:36'),
(64, 3, 1, 3, 5, '2025-12-06 14:43:36'),
(65, 3, 1, 4, 1, '2025-12-06 14:43:36'),
(78, 2, 5, 9, 5, '2025-12-06 14:45:39'),
(79, 2, 5, 6, 4, '2025-12-06 14:45:39'),
(80, 2, 5, 7, 3, '2025-12-06 14:45:39'),
(81, 2, 5, 8, 5, '2025-12-06 14:45:39'),
(82, 2, 1, 9, 3, '2025-12-06 14:51:48'),
(83, 2, 1, 6, 4, '2025-12-06 14:51:48'),
(84, 2, 1, 7, 2, '2025-12-06 14:51:48'),
(85, 2, 1, 8, 5, '2025-12-06 14:51:48'),
(86, 2, 2, 9, 5, '2025-12-06 14:52:23'),
(87, 2, 2, 6, 3, '2025-12-06 14:52:23'),
(88, 2, 2, 7, 4, '2025-12-06 14:52:23'),
(89, 2, 2, 8, 1, '2025-12-06 14:52:23'),
(90, 2, 3, 9, 4, '2025-12-06 14:52:48'),
(91, 2, 3, 6, 3, '2025-12-06 14:52:48'),
(92, 2, 3, 7, 3, '2025-12-06 14:52:48'),
(93, 2, 3, 8, 5, '2025-12-06 14:52:48'),
(94, 2, 4, 9, 2, '2025-12-06 14:53:11'),
(95, 2, 4, 6, 5, '2025-12-06 14:53:11'),
(96, 2, 4, 7, 3, '2025-12-06 14:53:11'),
(97, 2, 4, 8, 1, '2025-12-06 14:53:11'),
(98, 4, 1, 11, 2, '2025-12-08 05:42:53'),
(99, 4, 1, 10, 4, '2025-12-08 05:42:53'),
(100, 4, 1, 12, 5, '2025-12-08 05:42:53');

-- --------------------------------------------------------

--
-- Table structure for table `topsis_results`
--

CREATE TABLE `topsis_results` (
  `id` int NOT NULL,
  `field` enum('supervisor','teknis','keuangan') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Bidang DM',
  `project_id` int NOT NULL,
  `topsis_score` float NOT NULL COMMENT 'Nilai preferensi Ci (0-1)',
  `d_positive` float NOT NULL COMMENT 'Jarak ke solusi ideal positif',
  `d_negative` float NOT NULL COMMENT 'Jarak ke solusi ideal negatif',
  `rank` int NOT NULL COMMENT 'Ranking per bidang',
  `calculated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `topsis_results`
--

INSERT INTO `topsis_results` (`id`, `field`, `project_id`, `topsis_score`, `d_positive`, `d_negative`, `rank`, `calculated_at`) VALUES
(51, 'teknis', 2, 0.69756, 0.064663, 0.149142, 1, '2025-12-06 14:53:15'),
(52, 'teknis', 4, 0.598105, 0.084747, 0.126121, 2, '2025-12-06 14:53:15'),
(53, 'teknis', 5, 0.44526, 0.113007, 0.090705, 3, '2025-12-06 14:53:15'),
(54, 'teknis', 3, 0.333035, 0.128527, 0.064177, 4, '2025-12-06 14:53:15'),
(55, 'teknis', 1, 0.222585, 0.142216, 0.040718, 5, '2025-12-06 14:53:15'),
(61, 'keuangan', 2, 0.77437, 0.036365, 0.124808, 1, '2025-12-08 05:42:59'),
(62, 'keuangan', 1, 0.5, 0.069901, 0.069901, 2, '2025-12-08 05:42:59'),
(63, 'keuangan', 3, 0.5, 0.069901, 0.069901, 3, '2025-12-08 05:42:59'),
(64, 'keuangan', 5, 0.431049, 0.078794, 0.059696, 4, '2025-12-08 05:42:59'),
(65, 'keuangan', 4, 0.22563, 0.124808, 0.036365, 5, '2025-12-08 05:42:59'),
(66, 'supervisor', 2, 0.79733, 0.031031, 0.122079, 1, '2025-12-08 05:45:04'),
(67, 'supervisor', 1, 0.712332, 0.04592, 0.113708, 2, '2025-12-08 05:45:04'),
(68, 'supervisor', 3, 0.471942, 0.095278, 0.085153, 3, '2025-12-08 05:45:04'),
(69, 'supervisor', 5, 0.367808, 0.109977, 0.063984, 4, '2025-12-08 05:45:04'),
(70, 'supervisor', 4, 0, 0.133387, 0, 5, '2025-12-08 05:45:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','supervisor','teknis','keuangan') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator GDSS', 'admin', '$2y$12$oqdZ620KwVhw0aJIQDZg6eRpxHC6Oe3uKK/r9GOOaOXfQXXcG1hsS', 'admin', '2025-12-06 10:30:44'),
(2, 'Decision Maker Teknis', 'teknis', '$2y$12$EkSB5cSYoaMGLZlF2n9Glux.JLaDZdAdfPfh95bIT8LDZitIFmcHe', 'teknis', '2025-12-06 10:30:44'),
(3, 'Decision Maker Supervisor', 'supervisor', '$2y$12$CYyADxGY7SkAV41QA082Q.7EBzIFrSXiFrboFkT1IraHjB2Ua26o.', 'supervisor', '2025-12-06 10:30:44'),
(4, 'Decision Maker Keuangan', 'keuangan', '$2y$12$WbcWhjFT3fi5pftCrPJ/6unLR1gGA/fbRSOTBrmoT/Q2M1JNX9akq', 'keuangan', '2025-12-06 10:30:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `borda_results`
--
ALTER TABLE `borda_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project` (`project_id`),
  ADD KEY `idx_final_rank` (`final_rank`);

--
-- Indexes for table `criteria`
--
ALTER TABLE `criteria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_field` (`field`);

--
-- Indexes for table `part_weights`
--
ALTER TABLE `part_weights`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_part` (`part`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_code` (`project_code`),
  ADD KEY `idx_project_code` (`project_code`);

--
-- Indexes for table `scores`
--
ALTER TABLE `scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_score` (`user_id`,`project_id`,`criteria_id`),
  ADD KEY `idx_user_project` (`user_id`,`project_id`),
  ADD KEY `idx_criteria` (`criteria_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `topsis_results`
--
ALTER TABLE `topsis_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_field_project` (`field`,`project_id`),
  ADD KEY `idx_field_project` (`field`,`project_id`),
  ADD KEY `idx_rank` (`rank`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `borda_results`
--
ALTER TABLE `borda_results`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `criteria`
--
ALTER TABLE `criteria`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `part_weights`
--
ALTER TABLE `part_weights`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `scores`
--
ALTER TABLE `scores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `topsis_results`
--
ALTER TABLE `topsis_results`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borda_results`
--
ALTER TABLE `borda_results`
  ADD CONSTRAINT `borda_results_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `scores`
--
ALTER TABLE `scores`
  ADD CONSTRAINT `scores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scores_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scores_ibfk_3` FOREIGN KEY (`criteria_id`) REFERENCES `criteria` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `topsis_results`
--
ALTER TABLE `topsis_results`
  ADD CONSTRAINT `topsis_results_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
