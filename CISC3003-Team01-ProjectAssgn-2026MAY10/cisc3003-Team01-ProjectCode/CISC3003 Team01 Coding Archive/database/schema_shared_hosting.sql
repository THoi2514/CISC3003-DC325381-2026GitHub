-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- 主機： sql103.infinityfree.com
-- 產生時間： 2026 年 05 月 07 日 03:28
-- 伺服器版本： 11.4.10-MariaDB
-- PHP 版本： 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `if0_41825875_um_rental_system`
--

-- --------------------------------------------------------

--
-- 資料表結構 `administrators`
--

CREATE TABLE `administrators` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `user_name` varchar(50) NOT NULL,
  `privilege` enum('super_admin','ops_admin','content_admin','finance_admin') NOT NULL DEFAULT 'ops_admin',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 傾印資料表的資料 `administrators`
--

INSERT INTO `administrators` (`id`, `user_id`, `user_name`, `privilege`, `created_at`, `updated_at`) VALUES
(1, 13, 'um_super_admin', 'super_admin', '2026-05-04 10:08:56', '2026-05-04 10:08:56');

-- --------------------------------------------------------

--
-- 資料表結構 `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `actor_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `actor_role` enum('system','user','admin') NOT NULL DEFAULT 'user',
  `action_type` varchar(80) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` bigint(20) UNSIGNED DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

--
-- 傾印資料表的資料 `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `actor_user_id`, `actor_role`, `action_type`, `target_type`, `target_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 13, 'admin', 'ACCOUNT_UPDATE_PROFILE', 'user', 13, '[]', '122.100.145.205', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 10:26:50'),
(2, 1, '', 'ACCOUNT_TOPUP', 'user', 1, '{\"amount\":1231,\"payment_method\":\"credit_card\"}', '122.100.145.205', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-04 10:55:27'),
(3, 13, 'admin', 'ADMIN_FORCE_END', 'rental_order', 3, '{\"reason\":\"end\",\"adjustFee\":0}', '149.102.98.202', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 13:36:54'),
(4, 13, 'admin', 'ADMIN_UPDATE_VEHICLE_STATUS', 'vehicle', 19, '{\"newStatus\":\"available\"}', '149.102.98.202', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 13:37:03'),
(5, 13, 'admin', 'ADMIN_UPDATE_VEHICLE_STATUS', 'vehicle', 14, '{\"newStatus\":\"available\"}', '149.102.98.202', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 13:37:06'),
(6, 13, 'admin', 'ADMIN_UPDATE_VEHICLE_STATUS', 'vehicle', 9, '{\"newStatus\":\"available\"}', '149.102.98.202', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 13:37:09'),
(7, 13, 'admin', 'ADMIN_UPDATE_VEHICLE_STATUS', 'vehicle', 4, '{\"newStatus\":\"available\"}', '149.102.98.202', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 13:37:12'),
(8, 13, 'admin', 'ADMIN_IMPORT_VEHICLES_CSV', 'vehicle', NULL, '{\"created\":80,\"errors\":[]}', '149.102.98.202', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 13:43:57'),
(9, 13, 'admin', 'ACCOUNT_TOPUP', 'user', 13, '{\"amount\":999,\"payment_method\":\"credit_card\"}', '149.102.98.202', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 13:46:21'),
(10, 27, '', 'ACCOUNT_TOPUP', 'user', 27, '{\"amount\":1000,\"payment_method\":\"credit_card\"}', '149.102.98.202', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 14:00:50'),
(11, 13, 'admin', 'ACCOUNT_ADMIN_TEST_TOPUP', 'user', 13, '{\"amount\":100}', '149.102.98.202', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 17:58:17'),
(12, 13, 'admin', 'ACCOUNT_ADMIN_TEST_TOPUP', 'user', 13, '{\"amount\":100}', '149.102.98.23', 'Mozilla/5.0 (iPad; CPU OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/147.0.7727.99 Mobile/15E148 Safari/604.1', '2026-05-05 18:02:12'),
(13, 13, 'admin', 'ACCOUNT_ADMIN_TEST_TOPUP', 'user', 13, '{\"amount\":1000}', '149.102.98.23', 'Mozilla/5.0 (iPad; CPU OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/147.0.7727.99 Mobile/15E148 Safari/604.1', '2026-05-05 18:02:18'),
(14, 13, 'admin', 'ACCOUNT_TOPUP', 'user', 13, '{\"amount\":1000,\"payment_method\":\"credit_card\"}', '149.102.98.23', 'Mozilla/5.0 (iPad; CPU OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/147.0.7727.99 Mobile/15E148 Safari/604.1', '2026-05-05 18:04:16');

-- --------------------------------------------------------

--
-- 資料表結構 `brands`
--

CREATE TABLE `brands` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `price_multiplier` decimal(5,2) NOT NULL DEFAULT 1.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 傾印資料表的資料 `brands`
--

INSERT INTO `brands` (`id`, `name`, `price_multiplier`, `created_at`) VALUES
(1, 'Giant', '1.00', '2026-05-04 10:07:15'),
(2, 'Xiaomi', '1.10', '2026-05-04 10:07:15'),
(3, 'Ninebot', '1.15', '2026-05-04 10:07:15');

-- --------------------------------------------------------

--
-- 資料表結構 `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `description` text NOT NULL,
  `category` enum('bug','payment','vehicle','station','account','other') NOT NULL DEFAULT 'other',
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 傾印資料表的資料 `feedbacks`
--

