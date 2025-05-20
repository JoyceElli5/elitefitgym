-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 20, 2025 at 02:27 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `elitefitgym`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action` varchar(50) DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `activity_type`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 7, 'registration', '', NULL, '::1', NULL, '2025-04-22 11:55:20'),
(2, 7, 'login', '', NULL, '::1', NULL, '2025-04-22 12:12:30'),
(3, 8, 'registration', '', NULL, '::1', NULL, '2025-04-23 12:01:11'),
(4, 8, 'login', '', NULL, '::1', NULL, '2025-04-23 12:01:47'),
(5, 7, 'login', '', NULL, '::1', NULL, '2025-04-26 14:26:23'),
(6, 7, 'login', '', NULL, '::1', NULL, '2025-04-26 14:27:43');

-- --------------------------------------------------------

--
-- Table structure for table `administrators`
--

DROP TABLE IF EXISTS `administrators`;
CREATE TABLE IF NOT EXISTS `administrators` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `access_level` enum('limited','full') DEFAULT 'limited',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `position` varchar(100) NOT NULL,
  `responsibilities` text,
  `access_requirements` text,
  PRIMARY KEY (`admin_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `user_id`, `position`, `responsibilities`, `access_requirements`) VALUES
(1, 2, 'System Admin', 'Admin', 'Admin');

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

