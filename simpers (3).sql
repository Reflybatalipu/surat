-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 12, 2026 at 05:07 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `simpers`
--

-- --------------------------------------------------------

--
-- Table structure for table `arsip`
--

CREATE TABLE `arsip` (
  `id` bigint NOT NULL,
  `surat_ref_id` bigint NOT NULL,
  `tipe_surat` enum('Masuk','Keluar') NOT NULL,
  `klasifikasi_arsip` varchar(50) NOT NULL,
  `masa_retensi_tahun` int NOT NULL DEFAULT '5',
  `expired_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint NOT NULL,
  `user_id` int NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` bigint NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `ip_address`, `user_agent`, `created_at`) VALUES
(2, 2, 'LOGIN_SUCCESS', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 13:31:43'),
(3, 2, 'LOGOUT', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 13:32:01'),
(4, 3, 'LOGIN_SUCCESS', 'users', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-16 13:33:41'),
(5, 1, 'LOGIN_SUCCESS', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 13:41:20'),
(6, 1, 'CREATE_USER', 'users', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 13:42:23'),
(7, 8, 'LOGOUT', 'users', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 13:43:31'),
(8, 8, 'LOGIN_SUCCESS', 'users', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 13:43:40'),
(9, 1, 'CREATE_USER', 'users', 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 13:53:47'),
(10, 9, 'ACTIVATE_ACCOUNT', 'users', 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 13:54:27'),
(11, 9, 'LOGIN_SUCCESS', 'users', 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 13:54:37'),
(12, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 16:54:39'),
(13, 1, 'CREATE_DISPOSISI', 'disposisi', 22, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 16:55:04'),
(14, 2, 'LOGIN_SUCCESS', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 16:55:22'),
(15, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 22, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 16:55:38'),
(16, 2, 'FINISH_SURAT_MASUK', 'surat_masuk', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 16:55:38'),
(17, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 17:29:01'),
(18, 1, 'CREATE_DISPOSISI', 'disposisi', 23, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 17:29:23'),
(19, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 23, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 17:29:35'),
(20, 2, 'FINISH_SURAT_MASUK', 'surat_masuk', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 17:29:35'),
(21, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 18:32:16'),
(22, 1, 'CREATE_DISPOSISI', 'disposisi', 24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 18:33:00'),
(23, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 18:33:22'),
(24, 2, 'FINISH_SURAT_MASUK', 'surat_masuk', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 18:33:22'),
(25, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 18:42:08'),
(26, 1, 'CREATE_DISPOSISI', 'disposisi', 25, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 18:42:31'),
(27, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 25, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 18:42:49'),
(28, 2, 'FINISH_SURAT_MASUK', 'surat_masuk', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-16 18:42:49'),
(29, 1, 'LOGIN_SUCCESS', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 02:53:55'),
(30, 1, 'LOGIN_SUCCESS', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 02:54:26'),
(31, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 02:56:13'),
(32, 1, 'CREATE_DISPOSISI', 'disposisi', 26, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 02:56:50'),
(33, 2, 'LOGIN_SUCCESS', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 02:57:24'),
(34, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 26, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 02:58:25'),
(35, 2, 'FORWARD_DISPOSISI', 'disposisi', 27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 02:58:25'),
(36, 1, 'SEND_SURAT_KELUAR', 'surat_keluar', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 03:12:37'),
(37, 6, 'LOGIN_SUCCESS', 'users', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 03:12:58'),
(38, 6, 'FOLLOW_UP_DISPOSISI', 'disposisi', 27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 03:13:14'),
(39, 6, 'FINISH_SURAT_MASUK', 'surat_masuk', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 03:13:14'),
(40, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 15, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 03:17:27'),
(41, 1, 'CREATE_DISPOSISI', 'disposisi', 28, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 03:17:41'),
(42, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 28, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 03:18:03'),
(43, 2, 'FINISH_SURAT_MASUK', 'surat_masuk', 15, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 03:18:03'),
(44, 1, 'CREATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 03:18:39'),
(45, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 03:18:44'),
(46, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 03:19:14'),
(47, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 16, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 08:16:54'),
(48, 1, 'CREATE_USER', 'users', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 08:44:16'),
(49, 10, 'ACTIVATE_ACCOUNT', 'users', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 08:46:53'),
(50, 10, 'LOGIN_SUCCESS', 'users', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 08:47:07'),
(51, 10, 'LOGOUT', 'users', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 08:53:35'),
(52, 10, 'LOGIN_SUCCESS', 'users', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 08:53:53'),
(53, 1, 'CREATE_DISPOSISI', 'disposisi', 29, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 09:25:52'),
(54, 2, 'LOGIN_SUCCESS', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 09:26:38'),
(55, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 09:38:36'),
(56, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 09:39:01'),
(57, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 29, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 10:58:23'),
(58, 2, 'FORWARD_DISPOSISI', 'disposisi', 30, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 10:58:23'),
(59, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 19, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 10:59:35'),
(60, 1, 'CREATE_DISPOSISI', 'disposisi', 31, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:01:50'),
(61, 1, 'CREATE_DISPOSISI', 'disposisi', 32, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:01:50'),
(62, 1, 'CREATE_DISPOSISI', 'disposisi', 33, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:04:21'),
(63, 1, 'CREATE_DISPOSISI', 'disposisi', 34, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:05:53'),
(64, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 20, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:08:07'),
(65, 1, 'CREATE_DISPOSISI', 'disposisi', 35, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:10:37'),
(66, 1, 'CREATE_DISPOSISI', 'disposisi', 36, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:10:37'),
(67, 1, 'CREATE_DISPOSISI', 'disposisi', 37, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:10:37'),
(68, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:11:23'),
(69, 1, 'CREATE_DISPOSISI', 'disposisi', 38, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:13:02'),
(70, 1, 'CREATE_DISPOSISI', 'disposisi', 39, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:13:02'),
(71, 1, 'CREATE_DISPOSISI', 'disposisi', 40, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:13:02'),
(72, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 22, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:20:15'),
(73, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 23, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:21:55'),
(74, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:23:04'),
(75, 1, 'CREATE_DISPOSISI', 'disposisi', 41, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:23:35'),
(76, 1, 'CREATE_DISPOSISI', 'disposisi', 42, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:24:20'),
(77, 1, 'CREATE_DISPOSISI', 'disposisi', 43, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:24:20'),
(78, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 25, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:25:05'),
(79, 1, 'CREATE_DISPOSISI', 'disposisi', 44, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:25:50'),
(80, 1, 'CREATE_DISPOSISI', 'disposisi', 45, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:25:50'),
(81, 1, 'DELETE_SURAT_MASUK', 'surat_masuk', 23, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:27:45'),
(82, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 26, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:29:10'),
(83, 1, 'CREATE_DISPOSISI', 'disposisi', 46, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:30:46'),
(84, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:35:53'),
(85, 1, 'CREATE_DISPOSISI', 'disposisi', 47, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:36:16'),
(86, 1, 'CREATE_DISPOSISI', 'disposisi', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 11:36:16'),
(87, 2, 'LOGIN_SUCCESS', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:02:50'),
(88, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 47, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:03:03'),
(89, 2, 'FINISH_SURAT_MASUK', 'surat_masuk', 27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:03:03'),
(90, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 46, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:03:10'),
(91, 2, 'FINISH_SURAT_MASUK', 'surat_masuk', 26, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:03:10'),
(92, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 44, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:03:16'),
(93, 2, 'FINISH_SURAT_MASUK', 'surat_masuk', 25, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:03:16'),
(94, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 42, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:03:24'),
(95, 2, 'FINISH_SURAT_MASUK', 'surat_masuk', 22, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:03:24'),
(96, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 38, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:03:31'),
(97, 2, 'FINISH_SURAT_MASUK', 'surat_masuk', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:03:31'),
(98, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 35, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:03:38'),
(99, 2, 'FINISH_SURAT_MASUK', 'surat_masuk', 20, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:03:38'),
(100, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 34, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:03:45'),
(101, 2, 'FINISH_SURAT_MASUK', 'surat_masuk', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:03:45'),
(102, 10, 'LOGIN_SUCCESS', 'users', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:04:48'),
(103, 10, 'FOLLOW_UP_DISPOSISI', 'disposisi', 41, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:04:58'),
(104, 10, 'FINISH_SURAT_MASUK', 'surat_masuk', 24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:04:58'),
(105, 10, 'FOLLOW_UP_DISPOSISI', 'disposisi', 40, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:05:09'),
(106, 10, 'FINISH_SURAT_MASUK', 'surat_masuk', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:05:09'),
(107, 10, 'FOLLOW_UP_DISPOSISI', 'disposisi', 33, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:05:15'),
(108, 10, 'FINISH_SURAT_MASUK', 'surat_masuk', 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:05:15'),
(109, 10, 'FOLLOW_UP_DISPOSISI', 'disposisi', 37, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:05:22'),
(110, 10, 'FINISH_SURAT_MASUK', 'surat_masuk', 20, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:05:22'),
(111, 10, 'FOLLOW_UP_DISPOSISI', 'disposisi', 32, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:05:29'),
(112, 10, 'FINISH_SURAT_MASUK', 'surat_masuk', 19, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:05:29'),
(113, 10, 'FOLLOW_UP_DISPOSISI', 'disposisi', 30, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:05:35'),
(114, 10, 'FINISH_SURAT_MASUK', 'surat_masuk', 16, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:05:35'),
(115, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 28, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:06:47'),
(116, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 29, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:24:40'),
(117, 1, 'CREATE_DISPOSISI', 'disposisi', 49, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:31:33'),
(118, 1, 'CREATE_DISPOSISI', 'disposisi', 50, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:31:56'),
(119, 1, 'CREATE_DISPOSISI', 'disposisi', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:33:29'),
(120, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 30, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:36:32'),
(121, 1, 'CREATE_DISPOSISI', 'disposisi', 52, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:36:52'),
(122, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 31, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:39:17'),
(123, 1, 'CREATE_DISPOSISI', 'disposisi', 53, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:39:41'),
(124, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 32, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:46:30'),
(125, 1, 'CREATE_DISPOSISI', 'disposisi', 54, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:46:45'),
(126, 1, 'CREATE_DISPOSISI', 'disposisi', 55, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:48:52'),
(127, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 33, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:50:23'),
(128, 1, 'CREATE_DISPOSISI', 'disposisi', 56, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:51:04'),
(129, 1, 'CREATE_USER', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:56:20'),
(130, 10, 'LOGOUT', 'users', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 18:56:32'),
(131, 11, 'ACTIVATE_ACCOUNT', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:00:58'),
(132, 11, 'LOGIN_SUCCESS', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:01:20'),
(133, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 56, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:03:33'),
(134, 2, 'FORWARD_DISPOSISI', 'disposisi', 57, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:03:33'),
(135, 2, 'FORWARD_DISPOSISI', 'disposisi', 58, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:03:38'),
(136, 2, 'FORWARD_DISPOSISI', 'disposisi', 59, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:03:38'),
(137, 2, 'FORWARD_DISPOSISI', 'disposisi', 60, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:04:00'),
(138, 2, 'FORWARD_DISPOSISI', 'disposisi', 61, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:04:00'),
(139, 2, 'FORWARD_DISPOSISI', 'disposisi', 62, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:04:01'),
(140, 2, 'FORWARD_DISPOSISI', 'disposisi', 63, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:04:02'),
(141, 2, 'FORWARD_DISPOSISI', 'disposisi', 64, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:04:03'),
(142, 2, 'FORWARD_DISPOSISI', 'disposisi', 65, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:04:03'),
(143, 2, 'FORWARD_DISPOSISI', 'disposisi', 66, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:04:04'),
(144, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 34, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:06:49'),
(145, 1, 'CREATE_DISPOSISI', 'disposisi', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:08:39'),
(146, 1, 'CREATE_DISPOSISI', 'disposisi', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:08:40'),
(147, 1, 'CREATE_DISPOSISI', 'disposisi', 69, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:08:41'),
(148, 1, 'CREATE_DISPOSISI', 'disposisi', 70, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:08:42'),
(149, 1, 'CREATE_DISPOSISI', 'disposisi', 71, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:08:43'),
(150, 1, 'CREATE_DISPOSISI', 'disposisi', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:08:43'),
(151, 1, 'CREATE_DISPOSISI', 'disposisi', 73, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:08:44'),
(152, 1, 'CREATE_DISPOSISI', 'disposisi', 74, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:08:45'),
(153, 1, 'CREATE_DISPOSISI', 'disposisi', 75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:08:46'),
(154, 1, 'CREATE_DISPOSISI', 'disposisi', 76, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:08:47'),
(155, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 35, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:21:26'),
(156, 1, 'CREATE_DISPOSISI', 'disposisi', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:22:28'),
(157, 1, 'CREATE_DISPOSISI', 'disposisi', 78, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:22:30'),
(158, 1, 'CREATE_DISPOSISI', 'disposisi', 79, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:22:31'),
(159, 1, 'CREATE_DISPOSISI', 'disposisi', 80, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:22:32'),
(160, 1, 'CREATE_DISPOSISI', 'disposisi', 81, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:22:34'),
(161, 1, 'CREATE_DISPOSISI', 'disposisi', 82, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:22:36'),
(162, 1, 'CREATE_DISPOSISI', 'disposisi', 83, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:22:37'),
(163, 1, 'CREATE_DISPOSISI', 'disposisi', 84, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:22:38'),
(164, 1, 'CREATE_DISPOSISI', 'disposisi', 85, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:22:39'),
(165, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 36, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:45:59'),
(166, 1, 'CREATE_DISPOSISI', 'disposisi', 86, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:46:16'),
(167, 1, 'CREATE_DISPOSISI', 'disposisi', 87, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:50:47'),
(168, 1, 'CREATE_DISPOSISI', 'disposisi', 88, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 19:52:03'),
(169, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 88, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:13:44'),
(170, 2, 'FORWARD_DISPOSISI', 'disposisi', 89, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:13:44'),
(171, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 87, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:14:59'),
(172, 2, 'FORWARD_DISPOSISI', 'disposisi', 90, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:14:59'),
(173, 2, 'FORWARD_DISPOSISI', 'disposisi', 91, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:15:20'),
(174, 2, 'FORWARD_DISPOSISI', 'disposisi', 92, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:15:26'),
(175, 2, 'FORWARD_DISPOSISI', 'disposisi', 93, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:15:33'),
(176, 2, 'FORWARD_DISPOSISI', 'disposisi', 94, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:15:48'),
(177, 2, 'FORWARD_DISPOSISI', 'disposisi', 95, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:15:54'),
(178, 2, 'FORWARD_DISPOSISI', 'disposisi', 96, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:15:56'),
(179, 2, 'FORWARD_DISPOSISI', 'disposisi', 97, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:16:21'),
(180, 2, 'FORWARD_DISPOSISI', 'disposisi', 98, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:16:33'),
(181, 2, 'FORWARD_DISPOSISI', 'disposisi', 99, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:16:48'),
(182, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:20:36'),
(183, 2, 'FORWARD_DISPOSISI', 'disposisi', 100, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:20:36'),
(184, 1, 'CREATE_USER', 'users', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:25:11'),
(185, 12, 'ACTIVATE_ACCOUNT', 'users', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:26:54'),
(186, 12, 'LOGIN_SUCCESS', 'users', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:27:12'),
(187, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:28:14'),
(188, 2, 'FORWARD_DISPOSISI', 'disposisi', 101, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 20:28:14'),
(189, 1, 'FOLLOW_UP_DISPOSISI', 'disposisi', 90, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 14:03:16'),
(190, 1, 'FINISH_SURAT_MASUK', 'surat_masuk', 36, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 14:03:16'),
(191, 1, 'FOLLOW_UP_DISPOSISI', 'disposisi', 57, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 14:03:24'),
(192, 1, 'FINISH_SURAT_MASUK', 'surat_masuk', 33, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 14:03:24'),
(193, 1, 'SEND_SURAT_KELUAR', 'surat_keluar', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 14:05:38'),
(194, 1, 'LOGIN_SUCCESS', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:28:20'),
(195, 10, 'LOGIN_SUCCESS', 'users', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:30:18'),
(196, 1, 'CREATE_SURAT_MASUK', 'surat_masuk', 37, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:32:48'),
(197, 1, 'CREATE_DISPOSISI', 'disposisi', 102, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:34:49'),
(198, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 102, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:35:51'),
(199, 2, 'FORWARD_DISPOSISI', 'disposisi', 103, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:35:51'),
(200, 2, 'FORWARD_DISPOSISI', 'disposisi', 104, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:35:53'),
(201, 2, 'FORWARD_DISPOSISI', 'disposisi', 105, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:35:56'),
(202, 2, 'FORWARD_DISPOSISI', 'disposisi', 106, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:35:59'),
(203, 2, 'FORWARD_DISPOSISI', 'disposisi', 107, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:36:01'),
(204, 2, 'FORWARD_DISPOSISI', 'disposisi', 108, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:36:04'),
(205, 2, 'FORWARD_DISPOSISI', 'disposisi', 109, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:36:06'),
(206, 2, 'FORWARD_DISPOSISI', 'disposisi', 110, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:36:09'),
(207, 2, 'FORWARD_DISPOSISI', 'disposisi', 111, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:36:11'),
(208, 2, 'FORWARD_DISPOSISI', 'disposisi', 112, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:36:15'),
(209, 2, 'FORWARD_DISPOSISI', 'disposisi', 113, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:36:20'),
(210, 10, 'FOLLOW_UP_DISPOSISI', 'disposisi', 111, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:37:29'),
(211, 10, 'FINISH_SURAT_MASUK', 'surat_masuk', 37, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:37:29'),
(212, 10, 'CREATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:38:48'),
(213, 10, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:39:17'),
(214, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:40:51'),
(215, 10, 'SEND_SURAT_KELUAR', 'surat_keluar', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:41:05'),
(216, 1, 'CREATE_USER', 'users', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:43:56'),
(217, 13, 'ACTIVATE_ACCOUNT', 'users', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:45:09'),
(218, 13, 'LOGIN_SUCCESS', 'users', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:46:08'),
(219, 13, 'LOGIN_SUCCESS', 'users', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 13:46:36'),
(220, 1, 'LOGIN_SUCCESS', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 16:23:23'),
(221, 1, 'CREATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:02:33'),
(222, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:05:28'),
(223, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:12:16'),
(224, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:12:18'),
(225, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:12:19'),
(226, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:12:19'),
(227, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:14:03'),
(228, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:14:05'),
(229, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:17:24'),
(230, 1, 'CREATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:41:17'),
(231, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:48:12'),
(232, 1, 'CREATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 15, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:55:16'),
(233, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 15, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:55:26'),
(234, 2, 'LOGIN_SUCCESS', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:56:46'),
(235, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 15, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:57:05'),
(236, 2, 'REJECT_SURAT_KELUAR', 'surat_keluar', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:57:27'),
(237, 2, 'REJECT_SURAT_KELUAR', 'surat_keluar', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:57:33'),
(238, 1, 'SEND_SURAT_KELUAR', 'surat_keluar', 15, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 06:59:42'),
(239, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 07:00:31'),
(240, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 07:00:35'),
(241, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 07:02:28'),
(242, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 07:02:42'),
(243, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 07:03:51'),
(244, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 07:04:00'),
(245, 1, 'SEND_SURAT_KELUAR', 'surat_keluar', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 07:04:37'),
(246, 1, 'SEND_SURAT_KELUAR', 'surat_keluar', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 07:04:42'),
(247, 1, 'FOLLOW_UP_DISPOSISI', 'disposisi', 103, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 07:05:45'),
(248, 1, 'FINISH_SURAT_MASUK', 'surat_masuk', 37, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 07:05:45'),
(249, 1, 'LOGIN_SUCCESS', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 15:22:28'),
(250, 1, 'LOGIN_SUCCESS', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 09:45:39'),
(251, 1, 'CREATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:13:14'),
(252, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:13:18'),
(253, 2, 'LOGIN_SUCCESS', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:14:55'),
(254, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:15:10'),
(255, 1, 'SEND_SURAT_KELUAR', 'surat_keluar', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:15:40'),
(256, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 16, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:37:00'),
(257, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 16, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:38:18'),
(258, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 16, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:38:27'),
(259, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 16, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:38:40'),
(260, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 16, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:38:44'),
(261, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:42:00');
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `ip_address`, `user_agent`, `created_at`) VALUES
(262, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:46:32'),
(263, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:46:37'),
(264, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 19, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:53:01'),
(265, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 19, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:53:16'),
(266, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:53:22'),
(267, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 19, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:57:01'),
(268, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:57:07'),
(269, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 16, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:57:14'),
(270, 1, 'SEND_SURAT_KELUAR', 'surat_keluar', 19, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 15:58:02'),
(271, 2, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 20, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 16:01:13'),
(272, 2, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 20, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 16:01:26'),
(273, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 20, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 16:01:36'),
(274, 10, 'LOGIN_SUCCESS', 'users', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 16:04:40'),
(275, 1, 'SEND_SURAT_KELUAR', 'surat_keluar', 20, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 16:19:54'),
(276, 1, 'SEND_SURAT_KELUAR', 'surat_keluar', 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 16:20:00'),
(277, 1, 'SEND_SURAT_KELUAR', 'surat_keluar', 16, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 16:20:06'),
(278, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 86, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 17:32:30'),
(279, 2, 'FINISH_SURAT_MASUK', 'surat_masuk', 36, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 17:32:30'),
(280, 2, 'FOLLOW_UP_DISPOSISI', 'disposisi', 55, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 17:32:38'),
(281, 2, 'FINISH_SURAT_MASUK', 'surat_masuk', 32, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 17:32:38'),
(282, 10, 'LOGIN_SUCCESS', 'users', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 12:54:17'),
(283, 1, 'LOGOUT', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 14:40:07'),
(284, 3, 'LOGIN_SUCCESS', 'users', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 14:40:20'),
(285, 3, 'LOGOUT', 'users', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 14:46:47'),
(286, 1, 'LOGIN_SUCCESS', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 14:46:56'),
(287, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 15:03:20'),
(288, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 15:04:03'),
(289, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 15:04:06'),
(290, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 15:04:37'),
(291, 1, 'SEND_SURAT_KELUAR', 'surat_keluar', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 15:42:12'),
(292, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 22, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 15:43:31'),
(293, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 22, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 15:43:37'),
(294, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 22, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 15:43:53'),
(295, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 23, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 15:46:45'),
(296, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 23, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 15:46:50'),
(297, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 23, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 15:47:00'),
(298, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 16:05:58'),
(299, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 16:06:02'),
(300, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 16:06:14'),
(301, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 25, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 16:13:48'),
(302, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 25, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 16:13:52'),
(303, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 25, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 16:14:03'),
(304, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 26, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 16:25:05'),
(305, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 26, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 16:25:13'),
(306, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 26, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 16:25:34'),
(307, 1, 'UPDATE_DRAFT_SURAT_KELUAR', 'surat_keluar', 27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 16:30:31'),
(308, 1, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', 27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 16:30:38'),
(309, 2, 'APPROVE_SURAT_KELUAR', 'surat_keluar', 27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 16:30:52');

-- --------------------------------------------------------

--
-- Table structure for table `disposisi`
--

CREATE TABLE `disposisi` (
  `id` bigint NOT NULL,
  `surat_id` bigint NOT NULL,
  `dari_user_id` int NOT NULL,
  `ke_user_id` int NOT NULL,
  `instruksi` text NOT NULL,
  `batas_waktu_sla` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `acted_at` datetime DEFAULT NULL,
  `status` enum('Menunggu','Dibaca','Selesai') DEFAULT 'Menunggu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `disposisi`
--

INSERT INTO `disposisi` (`id`, `surat_id`, `dari_user_id`, `ke_user_id`, `instruksi`, `batas_waktu_sla`, `read_at`, `acted_at`, `status`) VALUES
(1, 2, 1, 2, 'Mohon kofirmasi Bapak terkait Surat peminjaman Lab TKJ', '2026-03-16 23:59:00', '2026-03-15 21:52:55', '2026-03-15 21:52:55', 'Selesai'),
(2, 3, 1, 5, 'Mohon ACC PAK', '2026-03-17 22:06:00', '2026-03-15 22:08:29', '2026-03-15 22:08:29', 'Selesai'),
(3, 4, 1, 2, 'Mohon Arahannya agar kiranya dapat mengkonfirmasi permohonan peminjaman LAB', '2026-03-16 22:16:00', '2026-03-15 22:17:37', '2026-03-15 22:17:37', 'Selesai'),
(4, 4, 2, 5, 'Telah di ACC', NULL, '2026-03-15 22:21:28', '2026-03-15 22:21:28', 'Selesai'),
(5, 5, 1, 2, 'Mohon konfirmasinya PAK terkait surat peminjaman Aula SMK', '2026-03-15 22:37:00', '2026-03-15 22:38:24', '2026-03-15 22:38:24', 'Selesai'),
(6, 6, 1, 2, 'Mohon untuk diketahui', '2026-03-16 23:13:00', '2026-03-15 23:24:35', '2026-03-15 23:24:35', 'Selesai'),
(7, 6, 1, 3, 'Mohon untuk diketahui', '2026-03-16 23:13:00', '2026-03-15 23:37:34', '2026-03-15 23:37:34', 'Selesai'),
(8, 6, 1, 4, 'Mohon untuk diketahui', '2026-03-16 23:13:00', '2026-03-15 23:36:00', '2026-03-15 23:36:00', 'Selesai'),
(9, 6, 1, 5, 'Mohon untuk diketahui', '2026-03-16 23:13:00', NULL, NULL, 'Menunggu'),
(10, 6, 1, 6, 'Mohon untuk diketahui', '2026-03-16 23:13:00', '2026-03-15 23:29:37', '2026-03-15 23:29:37', 'Selesai'),
(11, 6, 2, 1, 'Harap Hadir dalam sosialisasi besok', NULL, '2026-03-15 23:29:06', '2026-03-15 23:29:06', 'Selesai'),
(12, 6, 2, 3, 'Harap Hadir dalam sosialisasi besok', NULL, '2026-03-15 23:37:30', '2026-03-15 23:37:30', 'Selesai'),
(13, 6, 2, 4, 'Harap Hadir dalam sosialisasi besok', NULL, '2026-03-15 23:36:05', '2026-03-15 23:36:05', 'Selesai'),
(14, 6, 2, 5, 'Harap Hadir dalam sosialisasi besok', NULL, NULL, NULL, 'Menunggu'),
(15, 6, 2, 6, 'Harap Hadir dalam sosialisasi besok', NULL, '2026-03-15 23:26:53', '2026-03-15 23:26:53', 'Selesai'),
(16, 7, 1, 2, 'Sosialisasi UNG untuk Kelas XII ', '2026-03-17 23:34:00', '2026-03-15 23:34:51', '2026-03-15 23:34:51', 'Selesai'),
(17, 7, 2, 3, '', NULL, '2026-03-15 23:37:24', '2026-03-15 23:37:24', 'Selesai'),
(18, 7, 2, 4, '', NULL, '2026-03-15 23:36:10', '2026-03-15 23:36:10', 'Selesai'),
(19, 8, 1, 2, 'Mohon arahannya IBU terkait Surat Pengambilan data', '2026-03-17 18:38:00', '2026-03-16 18:43:19', '2026-03-16 18:43:19', 'Selesai'),
(20, 9, 1, 2, 'Mohon Konfrimasinya Pak terkait mahasiswa yang akan melaksanakan Pengambilan data', '2026-03-18 18:41:00', '2026-03-16 18:43:44', '2026-03-16 18:43:44', 'Selesai'),
(21, 9, 2, 6, 'Tangani ini ya', NULL, '2026-03-16 18:44:26', '2026-03-16 18:44:26', 'Selesai'),
(22, 10, 1, 2, 'Mohon ACC Pak Kep', '2026-03-17 00:55:00', '2026-03-17 00:55:38', '2026-03-17 00:55:38', 'Selesai'),
(23, 11, 1, 2, 'tee', '2026-03-18 01:29:00', '2026-03-17 01:29:35', '2026-03-17 01:29:35', 'Selesai'),
(24, 12, 1, 2, 'Mohon Dibaca ya', '2026-03-17 15:45:00', '2026-03-17 02:33:22', '2026-03-17 02:33:22', 'Selesai'),
(25, 13, 1, 2, 'ACC PAK', '2026-03-17 07:42:00', '2026-03-17 02:42:49', '2026-03-17 02:42:49', 'Selesai'),
(26, 14, 1, 2, 'ACC', '2026-03-18 10:56:00', '2026-03-17 10:58:25', '2026-03-17 10:58:25', 'Selesai'),
(27, 14, 2, 6, 'pp', NULL, '2026-03-17 11:13:14', '2026-03-17 11:13:14', 'Selesai'),
(28, 15, 1, 2, 'as', '2026-03-18 11:17:00', '2026-03-17 11:18:03', '2026-03-17 11:18:03', 'Selesai'),
(29, 16, 1, 2, 'Mohon', '2026-03-19 17:25:00', '2026-03-18 18:58:23', '2026-03-18 18:58:23', 'Selesai'),
(30, 16, 2, 10, '', NULL, '2026-03-19 02:05:35', '2026-03-19 02:05:35', 'Selesai'),
(31, 19, 1, 8, 'testing', '2026-03-19 19:01:00', NULL, NULL, 'Menunggu'),
(32, 19, 1, 10, 'testing', '2026-03-19 19:01:00', '2026-03-19 02:05:29', '2026-03-19 02:05:29', 'Selesai'),
(33, 18, 1, 10, 'tesss', '2026-03-18 19:04:00', '2026-03-19 02:05:15', '2026-03-19 02:05:15', 'Selesai'),
(34, 17, 1, 2, 'pp', '2026-03-18 19:05:00', '2026-03-19 02:03:45', '2026-03-19 02:03:45', 'Selesai'),
(35, 20, 1, 2, 'ppp', NULL, '2026-03-19 02:03:38', '2026-03-19 02:03:38', 'Selesai'),
(36, 20, 1, 8, 'ppp', NULL, NULL, NULL, 'Menunggu'),
(37, 20, 1, 10, 'ppp', NULL, '2026-03-19 02:05:22', '2026-03-19 02:05:22', 'Selesai'),
(38, 21, 1, 2, 'pp', NULL, '2026-03-19 02:03:31', '2026-03-19 02:03:31', 'Selesai'),
(39, 21, 1, 8, 'pp', NULL, NULL, NULL, 'Menunggu'),
(40, 21, 1, 10, 'pp', NULL, '2026-03-19 02:05:09', '2026-03-19 02:05:09', 'Selesai'),
(41, 24, 1, 10, 'ppp\r\n', '2026-03-18 19:23:00', '2026-03-19 02:04:58', '2026-03-19 02:04:58', 'Selesai'),
(42, 22, 1, 2, 'pp', NULL, '2026-03-19 02:03:24', '2026-03-19 02:03:24', 'Selesai'),
(43, 22, 1, 9, 'pp', NULL, NULL, NULL, 'Menunggu'),
(44, 25, 1, 2, 'pp', '2026-03-18 19:25:00', '2026-03-19 02:03:16', '2026-03-19 02:03:16', 'Selesai'),
(45, 25, 1, 8, 'pp', '2026-03-18 19:25:00', NULL, NULL, 'Menunggu'),
(46, 26, 1, 2, 'pp', '2026-03-18 19:30:00', '2026-03-19 02:03:10', '2026-03-19 02:03:10', 'Selesai'),
(47, 27, 1, 2, 'testing', NULL, '2026-03-19 02:03:03', '2026-03-19 02:03:03', 'Selesai'),
(48, 27, 1, 3, 'testing', NULL, NULL, NULL, 'Menunggu'),
(49, 29, 1, 2, 'Mohon Konfirmasi Pak', NULL, NULL, NULL, 'Selesai'),
(50, 29, 1, 2, 'Mohon Konfirmasi Pak', NULL, NULL, NULL, 'Selesai'),
(51, 28, 1, 2, 'Mohon ACC', '2026-03-20 02:33:00', NULL, NULL, 'Selesai'),
(52, 30, 1, 2, 'p', '2026-03-19 07:36:00', NULL, NULL, 'Selesai'),
(53, 31, 1, 2, 'p', '2026-03-19 02:39:00', NULL, NULL, 'Selesai'),
(54, 32, 1, 2, 'ppp', '2026-03-20 02:46:00', NULL, NULL, 'Selesai'),
(55, 32, 1, 2, 'ppp', '2026-03-20 02:46:00', '2026-04-11 01:32:38', '2026-04-11 01:32:38', 'Selesai'),
(56, 33, 1, 2, 'Mohon Tindak Lanjuti Pak', '2026-03-20 02:50:00', '2026-03-19 03:03:33', '2026-03-19 03:03:33', 'Selesai'),
(57, 33, 2, 1, 'ACC untuk Cuti Lebaran Kalian ya ...', NULL, '2026-03-20 22:03:24', '2026-03-20 22:03:24', 'Selesai'),
(58, 33, 2, 3, 'ACC untuk Cuti Lebaran Kalian ya ...', NULL, NULL, NULL, 'Menunggu'),
(59, 33, 2, 4, 'ACC untuk Cuti Lebaran Kalian ya ...', NULL, NULL, NULL, 'Menunggu'),
(60, 33, 2, 5, 'ACC untuk Cuti Lebaran Kalian ya ...', NULL, NULL, NULL, 'Menunggu'),
(61, 33, 2, 6, 'ACC untuk Cuti Lebaran Kalian ya ...', NULL, NULL, NULL, 'Menunggu'),
(62, 33, 2, 7, 'ACC untuk Cuti Lebaran Kalian ya ...', NULL, NULL, NULL, 'Menunggu'),
(63, 33, 2, 8, 'ACC untuk Cuti Lebaran Kalian ya ...', NULL, NULL, NULL, 'Menunggu'),
(64, 33, 2, 9, 'ACC untuk Cuti Lebaran Kalian ya ...', NULL, NULL, NULL, 'Menunggu'),
(65, 33, 2, 10, 'ACC untuk Cuti Lebaran Kalian ya ...', NULL, NULL, NULL, 'Menunggu'),
(66, 33, 2, 11, 'ACC untuk Cuti Lebaran Kalian ya ...', NULL, NULL, NULL, 'Menunggu'),
(67, 34, 1, 2, 'Edaran Tanggal Merah', '2026-03-20 03:08:00', '2026-03-19 04:28:14', '2026-03-19 04:28:14', 'Selesai'),
(68, 34, 1, 3, 'Edaran Tanggal Merah', '2026-03-20 03:08:00', NULL, NULL, 'Menunggu'),
(69, 34, 1, 4, 'Edaran Tanggal Merah', '2026-03-20 03:08:00', NULL, NULL, 'Menunggu'),
(70, 34, 1, 5, 'Edaran Tanggal Merah', '2026-03-20 03:08:00', NULL, NULL, 'Menunggu'),
(71, 34, 1, 6, 'Edaran Tanggal Merah', '2026-03-20 03:08:00', NULL, NULL, 'Menunggu'),
(72, 34, 1, 7, 'Edaran Tanggal Merah', '2026-03-20 03:08:00', NULL, NULL, 'Menunggu'),
(73, 34, 1, 8, 'Edaran Tanggal Merah', '2026-03-20 03:08:00', NULL, NULL, 'Menunggu'),
(74, 34, 1, 9, 'Edaran Tanggal Merah', '2026-03-20 03:08:00', NULL, NULL, 'Menunggu'),
(75, 34, 1, 10, 'Edaran Tanggal Merah', '2026-03-20 03:08:00', NULL, NULL, 'Menunggu'),
(76, 34, 1, 11, 'Edaran Tanggal Merah', '2026-03-20 03:08:00', NULL, NULL, 'Menunggu'),
(77, 35, 1, 2, 'Infromasi Untuk Bapak dan Ibu Bahwa Libur Mulai Besok ya. Mohon Maaf Lahir dan Batin', NULL, '2026-03-19 04:20:36', '2026-03-19 04:20:36', 'Selesai'),
(78, 35, 1, 3, 'Infromasi Untuk Bapak dan Ibu Bahwa Libur Mulai Besok ya. Mohon Maaf Lahir dan Batin', NULL, NULL, NULL, 'Menunggu'),
(79, 35, 1, 4, 'Infromasi Untuk Bapak dan Ibu Bahwa Libur Mulai Besok ya. Mohon Maaf Lahir dan Batin', NULL, NULL, NULL, 'Menunggu'),
(80, 35, 1, 5, 'Infromasi Untuk Bapak dan Ibu Bahwa Libur Mulai Besok ya. Mohon Maaf Lahir dan Batin', NULL, NULL, NULL, 'Menunggu'),
(81, 35, 1, 6, 'Infromasi Untuk Bapak dan Ibu Bahwa Libur Mulai Besok ya. Mohon Maaf Lahir dan Batin', NULL, NULL, NULL, 'Menunggu'),
(82, 35, 1, 8, 'Infromasi Untuk Bapak dan Ibu Bahwa Libur Mulai Besok ya. Mohon Maaf Lahir dan Batin', NULL, NULL, NULL, 'Menunggu'),
(83, 35, 1, 9, 'Infromasi Untuk Bapak dan Ibu Bahwa Libur Mulai Besok ya. Mohon Maaf Lahir dan Batin', NULL, NULL, NULL, 'Menunggu'),
(84, 35, 1, 10, 'Infromasi Untuk Bapak dan Ibu Bahwa Libur Mulai Besok ya. Mohon Maaf Lahir dan Batin', NULL, NULL, NULL, 'Menunggu'),
(85, 35, 1, 11, 'Infromasi Untuk Bapak dan Ibu Bahwa Libur Mulai Besok ya. Mohon Maaf Lahir dan Batin', NULL, NULL, NULL, 'Menunggu'),
(86, 36, 1, 2, 'tes', NULL, '2026-04-11 01:32:30', '2026-04-11 01:32:30', 'Selesai'),
(87, 36, 1, 2, 'tes', NULL, '2026-03-19 04:14:59', '2026-03-19 04:14:59', 'Selesai'),
(88, 36, 1, 2, 'tes', NULL, '2026-03-19 04:13:44', '2026-03-19 04:13:44', 'Selesai'),
(89, 36, 2, 5, 'Libur Hanya Untuk Kamu Ya, yang lain WFA', NULL, NULL, NULL, 'Menunggu'),
(90, 36, 2, 1, 'TIDAK ADA LIBUR! SEMUA WAJIB MASUK ', NULL, '2026-03-20 22:03:16', '2026-03-20 22:03:16', 'Selesai'),
(91, 36, 2, 3, 'TIDAK ADA LIBUR! SEMUA WAJIB MASUK ', NULL, NULL, NULL, 'Menunggu'),
(92, 36, 2, 4, 'TIDAK ADA LIBUR! SEMUA WAJIB MASUK ', NULL, NULL, NULL, 'Menunggu'),
(93, 36, 2, 5, 'TIDAK ADA LIBUR! SEMUA WAJIB MASUK ', NULL, NULL, NULL, 'Menunggu'),
(94, 36, 2, 6, 'TIDAK ADA LIBUR! SEMUA WAJIB MASUK ', NULL, NULL, NULL, 'Menunggu'),
(95, 36, 2, 7, 'TIDAK ADA LIBUR! SEMUA WAJIB MASUK ', NULL, NULL, NULL, 'Menunggu'),
(96, 36, 2, 8, 'TIDAK ADA LIBUR! SEMUA WAJIB MASUK ', NULL, NULL, NULL, 'Menunggu'),
(97, 36, 2, 9, 'TIDAK ADA LIBUR! SEMUA WAJIB MASUK ', NULL, NULL, NULL, 'Menunggu'),
(98, 36, 2, 10, 'TIDAK ADA LIBUR! SEMUA WAJIB MASUK ', NULL, NULL, NULL, 'Menunggu'),
(99, 36, 2, 11, 'TIDAK ADA LIBUR! SEMUA WAJIB MASUK ', NULL, NULL, NULL, 'Menunggu'),
(100, 35, 2, 6, 'TETAP HADIR NABIL', NULL, NULL, NULL, 'Menunggu'),
(101, 34, 2, 12, 'Libur Ya...', NULL, NULL, NULL, 'Menunggu'),
(102, 37, 1, 2, 'ACC PAK...', '2026-03-25 21:34:00', '2026-03-24 21:35:51', '2026-03-24 21:35:51', 'Selesai'),
(103, 37, 2, 1, 'ACC', NULL, '2026-04-03 15:05:45', '2026-04-03 15:05:45', 'Selesai'),
(104, 37, 2, 3, 'ACC', NULL, NULL, NULL, 'Menunggu'),
(105, 37, 2, 4, 'ACC', NULL, NULL, NULL, 'Menunggu'),
(106, 37, 2, 5, 'ACC', NULL, NULL, NULL, 'Menunggu'),
(107, 37, 2, 6, 'ACC', NULL, NULL, NULL, 'Menunggu'),
(108, 37, 2, 7, 'ACC', NULL, NULL, NULL, 'Menunggu'),
(109, 37, 2, 8, 'ACC', NULL, NULL, NULL, 'Menunggu'),
(110, 37, 2, 9, 'ACC', NULL, NULL, NULL, 'Menunggu'),
(111, 37, 2, 10, 'ACC', NULL, '2026-03-24 21:37:29', '2026-03-24 21:37:29', 'Selesai'),
(112, 37, 2, 11, 'ACC', NULL, NULL, NULL, 'Menunggu'),
(113, 37, 2, 12, 'ACC', NULL, NULL, NULL, 'Menunggu');

-- --------------------------------------------------------

--
-- Table structure for table `klasifikasi_surat`
--

CREATE TABLE `klasifikasi_surat` (
  `id` int NOT NULL,
  `kode` varchar(10) NOT NULL,
  `keterangan` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `klasifikasi_surat`
--

INSERT INTO `klasifikasi_surat` (`id`, `kode`, `keterangan`) VALUES
(1, '420', 'Pendidikan (Undangan Wali Murid, Rapat Sekolah, dll)'),
(2, '800', 'Kepegawaian (SK Guru, Tugas Mengajar, dll)'),
(3, '090', 'Perjalanan Dinas (Surat Tugas, SPPD)'),
(4, '900', 'Keuangan (BOS, Laporan Dana, dll)'),
(5, '005', 'Undangan Resmi (Pihak Luar/Dinas)');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int NOT NULL,
  `nama_role` varchar(50) NOT NULL,
  `akses_level` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `nama_role`, `akses_level`) VALUES
(1, 'Admin_TU', NULL),
(2, 'Kepala_Sekolah', NULL),
(3, 'Waka', NULL),
(4, 'Guru', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `surat_keluar`
--

CREATE TABLE `surat_keluar` (
  `id` bigint NOT NULL,
  `nomor_urut` int DEFAULT NULL,
  `nomor_surat` varchar(100) DEFAULT NULL,
  `tanggal_keluar` date DEFAULT NULL,
  `tujuan` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `perihal` text NOT NULL,
  `klasifikasi` varchar(50) DEFAULT NULL,
  `lokasi_fisik` varchar(150) DEFAULT 'Belum diarsipkan secara fisik',
  `draft_by` int NOT NULL,
  `approved_by` int DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `is_tte` tinyint(1) DEFAULT '0',
  `status_workflow` varchar(50) DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `surat_keluar`
--

INSERT INTO `surat_keluar` (`id`, `nomor_urut`, `nomor_surat`, `tanggal_keluar`, `tujuan`, `perihal`, `klasifikasi`, `lokasi_fisik`, `draft_by`, `approved_by`, `file_path`, `is_tte`, `status_workflow`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, NULL, '125/001/SMKN4GTLO/III/2026', '2026-03-16', 'Kepala Sekolah', 'Pengajuan Cuti Melahirkan', 'Penting', 'Belum diarsipkan secara fisik', 6, 2, 'SK_20260315_224711_86.pdf', 1, 'Terkirim', '2026-03-15 22:27:57', '2026-03-16 08:45:07', NULL),
(2, NULL, NULL, NULL, 'Kepala Sekolah', 'Pengajuan Cuti Bulanan', 'Penting', 'Belum diarsipkan secara fisik', 6, 2, 'TTE_SK_20260316_054306_48.pdf', 1, 'Terkirim', '2026-03-16 05:43:06', '2026-03-16 09:41:14', NULL),
(3, NULL, '04.002/CUTI/SMKN4GTLO/III/2026', NULL, 'Kepala Sekolah', 'Pengajuan Cuti Bulanan', 'Penting', 'Belum diarsipkan secara fisik', 6, 2, 'TTE_SK_20260316_060408_88.pdf', 1, 'Terkirim', '2026-03-16 06:04:08', '2026-03-16 09:41:11', NULL),
(4, NULL, '04.003/CUTI/SMKN4GTLO/III/2026', NULL, 'Kepala Sekolah', 'Pengajuan Cuti Bulanan', 'Penting', 'Belum diarsipkan secara fisik', 6, 2, 'TTE_SK_20260316_062058_28.pdf', 1, 'Terkirim', '2026-03-16 06:20:58', '2026-03-16 09:41:08', NULL),
(5, NULL, '04.004/CUTI/SMKN4GTLO/III/2026', '2026-03-16', 'Kepala Sekolah', 'Pengajuan Cuti Bulanan', 'Biasa', 'Belum diarsipkan secara fisik', 6, 2, 'TTE_SK_20260316_062218_18.pdf', 1, 'Terkirim', '2026-03-16 06:22:18', '2026-03-16 08:54:10', NULL),
(6, NULL, '04.006/CUTI/SMKN4GTLO/III/2026', '2026-03-16', 'Kepala Sekolah', 'Pengajuan Cuti Bulanan', 'Biasa', 'Belum diarsipkan secara fisik', 6, 2, 'TTE_SK_20260316_064257_52.pdf', 1, 'Terkirim', '2026-03-16 06:42:57', '2026-03-16 08:54:04', NULL),
(7, NULL, '04.007/CUTI/SMKN4GTLO/III/2026', '2026-03-16', 'Kepala Sekolah', 'Pengajuan Cuti Bulanan', 'Penting', 'Belum diarsipkan secara fisik', 6, 2, 'TTE_SK_20260316_064936_88.pdf', 1, 'Terkirim', '2026-03-16 06:49:36', '2026-03-16 07:21:20', NULL),
(8, NULL, '02.009/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-16', 'Kepala Sekolah', 'Permohonan Peminjaman', 'Biasa', 'Belum diarsipkan secara fisik', 6, 2, 'TTE_SK_20260316_071144_83.pdf', 1, 'Terkirim', '2026-03-16 07:11:44', '2026-03-16 08:45:00', NULL),
(9, NULL, '05.001/RAPAT/SMKN4GTLO/III/2026', '2026-03-16', 'Kepala Sekolah', 'Undangan', 'Biasa', 'Belum diarsipkan secara fisik', 1, 2, 'TTE_SK_20260316_085902_82.pdf', 1, 'Terkirim', '2026-03-16 08:59:02', '2026-03-16 10:47:02', NULL),
(10, NULL, '04.021/SMKN4GTLO/III/2026', '2026-03-16', 'Kepala Sekolah', 'Permohonan Cutii Puasa', 'Biasa', 'Belum diarsipkan secara fisik', 6, 2, 'TTE_SK_20260316_094231_72.pdf', 1, 'Terkirim', '2026-03-16 09:42:31', '2026-03-17 03:12:37', NULL),
(11, NULL, '02.006/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-17', 'Kepala Sekolah', 'Pengajuan Cuti Bulanan', 'Penting', 'Belum diarsipkan secara fisik', 1, 2, 'TTE_SK_20260317_031839_78.pdf', 1, 'Terkirim', '2026-03-17 03:18:39', '2026-03-20 14:05:38', NULL),
(12, NULL, '05.021/SMKN4GTLO/III/2026', '2026-03-24', 'Kepala Sekolah', 'Permohonan Cutii Lebaran', 'Penting', 'Belum diarsipkan secara fisik', 10, 2, 'TTE_SK_20260324_133848_89.pdf', 1, 'Terkirim', '2026-03-24 13:38:48', '2026-03-24 13:41:05', NULL),
(13, NULL, '09.001/PP-MUBES/UKM-Risti/UNG/IV/2026', '2026-04-03', 'Kepala Sekolah', 'Permohonan Peminjaman', 'Rahasia', 'Belum diarsipkan secara fisik', 1, 2, 'TTE_SK_20260403_060233_54.pdf', 1, 'Terkirim', '2026-04-03 06:02:33', '2026-04-03 07:04:42', NULL),
(14, NULL, '09.002/PP-MUBES/UKM-Risti/UNG/IV/2026', '2026-04-03', 'Kepala Sekolah', 'Pengajuan Cuti Bulanan', 'Biasa', 'Belum diarsipkan secara fisik', 1, 2, 'TTE_SK_20260403_064117_68.pdf', 1, 'Terkirim', '2026-04-03 06:41:17', '2026-04-03 07:04:37', NULL),
(15, NULL, '09.003/PP-MUBES/UKM-Risti/UNG/IV/2026', '2026-04-03', 'Kepala Sekolah', 'Pengajuan Cuti Bulanan', 'Biasa', 'Lemari TU 1 kiri atas', 1, 2, 'TTE_SK_20260403_065516_64.pdf', 1, 'Terkirim', '2026-04-03 06:55:16', '2026-04-10 13:55:56', NULL),
(16, 1, '800/001/SMAN3/IV/2026', '2026-04-10', 'Kepala Sekolah', 'Pengajuan Cuti Bulanan', 'Biasa', 'Belum diarsipkan secara fisik', 1, 2, 'TTE_SK_20260410_153700_38.pdf', 1, 'Terkirim', '2026-04-10 15:12:29', '2026-04-10 16:20:06', NULL),
(18, 2, '800/002/SMAN3/IV/2026', '2026-04-10', 'Kepala Sekolah', 'Pengajuan Cuti Bulanan', 'Biasa', 'Belum diarsipkan secara fisik', 1, 2, 'TTE_SK_20260410_154200_15.pdf', 1, 'Terkirim', '2026-04-10 15:39:28', '2026-04-10 16:20:00', NULL),
(19, 3, '800/003/SMAN3/IV/2026', '2026-04-10', 'Dinas Pendidikan Provinsi Gorontalo', 'Pengajuan Cuti Bulanan', '800', 'Belum diarsipkan secara fisik', 1, 2, 'TTE_SK_20260410_155301_56.pdf', 1, 'Terkirim', '2026-04-10 15:47:17', '2026-04-10 15:58:02', NULL),
(20, 4, '005/004/SMAN3/IV/2026', '2026-04-11', 'Orang Tua Siswa', 'Rapat Orang Tua Siswa', '005', 'Belum diarsipkan secara fisik', 2, 2, 'TTE_SK_20260410_160113_87.pdf', 1, 'Terkirim', '2026-04-10 15:59:14', '2026-04-10 16:19:54', NULL),
(21, 5, '090/005/SMAN3/IV/2026', '2026-04-11', 'Kepala Sekolah', 'LAPORAN ADMINISTRASI SURAT KELUAR', 'Biasa', 'Belum diarsipkan secara fisik', 1, 2, 'TTE_SK_20260411_230320_51.pdf', 1, 'Terkirim', '2026-04-10 17:27:48', '2026-04-11 15:42:12', NULL),
(22, 6, '420/006/SMAN3/IV/2026', '2026-04-11', 'Kepala Sekolah', 'Rapat', '420', 'Belum diarsipkan secara fisik', 1, 2, 'TTE_SK_20260411_234331_30.pdf', 1, 'Approved', '2026-04-11 15:42:45', '2026-04-11 15:43:53', NULL),
(23, 7, '900/007/SMAN3/IV/2026', '2026-04-11', 'Kepala Sekolah', 'RAB', '900', 'Belum diarsipkan secara fisik', 1, 2, 'TTE_SK_20260411_234645_43.pdf', 1, 'Approved', '2026-04-11 15:46:25', '2026-04-11 15:47:00', NULL),
(24, 8, '090/008/SMAN3/IV/2026', '2026-04-12', 'Kepala Sekolah', 'Kegiatan KSL', '090', 'Belum diarsipkan secara fisik', 1, 2, 'TTE_SK_20260412_000558_15.pdf', 1, 'Approved', '2026-04-11 16:05:14', '2026-04-11 16:06:14', NULL),
(25, 9, '800/009/SMAN3/IV/2026', '2026-04-12', 'Kepala Sekolah', 'Kegiatan LKS', '800', 'Belum diarsipkan secara fisik', 1, 2, 'TTE_SK_20260412_001348_66.pdf', 1, 'Approved', '2026-04-11 16:13:19', '2026-04-11 16:14:03', NULL),
(26, 10, '090/010/SMAN3/IV/2026', '2026-04-12', 'Kepala Sekolah', 'Kegiatan KSL', '090', 'Belum diarsipkan secara fisik', 1, 2, 'TTE_SK_20260412_002505_64.pdf', 1, 'Approved', '2026-04-11 16:24:37', '2026-04-11 16:25:34', NULL),
(27, 11, '800/011/SMAN3/IV/2026', '2026-04-12', 'Kepala Sekolah', 'Rapat', '800', 'Belum diarsipkan secara fisik', 1, 2, 'TTE_SK_20260412_003031_39.pdf', 1, 'Approved', '2026-04-11 16:30:13', '2026-04-11 16:30:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `surat_masuk`
--

CREATE TABLE `surat_masuk` (
  `id` bigint NOT NULL,
  `nomor_surat` varchar(100) NOT NULL,
  `tanggal_surat` date NOT NULL,
  `tanggal_terima` date NOT NULL,
  `pengirim` varchar(150) NOT NULL,
  `perihal` text NOT NULL,
  `klasifikasi` varchar(50) DEFAULT NULL,
  `lokasi_fisik` varchar(150) DEFAULT 'Belum diarsipkan secara fisik',
  `unit_tujuan_id` int DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_hash` varchar(255) DEFAULT NULL,
  `ocr_text` longtext,
  `status_workflow` enum('Baru','Disposisi','Selesai','Diarsipkan') DEFAULT 'Baru',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `surat_masuk`
--

INSERT INTO `surat_masuk` (`id`, `nomor_surat`, `tanggal_surat`, `tanggal_terima`, `pengirim`, `perihal`, `klasifikasi`, `lokasi_fisik`, `unit_tujuan_id`, `file_path`, `file_hash`, `ocr_text`, `status_workflow`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '02.004/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-10', '2026-03-15', 'UKM Risti UNG', 'Permohonan Peminjaman', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1773564549.pdf', '', NULL, 'Baru', 1, '2026-03-15 08:49:09', '2026-03-15 08:50:50', '2026-03-15 08:50:50'),
(2, '02.004/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-10', '2026-03-15', 'UKM Risti UNG', 'Permohonan Peminjaman', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1773564676.pdf', '7060f7d94d413da48049a72383cd7e7bf6398950', NULL, 'Selesai', 1, '2026-03-15 08:51:16', '2026-03-15 13:52:56', NULL),
(3, '02.005/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-11', '2026-03-15', 'UKM Risti UNG', 'Permohonan Peminjaman Aula Sekolah', 'Biasa', 'Belum diarsipkan secara fisik', 6, 'SM_1773583578.pdf', '7060f7d94d413da48049a72383cd7e7bf6398950', NULL, 'Selesai', 1, '2026-03-15 14:06:18', '2026-03-15 14:08:29', NULL),
(4, '08.006/PP-OPREC/UKM-Risti/UNG/III/2026', '2026-03-14', '2026-03-15', 'UKM Risti UNG', 'Surat Permohonan Peminjaman Lab TKJ dan Lab RPL', 'Penting', 'Belum diarsipkan secara fisik', 2, 'SM_1773583932.pdf', '7060f7d94d413da48049a72383cd7e7bf6398950', NULL, 'Selesai', 1, '2026-03-15 14:12:12', '2026-03-15 14:21:28', NULL),
(5, '08.007/PP-OPREC/UKM-Risti/UNG/III/2026', '2026-03-15', '2026-03-15', 'UKM Risti UNG', 'peminjaman aula', 'Rahasia', 'Belum diarsipkan secara fisik', 2, 'SM_1773585231.pdf', '7060f7d94d413da48049a72383cd7e7bf6398950', NULL, 'Selesai', 1, '2026-03-15 14:33:51', '2026-03-15 14:38:24', NULL),
(6, '01.001/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-15', '2026-03-15', 'UKM Risti UNG', 'sosialisasi dari Puskesmas ', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1773587478.pdf', '7060f7d94d413da48049a72383cd7e7bf6398950', NULL, 'Selesai', 1, '2026-03-15 15:11:18', '2026-03-15 15:26:53', NULL),
(7, '01.002/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-16', '2026-03-16', 'UKM Risti UNG', 'Sosialisasi Siswa kelas XII', 'Penting', 'Belum diarsipkan secara fisik', 2, 'SM_1773588749.pdf', '7060f7d94d413da48049a72383cd7e7bf6398950', NULL, 'Selesai', 1, '2026-03-15 15:32:29', '2026-03-15 15:36:10', NULL),
(8, 'B/367/UN47.b5/PK.01.06/2026', '2026-03-06', '2026-03-16', 'Dekan Fakultas Teknik, Universitas Negeri Gorontalo', 'Permohonan pengambilan data', 'Penting', 'Belum diarsipkan secara fisik', 2, 'SM_1773657445.pdf', '7060f7d94d413da48049a72383cd7e7bf6398950', NULL, 'Selesai', 1, '2026-03-16 10:37:25', '2026-03-16 10:43:19', NULL),
(9, 'B/368/UN47.b5/PK.01.06/2026', '2026-03-07', '2026-03-16', 'Dekan Fakultas Teknik, Universitas Negeri Gorontalo', 'Permohonan Pengambilan Data', 'Penting', 'Belum diarsipkan secara fisik', 2, 'SM_1773657660.pdf', '7060f7d94d413da48049a72383cd7e7bf6398950', NULL, 'Selesai', 1, '2026-03-16 10:41:00', '2026-03-16 10:44:26', NULL),
(10, '003/C/11.029-11.030/PTB-A.05/III/2026', '2026-03-15', '2026-03-16', 'KWARRAN POPTIM', 'Permohonan Biaya Peran Saka Nasional 2026', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1773680078.png', 'fc8541136db5ba7c98c33c2c73d91b4549135b52', 'GERAKAN PRAMUKA\nKWARTIR RANTING CIMALAKA\n\nSekretariat Komplek Perkantoran KecametanCimalake-Sumedang 45359\n\nNomor 033. /09.11.22-€ ‘Sumedang, 31 Mei 2011\nLampiran — : 1 Bundel\nPeri Permohonan Biaya Jambore Nasional 2011\n\nYang terhormat,\nBapak Camat Cimalaka\nSelaku Ketua Mabiran\n\nAssalamu’alaikum,wrwb\nSalam Pramuka\n\nDieritahukan dengan hormat, bahwa Jambore Nasional 2011 akan dilaksanakan pada\ntanggal 2-9 Juli 2011 di Kabupaten Ogan Komering llir (OKI) Propinsi Sumatera Selatan\nKwartir Ranting Cimalaka akan mengirimkan 2 orang peserta didik (1 PA dan 1 PI) untuk\nmengikuti kegiatan tersebut. Biaya kegiatan tersebut sebesar Rp. 7.170,000,- untuk\n\njaftaran, Konsumsi, perlengkapan regu, latihan dl\nBerdasarkan hal terscbut.kami mohon Bapak dapat memberikan dana sebesar Rp.\n\n1.000.000, (Satu Juta Rupiah) untuk kegiatan tersebut.\nDemikian surat permohonan ini kami sampaikan, atas perhatian dan kerjasamanya\n\nami ucapkan terima kasih\n\n‘Wassalamu’alaikum,wr.wb\n\nKwartir Ranting Cimalaka,\nKetua,\n\nPd, M.PdL', 'Selesai', 1, '2026-03-16 16:54:39', '2026-03-16 16:55:38', NULL),
(11, '02.014/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-10', '2026-03-16', 'KWARRAN POPTIM', 'testing', 'Penting', 'Belum diarsipkan secara fisik', 2, 'SM_1773682141.pdf', 'db2d460cd68ff1858971e0d4918c5b4364418bad', 'Hai Saya refly batalipu yang sedang belajar mengembagkan sistem persuratan di smk n 4 gorontalo. Saat ini aku secang mencoba fitur ORC yang mengubah file pdf hasil save as dari word', 'Selesai', 1, '2026-03-16 17:29:01', '2026-03-16 17:29:35', NULL),
(12, '07.001/PP-PRAMUKA/SMKN4GTLO/III/2026', '2026-03-16', '2026-03-17', 'KWARRAN POPTIM', 'Permohonan Biaya Jambore Nasional 2027', 'Rahasia', 'Belum diarsipkan secara fisik', 2, 'SM_1773685935.pdf', '64eccdcf320cfc4e7859947f9d8ad5ba205c7fcb', '', 'Selesai', 1, '2026-03-16 18:32:16', '2026-03-16 18:33:22', NULL),
(13, '07.002/PP-PRAMUKA/SMKN4GTMLO/III/2026', '2026-03-16', '2026-03-17', 'KWARRAN POPTIM', 'Iuran pramuka', 'Penting', 'Belum diarsipkan secara fisik', 2, 'SM_1773686524.pdf', '64eccdcf320cfc4e7859947f9d8ad5ba205c7fcb', 'GERAKAN PRAMUKA\nKWARTIR RANTING CIMALAKA\n\nSekretariat: Komplek Perkantoran Kecamatan Cimalaka-Sumedang 45353\n\nNomor : /09.11.22-C Sumedang, 31 Mei 2011\nPerihal : luran Jambore Nasional\n\nYang terhormat,\nKepala Sekolah\n\nSelaku Ketua Mabigus\n\nAssalamu’alaikum.wr,wb\nSalam Pramuka\n\nDiberitahukan dengan hormat, bahwa Jambore Nasional 2011 akan dilaksanakan pada\ntanggal 2-9 Juli 2011 di Kabupaten Ogan Komering Ilir (OKI) Propinsi Sumatera Selatan.\nKwartir Ranting Cimalaka akan mengirimkan 2 orang peserta didik (1 PA dan 1 PI) untuk\nmengikuti kegiatan tersebut. Biaya kegiatan tersebut sebesar Rp. 5.000.000,- untuk\npendaftaran, konsumsi, perlengkapan regu, latihan dll.\n\nBerdasarkan rapat pimpinan Kwarran, biaya terscbut akan ditanggung bersama 47\nGugus Depan yang ada di Kwartir Ranting Cimalaka. Besarnya iuran Gugus Depan Rp.\n250.000,- . untuk pembayaran paling lambat tanggal 8 Juni 2011, melalui Kak Dra Juangsih (\nKepala SDN Margamukti) atau ke Kak Ismail Farid, S.Pd. M.Pd.1. ( MAN 1 Sumedang).\n\nDemikian surat permohonan ini kami sampaikan, atas perhatian dan kerjasamanya\nkami ucapkan terima kasih.\n\nWassalamu’alaikum.wr.wb\nKwartir Ranting Cimalaka,\nKetua,\n\nIsmail Farid. 8.Pd, M.Pd.l\n\nTembusan\n1. Yth. Ketua Kwarcab Sumedang\n2. Yth. Camat Cimalaka Selaku Ketua Mabiran', 'Selesai', 1, '2026-03-16 18:42:08', '2026-03-16 18:42:49', NULL),
(14, '02.014/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-12', '2026-03-17', 'KWARRAN POPTIM', 'permohnan', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1773716172.pdf', 'db2d460cd68ff1858971e0d4918c5b4364418bad', 'Hai Saya refly batalipu yang sedang belajar mengembagkan sistem persuratan di smk n 4 gorontalo. Saat ini aku secang mencoba fitur ORC yang mengubah file pdf hasil save as dari word', 'Selesai', 1, '2026-03-17 02:56:13', '2026-03-17 03:13:14', NULL),
(15, '02.005/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-17', '2026-03-17', 'UKM Risti UNG', 'as', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1773717440.pdf', '64eccdcf320cfc4e7859947f9d8ad5ba205c7fcb', 'GERAKAN PRAMUKA\nKWARTIR RANTING CIMALAKA\n\nSekretariat: Komplek Perkantoran Kecamatan Cimalaka-Sumedang 45353\n\nNomor : /09.11.22-C Sumedang, 31 Mei 2011\nPerihal : luran Jambore Nasional\n\nYang terhormat,\nKepala Sekolah\n\nSelaku Ketua Mabigus\n\nAssalamu’alaikum.wr,wb\nSalam Pramuka\n\nDiberitahukan dengan hormat, bahwa Jambore Nasional 2011 akan dilaksanakan pada\ntanggal 2-9 Juli 2011 di Kabupaten Ogan Komering Ilir (OKI) Propinsi Sumatera Selatan.\nKwartir Ranting Cimalaka akan mengirimkan 2 orang peserta didik (1 PA dan 1 PI) untuk\nmengikuti kegiatan tersebut. Biaya kegiatan tersebut sebesar Rp. 5.000.000,- untuk\npendaftaran, konsumsi, perlengkapan regu, latihan dll.\n\nBerdasarkan rapat pimpinan Kwarran, biaya terscbut akan ditanggung bersama 47\nGugus Depan yang ada di Kwartir Ranting Cimalaka. Besarnya iuran Gugus Depan Rp.\n250.000,- . untuk pembayaran paling lambat tanggal 8 Juni 2011, melalui Kak Dra Juangsih (\nKepala SDN Margamukti) atau ke Kak Ismail Farid, S.Pd. M.Pd.1. ( MAN 1 Sumedang).\n\nDemikian surat permohonan ini kami sampaikan, atas perhatian dan kerjasamanya\nkami ucapkan terima kasih.\n\nWassalamu’alaikum.wr.wb\nKwartir Ranting Cimalaka,\nKetua,\n\nIsmail Farid. 8.Pd, M.Pd.l\n\nTembusan\n1. Yth. Ketua Kwarcab Sumedang\n2. Yth. Camat Cimalaka Selaku Ketua Mabiran', 'Selesai', 1, '2026-03-17 03:17:27', '2026-03-17 03:18:03', NULL),
(16, '02.044/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-18', '2026-03-18', 'UKM Risti UNG', 'testing', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1773821813.pdf', 'db2d460cd68ff1858971e0d4918c5b4364418bad', 'Hai Saya refly batalipu yang sedang belajar mengembagkan sistem persuratan di smk n 4 gorontalo. Saat ini aku secang mencoba fitur ORC yang mengubah file pdf hasil save as dari word', 'Selesai', 1, '2026-03-18 08:16:54', '2026-03-18 18:05:35', NULL),
(17, '09.004/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-17', '2026-03-18', 'UKM Risti UNG', 'Moohonnnn', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1773826716.pdf', 'db2d460cd68ff1858971e0d4918c5b4364418bad', 'Hai Saya refly batalipu yang sedang belajar mengembagkan sistem persuratan di smk n 4 gorontalo. Saat ini aku secang mencoba fitur ORC yang mengubah file pdf hasil save as dari word', 'Selesai', 1, '2026-03-18 09:38:36', '2026-03-18 18:03:45', NULL),
(18, '09.004/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-17', '2026-03-18', 'UKM Risti UNG', 'Moohonnnn', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1773826741.pdf', 'db2d460cd68ff1858971e0d4918c5b4364418bad', 'Hai Saya refly batalipu yang sedang belajar mengembagkan sistem persuratan di smk n 4 gorontalo. Saat ini aku secang mencoba fitur ORC yang mengubah file pdf hasil save as dari word', 'Selesai', 1, '2026-03-18 09:39:01', '2026-03-18 18:05:15', NULL),
(19, '02.105/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-17', '2026-03-18', 'tess', 'testing fauzan', 'Biasa', 'Belum diarsipkan secara fisik', 7, 'SM_1773831574.pdf', 'db2d460cd68ff1858971e0d4918c5b4364418bad', 'Hai Saya refly batalipu yang sedang belajar mengembagkan sistem persuratan di smk n 4 gorontalo. Saat ini aku secang mencoba fitur ORC yang mengubah file pdf hasil save as dari word', 'Selesai', 1, '2026-03-18 10:59:35', '2026-03-18 18:05:29', NULL),
(20, '02.008/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-18', '2026-03-18', 'KWARRAN POPTIM', 'testing', 'Biasa', 'Belum diarsipkan secara fisik', 7, 'SM_1773832087.pdf', 'db2d460cd68ff1858971e0d4918c5b4364418bad', 'Hai Saya refly batalipu yang sedang belajar mengembagkan sistem persuratan di smk n 4 gorontalo. Saat ini aku secang mencoba fitur ORC yang mengubah file pdf hasil save as dari word', 'Selesai', 1, '2026-03-18 11:08:07', '2026-03-18 18:03:38', NULL),
(21, '07.004/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-18', '2026-03-18', 'UKM Risti UNG', 'pp', 'Biasa', 'Belum diarsipkan secara fisik', 7, 'SM_1773832283.pdf', 'db2d460cd68ff1858971e0d4918c5b4364418bad', 'Hai Saya refly batalipu yang sedang belajar mengembagkan sistem persuratan di smk n 4 gorontalo. Saat ini aku secang mencoba fitur ORC yang mengubah file pdf hasil save as dari word', 'Selesai', 1, '2026-03-18 11:11:23', '2026-03-18 18:03:31', NULL),
(22, '02.034/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-18', '2026-03-18', 'refly', 'permohonan', 'Biasa', 'Belum diarsipkan secara fisik', 7, 'SM_1773832814.pdf', 'db2d460cd68ff1858971e0d4918c5b4364418bad', 'Hai Saya refly batalipu yang sedang belajar mengembagkan sistem persuratan di smk n 4 gorontalo. Saat ini aku secang mencoba fitur ORC yang mengubah file pdf hasil save as dari word', 'Selesai', 1, '2026-03-18 11:20:15', '2026-03-18 18:03:24', NULL),
(23, '02.034/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-18', '2026-03-18', 'refly', 'permohonan', 'Biasa', 'Belum diarsipkan secara fisik', 7, 'SM_1773832915.pdf', 'db2d460cd68ff1858971e0d4918c5b4364418bad', 'Hai Saya refly batalipu yang sedang belajar mengembagkan sistem persuratan di smk n 4 gorontalo. Saat ini aku secang mencoba fitur ORC yang mengubah file pdf hasil save as dari word', 'Baru', 1, '2026-03-18 11:21:55', '2026-03-18 11:27:45', '2026-03-18 11:27:45'),
(24, '02.034/PP-MUBES/UKM-Risti/UNG/III/2026', '2026-03-18', '2026-03-18', 'refly', 'permohonan', 'Biasa', 'Belum diarsipkan secara fisik', 7, 'SM_1773832984.pdf', 'db2d460cd68ff1858971e0d4918c5b4364418bad', 'Hai Saya refly batalipu yang sedang belajar mengembagkan sistem persuratan di smk n 4 gorontalo. Saat ini aku secang mencoba fitur ORC yang mengubah file pdf hasil save as dari word', 'Selesai', 1, '2026-03-18 11:23:04', '2026-03-18 18:04:58', NULL),
(25, '09.027/PP-OPREC/UKM-Risti/UNG/III/2026', '2026-03-18', '2026-03-18', 'tess', 'pp', 'Biasa', 'Belum diarsipkan secara fisik', 7, 'SM_1773833105.pdf', 'db2d460cd68ff1858971e0d4918c5b4364418bad', 'Hai Saya refly batalipu yang sedang belajar mengembagkan sistem persuratan di smk n 4 gorontalo. Saat ini aku secang mencoba fitur ORC yang mengubah file pdf hasil save as dari word', 'Selesai', 1, '2026-03-18 11:25:05', '2026-03-18 18:03:16', NULL),
(26, '09.008/PP-OPREC/UKM-Risti/UNG/III/2026', '2026-03-18', '2026-03-18', 'UKM Risti UNG', 'p', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1773833350.pdf', 'db2d460cd68ff1858971e0d4918c5b4364418bad', 'Hai Saya refly batalipu yang sedang belajar mengembagkan sistem persuratan di smk n 4 gorontalo. Saat ini aku secang mencoba fitur ORC yang mengubah file pdf hasil save as dari word', 'Selesai', 1, '2026-03-18 11:29:10', '2026-03-18 18:03:10', NULL),
(27, '04.001/PERMOHONAN/SMKN4GLTO/III2026', '2026-03-18', '2026-03-18', 'permohnan', 'testing', 'Biasa', 'Belum diarsipkan secara fisik', 7, 'SM_1773833753.pdf', 'db2d460cd68ff1858971e0d4918c5b4364418bad', 'Hai Saya refly batalipu yang sedang belajar mengembagkan sistem persuratan di smk n 4 gorontalo. Saat ini aku secang mencoba fitur ORC yang mengubah file pdf hasil save as dari word', 'Selesai', 1, '2026-03-18 11:35:53', '2026-03-18 18:03:03', NULL),
(28, '04.003/CUTI/SMKN4GLTO/III2026', '2026-03-19', '2026-03-19', 'SAYA', 'Permohonan Cuti Tahunan', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1773857206.pdf', '7b056f783ac316a764cbb1c770ea6656152ed930', 'Gorontalo, 16 Maret 2026 Kepada Yth. Kepala Sekolah SMKN 4 GORONTALO di tempat Perihal: Permohonan Cuti Melahirkan Dengan hormat, Saya yang bertanda tangan di bawah ini: Nama : Lina Maharani Jabatan : Guru Honorer Mata Pelajaran : Matematika Melalui surat ini, saya mengajukan permohonan cuti melahirkan mulai tanggal 18 Maret 2026 hingga 18 Mei 2026. Permohonan ini saya sampaikan karena kondisi kehamilan yang telah memasuki masa persalinan dan membutuhkan waktu pemulihan yang cukup setelah melahirkan. Selama masa cuti, saya akan memastikan seluruh materi pembelajaran, penilaian, dan administrasi yang masih berjalan telah diselesaikan atau diserahkan kepada guru pengganti. Saya juga siap memberikan informasi tambahan apabila dibutuhkan demi kelancaran proses belajar mengajar. Demikian permohonan cuti ini saya ajukan dengan sebenar-benarnya. Saya berharap sekolah dapat memberikan izin sesuai ketentuan yang berlaku. Atas perhatian dan kebijaksanaannya, saya ucapkan terima kasih. Hormat saya, Lina Maharani', 'Disposisi', 1, '2026-03-18 18:06:47', '2026-03-18 18:33:29', NULL),
(29, '04.004/CUTI/SMKN4GTLO/III/2026', '2026-03-18', '2026-03-19', 'AKU', 'Permohonan Cuti Puasa', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1773858280.pdf', '7b056f783ac316a764cbb1c770ea6656152ed930', 'Gorontalo, 16 Maret 2026 Kepada Yth. Kepala Sekolah SMKN 4 GORONTALO di tempat Perihal: Permohonan Cuti Melahirkan Dengan hormat, Saya yang bertanda tangan di bawah ini: Nama : Lina Maharani Jabatan : Guru Honorer Mata Pelajaran : Matematika Melalui surat ini, saya mengajukan permohonan cuti melahirkan mulai tanggal 18 Maret 2026 hingga 18 Mei 2026. Permohonan ini saya sampaikan karena kondisi kehamilan yang telah memasuki masa persalinan dan membutuhkan waktu pemulihan yang cukup setelah melahirkan. Selama masa cuti, saya akan memastikan seluruh materi pembelajaran, penilaian, dan administrasi yang masih berjalan telah diselesaikan atau diserahkan kepada guru pengganti. Saya juga siap memberikan informasi tambahan apabila dibutuhkan demi kelancaran proses belajar mengajar. Demikian permohonan cuti ini saya ajukan dengan sebenar-benarnya. Saya berharap sekolah dapat memberikan izin sesuai ketentuan yang berlaku. Atas perhatian dan kebijaksanaannya, saya ucapkan terima kasih. Hormat saya, Lina Maharani', 'Disposisi', 1, '2026-03-18 18:24:40', '2026-03-18 18:31:57', NULL),
(30, '04.005/CUTI/SMKN4GTLO/III/2026', '2026-03-19', '2026-03-19', 'SAYA', 'Permohonan Cuti Puasa', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1773858992.pdf', 'db2d460cd68ff1858971e0d4918c5b4364418bad', 'Hai Saya refly batalipu yang sedang belajar mengembagkan sistem persuratan di smk n 4 gorontalo. Saat ini aku secang mencoba fitur ORC yang mengubah file pdf hasil save as dari word', 'Disposisi', 1, '2026-03-18 18:36:32', '2026-03-18 18:36:53', NULL),
(31, '04.006/CUTI/SMKN4GTLO/III/2026', '2026-03-19', '2026-03-19', 'SAYA', 'Permohonan Cuti Lebaran', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1773859157.pdf', '7b056f783ac316a764cbb1c770ea6656152ed930', 'Gorontalo, 16 Maret 2026 Kepada Yth. Kepala Sekolah SMKN 4 GORONTALO di tempat Perihal: Permohonan Cuti Melahirkan Dengan hormat, Saya yang bertanda tangan di bawah ini: Nama : Lina Maharani Jabatan : Guru Honorer Mata Pelajaran : Matematika Melalui surat ini, saya mengajukan permohonan cuti melahirkan mulai tanggal 18 Maret 2026 hingga 18 Mei 2026. Permohonan ini saya sampaikan karena kondisi kehamilan yang telah memasuki masa persalinan dan membutuhkan waktu pemulihan yang cukup setelah melahirkan. Selama masa cuti, saya akan memastikan seluruh materi pembelajaran, penilaian, dan administrasi yang masih berjalan telah diselesaikan atau diserahkan kepada guru pengganti. Saya juga siap memberikan informasi tambahan apabila dibutuhkan demi kelancaran proses belajar mengajar. Demikian permohonan cuti ini saya ajukan dengan sebenar-benarnya. Saya berharap sekolah dapat memberikan izin sesuai ketentuan yang berlaku. Atas perhatian dan kebijaksanaannya, saya ucapkan terima kasih. Hormat saya, Lina Maharani', 'Disposisi', 1, '2026-03-18 18:39:17', '2026-03-18 18:39:42', NULL),
(32, '04.007/CUTI/SMKN4GTLO/III/2026', '2026-03-19', '2026-03-19', 'SAYA', 'pp', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1773859589.pdf', '7b056f783ac316a764cbb1c770ea6656152ed930', 'Gorontalo, 16 Maret 2026 Kepada Yth. Kepala Sekolah SMKN 4 GORONTALO di tempat Perihal: Permohonan Cuti Melahirkan Dengan hormat, Saya yang bertanda tangan di bawah ini: Nama : Lina Maharani Jabatan : Guru Honorer Mata Pelajaran : Matematika Melalui surat ini, saya mengajukan permohonan cuti melahirkan mulai tanggal 18 Maret 2026 hingga 18 Mei 2026. Permohonan ini saya sampaikan karena kondisi kehamilan yang telah memasuki masa persalinan dan membutuhkan waktu pemulihan yang cukup setelah melahirkan. Selama masa cuti, saya akan memastikan seluruh materi pembelajaran, penilaian, dan administrasi yang masih berjalan telah diselesaikan atau diserahkan kepada guru pengganti. Saya juga siap memberikan informasi tambahan apabila dibutuhkan demi kelancaran proses belajar mengajar. Demikian permohonan cuti ini saya ajukan dengan sebenar-benarnya. Saya berharap sekolah dapat memberikan izin sesuai ketentuan yang berlaku. Atas perhatian dan kebijaksanaannya, saya ucapkan terima kasih. Hormat saya, Lina Maharani', 'Selesai', 1, '2026-03-18 18:46:30', '2026-04-10 17:32:38', NULL),
(33, '04.008/CUTI/SMKN4GTLO/III/2026', '2026-03-19', '2026-03-19', 'Guru TIK', 'Permohonan Cuti', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1773859823.pdf', '7b056f783ac316a764cbb1c770ea6656152ed930', 'Gorontalo, 16 Maret 2026 Kepada Yth. Kepala Sekolah SMKN 4 GORONTALO di tempat Perihal: Permohonan Cuti Melahirkan Dengan hormat, Saya yang bertanda tangan di bawah ini: Nama : Lina Maharani Jabatan : Guru Honorer Mata Pelajaran : Matematika Melalui surat ini, saya mengajukan permohonan cuti melahirkan mulai tanggal 18 Maret 2026 hingga 18 Mei 2026. Permohonan ini saya sampaikan karena kondisi kehamilan yang telah memasuki masa persalinan dan membutuhkan waktu pemulihan yang cukup setelah melahirkan. Selama masa cuti, saya akan memastikan seluruh materi pembelajaran, penilaian, dan administrasi yang masih berjalan telah diselesaikan atau diserahkan kepada guru pengganti. Saya juga siap memberikan informasi tambahan apabila dibutuhkan demi kelancaran proses belajar mengajar. Demikian permohonan cuti ini saya ajukan dengan sebenar-benarnya. Saya berharap sekolah dapat memberikan izin sesuai ketentuan yang berlaku. Atas perhatian dan kebijaksanaannya, saya ucapkan terima kasih. Hormat saya, Lina Maharani', 'Selesai', 1, '2026-03-18 18:50:23', '2026-03-20 14:03:24', NULL),
(34, '04.009/CUTI/SMKN4GTLO/III/2026', '2026-03-20', '2026-03-18', 'Dinas Pemeritah Provinsi', 'Edaran Cuti Ramadhan', 'Penting', 'Belum diarsipkan secara fisik', 2, 'SM_1773860800.pdf', '898e6edac48c589f85d9a7c2bd6ccaaf5e9f03d3', 'Menimbang\n\nMengingat\n\nKEPUTUSAN BERSAMA\nMENTERI AGAMA, MENTERI KETENAGAKERJAAN,\nDAN MENTERI PENDAYAGUNAAN APARATUR NEGARA\nDAN REFORMASI BIROKRASI\nREPUBLIK INDONESIA\n\nNOMOR : 1497 TAHUN 2025\n\nNOMOR : 2 TAHUN 2025\nNOMOR : 5  TAHUN 2025\nTENTANG\n\nHARI LIBUR NASIONAL DAN CUTI BERSAMA TAHUN 2026\nDENGAN RAHMAT TUHAN YANG MAHA ESA\n\nMENTERI AGAMA, MENTERI KETENAGAKERJAAN,\nDAN MENTERI PENDAYAGUNAAN APARATUR NEGARA\n\nDAN REFORMASI BIROKRASI,\n\nbahwa dalam rangka efisiensi dan efektivitas hari kerja serta\nmemberi pedoman bagi instansi pemerintah dan swasta\ndalam melaksanakan hari libur nasional dan cuti bersama\ntahun 2026, perlu menetapkan hari libur nasional dan cuti\nbersama tahun 2026;\n\nbahwa berdasarkan pertimbangan sebagaimana dimaksud\ndalam huruf a, perlu menetapkan Keputusan Bersama\nMenteri Agama, Menteri Ketenagakerjaan, dan Menteri\nPendayagunaan Aparatur Negara dan Reformasi Birokrasi\ntentang Hari Libur Nasional dan Cuti Bersama Tahun 2026;\n\nUndang-Undang Nomor 20 Tahun 2023 tentang Aparatur\nSipil Negara (Lembaran Negara Republik Indonesia Tahun\n2023 Nomor 141, Tambahan Lembaran Negara Republik\nIndonesia Nomor 6897);\n\nPeraturan Pemerintah Nomor 11 Tahun 2017 tentang\nManajemen Pegawai Negeri Sipil (hkembaran Negara Republik\nIndonesia Tahun 2017 Nomor 63, Tambahan Lembaran\nNegara Republik Indonesia Nomor 6037) sebagaimana telah\ndiubah dengan Peraturan Pemerintah Nomor 17 Tahun 2020\ntentang Perubahan atas Peraturan Pemerintah Nomor 11\nTahun 2017 tentang Manajemen Pegawai Negeri Sipil\n(Lembaran Negara Republik Indonesia Tahun 2020 Nomor\n68, Tambahan Lembaran Negara Republik Indonesia Nomor\n6477);\n\n3. Peraturan ...', 'Disposisi', 1, '2026-03-18 19:06:49', '2026-03-18 19:09:08', NULL),
(35, '04.010/CUTI/SMKN4GTLO/III/2026', '2026-03-17', '2026-03-18', 'Dinas Pendidikan Provinsi Gorontalo', 'Edaran Libur Ramadhan', 'Penting', 'Belum diarsipkan secara fisik', 2, 'SM_1773861680.pdf', '898e6edac48c589f85d9a7c2bd6ccaaf5e9f03d3', 'Menimbang\n\nMengingat\n\nKEPUTUSAN BERSAMA\nMENTERI AGAMA, MENTERI KETENAGAKERJAAN,\nDAN MENTERI PENDAYAGUNAAN APARATUR NEGARA\nDAN REFORMASI BIROKRASI\nREPUBLIK INDONESIA\n\nNOMOR : 1497 TAHUN 2025\n\nNOMOR : 2 TAHUN 2025\nNOMOR : 5  TAHUN 2025\nTENTANG\n\nHARI LIBUR NASIONAL DAN CUTI BERSAMA TAHUN 2026\nDENGAN RAHMAT TUHAN YANG MAHA ESA\n\nMENTERI AGAMA, MENTERI KETENAGAKERJAAN,\nDAN MENTERI PENDAYAGUNAAN APARATUR NEGARA\n\nDAN REFORMASI BIROKRASI,\n\nbahwa dalam rangka efisiensi dan efektivitas hari kerja serta\nmemberi pedoman bagi instansi pemerintah dan swasta\ndalam melaksanakan hari libur nasional dan cuti bersama\ntahun 2026, perlu menetapkan hari libur nasional dan cuti\nbersama tahun 2026;\n\nbahwa berdasarkan pertimbangan sebagaimana dimaksud\ndalam huruf a, perlu menetapkan Keputusan Bersama\nMenteri Agama, Menteri Ketenagakerjaan, dan Menteri\nPendayagunaan Aparatur Negara dan Reformasi Birokrasi\ntentang Hari Libur Nasional dan Cuti Bersama Tahun 2026;\n\nUndang-Undang Nomor 20 Tahun 2023 tentang Aparatur\nSipil Negara (Lembaran Negara Republik Indonesia Tahun\n2023 Nomor 141, Tambahan Lembaran Negara Republik\nIndonesia Nomor 6897);\n\nPeraturan Pemerintah Nomor 11 Tahun 2017 tentang\nManajemen Pegawai Negeri Sipil (hkembaran Negara Republik\nIndonesia Tahun 2017 Nomor 63, Tambahan Lembaran\nNegara Republik Indonesia Nomor 6037) sebagaimana telah\ndiubah dengan Peraturan Pemerintah Nomor 17 Tahun 2020\ntentang Perubahan atas Peraturan Pemerintah Nomor 11\nTahun 2017 tentang Manajemen Pegawai Negeri Sipil\n(Lembaran Negara Republik Indonesia Tahun 2020 Nomor\n68, Tambahan Lembaran Negara Republik Indonesia Nomor\n6477);\n\n3. Peraturan ...', 'Disposisi', 1, '2026-03-18 19:21:26', '2026-03-18 19:22:40', NULL),
(36, '04.011/CUTI/SMKN4GTLO/III/2026', '2026-03-17', '2026-03-18', 'Dinas Pendidikan Provinsi Gorontalo', 'Edaran Cuti', 'Penting', 'Belum diarsipkan secara fisik', 2, 'SM_1773863153.pdf', '898e6edac48c589f85d9a7c2bd6ccaaf5e9f03d3', 'Menimbang\n\nMengingat\n\nKEPUTUSAN BERSAMA\nMENTERI AGAMA, MENTERI KETENAGAKERJAAN,\nDAN MENTERI PENDAYAGUNAAN APARATUR NEGARA\nDAN REFORMASI BIROKRASI\nREPUBLIK INDONESIA\n\nNOMOR : 1497 TAHUN 2025\n\nNOMOR : 2 TAHUN 2025\nNOMOR : 5  TAHUN 2025\nTENTANG\n\nHARI LIBUR NASIONAL DAN CUTI BERSAMA TAHUN 2026\nDENGAN RAHMAT TUHAN YANG MAHA ESA\n\nMENTERI AGAMA, MENTERI KETENAGAKERJAAN,\nDAN MENTERI PENDAYAGUNAAN APARATUR NEGARA\n\nDAN REFORMASI BIROKRASI,\n\nbahwa dalam rangka efisiensi dan efektivitas hari kerja serta\nmemberi pedoman bagi instansi pemerintah dan swasta\ndalam melaksanakan hari libur nasional dan cuti bersama\ntahun 2026, perlu menetapkan hari libur nasional dan cuti\nbersama tahun 2026;\n\nbahwa berdasarkan pertimbangan sebagaimana dimaksud\ndalam huruf a, perlu menetapkan Keputusan Bersama\nMenteri Agama, Menteri Ketenagakerjaan, dan Menteri\nPendayagunaan Aparatur Negara dan Reformasi Birokrasi\ntentang Hari Libur Nasional dan Cuti Bersama Tahun 2026;\n\nUndang-Undang Nomor 20 Tahun 2023 tentang Aparatur\nSipil Negara (Lembaran Negara Republik Indonesia Tahun\n2023 Nomor 141, Tambahan Lembaran Negara Republik\nIndonesia Nomor 6897);\n\nPeraturan Pemerintah Nomor 11 Tahun 2017 tentang\nManajemen Pegawai Negeri Sipil (hkembaran Negara Republik\nIndonesia Tahun 2017 Nomor 63, Tambahan Lembaran\nNegara Republik Indonesia Nomor 6037) sebagaimana telah\ndiubah dengan Peraturan Pemerintah Nomor 17 Tahun 2020\ntentang Perubahan atas Peraturan Pemerintah Nomor 11\nTahun 2017 tentang Manajemen Pegawai Negeri Sipil\n(Lembaran Negara Republik Indonesia Tahun 2020 Nomor\n68, Tambahan Lembaran Negara Republik Indonesia Nomor\n6477);\n\n3. Peraturan ...', 'Selesai', 1, '2026-03-18 19:45:59', '2026-03-20 14:03:16', NULL),
(37, '06.007/RAPAT/SMKN4GTLO/III/2026', '2026-03-18', '2026-03-24', 'Dekan Fakultas Teknik, Universitas Negeri Gorontalo', 'Permohonan Pengambilan Data', 'Biasa', 'Belum diarsipkan secara fisik', 2, 'SM_1774359159.pdf', '64eccdcf320cfc4e7859947f9d8ad5ba205c7fcb', 'GERAKAN PRAMUKA\nKWARTIR RANTING CIMALAKA\n\nSekretariat: Komplek Perkantoran Kecamatan Cimalaka-Sumedang 45353\n\nNomor : /09.11.22-C Sumedang, 31 Mei 2011\nPerihal : luran Jambore Nasional\n\nYang terhormat,\nKepala Sekolah\n\nSelaku Ketua Mabigus\n\nAssalamu’alaikum.wr,wb\nSalam Pramuka\n\nDiberitahukan dengan hormat, bahwa Jambore Nasional 2011 akan dilaksanakan pada\ntanggal 2-9 Juli 2011 di Kabupaten Ogan Komering Ilir (OKI) Propinsi Sumatera Selatan.\nKwartir Ranting Cimalaka akan mengirimkan 2 orang peserta didik (1 PA dan 1 PI) untuk\nmengikuti kegiatan tersebut. Biaya kegiatan tersebut sebesar Rp. 5.000.000,- untuk\npendaftaran, konsumsi, perlengkapan regu, latihan dll.\n\nBerdasarkan rapat pimpinan Kwarran, biaya terscbut akan ditanggung bersama 47\nGugus Depan yang ada di Kwartir Ranting Cimalaka. Besarnya iuran Gugus Depan Rp.\n250.000,- . untuk pembayaran paling lambat tanggal 8 Juni 2011, melalui Kak Dra Juangsih (\nKepala SDN Margamukti) atau ke Kak Ismail Farid, S.Pd. M.Pd.1. ( MAN 1 Sumedang).\n\nDemikian surat permohonan ini kami sampaikan, atas perhatian dan kerjasamanya\nkami ucapkan terima kasih.\n\nWassalamu’alaikum.wr.wb\nKwartir Ranting Cimalaka,\nKetua,\n\nIsmail Farid. 8.Pd, M.Pd.l\n\nTembusan\n1. Yth. Ketua Kwarcab Sumedang\n2. Yth. Camat Cimalaka Selaku Ketua Mabiran', 'Selesai', 1, '2026-03-24 13:32:48', '2026-03-24 13:37:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `unit_kerja`
--

CREATE TABLE `unit_kerja` (
  `id` int NOT NULL,
  `nama_unit` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `unit_kerja`
--

INSERT INTO `unit_kerja` (`id`, `nama_unit`) VALUES
(1, 'Tata Usaha (TU)'),
(2, 'Kepala Sekolah'),
(3, 'Kurikulum'),
(4, 'Kesiswaan'),
(5, 'Humas & Kemitraan'),
(6, 'Sarana Prasarana'),
(7, 'Guru Mata Pelajaran');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nip` varchar(50) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `telegram_id` varchar(50) DEFAULT NULL,
  `role_id` int NOT NULL,
  `unit_id` int NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `remember_token` varchar(255) DEFAULT NULL,
  `plt_user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nip`, `nama_lengkap`, `telegram_id`, `role_id`, `unit_id`, `password_hash`, `is_active`, `remember_token`, `plt_user_id`) VALUES
(1, 'admin123', 'Administrator SIMPERS', '6285394338359', 1, 1, '$2y$10$SlLX6rB2smmALkB2bz95UefKNCk02kEeyT9SA91O2E4cuiNltaBx2', 1, NULL, NULL),
(2, 'kepsek01', 'Refly K Batalipu', '8438615025', 2, 2, '$2y$10$awGPsiilY/n1aWWq8ekam.jIaPaB.qpxxpl2JZZJvXo6bHaNMpJl.', 1, 'ab63d90a8e9a87875610e1671c100b80c3220d7dcfb38a698b1d967263d98146', NULL),
(3, 'waka123', 'Ghadiza M. Lompad, S.Kom', '5265651532', 3, 3, '$2y$10$OwnMMhmbJ7TTMgRTj2RkXuzuT5lH8I.BXRNHrhkU.R9bDeKRe2IUy', 1, NULL, NULL),
(4, 'waka456', 'Natasya P. Daud, S.Kom', '5674273336', 3, 4, '$2y$10$4WlrGbK1b4gauxP5BlvA/.jdM9uDRMe4J5cMJ.0qKZ5ifF1MTDHDW', 1, NULL, NULL),
(5, 'waka789', 'Moh. Fadel Nugraha Adam, S.Kom', '8438615027', 3, 6, '$2y$10$0FNpshcm5Wkt0s7Ls3hoHupHm8c0n9whiRFkrb67e63HXQZ28383y', 1, NULL, NULL),
(6, 'guru123', 'Ahmad Nabil R. Putra, S.Kom', '1279823797', 4, 7, '$2y$10$YNyMPwfr2a1sKgdSjdSJaukuE/C4wBb3F/eGHx2ZJVo1HS5M/MPnO', 1, NULL, NULL),
(7, 'guru456', 'Fikri Guamo', '6281326415155', 4, 7, '$2y$10$gteQXp2GYn6mFWQPT1OV6uVeJrZy3QKtHIevoXcHF5fZa6aJIB/8W', 1, NULL, NULL),
(8, 'guru789', 'Audi Marsabesi, S.Kom', '6766754683', 4, 7, '$2y$10$xYxJTTh9WVq24Aiqp5B/2u/c5TWS93uYHvWQvD87z490KmnpRWzOy', 1, NULL, NULL),
(9, 'guru1112', 'Rossa M. Molamahu, S.Kom', '6281244102467', 4, 7, '$2y$10$OZe2qAiYDcBaYhP2ZUC0UOozBsFEROzDx2fRbsQbrmb.rpGHcmh0K', 1, '37de15c99729a8e945723a10d509780b5662334ba779d6eb6527a3be591b0498', NULL),
(10, 'guru1345', 'Anwar Fauzan Aqil', '1724127615', 4, 7, '$2y$10$7n4voJi7EKyRSlBymuA2kuBju.3P6pRuGUy77gZ3Yv8/oXxp1GPaW', 1, NULL, NULL),
(11, 'guru1314', 'Desrinta Tri Dealova Taludio, S.Kom', '1795076466', 4, 7, '$2y$10$Ya/gSIBZFnC2Woj/J9Yw.ukPK/kmAcE1PbjP6Ks/Xv2SMHGpCfAx2', 1, NULL, NULL),
(12, 'guru1678', 'Fildzah Chairunisah abdul, S.Kom', '1569990053', 4, 7, '$2y$10$L57oS5le0gP8APizFz765ef09XQFuEk.v8yqRbd2.FS9d/cy26JMC', 1, NULL, NULL),
(13, 'waka1122', 'Sunarti Kadir,S.Kom', '1234511222', 3, 4, '$2y$10$Slp3BYv6bti8fIiizgX57eh.JoLoyuKmgJc8TSz2k0NgyJvMJAPa2', 1, '9e432895bab1e68dc529fdfc1fb7c11bb9a601a5f7fe26ebaecaea404906d95c', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `arsip`
--
ALTER TABLE `arsip`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_surat_ref` (`surat_ref_id`,`tipe_surat`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `disposisi`
--
ALTER TABLE `disposisi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `surat_id` (`surat_id`),
  ADD KEY `dari_user_id` (`dari_user_id`),
  ADD KEY `ke_user_id` (`ke_user_id`);

--
-- Indexes for table `klasifikasi_surat`
--
ALTER TABLE `klasifikasi_surat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `surat_keluar`
--
ALTER TABLE `surat_keluar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_surat` (`nomor_surat`),
  ADD KEY `draft_by` (`draft_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_nomor_surat_keluar` (`nomor_surat`);

--
-- Indexes for table `surat_masuk`
--
ALTER TABLE `surat_masuk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unit_tujuan_id` (`unit_tujuan_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_nomor_surat_masuk` (`nomor_surat`);

--
-- Indexes for table `unit_kerja`
--
ALTER TABLE `unit_kerja`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `plt_user_id` (`plt_user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `arsip`
--
ALTER TABLE `arsip`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=310;

--
-- AUTO_INCREMENT for table `disposisi`
--
ALTER TABLE `disposisi`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `klasifikasi_surat`
--
ALTER TABLE `klasifikasi_surat`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `surat_keluar`
--
ALTER TABLE `surat_keluar`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `surat_masuk`
--
ALTER TABLE `surat_masuk`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `unit_kerja`
--
ALTER TABLE `unit_kerja`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `disposisi`
--
ALTER TABLE `disposisi`
  ADD CONSTRAINT `disposisi_ibfk_1` FOREIGN KEY (`surat_id`) REFERENCES `surat_masuk` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disposisi_ibfk_2` FOREIGN KEY (`dari_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `disposisi_ibfk_3` FOREIGN KEY (`ke_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `surat_keluar`
--
ALTER TABLE `surat_keluar`
  ADD CONSTRAINT `surat_keluar_ibfk_1` FOREIGN KEY (`draft_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `surat_keluar_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `surat_masuk`
--
ALTER TABLE `surat_masuk`
  ADD CONSTRAINT `surat_masuk_ibfk_1` FOREIGN KEY (`unit_tujuan_id`) REFERENCES `unit_kerja` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `surat_masuk_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `unit_kerja` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `users_ibfk_3` FOREIGN KEY (`plt_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