INSERT INTO `feedbacks` (`id`, `user_id`, `title`, `description`, `category`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'Scooter brake feels loose', 'Vehicle SC-1004 had weak brake response near Sports Complex (N8).', 'vehicle', 'open', '2026-05-04 10:08:56', '2026-05-04 10:08:56');

-- --------------------------------------------------------

--
-- 資料表結構 `feedback_replies`
--

CREATE TABLE `feedback_replies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `feedback_id` bigint(20) UNSIGNED NOT NULL,
  `admin_user_id` bigint(20) UNSIGNED NOT NULL,
  `reply_content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `forum_posts`
--

CREATE TABLE `forum_posts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `category` enum('help','experience','suggestion','lost_found','other') NOT NULL DEFAULT 'other',
  `content` text NOT NULL,
  `status` enum('visible','locked','hidden','deleted') NOT NULL DEFAULT 'visible',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 傾印資料表的資料 `forum_posts`
--

INSERT INTO `forum_posts` (`id`, `user_id`, `title`, `category`, `content`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Best route from library to dorm?', 'experience', 'Any shortcut recommendation for evening rides?', 'visible', '2026-05-04 10:08:56', '2026-05-04 10:08:56');

-- --------------------------------------------------------

--
-- 資料表結構 `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `scope_key` varchar(190) NOT NULL,
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `window_start` datetime NOT NULL,
  `blocked_until` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 傾印資料表的資料 `rate_limits`
--

INSERT INTO `rate_limits` (`id`, `scope_key`, `attempts`, `window_start`, `blocked_until`, `updated_at`) VALUES
(1, 'action:account_update_profile:user:13:ip:122.100.145.205', 1, '2026-05-04 13:26:49', NULL, '2026-05-04 10:26:50'),
(2, 'dashboard_api:rent:user:13:ip:122.100.145.205', 1, '2026-05-06 00:37:42', NULL, '2026-05-06 00:37:42'),
(3, 'dashboard_api:return:user:13:ip:122.100.145.205', 1, '2026-05-06 00:38:02', NULL, '2026-05-06 00:38:02'),
(4, 'action:account_topup:user:1:ip:122.100.145.205', 1, '2026-05-05 01:55:26', NULL, '2026-05-04 10:55:27'),
(5, 'dashboard_api:rent:user:1:ip:122.100.145.205', 1, '2026-05-05 01:55:46', NULL, '2026-05-04 10:55:47'),
(6, 'dashboard_api:return:user:1:ip:149.102.98.202', 1, '2026-05-05 13:31:06', NULL, '2026-05-05 13:31:06'),
(7, 'dashboard_api:rent:user:1:ip:149.102.98.202', 1, '2026-05-05 13:31:10', NULL, '2026-05-05 13:31:10'),
(8, 'dashboard_api:rent:user:13:ip:149.102.98.202', 1, '2026-05-05 17:55:07', NULL, '2026-05-05 17:55:07'),
(9, 'dashboard_api:return:user:13:ip:149.102.98.202', 1, '2026-05-05 17:57:21', NULL, '2026-05-05 17:57:21'),
(10, 'action:admin_force_end:user:13:ip:149.102.98.202', 1, '2026-05-05 13:36:55', NULL, '2026-05-05 13:36:54'),
(11, 'action:admin_update_vehicle:user:13:ip:149.102.98.202', 4, '2026-05-05 13:37:12', NULL, '2026-05-05 13:37:12'),
(12, 'action:staff_import_bicycles_csv:user:13:ip:149.102.98.202', 1, '2026-05-05 13:43:57', NULL, '2026-05-05 13:43:57'),
(13, 'action:account_topup:user:13:ip:149.102.98.202', 1, '2026-05-05 13:46:22', NULL, '2026-05-05 13:46:21'),
(14, 'dashboard_api:rent:user:27:ip:149.102.98.202', 2, '2026-05-05 14:01:04', NULL, '2026-05-05 14:01:05'),
(15, 'action:account_topup:user:27:ip:149.102.98.202', 1, '2026-05-05 14:00:50', NULL, '2026-05-05 14:00:50'),
(16, 'dashboard_api:return:user:27:ip:149.102.98.202', 1, '2026-05-05 14:01:22', NULL, '2026-05-05 14:01:22'),
(17, 'dashboard_api:return:user:1:ip:103.143.92.169', 1, '2026-05-05 15:54:09', NULL, '2026-05-05 15:54:10'),
(18, 'action:account_admin_test_topup:user:13:ip:149.102.98.202', 1, '2026-05-05 17:58:17', NULL, '2026-05-05 17:58:17'),
(19, 'action:account_admin_test_topup:user:13:ip:149.102.98.23', 4, '2026-05-05 18:02:17', NULL, '2026-05-05 18:02:18'),
(20, 'action:account_topup:user:13:ip:149.102.98.23', 1, '2026-05-05 18:04:15', NULL, '2026-05-05 18:04:16');

