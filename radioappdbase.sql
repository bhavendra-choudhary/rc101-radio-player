-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 15, 2026 at 03:28 AM
-- Server version: 8.0.46
-- PHP Version: 8.4.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dzm_rc101`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_token` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `otp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp_expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `city`, `email_verified_at`, `password`, `remember_token`, `device_token`, `is_active`, `otp`, `otp_expires_at`, `created_at`, `updated_at`) VALUES
(1, 'Mike', 'socialrockstar@me.com', NULL, NULL, NULL, '$2y$12$xRQCjQobBcZBGbNQYaPkdO7/lg3vf/MTK4OvuYN4sBqvw.8FQLVwi', '2S2J1BWIx0iTjo4BUU8WVPoxRnQhledFpZEq5ZGTbLnOrztx3uxIEHGquEMo', NULL, 1, NULL, NULL, '2026-10-02 07:50:41', '2026-10-02 07:50:41'),
(2, 'Listener_YYC6', 'user_1791451887@radiomast.local', '121212121212', NULL, NULL, '$2y$12$En.XxWjugqs1X4hcl/3gVeVtpsyGH9cuNC1BKq30jlIHR5fj/e0TK', '2u6BkrCigzB2p3uJr3tlUW3I5oovznodXG8f7B2NUrHhn5qph3KPWeHO9DKs', NULL, 1, NULL, NULL, '2026-10-08 09:31:28', '2026-10-08 09:31:55'),
(3, 'Mike', 'listener_1791656579_h5Dz4y@radiomast.local', '2166333738', 'Cleveland', NULL, '$2y$12$ZYrB3P3OXGK/hPLmQNwkw.voP9sqmZr3z6/9jNtLU0NHJI1gxQC8i', 'cksfXmTuTEmreVrtPQFyfWNn176hmgcLSJF4AaqYga5WoVxipWUaqTmcpn3a', '3cfa5cd65678bad99022481cf2c8222cf87ed2e3dcf798da44b7136a14960c76', 1, NULL, NULL, '2026-10-10 18:22:59', '2026-10-10 18:25:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_phone_unique` (`phone`),
  ADD KEY `users_device_token_index` (`device_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