DROP TABLE IF EXISTS `billing`;
CREATE TABLE IF NOT EXISTS `billing` (
  `billing_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`billing_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

DROP TABLE IF EXISTS `equipment`;
CREATE TABLE IF NOT EXISTS `equipment` (
  `equipment_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `category` varchar(50) DEFAULT NULL,
  `status` enum('available','maintenance','out_of_order') DEFAULT 'available',
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  PRIMARY KEY (`equipment_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equipment_id`, `name`, `description`, `category`, `status`, `last_maintenance_date`, `next_maintenance_date`, `type`) VALUES
(1, 'Treadmill #1', NULL, NULL, 'available', NULL, NULL, 'Cardio'),
(2, 'Bench Press #1', NULL, NULL, '', NULL, NULL, 'Strength'),
(3, 'Leg Press Machine', NULL, NULL, 'available', NULL, NULL, 'Strength'),
(4, 'Elliptical #2', NULL, NULL, 'maintenance', NULL, NULL, 'Cardio'),
(5, 'Rowing Machine', NULL, NULL, 'available', NULL, NULL, 'Cardio'),
(6, 'Treadmill #1', NULL, NULL, 'available', '2025-03-29', NULL, 'Cardio'),
(7, 'Bench Press #1', NULL, NULL, '', '2025-03-14', NULL, 'Strength'),
(8, 'Leg Press Machine', NULL, NULL, 'available', '2025-04-13', NULL, 'Strength'),
(9, 'Elliptical #2', NULL, NULL, 'maintenance', '2025-04-23', NULL, 'Cardio'),
(10, 'Rowing Machine', NULL, NULL, 'available', '2025-02-27', NULL, 'Cardio'),
(11, 'Dumbell', 'New', 'new', 'available', NULL, NULL, 'Strength'),
(12, 'Joyce Elli', 'Cardio weights', 'new', 'available', NULL, NULL, 'Cardio');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_issues`
--

DROP TABLE IF EXISTS `equipment_issues`;
CREATE TABLE IF NOT EXISTS `equipment_issues` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipment_id` int NOT NULL,
  `reported_by` int NOT NULL,
  `issue` text NOT NULL,
  `status` enum('open','resolved') DEFAULT 'open',
  `reported_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `reported_by` (`reported_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment_managers`
--

DROP TABLE IF EXISTS `equipment_managers`;
CREATE TABLE IF NOT EXISTS `equipment_managers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exercises`
--

DROP TABLE IF EXISTS `exercises`;
CREATE TABLE IF NOT EXISTS `exercises` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `category` enum('strength','cardio','flexibility','balance','other') DEFAULT 'other',
  `equipment_needed` text,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `muscle_group` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

DROP TABLE IF EXISTS `login_logs`;
CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `role` varchar(50) DEFAULT NULL,
  `login_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `email`, `success`, `ip_address`, `user_agent`, `role`, `login_time`, `timestamp`) VALUES
(1, 'alda1@gmail.com', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', NULL, '2025-04-28 01:09:19', '2025-04-28 01:09:19'),
(2, 'elli@gmail.com', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', NULL, '2025-04-28 01:11:58', '2025-04-28 01:11:58'),
(3, 'elli@gmail.com', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', NULL, '2025-04-28 01:12:35', '2025-04-28 01:12:35'),
(4, 'admin@elitefitgym.com', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', NULL, '2025-04-28 09:27:35', '2025-04-28 09:27:35'),
(5, 'admin@elitefitgym.com', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', NULL, '2025-04-28 15:41:32', '2025-04-28 15:41:32'),
(6, 'admin@elitefitgym.com', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', NULL, '2025-04-28 15:41:59', '2025-04-28 15:41:59'),
(7, 'admin@elitefitgym.com', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', NULL, '2025-04-28 15:42:27', '2025-04-28 15:42:27'),
(8, 'elliattawah23@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'EquipmentManager', '2025-04-28 16:42:24', '2025-04-28 16:42:24'),
(9, 'elliattawah23@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'EquipmentManager', '2025-04-28 17:29:04', '2025-04-28 17:29:04'),
(10, 'elliattawah23@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'EquipmentManager', '2025-04-28 17:33:30', '2025-04-28 17:33:30'),
(11, 'admin@elitefitgym.com', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', NULL, '2025-04-29 04:35:43', '2025-04-29 04:35:43'),
(12, 'elliattawah23@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'EquipmentManager', '2025-04-29 04:35:49', '2025-04-29 04:35:49'),
(13, 'elliattawah23@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'EquipmentManager', '2025-04-29 10:32:46', '2025-04-29 10:32:46'),
(14, 'elliattawah23@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'EquipmentManager', '2025-04-29 10:36:55', '2025-04-29 10:36:55'),
(15, 'elliattawah23@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'EquipmentManager', '2025-04-29 10:47:01', '2025-04-29 10:47:01'),
(16, 'elliattawah23@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'EquipmentManager', '2025-04-29 10:53:18', '2025-04-29 10:53:18'),
(17, 'elliattawah23@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'EquipmentManager', '2025-04-29 10:53:31', '2025-04-29 10:53:31'),
(18, 'elliattawah23@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'EquipmentManager', '2025-04-29 10:55:33', '2025-04-29 10:55:33'),
(19, 'ellijoyce7@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'Admin', '2025-04-29 11:02:05', '2025-04-29 11:02:05'),
(20, 'ellijoyce7@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'Admin', '2025-04-29 12:22:16', '2025-04-29 12:22:16'),
(21, 'elliattawah23@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'EquipmentManager', '2025-04-29 12:22:35', '2025-04-29 12:22:35'),
(22, 'elliattawah23@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'EquipmentManager', '2025-04-29 12:47:01', '2025-04-29 12:47:01'),
(23, 'dorothy@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'EquipmentManager', '2025-05-02 11:11:03', '2025-05-02 11:11:03'),
(24, 'dorothy@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'EquipmentManager', '2025-05-02 11:15:20', '2025-05-02 11:15:20'),
(25, 'book@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'Member', '2025-05-06 04:19:55', '2025-05-06 04:19:55'),
(26, 'book@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'Member', '2025-05-06 04:27:16', '2025-05-06 04:27:16'),
(27, 'frank@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'Trainer', '2025-05-06 05:29:26', '2025-05-06 05:29:26'),
(28, 'frank@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'Trainer', '2025-05-06 05:59:37', '2025-05-06 05:59:37'),
(29, 'kwaku@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'Member', '2025-05-06 10:40:17', '2025-05-06 10:40:17'),
(30, 'elliattawah23@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'EquipmentManager', '2025-05-19 14:30:23', '2025-05-19 14:30:23'),
(31, 'elliattawah23@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'EquipmentManager', '2025-05-19 14:33:43', '2025-05-19 14:33:43'),
(32, 'julius@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'Member', '2025-05-19 14:34:23', '2025-05-19 14:34:23'),
(33, 'elliattawah23@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'EquipmentManager', '2025-05-19 14:51:24', '2025-05-19 14:51:24'),
(34, 'emmay@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'Trainer', '2025-05-19 15:38:58', '2025-05-19 15:38:58'),
(35, 'emmay@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'Trainer', '2025-05-19 16:18:41', '2025-05-19 16:18:41'),
(36, 'emmay@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'Trainer', '2025-05-19 16:19:05', '2025-05-19 16:19:05'),
(37, 'ama@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'Member', '2025-05-19 17:03:57', '2025-05-19 17:03:57'),
(38, 'ama@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'Member', '2025-05-19 18:00:13', '2025-05-19 18:00:13'),
(39, 'ama@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'Member', '2025-05-19 18:13:23', '2025-05-19 18:13:23'),
(40, 'adwoa@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'Member', '2025-05-19 18:45:47', '2025-05-19 18:45:47'),
(41, 'abena@gmail.com', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'Member', '2025-05-19 19:03:55', '2025-05-19 19:03:55');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_logs`
--

DROP TABLE IF EXISTS `maintenance_logs`;
CREATE TABLE IF NOT EXISTS `maintenance_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `equipment_id` int NOT NULL,
  `manager_id` int NOT NULL,
  `maintenance_date` date NOT NULL,
  `description` text,
  `status` enum('scheduled','in_progress','completed') DEFAULT 'scheduled',
  PRIMARY KEY (`log_id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `manager_id` (`manager_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_schedule`
--

DROP TABLE IF EXISTS `maintenance_schedule`;
CREATE TABLE IF NOT EXISTS `maintenance_schedule` (
  `schedule_id` int NOT NULL AUTO_INCREMENT,
  `equipment_id` int NOT NULL,
  `scheduled_date` date NOT NULL,
  `description` text NOT NULL,
  `status` enum('Scheduled','In Progress','Completed','Overdue') NOT NULL DEFAULT 'Scheduled',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `maintenance_schedule`
--

INSERT INTO `maintenance_schedule` (`schedule_id`, `equipment_id`, `scheduled_date`, `description`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 4, '2025-04-30', 'Replace belt and lubricate', 'Scheduled', 1, '2025-04-28 17:07:40', '2025-04-28 17:07:40'),
(2, 1, '2025-05-05', 'Regular maintenance check', 'Scheduled', 1, '2025-04-28 17:07:40', '2025-04-28 17:07:40'),
(3, 11, '2025-05-19', 'First schedule', 'Scheduled', 0, '2025-05-19 15:25:54', '2025-05-19 15:25:54');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
CREATE TABLE IF NOT EXISTS `members` (
  `member_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `experience_level` enum('Beginner','Intermediate','Advanced','Professional') NOT NULL,
  `fitness_goals` text,
  `preferred_routines` text,
  PRIMARY KEY (`member_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`member_id`, `user_id`, `experience_level`, `fitness_goals`, `preferred_routines`) VALUES
(1, 6, 'Beginner', 'Six packs', 'Daily'),
(2, 7, 'Beginner', 'Six packs', 'Daily'),
(3, 10, 'Intermediate', 'Six packs', 'Everyday'),
(4, 12, 'Intermediate', 'Slim', 'Daily'),
(5, 13, 'Intermediate', 'To slim down', 'Daily'),
(6, 14, 'Beginner', 'Weight  gain', 'Daily'),
(7, 15, 'Beginner', 'Weight Loss', 'Daily'),
(8, 16, 'Intermediate', 'Weight Loss', 'Daily'),
(9, 17, 'Intermediate', 'Weight Loss', 'Daily'),
(10, 18, 'Intermediate', 'Weight Loss', 'Daily'),
(11, 19, 'Beginner', 'Weight Loss', 'Daily'),
(12, 20, 'Beginner', 'Muscle gain', 'Daily'),
(13, 21, 'Beginner', 'Muscle Gain', 'Daily'),
(14, 22, 'Intermediate', 'Weight gain', 'Daily'),
(15, 23, 'Beginner', 'asdf', 'daily'),
(16, 24, 'Beginner', 'asdgg', 'daily'),
(17, 25, 'Beginner', 'Gain', 'ggg');

-- --------------------------------------------------------

--
-- Table structure for table `member_profiles`
--

DROP TABLE IF EXISTS `member_profiles`;
CREATE TABLE IF NOT EXISTS `member_profiles` (
  `profile_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `fitness_goals` text,
  `experience_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `preferred_workout_types` text,
  `health_conditions` text,
  `date_of_birth` date DEFAULT NULL,
  `height` float DEFAULT NULL,
  `weight` float DEFAULT NULL,
  PRIMARY KEY (`profile_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `member_profiles`
--

INSERT INTO `member_profiles` (`profile_id`, `user_id`, `fitness_goals`, `experience_level`, `preferred_workout_types`, `health_conditions`, `date_of_birth`, `height`, `weight`) VALUES
(1, 2, NULL, 'beginner', NULL, NULL, NULL, NULL, NULL),
(2, 3, NULL, 'beginner', NULL, NULL, NULL, NULL, NULL),
(3, 14, 'Weight  gain', 'beginner', 'Cardio', 'NOne', '2003-04-19', 170, 60),
(4, 15, 'Weight Loss', 'beginner', 'Cardio', 'None', '2000-04-05', 127, 50),
(5, 16, 'Weight Loss', 'intermediate', 'Cardio', 'None', '2000-04-04', 123, 80),
(6, 17, 'Weight Loss', 'intermediate', 'Cardio', 'None', '2000-04-04', 123, 80),
(7, 18, 'Weight Loss', 'intermediate', 'Cardio', 'None', '2000-09-09', 123, 60),
(8, 19, 'Weight Loss', 'beginner', 'Cardio', 'None', '2000-08-09', 123, 68),
(9, 20, 'Muscle gain', 'beginner', 'Strength Training', 'None', '2000-07-09', 213, 67),
(10, 21, 'Muscle Gain', 'beginner', 'Sreanth', 'None', '2000-08-09', 234, 25),
(11, 22, 'Weight gain', 'intermediate', 'Cardio', 'None', '2000-07-08', 234, 54),
(12, 23, 'asdf', 'beginner', 'cardio', 'none', '2000-04-09', 345, 54),
(13, 24, 'asdgg', 'beginner', 'cardio', 'none', '2000-09-09', 234, 34),
(14, 25, 'Gain', 'beginner', 'Cardio', 'no', '2000-09-09', 234, 45);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_status` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_read` tinyint(1) DEFAULT '0',
  `type` varchar(50) DEFAULT NULL,
  `related_id` int DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

DROP TABLE IF EXISTS `otp_codes`;
CREATE TABLE IF NOT EXISTS `otp_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `email` varchar(100) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `verified` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `otp_codes`
--

INSERT INTO `otp_codes` (`id`, `user_id`, `email`, `otp_code`, `created_at`, `expires_at`, `verified`) VALUES
(1, 16, 'obolo@gmail.com', '784327', '2025-05-20 11:59:49', '2025-05-20 12:14:49', 0),
(2, 17, 'westjason980@gmail.com', '990023', '2025-05-20 12:02:34', '2025-05-20 12:17:34', 0),
(3, 18, 'henrybirch@gmail.com', '448887', '2025-05-20 12:37:33', '2025-05-20 12:52:33', 0),
(4, 19, 'bbnanayaw27@gmail.com', '461420', '2025-05-20 12:57:28', '2025-05-20 13:12:28', 0),
(5, 20, 'maxgrip500@gmail.com', '288311', '2025-05-20 13:22:07', '2025-05-20 13:37:07', 0),
(9, 21, 'marylyngrip@gmail.com', '104411', '2025-05-20 13:34:09', '2025-05-20 13:49:09', 0),
(10, 22, 'maxgrip500@gmail.com', '171876', '2025-05-20 13:42:05', '2025-05-20 13:57:05', 0),
(15, 23, 'marylyngrip@gmail.com', '822454', '2025-05-20 13:54:17', '2025-05-20 14:09:17', 0),
(17, 24, 'maxgrip500@gmail.com', '494843', '2025-05-20 14:09:45', '2025-05-20 14:24:45', 0),
(21, 25, 'marylyngrip@gmail.com', '523479', '2025-05-20 14:16:08', '2025-05-20 14:31:08', 1);

-- --------------------------------------------------------

--
-- Table structure for table `progress`
--

DROP TABLE IF EXISTS `progress`;
CREATE TABLE IF NOT EXISTS `progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `log_date` date NOT NULL,
  `metric` varchar(100) NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `progress_tracking`
--

DROP TABLE IF EXISTS `progress_tracking`;
CREATE TABLE IF NOT EXISTS `progress_tracking` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` int UNSIGNED NOT NULL,
  `tracking_date` date NOT NULL,
  `weight` float DEFAULT NULL,
  `body_fat_percentage` float DEFAULT NULL,
  `chest_measurement` float DEFAULT NULL,
  `waist_measurement` float DEFAULT NULL,
  `hip_measurement` float DEFAULT NULL,
  `arm_measurement` float DEFAULT NULL,
  `thigh_measurement` float DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registration_logs`
--

DROP TABLE IF EXISTS `registration_logs`;
CREATE TABLE IF NOT EXISTS `registration_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `role` varchar(100) NOT NULL,
  `message` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `registration_logs`
--

INSERT INTO `registration_logs` (`id`, `email`, `success`, `role`, `message`, `ip_address`, `created_at`, `timestamp`) VALUES
(1, 'ellijoyce7@gmail.com', 0, 'EquipmentManager', 'Password must be at least 8 characters long and include uppercase, lowercase, number, and special character', '::1', '2025-04-26 18:50:35', '2025-04-26 18:50:35'),
(2, 'lovelace@gmail.com', 0, 'EquipmentManager', 'Password must be at least 8 characters long and include uppercase, lowercase, number, and special character', '::1', '2025-04-26 18:52:22', '2025-04-26 18:52:22'),
(3, 'lovelace.baidoo@st.rmu.edu.gh', 0, 'EquipmentManager', 'Database error: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'id\' in \'field list\'', '::1', '2025-04-26 18:54:25', '2025-04-26 18:54:25'),
(4, 'ellijoycelyn1@gmail.com', 0, 'EquipmentManager', 'Database error: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'id\' in \'field list\'', '::1', '2025-04-27 22:45:21', '2025-04-27 22:45:21'),
(5, 'alda@gmail.com', 0, 'Member', 'Database error: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'id\' in \'field list\'', '::1', '2025-04-27 22:47:14', '2025-04-27 22:47:14'),
(6, 'elli@gmail.com', 0, 'EquipmentManager', 'Database error: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'id\' in \'field list\'', '::1', '2025-04-28 01:11:23', '2025-04-28 01:11:23'),
(7, 'dela@gmail.com', 0, 'EquipmentManager', 'Password must be at least 8 characters long and include uppercase, lowercase, number, and special character', '::1', '2025-04-28 09:19:15', '2025-04-28 09:19:15'),
(8, 'junior@gmail.com', 0, 'EquipmentManager', 'Database error: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'experience_level\' in \'field list\'', '::1', '2025-04-28 15:44:02', '2025-04-28 15:44:02'),
(9, 'elliattawah23@gmail.com', 0, 'Admin', 'Database error: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'experience_level\' in \'field list\'', '::1', '2025-04-28 16:12:16', '2025-04-28 16:12:16'),
(10, 'elliattawah23@gmail.com', 0, 'EquipmentManager', 'Database error: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'experience_level\' in \'field list\'', '::1', '2025-04-28 16:25:33', '2025-04-28 16:25:33'),
(11, 'elliattawah23@gmail.com', 0, 'EquipmentManager', 'Database error: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'area_of_expertise\' in \'field list\'', '::1', '2025-04-28 16:32:34', '2025-04-28 16:32:34'),
(12, 'elliattawah23@gmail.com', 0, 'EquipmentManager', 'Email already exists', '::1', '2025-04-28 16:41:40', '2025-04-28 16:41:40'),
(13, 'ellijoyce7@gmail.com', 0, 'Admin', 'Password must be at least 8 characters long and include uppercase, lowercase, number, and special character', '::1', '2025-04-29 11:00:21', '2025-04-29 11:00:21'),
(14, 'ellijoyce7@gmail.com', 1, 'Admin', 'Registration successful', '::1', '2025-04-29 11:01:57', '2025-04-29 11:01:57'),
(15, 'emma@gmail.com', 0, 'Trainer', 'Database error: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'professional_experience\' in \'field list\'', '::1', '2025-04-29 11:06:49', '2025-04-29 11:06:49'),
(16, 'emma@gmail.com', 0, 'Trainer', 'Email already exists', '::1', '2025-04-29 11:22:38', '2025-04-29 11:22:38'),
(17, 'harry-agyemang@gmail.com', 0, 'EquipmentManager', 'Database error: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'area_of_expertise\' in \'field list\'', '::1', '2025-04-29 12:45:51', '2025-04-29 12:45:51'),
(18, 'dorothy@gmail.com', 0, 'EquipmentManager', 'Database error: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'area_of_expertise\' in \'field list\'', '::1', '2025-05-02 11:07:09', '2025-05-02 11:07:09'),
(19, 'book@gmail.com', 1, 'Member', 'Registration successful', '::1', '2025-05-06 04:19:45', '2025-05-06 04:19:45'),
(20, 'kwaku@gmail.com', 1, 'Member', 'Registration successful', '::1', '2025-05-06 04:55:40', '2025-05-06 04:55:40'),
(21, 'frank@gmail.com', 0, 'Trainer', 'Database error: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'professional_experience\' in \'field list\'', '::1', '2025-05-06 05:25:14', '2025-05-06 05:25:14'),
(22, 'godwin@gmail.com', 0, 'Trainer', 'Database error: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'professional_experience\' in \'field list\'', '::1', '2025-05-06 05:47:10', '2025-05-06 05:47:10'),
(23, 'julius@gmail.com', 1, 'Member', 'Registration successful', '::1', '2025-05-19 14:33:31', '2025-05-19 14:33:31'),
(24, 'emmay@gmail.com', 0, 'Trainer', 'Database error: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'professional_experience\' in \'field list\'', '::1', '2025-05-19 14:40:59', '2025-05-19 14:40:59'),
(25, 'ama@gmail.com', 1, 'Member', 'Registration successful', '::1', '2025-05-19 17:03:37', '2025-05-19 17:03:37'),
(26, 'adwoa@gmail.com', 1, 'Member', 'Registration successful', '::1', '2025-05-19 18:45:27', '2025-05-19 18:45:27'),
(27, 'abena@gmail.com', 0, 'Member', 'Password must be at least 8 characters long and include uppercase, lowercase, number, and special character', '::1', '2025-05-19 19:02:34', '2025-05-19 19:02:34'),
(28, 'abena@gmail.com', 1, 'Member', 'Registration successful', '::1', '2025-05-19 19:03:50', '2025-05-19 19:03:50'),
(29, 'bebe@gmail.com', 1, 'Member', 'Registration successful', '::1', '2025-05-20 11:18:04', '2025-05-20 11:18:04'),
(30, 'obolo@gmail.com', 1, 'Member', 'Registration initiated, OTP verification pending', '::1', '2025-05-20 11:59:51', '2025-05-20 11:59:51'),
(31, 'westjason980@gmail.com', 1, 'Member', 'Registration initiated, OTP verification pending', '::1', '2025-05-20 12:02:36', '2025-05-20 12:02:36'),
(32, 'westjason980@gmail.com', 0, 'Member', 'Email already exists', '::1', '2025-05-20 12:35:37', '2025-05-20 12:35:37'),
(33, 'henrybirch@gmail.com', 1, 'Member', 'Registration initiated, OTP verification pending', '::1', '2025-05-20 12:37:33', '2025-05-20 12:37:33'),
(34, 'bbnanayaw27@gmail.com', 1, 'Member', 'Registration initiated, OTP verification pending', '::1', '2025-05-20 12:57:29', '2025-05-20 12:57:29'),
(35, 'maxgrip500@gmail.com', 1, 'Member', 'Registration initiated, OTP verification pending', '::1', '2025-05-20 13:22:07', '2025-05-20 13:22:07'),
(36, 'marylyngrip@gmail.com', 1, 'Member', 'Registration initiated, OTP verification pending', '::1', '2025-05-20 13:32:32', '2025-05-20 13:32:32'),
(37, 'marylyngrip@gmail.com', 0, 'Member', 'Invalid or expired OTP', '::1', '2025-05-20 13:34:03', '2025-05-20 13:34:03'),
(38, 'maxgrip500@gmail.com', 1, 'Member', 'Registration initiated, OTP verification pending', '::1', '2025-05-20 13:42:05', '2025-05-20 13:42:05'),
(39, 'marylyngrip@gmail.com', 1, 'Member', 'Registration initiated, OTP verification pending', '::1', '2025-05-20 13:53:04', '2025-05-20 13:53:04'),
(40, 'maxgrip500@gmail.com', 0, 'Member', 'Password must be at least 8 characters long and include uppercase, lowercase, number, and special character', '::1', '2025-05-20 13:59:27', '2025-05-20 13:59:27'),
(41, 'maxgrip500@gmail.com', 0, 'Member', 'Password must be at least 8 characters long and include uppercase, lowercase, number, and special character', '::1', '2025-05-20 14:00:46', '2025-05-20 14:00:46'),
(42, 'maxgrip500@gmail.com', 1, 'Member', 'Registration initiated, OTP verification pending', '::1', '2025-05-20 14:02:37', '2025-05-20 14:02:37'),
(43, 'marylyngrip@gmail.com', 1, 'Member', 'Registration initiated, OTP verification pending', '::1', '2025-05-20 14:11:07', '2025-05-20 14:11:07'),
(44, 'marylyngrip@gmail.com', 1, 'Member', 'Email verification successful', '::1', '2025-05-20 14:16:38', '2025-05-20 14:16:38');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `session_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `trainer_id` int DEFAULT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `notes` text,
  PRIMARY KEY (`session_id`),
  KEY `member_id` (`member_id`),
  KEY `trainer_id` (`trainer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `subscription_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `plan_type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`subscription_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

DROP TABLE IF EXISTS `trainers`;
CREATE TABLE IF NOT EXISTS `trainers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `certification` varchar(255) DEFAULT NULL,
  `bio` text,
  `years_of_experience` int DEFAULT '0',
  `hourly_rate` decimal(10,2) DEFAULT '0.00',
  `availability` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainer_profiles`
--

DROP TABLE IF EXISTS `trainer_profiles`;
CREATE TABLE IF NOT EXISTS `trainer_profiles` (
  `profile_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `specialization` text,
  `certification` text,
  `experience_years` int DEFAULT NULL,
  `bio` text,
  `professional_experience` text,
  `availability` text,
  PRIMARY KEY (`profile_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Member','Trainer','Admin','EquipmentManager') NOT NULL,
  `verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `verified`, `created_at`, `updated_at`) VALUES
(1, 'Joyce Elli', 'elliattawah23@gmail.com', '$2y$10$0RZHLRSuYDW7TbjFZVa7X.lVZV74eFXYnDagsgpdvJ7wYI5SOsBk.', 'EquipmentManager', 0, '2025-04-28 16:32:34', '2025-04-28 16:32:34'),
(2, 'Attawah Elli', 'ellijoyce7@gmail.com', '$2y$10$uxcmqB0635MwvJHtar4w7.mZgxWCbN2Zsp4XG3w/j.z5GkxdT2t/S', 'Admin', 0, '2025-04-29 11:01:57', '2025-04-29 11:01:57'),
(3, 'Emma Atakora', 'emma@gmail.com', '$2y$10$ujABpFjs1nH.X680GG7u3OgiaNAoTnu21Bo4LzbjZKO4EA169E/Ne', 'Trainer', 0, '2025-04-29 11:06:49', '2025-04-29 11:06:49'),
(4, 'Harry Johnson', 'harry-agyemang@gmail.com', '$2y$10$bmwR/yyMlMurVlYpj5ujgOit4oA/NOl2PcPOJq1s3LHE/QF2SCtfu', 'EquipmentManager', 0, '2025-04-29 12:45:51', '2025-04-29 12:45:51'),
(5, 'Dorothy', 'dorothy@gmail.com', '$2y$10$8REanGJXtukQOSy/wGboveJ9ehK8yCA4NxKtRMvgV3AVUWqPZp81K', 'EquipmentManager', 0, '2025-05-02 11:07:09', '2025-05-02 11:07:09'),
(6, 'Book 2', 'book@gmail.com', '$2y$10$9B5PxBBYyj0soqwR03iQl.BEL1YaLvGM3KtD6xYdtEey.P/OqYy1e', 'Member', 0, '2025-05-06 04:19:45', '2025-05-06 04:19:45'),
(7, 'Kwaku Joshua', 'kwaku@gmail.com', '$2y$10$v4LnoFZf1Jp3Nx7ezpjYqOtdh3x8fsUzUihVzazf6KV1d4RV4JaMK', 'Member', 0, '2025-05-06 04:55:40', '2025-05-06 04:55:40'),
(8, 'Trainer Frank', 'frank@gmail.com', '$2y$10$Zs6op.f.K6mfubGeTQo4h.FeLFIDmh7WFd6BQBRYpvOkLoCVmkAyK', 'Trainer', 0, '2025-05-06 05:25:14', '2025-05-06 05:25:14'),
(9, 'Trainer Godwin', 'godwin@gmail.com', '$2y$10$YMc/oVT8AoiFHGT/4Rw/Z.7zOweP/l.tF8WHuy9GeZGOFOvY4Vxs6', 'Trainer', 0, '2025-05-06 05:47:10', '2025-05-06 05:47:10'),
(10, 'Julius Yawli', 'julius@gmail.com', '$2y$10$nGnkVqrsVc2TtkV8NUzPOeTgjAaccjALFv.VFmKP0jLQS3WbJo3Ei', 'Member', 0, '2025-05-19 14:33:31', '2025-05-19 14:33:31'),
(11, 'Emma Yaw', 'emmay@gmail.com', '$2y$10$/gTayk1s/qKqjQU1G3PCP.fBIl4YN0FG7zrfQOGAq5lY6ELXoRhmC', 'Trainer', 0, '2025-05-19 14:40:59', '2025-05-19 14:40:59'),
(12, 'Ama KK', 'ama@gmail.com', '$2y$10$LQS1j0dJcT.WwSfu6Wq.DOzJksOM08O68GxijAYq6mwWMp8370OuS', 'Member', 0, '2025-05-19 17:03:37', '2025-05-19 17:03:37'),
(13, 'Adwoa KK', 'adwoa@gmail.com', '$2y$10$7WxaF/FHWMcfR3bXtp26eOC97gc9Kbntu4Oqo2ISUFFe/En9xtKVi', 'Member', 0, '2025-05-19 18:45:27', '2025-05-19 18:45:27'),
(14, 'Abena Praba', 'abena@gmail.com', '$2y$10$zI/4kWwXcrgxsZvsiRuFgOPDft6rlov25mArXNE3d09V9cl0phol2', 'Member', 0, '2025-05-19 19:03:50', '2025-05-19 19:03:50'),
(15, 'Bebe Elli', 'bebe@gmail.com', '$2y$10$hefY1gyYnr5jiH8V2b2FIelgu1.qeUtRt36inuAM2Eyvo8cwlV3eW', 'Member', 0, '2025-05-20 11:18:04', '2025-05-20 11:18:04'),
(16, 'Obolo Bernard', 'obolo@gmail.com', '$2y$10$wc.vWYW0RHITNJwy0pTNg.d/yW3PI9kWN2a/I4RnKagTH0.dNChia', 'Member', 0, '2025-05-20 11:59:49', '2025-05-20 11:59:49'),
(17, 'Obolo Bernard', 'westjason980@gmail.com', '$2y$10$5fvD1mBJeQNL4QobX4UvVee/99erkVSdmQNAZOJQChpTxcLVmLUDm', 'Member', 0, '2025-05-20 12:02:34', '2025-05-20 12:02:34'),
(18, 'Obolo jj', 'henrybirch@gmail.com', '$2y$10$4wqE3f.gipUWdtMuZK8Ft.aYnZY1IY11okBRi17ks9J2cd2qfBije', 'Member', 0, '2025-05-20 12:37:33', '2025-05-20 12:37:33'),
(19, 'NanaYaw Boateng', 'bbnanayaw27@gmail.com', '$2y$10$cegw3Ep2KhTJBNf6tUPMm.LkW5Wf8xhAmcsQWNOVUJjIPD9PgWgbO', 'Member', 0, '2025-05-20 12:57:28', '2025-05-20 12:57:28'),
(25, 'Junior Fianko', 'marylyngrip@gmail.com', '$2y$10$GaTPt4DNRVzGPI9fV4iSVeBV3OdOh4qz0ZiBCIzQH3W26qItrjWES', 'Member', 1, '2025-05-20 14:11:07', '2025-05-20 14:16:38'),
(24, 'Junior Fianko', 'maxgrip500@gmail.com', '$2y$10$wm8Tm.R90BdwrCNBOcIkeeyH/fyT2j0BNzqYrNPAOfy4VXv5b/kVW', 'Member', 0, '2025-05-20 14:02:33', '2025-05-20 14:02:33');

-- --------------------------------------------------------

--
-- Table structure for table `workout_exercises`
--

DROP TABLE IF EXISTS `workout_exercises`;
CREATE TABLE IF NOT EXISTS `workout_exercises` (
  `exercise_id` int NOT NULL AUTO_INCREMENT,
  `plan_id` int NOT NULL,
  `equipment_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `sets` int DEFAULT NULL,
  `reps` int DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `rest_time` int DEFAULT NULL,
  `day_of_week` varchar(10) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`exercise_id`),
  KEY `plan_id` (`plan_id`),
  KEY `equipment_id` (`equipment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workout_plans`
--

DROP TABLE IF EXISTS `workout_plans`;
CREATE TABLE IF NOT EXISTS `workout_plans` (
  `plan_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `trainer_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('draft','pending','active','completed') DEFAULT 'draft',
  PRIMARY KEY (`plan_id`),
  KEY `member_id` (`member_id`),
  KEY `trainer_id` (`trainer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