-- --------------------------------------------------------

--
-- 資料表結構 `rental_orders`
--

CREATE TABLE `rental_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_id` bigint(20) UNSIGNED NOT NULL,
  `start_station_id` bigint(20) UNSIGNED DEFAULT NULL,
  `end_station_id` bigint(20) UNSIGNED DEFAULT NULL,
  `start_time` datetime NOT NULL DEFAULT current_timestamp(),
  `end_time` datetime DEFAULT NULL,
  `duration_minutes` int(10) UNSIGNED DEFAULT NULL,
  `fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','completed','payment_pending','force_closed','cancelled') NOT NULL DEFAULT 'active',
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- 傾印資料表的資料 `rental_orders`
--

INSERT INTO `rental_orders` (`id`, `user_id`, `vehicle_id`, `start_station_id`, `end_station_id`, `start_time`, `end_time`, `duration_minutes`, `fee`, `status`, `note`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 3, 1, '2026-05-01 10:08:56', '2026-05-01 10:43:56', 35, '8.50', 'completed', 'Demo completed order', '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(2, 11, 12, 6, 2, '2026-05-03 10:08:56', '2026-05-03 11:00:56', 52, '16.20', 'completed', 'Demo completed order', '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(3, 2, 14, 2, NULL, '2026-05-04 09:48:56', '2026-05-05 13:36:54', 1667, '0.00', 'force_closed', 'end', '2026-05-04 10:08:56', '2026-05-05 13:36:54'),
(4, 1, 1, 3, 1, '2026-05-01 10:26:04', '2026-05-01 11:01:04', 35, '8.50', 'completed', 'Demo completed order', '2026-05-04 10:26:04', '2026-05-04 10:26:04'),
(5, 11, 12, 6, 2, '2026-05-03 10:26:04', '2026-05-03 11:18:04', 52, '16.20', 'completed', 'Demo completed order', '2026-05-04 10:26:04', '2026-05-04 10:26:04'),
(6, 13, 11, 3, 5, '2026-05-04 10:27:25', '2026-05-04 10:27:39', 181, '61.60', 'completed', NULL, '2026-05-04 10:27:25', '2026-05-04 10:27:39'),
(7, 13, 10, 1, 2, '2026-05-04 10:27:56', '2026-05-04 10:29:57', 182, '38.50', 'completed', NULL, '2026-05-04 10:27:56', '2026-05-04 10:29:57'),
(8, 13, 8, 8, 8, '2026-05-04 10:30:07', '2026-05-04 10:31:40', 902, '170.50', 'completed', NULL, '2026-05-04 10:30:07', '2026-05-04 10:31:40'),
(9, 13, 5, 1, 1, '2026-05-04 10:31:48', '2026-05-04 10:32:42', 901, '178.25', 'completed', NULL, '2026-05-04 10:31:48', '2026-05-04 10:32:42'),
(10, 13, 5, 1, 4, '2026-05-04 10:38:55', '2026-05-04 10:39:05', 901, '178.25', 'completed', NULL, '2026-05-04 10:38:55', '2026-05-04 10:39:05'),
(11, 13, 5, 4, 8, '2026-05-04 10:39:18', '2026-05-04 10:42:09', 903, '178.25', 'completed', NULL, '2026-05-04 10:39:18', '2026-05-04 10:42:09'),
(12, 13, 8, 8, 4, '2026-05-04 10:42:21', '2026-05-04 10:42:33', 901, '170.50', 'completed', NULL, '2026-05-04 10:42:21', '2026-05-04 10:42:33'),
(13, 13, 11, 5, 6, '2026-05-04 10:45:42', '2026-05-04 10:45:48', 901, '272.80', 'completed', NULL, '2026-05-04 10:45:42', '2026-05-04 10:45:48'),
(14, 13, 2, 6, 7, '2026-05-04 10:47:25', '2026-05-04 10:47:40', 901, '170.50', 'completed', NULL, '2026-05-04 10:47:25', '2026-05-04 10:47:40'),
(15, 13, 15, 1, 5, '2026-05-04 10:48:40', '2026-05-04 10:48:47', 901, '272.80', 'completed', NULL, '2026-05-04 10:48:40', '2026-05-04 10:48:47'),
(16, 1, 2, 7, 4, '2026-05-04 10:55:47', '2026-05-04 21:40:54', 1546, '286.00', 'completed', NULL, '2026-05-04 10:55:47', '2026-05-04 21:40:54'),
(17, 1, 20, 1, 7, '2026-05-04 21:41:04', '2026-05-05 13:31:06', 951, '794.40', 'completed', NULL, '2026-05-04 21:41:04', '2026-05-05 13:31:06'),
(18, 1, 2, 4, 4, '2026-05-05 13:31:10', '2026-05-05 15:54:10', 143, '527.50', 'payment_pending', NULL, '2026-05-05 13:31:10', '2026-05-05 15:54:10'),
(19, 13, 10, 2, 7, '2026-05-05 13:31:59', '2026-05-05 13:32:05', 1, '5.50', 'completed', NULL, '2026-05-05 13:31:58', '2026-05-05 13:32:05'),
(20, 13, 85, 5, 5, '2026-05-05 13:44:21', '2026-05-05 13:45:20', 1, '5.50', 'completed', NULL, '2026-05-05 13:44:21', '2026-05-05 13:45:20'),
(21, 27, 74, 4, 4, '2026-05-05 14:01:04', '2026-05-05 14:01:22', 1, '5.00', 'completed', NULL, '2026-05-05 14:01:05', '2026-05-05 14:01:22'),
(22, 13, 84, 5, 2, '2026-05-05 17:55:07', '2026-05-05 17:57:21', 3, '5.00', 'completed', NULL, '2026-05-05 17:55:07', '2026-05-05 17:57:21'),
(23, 13, 115, 8, 2, '2026-05-06 00:37:42', '2026-05-06 00:38:02', 1, '5.50', 'completed', NULL, '2026-05-06 00:37:42', '2026-05-06 00:38:02');

-- --------------------------------------------------------

--
-- 資料表結構 `rental_stations`
--

CREATE TABLE `rental_stations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `name_zh_cn` varchar(120) NOT NULL DEFAULT '',
  `name_zh_tw` varchar(120) NOT NULL DEFAULT '',
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `capacity` int(10) UNSIGNED NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- 傾印資料表的資料 `rental_stations`
--

