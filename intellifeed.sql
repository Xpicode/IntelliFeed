-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 29, 2026 at 03:00 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `intellifeed`
--

-- --------------------------------------------------------

--
-- Table structure for table `arduino_data`
--

CREATE TABLE `arduino_data` (
  `id` int(11) NOT NULL,
  `device_id` varchar(50) DEFAULT 'esp32_main',
  `weight` decimal(8,2) DEFAULT 0.00,
  `feeding` tinyint(1) DEFAULT 0,
  `timestamp` datetime DEFAULT current_timestamp(),
  `food_empty` tinyint(1) DEFAULT 0,
  `no_plate` tinyint(1) NOT NULL DEFAULT 0,
  `plate_inside` tinyint(1) NOT NULL DEFAULT 0,
  `plate_distance` decimal(6,2) NOT NULL DEFAULT 0.00,
  `wstorage_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `wstorage_level_cm` decimal(5,2) NOT NULL DEFAULT 0.00,
  `wstorage_low` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `arduino_status`
--

CREATE TABLE `arduino_status` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` varchar(32) NOT NULL DEFAULT 'esp32_main',
  `weight` decimal(8,2) NOT NULL DEFAULT 0.00,
  `water_weight` decimal(8,2) NOT NULL DEFAULT 0.00,
  `feeding` tinyint(1) NOT NULL DEFAULT 0,
  `food_empty` tinyint(1) NOT NULL DEFAULT 0,
  `no_plate` tinyint(1) NOT NULL DEFAULT 0,
  `plate_inside` tinyint(1) NOT NULL DEFAULT 0,
  `plate_distance` decimal(6,2) NOT NULL DEFAULT 0.00,
  `wstorage_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `wstorage_level_cm` decimal(5,2) NOT NULL DEFAULT 0.00,
  `wstorage_low` tinyint(1) NOT NULL DEFAULT 0,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `arduino_status`
--

INSERT INTO `arduino_status` (`id`, `device_id`, `weight`, `water_weight`, `feeding`, `food_empty`, `no_plate`, `plate_inside`, `plate_distance`, `wstorage_pct`, `wstorage_level_cm`, `wstorage_low`, `timestamp`) VALUES
(1, 'esp32_main', 0.09, 0.09, 0, 0, 1, 0, 0.00, 7.10, 0.80, 1, '2026-03-29 06:23:11');

-- --------------------------------------------------------

--
-- Table structure for table `feeding_commands`
--

CREATE TABLE `feeding_commands` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `device_id` varchar(50) DEFAULT 'esp32_main',
  `command` varchar(50) NOT NULL,
  `plate_type` varchar(20) DEFAULT 'stock',
  `portion` decimal(8,2) DEFAULT 30.00,
  `executed` tinyint(1) DEFAULT 0,
  `executed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feeding_commands`
--

INSERT INTO `feeding_commands` (`id`, `user_id`, `device_id`, `command`, `plate_type`, `portion`, `executed`, `executed_at`, `created_at`) VALUES
(32, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-22 06:16:01', '2026-03-22 06:16:00'),
(33, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-22 06:17:25', '2026-03-22 06:17:15'),
(34, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-22 06:19:36', '2026-03-22 06:17:26'),
(35, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-22 06:39:15', '2026-03-22 06:39:05'),
(36, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-22 06:40:41', '2026-03-22 06:40:37'),
(37, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-22 06:43:20', '2026-03-22 06:43:16'),
(38, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-22 23:12:31', '2026-03-22 23:12:19'),
(39, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-22 23:55:49', '2026-03-22 23:55:35'),
(40, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-22 23:55:52', '2026-03-22 23:55:43'),
(41, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 00:24:12', '2026-03-23 00:17:24'),
(42, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 00:25:57', '2026-03-23 00:25:51'),
(43, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-23 00:33:26', '2026-03-23 00:33:18'),
(44, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 01:33:42', '2026-03-23 01:33:41'),
(45, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 01:38:54', '2026-03-23 01:38:51'),
(46, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 01:39:46', '2026-03-23 01:39:38'),
(47, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 01:41:01', '2026-03-23 01:40:51'),
(48, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 01:54:49', '2026-03-23 01:54:46'),
(49, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 02:12:59', '2026-03-23 02:11:26'),
(50, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 02:15:23', '2026-03-23 02:15:22'),
(51, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-23 04:44:36', '2026-03-23 04:44:35'),
(52, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 05:13:29', '2026-03-23 05:13:27'),
(53, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 05:13:55', '2026-03-23 05:13:48'),
(54, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-23 05:25:45', '2026-03-23 05:25:16'),
(55, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 05:27:59', '2026-03-23 05:27:39'),
(56, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 05:28:04', '2026-03-23 05:27:52'),
(57, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-23 05:41:26', '2026-03-23 05:41:04'),
(58, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 05:43:20', '2026-03-23 05:43:12'),
(59, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 05:51:15', '2026-03-23 05:50:59'),
(60, 3, 'esp32_main', 'feed_now', 'new', 280.00, 1, '2026-03-23 06:53:41', '2026-03-23 06:53:15'),
(61, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-23 06:57:15', '2026-03-23 06:56:36'),
(62, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 07:03:17', '2026-03-23 06:59:53'),
(63, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 07:22:53', '2026-03-23 07:22:52'),
(64, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 07:25:14', '2026-03-23 07:25:12'),
(65, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 07:25:19', '2026-03-23 07:25:12'),
(66, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-23 07:38:13', '2026-03-23 07:38:13'),
(67, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 07:44:59', '2026-03-23 07:44:56'),
(68, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 07:47:00', '2026-03-23 07:46:57'),
(69, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 07:51:22', '2026-03-23 07:51:18'),
(70, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 07:53:36', '2026-03-23 07:53:35'),
(71, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-23 07:58:26', '2026-03-23 07:57:50'),
(72, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 08:00:57', '2026-03-23 07:59:18'),
(73, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 08:02:02', '2026-03-23 08:00:16'),
(74, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 08:08:27', '2026-03-23 08:08:22'),
(75, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 08:08:33', '2026-03-23 08:08:22'),
(76, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 08:57:01', '2026-03-23 08:57:00'),
(77, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 09:18:42', '2026-03-23 09:11:00'),
(78, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 09:18:50', '2026-03-23 09:13:30'),
(79, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 09:19:22', '2026-03-23 09:19:21'),
(80, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 09:22:19', '2026-03-23 09:22:02'),
(81, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 09:28:20', '2026-03-23 09:25:03'),
(82, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 09:31:10', '2026-03-23 09:27:37'),
(83, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 09:31:12', '2026-03-23 09:28:36'),
(84, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 09:31:18', '2026-03-23 09:29:13'),
(85, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 16:33:07', '2026-03-23 16:33:05'),
(86, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 16:34:05', '2026-03-23 16:34:05'),
(87, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 16:38:12', '2026-03-23 16:38:10'),
(88, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 16:38:15', '2026-03-23 16:38:10'),
(89, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-23 16:39:08', '2026-03-23 16:39:08'),
(90, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-23 16:41:20', '2026-03-23 16:41:19'),
(91, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-23 16:42:09', '2026-03-23 16:41:47'),
(92, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 02:36:58', '2026-03-25 02:36:55'),
(93, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 02:38:43', '2026-03-25 02:38:42'),
(94, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 02:43:56', '2026-03-25 02:43:55'),
(95, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 02:47:53', '2026-03-25 02:47:51'),
(96, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 02:49:21', '2026-03-25 02:49:19'),
(97, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 02:49:46', '2026-03-25 02:49:44'),
(98, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 02:50:42', '2026-03-25 02:50:40'),
(99, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 02:53:33', '2026-03-25 02:53:31'),
(100, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 02:55:55', '2026-03-25 02:55:55'),
(101, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 03:00:37', '2026-03-25 03:00:36'),
(102, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 03:03:52', '2026-03-25 03:03:51'),
(103, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 03:05:57', '2026-03-25 03:05:56'),
(104, 3, 'esp32_main', 'pump', 'stock', 60.00, 1, '2026-03-25 03:14:49', '2026-03-25 03:14:48'),
(105, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 03:20:59', '2026-03-25 03:20:58'),
(106, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 03:25:22', '2026-03-25 03:25:22'),
(107, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 03:27:14', '2026-03-25 03:27:08'),
(108, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 03:41:53', '2026-03-25 03:41:50'),
(109, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 03:45:52', '2026-03-25 03:45:50'),
(110, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 03:46:28', '2026-03-25 03:46:27'),
(111, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 03:49:31', '2026-03-25 03:49:27'),
(112, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 03:56:20', '2026-03-25 03:56:18'),
(113, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 03:59:32', '2026-03-25 03:59:31'),
(114, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 04:17:19', '2026-03-25 04:17:19'),
(115, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 04:20:54', '2026-03-25 04:20:53'),
(116, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 04:23:08', '2026-03-25 04:23:07'),
(117, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 04:26:52', '2026-03-25 04:26:50'),
(118, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 04:27:48', '2026-03-25 04:27:48'),
(119, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 04:27:50', '2026-03-25 04:27:48'),
(120, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 04:29:01', '2026-03-25 04:29:01'),
(121, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 04:34:31', '2026-03-25 04:34:30'),
(122, 3, 'esp32_main', 'feed_now', 'stock', 40.00, 1, '2026-03-25 04:36:23', '2026-03-25 04:36:23'),
(123, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 04:43:00', '2026-03-25 04:43:00'),
(124, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 04:56:43', '2026-03-25 04:56:43'),
(125, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 04:57:13', '2026-03-25 04:57:12'),
(126, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 04:57:37', '2026-03-25 04:57:37'),
(127, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 05:00:01', '2026-03-25 05:00:01'),
(128, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 06:30:59', '2026-03-25 06:10:45'),
(129, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 07:00:12', '2026-03-25 07:00:11'),
(130, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 07:06:58', '2026-03-25 07:06:56'),
(131, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 07:07:43', '2026-03-25 07:07:42'),
(132, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 07:13:06', '2026-03-25 07:13:05'),
(133, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 07:13:56', '2026-03-25 07:13:55'),
(134, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 07:26:53', '2026-03-25 07:26:52'),
(135, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 07:27:35', '2026-03-25 07:27:33'),
(136, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 07:33:41', '2026-03-25 07:33:40'),
(137, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 07:36:22', '2026-03-25 07:36:22'),
(138, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 07:37:17', '2026-03-25 07:37:15'),
(139, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 07:37:54', '2026-03-25 07:37:52'),
(140, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 07:38:17', '2026-03-25 07:38:15'),
(141, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 08:02:10', '2026-03-25 08:02:07'),
(142, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 08:02:50', '2026-03-25 08:02:49'),
(143, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 08:03:55', '2026-03-25 08:03:54'),
(144, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 08:04:09', '2026-03-25 08:04:09'),
(145, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 08:04:18', '2026-03-25 08:04:18'),
(146, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 08:13:49', '2026-03-25 08:13:47'),
(147, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 08:15:22', '2026-03-25 08:15:22'),
(148, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 08:16:01', '2026-03-25 08:15:59'),
(149, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 08:16:14', '2026-03-25 08:16:13'),
(150, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 08:18:02', '2026-03-25 08:18:01'),
(151, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 08:19:15', '2026-03-25 08:19:09'),
(152, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 08:38:56', '2026-03-25 08:38:55'),
(153, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 08:38:58', '2026-03-25 08:38:55'),
(154, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 08:39:56', '2026-03-25 08:39:55'),
(155, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 08:39:58', '2026-03-25 08:39:55'),
(156, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 08:46:05', '2026-03-25 08:46:03'),
(157, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 08:48:43', '2026-03-25 08:48:41'),
(158, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 08:53:53', '2026-03-25 08:53:51'),
(159, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 09:12:48', '2026-03-25 09:12:47'),
(160, 3, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-25 09:13:44', '2026-03-25 09:13:42'),
(161, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 09:39:46', '2026-03-25 09:39:45'),
(162, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 09:58:34', '2026-03-25 09:58:34'),
(163, 3, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-25 10:57:24', '2026-03-25 10:57:24'),
(164, 3, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-25 10:58:54', '2026-03-25 10:58:52'),
(165, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-28 23:55:03', '2026-03-28 23:53:07'),
(166, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-28 23:56:02', '2026-03-28 23:56:00'),
(167, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-28 23:56:40', '2026-03-28 23:56:38'),
(168, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 00:04:41', '2026-03-29 00:03:58'),
(169, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 00:20:36', '2026-03-29 00:20:34'),
(170, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 00:25:56', '2026-03-29 00:25:52'),
(171, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 00:33:26', '2026-03-29 00:32:45'),
(172, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 00:35:38', '2026-03-29 00:35:36'),
(173, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 00:39:35', '2026-03-29 00:39:32'),
(174, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 00:40:34', '2026-03-29 00:40:21'),
(175, 1, 'esp32_main', 'feed_now', 'new', 50.00, 1, '2026-03-29 00:41:08', '2026-03-29 00:41:07'),
(176, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 00:41:57', '2026-03-29 00:41:48'),
(177, 1, 'esp32_main', 'feed_now', 'new', 50.00, 1, '2026-03-29 00:48:14', '2026-03-29 00:47:23'),
(178, 1, 'esp32_main', 'feed_now', 'new', 50.00, 1, '2026-03-29 00:49:32', '2026-03-29 00:49:31'),
(179, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 00:53:40', '2026-03-29 00:53:22'),
(180, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 00:54:22', '2026-03-29 00:54:20'),
(181, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 00:56:17', '2026-03-29 00:55:52'),
(182, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 00:57:19', '2026-03-29 00:57:11'),
(183, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 00:57:58', '2026-03-29 00:57:52'),
(184, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 00:58:48', '2026-03-29 00:58:43'),
(185, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 00:58:58', '2026-03-29 00:58:56'),
(186, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 00:59:44', '2026-03-29 00:59:32'),
(187, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 00:59:47', '2026-03-29 00:59:32'),
(188, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:00:16', '2026-03-29 01:00:05'),
(189, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:00:58', '2026-03-29 01:00:50'),
(190, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 01:01:46', '2026-03-29 01:01:46'),
(191, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:04:25', '2026-03-29 01:04:15'),
(192, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:04:27', '2026-03-29 01:04:20'),
(193, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 01:05:41', '2026-03-29 01:05:40'),
(194, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:06:29', '2026-03-29 01:06:25'),
(195, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 01:07:45', '2026-03-29 01:07:44'),
(196, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:08:32', '2026-03-29 01:08:22'),
(197, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 01:09:45', '2026-03-29 01:09:44'),
(198, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:10:17', '2026-03-29 01:10:09'),
(199, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 01:10:47', '2026-03-29 01:10:44'),
(200, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:11:39', '2026-03-29 01:11:24'),
(201, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 01:12:03', '2026-03-29 01:12:02'),
(202, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:13:22', '2026-03-29 01:13:21'),
(203, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 01:16:37', '2026-03-29 01:16:35'),
(204, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:16:56', '2026-03-29 01:16:54'),
(205, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 01:22:35', '2026-03-29 01:21:55'),
(206, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 01:23:52', '2026-03-29 01:23:52'),
(207, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 01:24:03', '2026-03-29 01:24:03'),
(208, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 01:24:14', '2026-03-29 01:24:13'),
(209, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:24:50', '2026-03-29 01:24:49'),
(210, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:25:27', '2026-03-29 01:25:25'),
(211, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 01:25:40', '2026-03-29 01:25:39'),
(212, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:28:35', '2026-03-29 01:28:33'),
(213, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:32:33', '2026-03-29 01:29:19'),
(214, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:32:35', '2026-03-29 01:29:35'),
(215, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:32:37', '2026-03-29 01:32:08'),
(216, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 01:37:08', '2026-03-29 01:37:02'),
(217, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:37:37', '2026-03-29 01:37:35'),
(218, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 01:39:30', '2026-03-29 01:39:28'),
(219, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 01:40:47', '2026-03-29 01:40:44'),
(220, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 02:09:13', '2026-03-29 02:08:44'),
(221, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 02:15:20', '2026-03-29 02:15:06'),
(222, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 02:17:48', '2026-03-29 02:17:45'),
(223, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 02:20:56', '2026-03-29 02:19:58'),
(224, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 02:21:32', '2026-03-29 02:21:32'),
(225, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 02:22:30', '2026-03-29 02:22:28'),
(226, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 02:22:47', '2026-03-29 02:22:47'),
(227, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 02:23:24', '2026-03-29 02:23:23'),
(228, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 02:23:34', '2026-03-29 02:23:34'),
(229, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 02:23:43', '2026-03-29 02:23:42'),
(230, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 02:44:08', '2026-03-29 02:43:52'),
(231, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 02:44:57', '2026-03-29 02:44:57'),
(232, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 02:46:56', '2026-03-29 02:46:54'),
(233, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 02:56:21', '2026-03-29 02:56:20'),
(234, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 03:15:16', '2026-03-29 03:15:14'),
(235, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 03:15:18', '2026-03-29 03:15:14'),
(236, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 04:01:53', '2026-03-29 04:01:01'),
(237, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 04:02:18', '2026-03-29 04:02:15'),
(238, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 04:02:21', '2026-03-29 04:02:15'),
(239, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 04:59:24', '2026-03-29 04:59:22'),
(240, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 05:01:04', '2026-03-29 05:01:02'),
(241, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 05:02:34', '2026-03-29 05:02:34'),
(242, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 05:11:59', '2026-03-29 05:11:59'),
(243, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 05:18:07', '2026-03-29 05:18:06'),
(244, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 05:23:44', '2026-03-29 05:23:43'),
(245, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 05:27:30', '2026-03-29 05:27:29'),
(246, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 05:28:05', '2026-03-29 05:28:05'),
(247, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 05:28:35', '2026-03-29 05:28:34'),
(248, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 05:29:00', '2026-03-29 05:29:00'),
(249, 1, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-29 05:29:26', '2026-03-29 05:29:24'),
(250, 1, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-29 05:38:40', '2026-03-29 05:38:38'),
(251, 1, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-29 05:39:27', '2026-03-29 05:39:26'),
(252, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 05:42:21', '2026-03-29 05:42:19'),
(253, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 05:43:34', '2026-03-29 05:43:33'),
(254, 1, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-29 05:44:11', '2026-03-29 05:44:04'),
(255, 1, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-29 05:44:27', '2026-03-29 05:44:25'),
(256, 1, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-29 05:44:44', '2026-03-29 05:44:44'),
(257, 1, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-29 05:46:02', '2026-03-29 05:46:02'),
(258, 1, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-29 05:46:26', '2026-03-29 05:46:24'),
(259, 1, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-29 05:46:30', '2026-03-29 05:46:29'),
(260, 1, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-29 05:46:53', '2026-03-29 05:46:52'),
(261, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 05:53:44', '2026-03-29 05:53:44'),
(262, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 05:54:29', '2026-03-29 05:54:28'),
(263, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 05:57:26', '2026-03-29 05:57:26'),
(264, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 05:59:19', '2026-03-29 05:59:18'),
(265, 1, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-29 06:01:20', '2026-03-29 06:01:19'),
(266, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 06:03:26', '2026-03-29 06:03:25'),
(267, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 06:12:24', '2026-03-29 06:12:22'),
(268, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 06:13:21', '2026-03-29 06:13:20'),
(269, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 06:13:24', '2026-03-29 06:13:20'),
(270, 1, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-29 06:14:12', '2026-03-29 06:14:10'),
(271, 1, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-29 06:14:27', '2026-03-29 06:14:27'),
(272, 1, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-29 06:14:46', '2026-03-29 06:14:44'),
(273, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 06:14:59', '2026-03-29 06:14:59'),
(274, 1, 'esp32_main', 'feed_now', 'stock', 30.00, 1, '2026-03-29 06:15:50', '2026-03-29 06:15:50'),
(275, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 06:19:25', '2026-03-29 06:19:23'),
(276, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 06:19:55', '2026-03-29 06:19:55'),
(277, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 06:21:02', '2026-03-29 06:21:00'),
(278, 1, 'esp32_main', 'dispose', 'stock', 0.00, 1, '2026-03-29 06:21:10', '2026-03-29 06:21:01'),
(279, 1, 'esp32_main', 'feed_now', 'new', 30.00, 1, '2026-03-29 06:21:12', '2026-03-29 06:21:01');

-- --------------------------------------------------------

--
-- Table structure for table `feeding_history`
--

CREATE TABLE `feeding_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `device_id` varchar(50) DEFAULT 'esp32_main',
  `plate_type` varchar(20) DEFAULT 'stock',
  `portion` decimal(8,2) DEFAULT 0.00,
  `weight_final` decimal(8,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feeding_history`
--

INSERT INTO `feeding_history` (`id`, `user_id`, `device_id`, `plate_type`, `portion`, `weight_final`, `created_at`) VALUES
(1, 1, 'esp32_main', 'new', 30.00, NULL, '2026-03-29 01:06:25'),
(2, 1, 'esp32_main', 'new', 30.00, NULL, '2026-03-29 01:11:24'),
(3, 1, 'esp32_main', 'new', 30.00, NULL, '2026-03-29 01:13:21'),
(4, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 01:16:54'),
(5, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 01:24:49'),
(6, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 01:25:25'),
(7, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 01:28:33'),
(8, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 01:29:19'),
(9, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 01:29:35'),
(10, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 01:32:08'),
(11, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 01:37:35'),
(12, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 01:39:28'),
(13, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 02:08:44'),
(14, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 02:17:45'),
(15, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 02:21:32'),
(16, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 02:22:28'),
(17, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 02:43:52'),
(18, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 02:44:57'),
(19, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 02:46:54'),
(20, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 04:01:01'),
(21, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 04:59:22'),
(22, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 05:01:02'),
(23, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 05:02:34'),
(24, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 05:11:59'),
(25, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 05:18:06'),
(26, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 05:23:43'),
(27, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 05:27:29'),
(28, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 05:28:05'),
(29, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 05:28:34'),
(30, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 05:29:00'),
(31, 1, 'esp32_main', 'stock', 30.00, 0.00, '2026-03-29 05:29:24'),
(32, 1, 'esp32_main', 'stock', 30.00, 0.00, '2026-03-29 05:38:38'),
(33, 1, 'esp32_main', 'stock', 30.00, 0.00, '2026-03-29 05:39:26'),
(34, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 05:42:19'),
(35, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 05:43:33'),
(36, 1, 'esp32_main', 'stock', 30.00, 0.00, '2026-03-29 05:44:04'),
(37, 1, 'esp32_main', 'stock', 30.00, 0.00, '2026-03-29 05:44:25'),
(38, 1, 'esp32_main', 'stock', 30.00, 0.00, '2026-03-29 05:44:44'),
(39, 1, 'esp32_main', 'stock', 30.00, 0.00, '2026-03-29 05:46:02'),
(40, 1, 'esp32_main', 'stock', 30.00, 0.00, '2026-03-29 05:46:24'),
(41, 1, 'esp32_main', 'stock', 30.00, 0.00, '2026-03-29 05:46:29'),
(42, 1, 'esp32_main', 'stock', 30.00, 0.00, '2026-03-29 05:46:52'),
(43, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 05:53:44'),
(44, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 05:54:28'),
(45, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 05:57:26'),
(46, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 05:59:18'),
(47, 1, 'esp32_main', 'stock', 30.00, 0.00, '2026-03-29 06:01:19'),
(48, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 06:03:25'),
(49, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 06:12:22'),
(50, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 06:13:20'),
(51, 1, 'esp32_main', 'stock', 30.00, 0.00, '2026-03-29 06:14:10'),
(52, 1, 'esp32_main', 'stock', 30.00, 0.00, '2026-03-29 06:14:27'),
(53, 1, 'esp32_main', 'stock', 30.00, 0.00, '2026-03-29 06:14:44'),
(54, 1, 'esp32_main', 'stock', 30.00, 0.00, '2026-03-29 06:15:50'),
(55, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 06:19:23'),
(56, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 06:19:55'),
(57, 1, 'esp32_main', 'new', 30.00, 0.00, '2026-03-29 06:21:00');

-- --------------------------------------------------------

--
-- Table structure for table `feeding_schedule`
--

CREATE TABLE `feeding_schedule` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `device_id` varchar(50) DEFAULT 'esp32_main',
  `schedule_time` time NOT NULL,
  `meal_label` varchar(50) NOT NULL DEFAULT 'Meal',
  `slot_index` tinyint(1) NOT NULL DEFAULT 0,
  `plate_type` varchar(20) DEFAULT 'stock',
  `portion` decimal(8,2) DEFAULT 30.00,
  `is_active` tinyint(1) DEFAULT 1,
  `last_triggered` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feeding_schedule`
--

INSERT INTO `feeding_schedule` (`id`, `user_id`, `device_id`, `schedule_time`, `meal_label`, `slot_index`, `plate_type`, `portion`, `is_active`, `last_triggered`, `created_at`) VALUES
(24, 1, 'esp32_main', '06:21:00', '☀️ Lunch', 1, 'new', 30.00, 1, '2026-03-29 06:21:01', '2026-03-29 06:20:40');

-- --------------------------------------------------------

--
-- Table structure for table `pet_journal`
--

CREATE TABLE `pet_journal` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pet_name` varchar(100) NOT NULL,
  `pet_type` enum('cat','dog') NOT NULL,
  `note_title` varchar(200) DEFAULT NULL,
  `note_content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pet_journal`
--

INSERT INTO `pet_journal` (`id`, `user_id`, `pet_name`, `pet_type`, `note_title`, `note_content`, `created_at`, `updated_at`, `deleted_at`) VALUES
(5, 1, 'dsfsd', 'dog', '', 'fsdfsd', '2026-03-06 11:08:01', '2026-03-06 11:17:12', NULL),
(6, 1, 'y', 'dog', '', 'yuiyuiyu', '2026-03-06 11:08:24', '2026-03-06 11:17:10', NULL),
(7, 1, 'mojo jojo', 'cat', 'kulit', 'kulit kulit maghapon', '2026-03-06 11:14:39', '2026-03-06 11:14:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `saved_pets`
--

CREATE TABLE `saved_pets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pet_unique_id` varchar(100) NOT NULL,
  `pet_name` varchar(50) NOT NULL,
  `pet_type` enum('cat','dog') NOT NULL,
  `pet_color` varchar(50) DEFAULT NULL,
  `first_seen` datetime DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL,
  `times_seen` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `saved_pets`
--

INSERT INTO `saved_pets` (`id`, `user_id`, `pet_unique_id`, `pet_name`, `pet_type`, `pet_color`, `first_seen`, `last_seen`, `times_seen`, `created_at`) VALUES
(6, 2, 'cat_6_7', 'gargar', 'cat', 'Unknown', '2026-03-04 23:01:26', '2026-03-04 23:01:26', 1, '2026-03-04 15:01:26'),
(7, 1, 'cat_10_7', 'Micaiah', 'cat', 'Brown', '2026-03-05 09:17:29', '2026-03-05 09:17:29', 1, '2026-03-05 01:17:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `full_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `is_admin`, `full_name`, `created_at`, `last_login`) VALUES
(1, 'alex', 'mais@gmail.com', '$2y$10$ok4yHg0tj2CI0CFZB9nbpOb814zuC1dQZvRV9m/deUVINIUOgwo8a', 1, 'alex maisog', '2026-03-04 03:14:28', '2026-03-28 17:31:33'),
(2, 'kian', 'kian@gmail.com', '$2y$10$GYsb5NebW4aJ2Kajrs7syeohYEP0qvZenzU7m3vnuq/MjXuVY2OpW', 0, 'kian kian', '2026-03-04 11:29:13', '2026-03-05 01:56:03'),
(3, 'asd', 'asd@sada.com', '$2y$10$KYr0TSgAhyoigrC0Y63T9OxwzZpsTobZj9hL2zI3SIikE6cQbvR1a', 0, '', '2026-03-19 15:37:49', '2026-03-25 02:58:30');

-- --------------------------------------------------------

--
-- Table structure for table `user_logs`
--

CREATE TABLE `user_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `action` enum('login','logout','failed_login','register') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'success',
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_logs`
--

INSERT INTO `user_logs` (`id`, `user_id`, `username`, `action`, `ip_address`, `user_agent`, `status`, `details`, `created_at`) VALUES
(1, 1, 'alex', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged out', '2026-03-04 12:04:10'),
(2, 1, 'alex', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-04 12:04:15'),
(3, 1, 'alex', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged out', '2026-03-04 13:14:42'),
(4, 2, 'kian', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-04 13:14:46'),
(5, 2, 'kian', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged out', '2026-03-05 01:16:09'),
(6, 1, 'alex', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 01:16:17'),
(7, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Added journal entry ID: 1', '2026-03-05 01:42:21'),
(8, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Soft deleted journal entry ID: 1', '2026-03-05 01:43:50'),
(9, 1, 'alex', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged out', '2026-03-05 01:55:49'),
(10, 1, 'alex', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 01:55:53'),
(11, 1, 'alex', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged out', '2026-03-05 01:55:59'),
(12, 2, 'kian', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 01:56:03'),
(13, 2, 'kian', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged out', '2026-03-05 05:24:26'),
(14, 1, 'alex', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 05:24:34'),
(15, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Added journal entry ID: 2', '2026-03-06 11:03:02'),
(16, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Soft deleted journal entry ID: 2', '2026-03-06 11:03:06'),
(17, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Added journal entry ID: 3', '2026-03-06 11:03:26'),
(18, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Emptied trash, deleted 2 entries', '2026-03-06 11:03:29'),
(19, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Soft deleted journal entry ID: 3', '2026-03-06 11:03:33'),
(20, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Added journal entry ID: 4', '2026-03-06 11:07:50'),
(21, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Soft deleted journal entry ID: 4', '2026-03-06 11:07:52'),
(22, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Emptied trash, deleted 2 entries', '2026-03-06 11:07:56'),
(23, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Added journal entry ID: 5', '2026-03-06 11:08:01'),
(24, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Soft deleted journal entry ID: 5', '2026-03-06 11:08:02'),
(25, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Added journal entry ID: 6', '2026-03-06 11:08:24'),
(26, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Soft deleted journal entry ID: 6', '2026-03-06 11:08:26'),
(27, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Added journal entry ID: 7', '2026-03-06 11:14:39'),
(28, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Restored journal entry ID: 6', '2026-03-06 11:17:10'),
(29, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Restored journal entry ID: 5', '2026-03-06 11:17:12'),
(30, 1, 'alex', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Emptied trash, deleted 0 entries', '2026-03-06 11:17:15'),
(31, 1, 'alex', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 06:59:32'),
(32, 1, 'alex', 'failed_login', '192.168.0.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'failed', 'Incorrect password attempt', '2026-03-10 10:43:01'),
(33, 1, 'alex', 'failed_login', '192.168.0.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'failed', 'Incorrect password attempt', '2026-03-10 10:43:05'),
(34, 1, 'alex', 'failed_login', '192.168.0.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'failed', 'Incorrect password attempt', '2026-03-10 10:43:13'),
(35, 1, 'alex', 'failed_login', '192.168.0.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'failed', 'Incorrect password attempt', '2026-03-10 10:43:18'),
(36, 1, 'alex', 'login', '192.168.0.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 10:43:29'),
(37, 1, 'alex', 'logout', '192.168.0.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged out', '2026-03-10 10:50:48'),
(38, 1, 'alex', 'login', '192.168.0.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 10:50:54'),
(39, 1, 'alex', 'login', '192.168.0.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 10:56:41'),
(40, 1, 'alex', 'login', '192.168.0.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 11:12:40'),
(41, 1, 'alex', 'login', '192.168.0.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 00:04:20'),
(42, 1, 'alex', 'login', '192.168.0.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 09:48:02'),
(43, 1, 'alex', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 10:01:24'),
(44, 1, 'alex', 'login', '172.20.10.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-18 10:12:38'),
(45, 1, 'alex', 'login', '192.168.0.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-19 10:56:50'),
(46, 1, 'alex', 'logout', '192.168.0.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged out', '2026-03-19 15:37:03'),
(47, 3, 'asd', 'register', '192.168.0.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'New account created', '2026-03-19 15:37:50'),
(48, 3, 'asd', 'login', '192.168.0.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-19 15:37:59'),
(49, 3, 'asd', 'login', '192.168.0.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 13:50:59'),
(50, 3, 'asd', 'login', '192.168.0.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 14:27:48'),
(51, 3, 'asd', 'login', '192.168.0.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-21 14:19:46'),
(52, 3, 'asd', 'login', '192.168.0.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-22 13:32:08'),
(53, 3, 'asd', 'login', '192.168.0.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-22 18:10:53'),
(54, 3, 'asd', 'login', '192.168.0.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-22 20:08:50'),
(55, 3, 'asd', 'login', '192.168.0.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-22 23:22:43'),
(56, 3, 'asd', 'login', '192.168.0.37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 00:56:49'),
(57, 3, 'asd', 'login', '2404:3c00:2550:6b00:ccf5:49d4:c16:21eb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 01:07:54'),
(58, 3, 'asd', 'login', '2404:3c00:2550:6b00:3fc0:bd95:ed0:fccd', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-23 01:27:24'),
(59, 1, 'alex', 'login', '2404:3c00:2550:6b00:cda8:480:b357:e8fe', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148', 'success', 'Logged in successfully from Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148', '2026-03-23 01:41:48'),
(60, 3, 'asd', 'login', '10.233.152.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 08:25:18'),
(61, 3, 'asd', 'login', '10.233.152.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 17:44:16'),
(62, 3, 'asd', 'login', '192.168.1.40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 21:44:11'),
(63, 3, 'asd', 'login', '10.233.152.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 22:31:17'),
(64, 3, 'asd', 'login', '10.233.152.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 02:55:25'),
(65, 3, 'asd', 'login', '10.233.152.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 02:58:30'),
(66, 1, 'alex', 'login', '192.168.0.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success', 'Logged in successfully from Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 17:31:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `arduino_data`
--
ALTER TABLE `arduino_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indexes for table `arduino_status`
--
ALTER TABLE `arduino_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_device` (`device_id`);

--
-- Indexes for table `feeding_commands`
--
ALTER TABLE `feeding_commands`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_executed_at` (`executed`,`executed_at`);

--
-- Indexes for table `feeding_history`
--
ALTER TABLE `feeding_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feeding_schedule`
--
ALTER TABLE `feeding_schedule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_slot` (`user_id`,`device_id`,`slot_index`);

--
-- Indexes for table `pet_journal`
--
ALTER TABLE `pet_journal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_deleted` (`deleted_at`);

--
-- Indexes for table `saved_pets`
--
ALTER TABLE `saved_pets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_pet` (`user_id`,`pet_unique_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `arduino_data`
--
ALTER TABLE `arduino_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `arduino_status`
--
ALTER TABLE `arduino_status`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18261;

--
-- AUTO_INCREMENT for table `feeding_commands`
--
ALTER TABLE `feeding_commands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=280;

--
-- AUTO_INCREMENT for table `feeding_history`
--
ALTER TABLE `feeding_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `feeding_schedule`
--
ALTER TABLE `feeding_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `pet_journal`
--
ALTER TABLE `pet_journal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `saved_pets`
--
ALTER TABLE `saved_pets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pet_journal`
--
ALTER TABLE `pet_journal`
  ADD CONSTRAINT `pet_journal_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_pets`
--
ALTER TABLE `saved_pets`
  ADD CONSTRAINT `saved_pets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD CONSTRAINT `user_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
