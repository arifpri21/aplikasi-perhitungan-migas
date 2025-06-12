-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 12 Jun 2025 pada 16.38
-- Versi server: 8.0.30
-- Versi PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mining_economic`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `cashflows`
--

CREATE TABLE `cashflows` (
  `id` bigint UNSIGNED NOT NULL,
  `year` int NOT NULL,
  `production` int DEFAULT NULL,
  `income` int NOT NULL,
  `opex` int NOT NULL,
  `taxable_income` int NOT NULL,
  `net_cashflow` int NOT NULL,
  `project_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `cashflows`
--

INSERT INTO `cashflows` (`id`, `year`, `production`, `income`, `opex`, `taxable_income`, `net_cashflow`, `project_id`, `created_at`, `updated_at`) VALUES
(4, 2025, 1000, 150000, 100000, 1000, 250000, 1, '2025-06-12 12:53:26', '2025-06-12 12:53:26'),
(10, 0, 0, 0, 0, 0, -15, 7, '2025-06-12 14:39:48', '2025-06-12 14:39:48'),
(11, 1, 10, 15, 10, 4, 4, 7, '2025-06-12 14:40:11', '2025-06-12 14:40:11'),
(12, 2, 10, 15, 10, 4, 4, 7, '2025-06-12 14:40:55', '2025-06-12 14:40:55'),
(13, 3, 10, 20, 10, 9, 8, 7, '2025-06-12 14:41:31', '2025-06-12 14:41:31'),
(14, 0, 0, 0, 0, 0, -1500000, 8, '2025-06-12 14:53:06', '2025-06-12 14:53:06'),
(15, 1, 100, 400000, 150000, 150000, 120000, 8, '2025-06-12 14:53:46', '2025-06-12 14:53:46'),
(16, 2, 100, 200, 25000, -31300, -24800, 8, '2025-06-12 15:41:05', '2025-06-12 15:41:05');

-- --------------------------------------------------------

--
-- Struktur dari tabel `projects`
--

CREATE TABLE `projects` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `site_manager` varchar(255) NOT NULL,
  `invest_capital` int NOT NULL,
  `invest_noncapital` int NOT NULL,
  `tax` int NOT NULL,
  `depreciation` int NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `projects`
--

INSERT INTO `projects` (`id`, `name`, `site_manager`, `invest_capital`, `invest_noncapital`, `tax`, `depreciation`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 'Sumur Condongcatur', 'Evo', 5000000, 1500000, 20, 10, 4, '2025-06-12 12:00:35', '2025-06-12 12:00:35'),
(4, 'Sumur Condongcatur', 'Agus', 690000000, 180000000, 20, 12, 4, '2025-06-12 13:07:52', '2025-06-12 13:07:52'),
(7, 'Sumur A', 'Sutoyo', 10, 5, 10, 5, 4, '2025-06-12 14:39:48', '2025-06-12 14:39:48'),
(8, 'proyek uji coba', 'arip', 1000000, 500000, 20, 5, 4, '2025-06-12 14:53:06', '2025-06-12 16:30:32');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(4, 'Arif Priyandika', 'arifpriyandika04@gmail.com', NULL, '$2y$10$SpcPXPV1t7zB5S1y1TmPJuuLok499kvAj1bYKXMb1q5CWHxruDHPO', NULL, '2025-06-12 11:48:07', '2025-06-12 11:48:07'),
(5, 'panry', 'panry@gmail.com', NULL, '$2y$10$0pmewIEEtGAV0g4Yb2zZU.AtAGyZbHo0S4AQx2qPvh1djFLuoJ1Pu', NULL, '2025-06-12 12:18:29', '2025-06-12 12:18:29');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `cashflows`
--
ALTER TABLE `cashflows`
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `biaya` (`project_id`);

--
-- Indeks untuk tabel `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pemilik` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD UNIQUE KEY `id` (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `cashflows`
--
ALTER TABLE `cashflows`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `projects`
--
ALTER TABLE `projects`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `cashflows`
--
ALTER TABLE `cashflows`
  ADD CONSTRAINT `biaya` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `pemilik` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