INSERT INTO `rental_stations` (`id`, `name`, `name_zh_cn`, `name_zh_tw`, `latitude`, `longitude`, `capacity`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Research Building (N21)', '科研大楼 (N21)', '科研大樓 (N21)', '22.1348120', '113.5454310', 26, 'active', '2026-05-04 10:08:56', '2026-05-04 10:26:04'),
(2, 'Sports Complex (N8)', '综合体育馆 (N8)', '綜合體育館 (N8)', '22.1334230', '113.5430760', 24, 'active', '2026-05-04 10:08:56', '2026-05-04 10:26:04'),
(3, 'Wu Yee Sun Library (E2)', '伍宜孙图书馆 (E2)', '伍宜孫圖書館 (E2)', '22.1305860', '113.5460320', 24, 'active', '2026-05-04 10:08:56', '2026-05-04 10:26:04'),
(4, 'Central Teaching Building (E6)', '中央教学楼 (E6)', '中央教學樓 (E6)', '22.1283300', '113.5446480', 22, 'active', '2026-05-04 10:08:56', '2026-05-04 10:26:04'),
(5, 'Science and Technology Building (E11)', '科技学院楼 (E11)', '科技學院樓 (E11)', '22.1324450', '113.5426340', 22, 'active', '2026-05-04 10:08:56', '2026-05-04 10:26:04'),
(6, 'Social Sciences and Humanities Building (E21)', '人文社科楼 (E21)', '人文社科樓 (E21)', '22.1289340', '113.5423840', 20, 'active', '2026-05-04 10:08:56', '2026-05-04 10:26:04'),
(7, 'Student Activity Centre (E31)', '学生活动中心 (E31)', '學生活動中心 (E31)', '22.1266130', '113.5440710', 22, 'active', '2026-05-04 10:08:56', '2026-05-04 10:26:04'),
(8, 'University Mall (S8)', '荟萃坊 (S8)', '薈萃坊 (S8)', '22.1245210', '113.5440740', 20, 'active', '2026-05-04 10:08:56', '2026-05-04 10:26:04');

-- --------------------------------------------------------

--
-- 資料表結構 `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campus_id` varchar(30) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `role` enum('student','teacher','staff','visitor','admin') NOT NULL DEFAULT 'student',
  `email` varchar(120) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `password_hash` varchar(255) NOT NULL,
  `failed_login_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `lock_until` datetime DEFAULT NULL,
  `account_status` enum('active','frozen','disabled') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `users`
--

INSERT INTO `users` (`id`, `campus_id`, `full_name`, `role`, `email`, `phone`, `balance`, `password_hash`, `failed_login_attempts`, `lock_until`, `account_status`, `created_at`, `updated_at`) VALUES
(1, 's1000001', 'Alice Chan', 'student', 'alice.chan@um.edu.mo', '+853-6200-0001', '270.60', '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active', '2026-05-04 10:08:56', '2026-05-05 13:31:06'),
(2, 's1000002', 'Brian Leong', 'student', 'brian.leong@um.edu.mo', '+853-6200-0002', '80.00', '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active', '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(3, 's1000003', 'Cindy Ho', 'student', 'cindy.ho@um.edu.mo', '+853-6200-0003', '64.00', '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active', '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(4, 's1000004', 'Daniel Kuok', 'student', 'daniel.kuok@um.edu.mo', '+853-6200-0004', '45.00', '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active', '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(5, 's1000005', 'Evelyn Cheang', 'student', 'evelyn.cheang@um.edu.mo', '+853-6200-0005', '200.00', '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active', '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(6, 's1000006', 'Frankie Lei', 'student', 'frankie.lei@um.edu.mo', '+853-6200-0006', '90.00', '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active', '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(7, 's1000007', 'Grace Tam', 'student', 'grace.tam@um.edu.mo', '+853-6200-0007', '34.00', '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active', '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(8, 's1000008', 'Henry Vong', 'student', 'henry.vong@um.edu.mo', '+853-6200-0008', '56.50', '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active', '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(9, 's1000009', 'Iris Choi', 'student', 'iris.choi@um.edu.mo', '+853-6200-0009', '72.00', '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active', '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(10, 's1000010', 'Jason Ng', 'student', 'jason.ng@um.edu.mo', '+853-6200-0010', '110.00', '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active', '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(11, 't2000001', 'Dr. Maria Wong', 'staff', 'maria.wong@um.edu.mo', '+853-6200-1001', '150.00', '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active', '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(12, 'v3000001', 'David Visitor', 'visitor', 'david.visitor@gmail.com', '+853-6200-2001', '60.00', '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active', '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(13, 'dc325107', 'Ho Weng Hong', 'admin', 'dc325107@um.edu.mo', '+853-6523-8465', '11484.55', '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active', '2026-05-04 10:08:56', '2026-05-06 00:38:02'),
(27, 'g_tthh3555114_966', 'Thomas', 'visitor', 'tthh3555114@gmail.com', NULL, '995.00', '$2y$10$3L2JF5o0trcfapqnpi6sDOTuTnegq7ZjPweAh5Vh5a8yk8REjgsDK', 0, NULL, 'active', '2026-05-05 02:13:51', '2026-05-05 14:01:22'),
(28, 's1234567', 'Thomas', 'student', 'dc32510@um.edu.mo', '+853-6523-8465', '0.00', '$2y$10$o6Lm0waQF/myWqSMFlMpSuui4rduk7takEIxdirms8dv4G0e9GX5y', 0, NULL, 'active', '2026-05-05 17:59:52', '2026-05-05 17:59:52'),
(29, 'catcatqq', '何大文', 'teacher', 'catcatqq@gmail.com', '+853-8888-8888', '100000.00', '$2y$10$bTQjWu542YjBdwXBYm.fpu2pwL2daXQZ2KK08WKVvboI9wgfK45oy', 0, NULL, 'active', '2026-05-06 00:40:45', '2026-05-05 09:41:59');

-- --------------------------------------------------------

--
-- 資料表結構 `vehicles`
--

CREATE TABLE `vehicles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `serial_no` varchar(50) NOT NULL,
  `type_id` smallint(5) UNSIGNED NOT NULL,
  `brand_id` smallint(5) UNSIGNED NOT NULL,
  `status` enum('available','rented','maintenance','retired') NOT NULL DEFAULT 'available',
  `station_id` bigint(20) UNSIGNED DEFAULT NULL,
  `battery_level` tinyint(3) UNSIGNED DEFAULT NULL,
  `issue_note` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `vehicles`
--

INSERT INTO `vehicles` (`id`, `serial_no`, `type_id`, `brand_id`, `status`, `station_id`, `battery_level`, `issue_note`, `created_at`, `updated_at`) VALUES
(1, 'BK-0001', 1, 1, 'available', 3, NULL, NULL, '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(2, 'BK-0002', 1, 2, 'available', 4, NULL, NULL, '2026-05-04 10:08:56', '2026-05-05 15:54:10'),
(3, 'BK-0003', 1, 3, 'rented', 8, NULL, NULL, '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(4, 'BK-0004', 1, 2, 'available', 2, NULL, NULL, '2026-05-04 10:08:56', '2026-05-05 13:37:12'),
(5, 'BK-0005', 1, 3, 'available', 8, NULL, NULL, '2026-05-04 10:08:56', '2026-05-04 10:42:09'),
(6, 'BK-0006', 1, 1, 'available', 3, NULL, NULL, '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(7, 'BK-0007', 1, 3, 'available', 6, NULL, NULL, '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(8, 'BK-0008', 1, 2, 'available', 4, NULL, NULL, '2026-05-04 10:08:56', '2026-05-04 10:42:33'),
(9, 'BK-0009', 1, 1, 'available', 2, NULL, NULL, '2026-05-04 10:08:56', '2026-05-05 13:37:09'),
(10, 'BK-0010', 1, 2, 'available', 7, NULL, NULL, '2026-05-04 10:08:56', '2026-05-05 13:32:05'),
(11, 'SC-1001', 2, 2, 'available', 6, 92, NULL, '2026-05-04 10:08:56', '2026-05-04 10:45:48'),
(12, 'SC-1002', 2, 3, 'available', 6, 78, NULL, '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(13, 'SC-1003', 2, 2, 'available', 8, 66, NULL, '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(14, 'SC-1004', 2, 3, 'available', 2, 54, NULL, '2026-05-04 10:08:56', '2026-05-05 13:37:06'),
(15, 'SC-1005', 2, 2, 'available', 5, 88, NULL, '2026-05-04 10:08:56', '2026-05-04 10:48:47'),
(16, 'SC-1006', 2, 3, 'available', 3, 95, NULL, '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(17, 'SC-1007', 2, 2, 'available', 6, 82, NULL, '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(18, 'SC-1008', 2, 3, 'available', 8, 76, NULL, '2026-05-04 10:08:56', '2026-05-04 10:08:56'),
(19, 'SC-1009', 2, 2, 'available', 2, 61, NULL, '2026-05-04 10:08:56', '2026-05-05 13:37:03'),
(20, 'SC-1010', 2, 3, 'available', 7, 89, NULL, '2026-05-04 10:08:56', '2026-05-05 13:31:06'),
(41, 'BK-N21-01', 1, 1, 'available', 1, 100, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(42, 'BK-N21-02', 1, 2, 'available', 1, 95, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(43, 'BK-N21-03', 1, 3, 'available', 1, 90, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(44, 'BK-N21-04', 1, 1, 'available', 1, 85, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(45, 'BK-N21-05', 1, 2, 'available', 1, 80, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(46, 'SC-N21-01', 2, 2, 'available', 1, 88, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(47, 'SC-N21-02', 2, 3, 'available', 1, 76, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(48, 'SC-N21-03', 2, 1, 'available', 1, 92, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(49, 'SC-N21-04', 2, 2, 'available', 1, 69, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(50, 'SC-N21-05', 2, 3, 'available', 1, 83, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(51, 'BK-N8-01', 1, 1, 'available', 2, 100, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(52, 'BK-N8-02', 1, 2, 'available', 2, 95, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(53, 'BK-N8-03', 1, 3, 'available', 2, 90, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(54, 'BK-N8-04', 1, 1, 'available', 2, 85, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(55, 'BK-N8-05', 1, 2, 'available', 2, 80, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(56, 'SC-N8-01', 2, 2, 'available', 2, 88, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(57, 'SC-N8-02', 2, 3, 'available', 2, 76, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(58, 'SC-N8-03', 2, 1, 'available', 2, 92, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(59, 'SC-N8-04', 2, 2, 'available', 2, 69, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(60, 'SC-N8-05', 2, 3, 'available', 2, 83, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(61, 'BK-E2-01', 1, 1, 'available', 3, 100, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(62, 'BK-E2-02', 1, 2, 'available', 3, 95, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(63, 'BK-E2-03', 1, 3, 'available', 3, 90, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(64, 'BK-E2-04', 1, 1, 'available', 3, 85, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(65, 'BK-E2-05', 1, 2, 'available', 3, 80, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(66, 'SC-E2-01', 2, 2, 'available', 3, 88, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(67, 'SC-E2-02', 2, 3, 'available', 3, 76, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(68, 'SC-E2-03', 2, 1, 'available', 3, 92, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(69, 'SC-E2-04', 2, 2, 'available', 3, 69, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(70, 'SC-E2-05', 2, 3, 'available', 3, 83, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(71, 'BK-E6-01', 1, 1, 'available', 4, 100, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(72, 'BK-E6-02', 1, 2, 'available', 4, 95, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(73, 'BK-E6-03', 1, 3, 'available', 4, 90, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(74, 'BK-E6-04', 1, 1, 'available', 4, 85, NULL, '2026-05-05 13:43:57', '2026-05-05 14:01:22'),
(75, 'BK-E6-05', 1, 2, 'available', 4, 80, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(76, 'SC-E6-01', 2, 2, 'available', 4, 88, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(77, 'SC-E6-02', 2, 3, 'available', 4, 76, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(78, 'SC-E6-03', 2, 1, 'available', 4, 92, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(79, 'SC-E6-04', 2, 2, 'available', 4, 69, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(80, 'SC-E6-05', 2, 3, 'available', 4, 83, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(81, 'BK-E11-01', 1, 1, 'available', 5, 100, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(82, 'BK-E11-02', 1, 2, 'available', 5, 95, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(83, 'BK-E11-03', 1, 3, 'available', 5, 90, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(84, 'BK-E11-04', 1, 1, 'available', 2, 85, NULL, '2026-05-05 13:43:57', '2026-05-05 17:57:21'),
(85, 'BK-E11-05', 1, 2, 'available', 5, 80, NULL, '2026-05-05 13:43:57', '2026-05-05 13:45:20'),
(86, 'SC-E11-01', 2, 2, 'available', 5, 88, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(87, 'SC-E11-02', 2, 3, 'available', 5, 76, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(88, 'SC-E11-03', 2, 1, 'available', 5, 92, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(89, 'SC-E11-04', 2, 2, 'available', 5, 69, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(90, 'SC-E11-05', 2, 3, 'available', 5, 83, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(91, 'BK-E21-01', 1, 1, 'available', 6, 100, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(92, 'BK-E21-02', 1, 2, 'available', 6, 95, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(93, 'BK-E21-03', 1, 3, 'available', 6, 90, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(94, 'BK-E21-04', 1, 1, 'available', 6, 85, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(95, 'BK-E21-05', 1, 2, 'available', 6, 80, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(96, 'SC-E21-01', 2, 2, 'available', 6, 88, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(97, 'SC-E21-02', 2, 3, 'available', 6, 76, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(98, 'SC-E21-03', 2, 1, 'available', 6, 92, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(99, 'SC-E21-04', 2, 2, 'available', 6, 69, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(100, 'SC-E21-05', 2, 3, 'available', 6, 83, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(101, 'BK-E31-01', 1, 1, 'available', 7, 100, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(102, 'BK-E31-02', 1, 2, 'available', 7, 95, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(103, 'BK-E31-03', 1, 3, 'available', 7, 90, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(104, 'BK-E31-04', 1, 1, 'available', 7, 85, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(105, 'BK-E31-05', 1, 2, 'available', 7, 80, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(106, 'SC-E31-01', 2, 2, 'available', 7, 88, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(107, 'SC-E31-02', 2, 3, 'available', 7, 76, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(108, 'SC-E31-03', 2, 1, 'available', 7, 92, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(109, 'SC-E31-04', 2, 2, 'available', 7, 69, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(110, 'SC-E31-05', 2, 3, 'available', 7, 83, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(111, 'BK-S8-01', 1, 1, 'available', 8, 100, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(112, 'BK-S8-02', 1, 2, 'available', 8, 95, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(113, 'BK-S8-03', 1, 3, 'available', 8, 90, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(114, 'BK-S8-04', 1, 1, 'available', 8, 85, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(115, 'BK-S8-05', 1, 2, 'available', 2, 80, NULL, '2026-05-05 13:43:57', '2026-05-06 00:38:02'),
(116, 'SC-S8-01', 2, 2, 'available', 8, 88, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(117, 'SC-S8-02', 2, 3, 'available', 8, 76, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(118, 'SC-S8-03', 2, 1, 'available', 8, 92, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(119, 'SC-S8-04', 2, 2, 'available', 8, 69, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57'),
(120, 'SC-S8-05', 2, 3, 'available', 8, 83, NULL, '2026-05-05 13:43:57', '2026-05-05 13:43:57');

-- --------------------------------------------------------

--
-- 資料表結構 `vehicle_types`
--

CREATE TABLE `vehicle_types` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `price_per_30_min` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 傾印資料表的資料 `vehicle_types`
--

INSERT INTO `vehicle_types` (`id`, `name`, `price_per_30_min`, `created_at`) VALUES
(1, 'bicycle', '5.00', '2026-05-04 10:07:15'),
(2, 'scooter', '8.00', '2026-05-04 10:07:15');

-- --------------------------------------------------------

--
-- 資料表結構 `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('topup','rental_charge','adjustment','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- 傾印資料表的資料 `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `user_id`, `type`, `amount`, `balance_after`, `reference_type`, `reference_id`, `note`, `created_at`) VALUES
(1, 1, 'topup', '150.00', '120.00', NULL, NULL, 'Initial top-up for demo', '2026-05-04 10:08:56'),
(2, 11, 'rental_charge', '-16.20', '150.00', 'rental_order', 2, 'Demo rental charge', '2026-05-04 10:08:56'),
(3, 11, 'rental_charge', '-16.20', '150.00', 'rental_order', 5, 'Demo rental charge', '2026-05-04 10:26:04'),
(4, 1, 'topup', '1231.00', '1351.00', 'card_topup', NULL, 'Top-up by credit card ending 2828', '2026-05-04 10:55:27'),
(5, 13, 'topup', '999.00', '9295.05', 'card_topup', NULL, 'Top-up by credit card ending 8888', '2026-05-05 13:46:21'),
(6, 27, 'topup', '1000.00', '1000.00', 'card_topup', NULL, 'Top-up by credit card ending 3456', '2026-05-05 14:00:50'),
(7, 13, 'topup', '100.00', '9390.05', 'admin_test_topup', NULL, 'Admin test balance top-up', '2026-05-05 17:58:17'),
(8, 13, 'topup', '100.00', '9490.05', 'admin_test_topup', NULL, 'Admin test balance top-up', '2026-05-05 18:02:12'),
(9, 13, 'topup', '1000.00', '10490.05', 'admin_test_topup', NULL, 'Admin test balance top-up', '2026-05-05 18:02:18'),
(10, 13, 'topup', '1000.00', '11490.05', 'card_topup', NULL, 'Top-up by credit card ending 4444', '2026-05-05 18:04:16');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `administrators`
--
ALTER TABLE `administrators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `user_name` (`user_name`);

--
-- 資料表索引 `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- 資料表索引 `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_feedback_user` (`user_id`),
  ADD KEY `idx_feedback_status_time` (`status`,`created_at`);

--
-- 資料表索引 `feedback_replies`
--
ALTER TABLE `feedback_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reply_feedback` (`feedback_id`),
  ADD KEY `fk_reply_admin_user` (`admin_user_id`);

--
-- 資料表索引 `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_post_user` (`user_id`),
  ADD KEY `idx_forum_status_time` (`status`,`created_at`);

--
-- 資料表索引 `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `scope_key` (`scope_key`),
  ADD KEY `idx_rate_blocked_until` (`blocked_until`);

--
-- 資料表索引 `rental_orders`
--
ALTER TABLE `rental_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_start_station` (`start_station_id`),
  ADD KEY `fk_order_end_station` (`end_station_id`),
  ADD KEY `idx_orders_user_status` (`user_id`,`status`),
  ADD KEY `idx_orders_vehicle_status` (`vehicle_id`,`status`),
  ADD KEY `idx_orders_start_time` (`start_time`),
  ADD KEY `idx_orders_status_time` (`status`,`start_time`,`end_time`);

--
-- 資料表索引 `rental_stations`
--
ALTER TABLE `rental_stations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- 資料表索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `campus_id` (`campus_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 資料表索引 `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial_no` (`serial_no`),
  ADD KEY `fk_vehicle_brand` (`brand_id`),
  ADD KEY `idx_vehicles_station_status` (`station_id`,`status`),
  ADD KEY `idx_vehicles_type_brand` (`type_id`,`brand_id`),
  ADD KEY `idx_vehicle_status_type` (`status`,`type_id`);

--
-- 資料表索引 `vehicle_types`
--
ALTER TABLE `vehicle_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- 資料表索引 `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wallet_user_time` (`user_id`,`created_at`),
  ADD KEY `idx_wallet_type_time` (`type`,`created_at`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `administrators`
--
ALTER TABLE `administrators`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `brands`
--
ALTER TABLE `brands`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `feedback_replies`
--
ALTER TABLE `feedback_replies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `forum_posts`
--
ALTER TABLE `forum_posts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `rental_orders`
--
ALTER TABLE `rental_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `rental_stations`
--
ALTER TABLE `rental_stations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `vehicle_types`
--
ALTER TABLE `vehicle_types`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `administrators`
--
ALTER TABLE `administrators`
  ADD CONSTRAINT `fk_admin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 資料表的限制式 `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `fk_feedback_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 資料表的限制式 `feedback_replies`
--
ALTER TABLE `feedback_replies`
  ADD CONSTRAINT `fk_reply_admin_user` FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reply_feedback` FOREIGN KEY (`feedback_id`) REFERENCES `feedbacks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 資料表的限制式 `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD CONSTRAINT `fk_post_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 資料表的限制式 `rental_orders`
--
ALTER TABLE `rental_orders`
  ADD CONSTRAINT `fk_order_end_station` FOREIGN KEY (`end_station_id`) REFERENCES `rental_stations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_start_station` FOREIGN KEY (`start_station_id`) REFERENCES `rental_stations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON UPDATE CASCADE;

--
-- 資料表的限制式 `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `fk_vehicle_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vehicle_station` FOREIGN KEY (`station_id`) REFERENCES `rental_stations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vehicle_type` FOREIGN KEY (`type_id`) REFERENCES `vehicle_types` (`id`) ON UPDATE CASCADE;

--
-- 資料表的限制式 `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `fk_wallet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
