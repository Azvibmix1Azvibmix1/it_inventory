-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 05, 2026 at 12:31 PM
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
-- Database: `it_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `asset_tag` varchar(50) NOT NULL,
  `serial_no` varchar(100) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `location_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `asset_tag`, `serial_no`, `brand`, `model`, `type`, `status`, `location_id`, `assigned_to`, `created_by`, `created_at`, `purchase_date`, `warranty_expiry`, `notes`) VALUES
(29, 'AST-000029', 'asfgdf11', 'dell', '2024', 'Desktop', 'Active', 36, 9, 7, '2026-01-05 12:58:02', '2026-01-05', '2026-01-09', ''),
(30, 'AST-000030', '12455', '', '', 'Desktop', 'Active', 36, 7, 7, '2026-01-05 14:20:54', '2026-01-05', '2026-04-07', '');

-- --------------------------------------------------------

--
-- Table structure for table `asset_logs`
--

CREATE TABLE `asset_logs` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `asset_logs`
--

INSERT INTO `asset_logs` (`id`, `asset_id`, `user_id`, `action`, `details`, `created_at`) VALUES
(1, 27, 7, 'assign', 'تسليم الجهاز للموظف: a | AssetID=27 | UserID=9', '2026-01-05 08:22:14'),
(2, 27, 7, 'unassign', 'إلغاء تسليم الجهاز | AssetID=27', '2026-01-05 08:22:15'),
(3, 26, 7, 'delete', 'حذف جهاز | Tag=AST-000026 | Type=Laptop | AssetID=26', '2026-01-05 08:24:19'),
(4, 28, 7, 'create', 'إضافة جهاز | Tag=AST-20260105-064607-134B | Type=Desktop | LocationID=36', '2026-01-05 08:46:07'),
(5, 28, 7, 'update', 'تاريخ الشراء: 2026-01-01 →  | انتهاء الضمان: 2026-06-10 →  | ملاحظات: جهاز عرض → ', '2026-01-05 11:11:54'),
(6, 28, 7, 'update', 'تم حفظ التعديل بدون تغييرات واضحة', '2026-01-05 11:12:01'),
(7, 29, 7, 'create', 'إضافة جهاز | Tag=AST-20260105-105802-1F87 | Type=Desktop | LocationID=36', '2026-01-05 12:58:02'),
(8, 28, 7, 'delete', 'حذف جهاز | Tag=AST-000028 | Type=Desktop | AssetID=28', '2026-01-05 12:58:59'),
(9, 30, 7, 'create', 'إضافة جهاز | Tag=AST-20260105-122054-F13D | Type=Desktop | LocationID=36', '2026-01-05 14:20:54');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'Building',
  `parent_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `name_ar`, `name_en`, `type`, `parent_id`, `created_at`) VALUES
(14, 'الرئيسي طلاب', 'asfan', 'College', NULL, '2025-12-26 20:06:48'),
(35, 'مبنى : 8', '', 'Building', 14, '2026-01-04 14:49:02'),
(36, 'معمل : 1', '', 'Lab', 35, '2026-01-04 14:49:32'),
(45, 'معمل : 2', '', 'Lab', 35, '2026-01-05 11:07:52'),
(46, 'معمل : 3', '', 'Lab', 35, '2026-01-05 11:08:05'),
(47, 'معمل : 4', '', 'Lab', 35, '2026-01-05 11:08:17'),
(48, 'معمل : 5', '', 'Lab', 35, '2026-01-05 11:08:26'),
(49, 'معمل : 6', '', 'Lab', 35, '2026-01-05 11:08:43'),
(50, 'معمل : 7', '', 'Lab', 35, '2026-01-05 11:09:01'),
(51, 'معمل : 8', '', 'Lab', 35, '2026-01-05 11:09:13'),
(52, 'معمل : 9', '', 'Lab', 35, '2026-01-05 11:09:27');

-- --------------------------------------------------------

--
-- Table structure for table `locations_audit`
--

CREATE TABLE `locations_audit` (
  `id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations_audit`
--

INSERT INTO `locations_audit` (`id`, `location_id`, `user_id`, `action`, `details`, `created_at`) VALUES
(1, 34, 8, 'update_location', '{\"id\":34,\"name_ar\":\"20\",\"name_en\":\"20\",\"type\":\"Building\",\"parent_id\":14}', '2026-01-02 19:25:22'),
(2, 34, 8, 'update_permissions', 'Updated location permissions', '2026-01-02 19:25:50'),
(3, 34, 8, 'update_permissions', 'Updated location permissions', '2026-01-02 19:26:09'),
(4, 34, 8, 'update_permissions', 'Updated location permissions', '2026-01-02 19:26:15'),
(5, 34, 8, 'update_permissions', 'Updated location permissions', '2026-01-02 19:34:48'),
(6, 33, 7, 'delete_location', 'Deleted', '2026-01-03 10:52:08'),
(7, 29, 7, 'update_permissions', 'Updated location permissions', '2026-01-03 10:59:12'),
(8, 14, 7, 'update_permissions', 'Updated location permissions', '2026-01-03 10:59:53'),
(9, 14, 7, 'update_permissions', 'Updated location permissions', '2026-01-03 10:59:58'),
(10, 14, 7, 'update_permissions', 'Updated location permissions', '2026-01-03 11:49:20'),
(11, 14, 7, 'update_permissions', 'Updated location permissions', '2026-01-03 11:49:47'),
(12, 14, 7, 'update_permissions', 'Updated location permissions', '2026-01-03 11:50:37'),
(13, 14, 7, 'update_permissions', 'Updated location permissions', '2026-01-03 11:51:24'),
(14, 34, 7, 'delete_location', 'Deleted', '2026-01-03 18:23:27'),
(15, 32, 7, 'delete_location', 'Deleted', '2026-01-03 18:23:40'),
(16, 29, 7, 'delete_location', 'Deleted', '2026-01-03 18:23:44'),
(17, 14, 7, 'update_permissions', 'Updated location permissions', '2026-01-03 19:37:33'),
(18, 14, 7, 'add_location', '{\"name_ar\":\"20\",\"name_en\":\"\",\"type\":\"Building\",\"parent_id\":14,\"name_err\":\"\"}', '2026-01-04 11:49:02'),
(19, 35, 7, 'add_location', '{\"name_ar\":\"1\",\"name_en\":\"\",\"type\":\"Lab\",\"parent_id\":35,\"name_err\":\"\"}', '2026-01-04 11:49:32'),
(20, 14, 7, 'update_permissions', 'Updated location permissions', '2026-01-04 11:50:34'),
(21, 14, 7, 'update_permissions', 'Updated location permissions', '2026-01-04 11:50:40'),
(22, 14, 7, 'update_permissions', 'Updated location permissions', '2026-01-04 11:50:43'),
(23, 14, 7, 'update_permissions', 'Updated location permissions', '2026-01-04 11:51:01'),
(24, 14, 7, 'update_permissions', 'Updated location permissions', '2026-01-04 11:51:05'),
(25, 14, 7, 'update_permissions', 'Updated location permissions', '2026-01-04 11:51:07'),
(26, 14, 7, 'update_location', '{\"id\":14,\"name_ar\":\"الرئيسي طلاب\",\"name_en\":\"asfan\",\"type\":\"College\",\"parent_id\":null}', '2026-01-05 08:02:39'),
(27, 35, 7, 'update_location', '{\"id\":35,\"name_ar\":\"مبنى : 20\",\"name_en\":\"\",\"type\":\"Building\",\"parent_id\":14}', '2026-01-05 08:03:23'),
(28, 36, 7, 'update_location', '{\"id\":36,\"name_ar\":\"معمل : 1\",\"name_en\":\"\",\"type\":\"Lab\",\"parent_id\":35}', '2026-01-05 08:03:39'),
(29, 0, 7, 'add_location', '{\"name_ar\":\"معمل : 2\",\"name_en\":\"\",\"type\":\"College\",\"parent_id\":null,\"name_err\":\"\"}', '2026-01-05 08:04:23'),
(30, 37, 7, 'delete_location', 'Deleted', '2026-01-05 08:04:32'),
(31, 35, 7, 'add_location', '{\"name_ar\":\"معمل : 2\",\"name_en\":\"\",\"type\":\"Building\",\"parent_id\":35,\"name_err\":\"\"}', '2026-01-05 08:04:48'),
(32, 35, 7, 'add_location', '{\"name_ar\":\"مبنى : 3\",\"name_en\":\"\",\"type\":\"Building\",\"parent_id\":35,\"name_err\":\"\"}', '2026-01-05 08:05:07'),
(33, 0, 7, 'add_location', '{\"name_ar\":\"معمل : 4\",\"name_en\":\"\",\"type\":\"College\",\"parent_id\":null,\"name_err\":\"\"}', '2026-01-05 08:05:23'),
(34, 40, 7, 'update_location', '{\"id\":40,\"name_ar\":\"معمل : 4\",\"name_en\":\"\",\"type\":\"Building\",\"parent_id\":35}', '2026-01-05 08:05:37'),
(35, 40, 7, 'delete_location', 'Deleted', '2026-01-05 08:05:53'),
(36, 35, 7, 'add_location', '{\"name_ar\":\"معمل : 4\",\"name_en\":\"\",\"type\":\"Building\",\"parent_id\":35,\"name_err\":\"\"}', '2026-01-05 08:06:10'),
(37, 41, 7, 'delete_location', 'Deleted', '2026-01-05 08:06:17'),
(38, 35, 7, 'add_location', '{\"name_ar\":\"معمل : 4\",\"name_en\":\"\",\"type\":\"Building\",\"parent_id\":35,\"name_err\":\"\"}', '2026-01-05 08:06:33'),
(39, 39, 7, 'delete_location', 'Deleted', '2026-01-05 08:06:49'),
(40, 38, 7, 'delete_location', 'Deleted', '2026-01-05 08:06:51'),
(41, 42, 7, 'delete_location', 'Deleted', '2026-01-05 08:06:54'),
(42, 35, 7, 'add_location', '{\"name_ar\":\"معمل : 2\",\"name_en\":\"\",\"type\":\"Building\",\"parent_id\":35,\"name_err\":\"\"}', '2026-01-05 08:07:04'),
(43, 35, 7, 'add_location', '{\"name_ar\":\"معمل : 3\",\"name_en\":\"\",\"type\":\"Building\",\"parent_id\":35,\"name_err\":\"\"}', '2026-01-05 08:07:19'),
(44, 44, 7, 'delete_location', 'Deleted', '2026-01-05 08:07:27'),
(45, 43, 7, 'delete_location', 'Deleted', '2026-01-05 08:07:42'),
(46, 35, 7, 'add_location', '{\"name_ar\":\"معمل : 2\",\"name_en\":\"\",\"type\":\"Lab\",\"parent_id\":35,\"name_err\":\"\"}', '2026-01-05 08:07:52'),
(47, 35, 7, 'add_location', '{\"name_ar\":\"معمل : 3\",\"name_en\":\"\",\"type\":\"Lab\",\"parent_id\":35,\"name_err\":\"\"}', '2026-01-05 08:08:05'),
(48, 35, 7, 'add_location', '{\"name_ar\":\"معمل : 4\",\"name_en\":\"\",\"type\":\"Lab\",\"parent_id\":35,\"name_err\":\"\"}', '2026-01-05 08:08:17'),
(49, 35, 7, 'add_location', '{\"name_ar\":\"معمل : 5\",\"name_en\":\"\",\"type\":\"Lab\",\"parent_id\":35,\"name_err\":\"\"}', '2026-01-05 08:08:26'),
(50, 35, 7, 'add_location', '{\"name_ar\":\"معمل : 6\",\"name_en\":\"\",\"type\":\"Lab\",\"parent_id\":35,\"name_err\":\"\"}', '2026-01-05 08:08:43'),
(51, 35, 7, 'add_location', '{\"name_ar\":\"معمل : 7\",\"name_en\":\"\",\"type\":\"Lab\",\"parent_id\":35,\"name_err\":\"\"}', '2026-01-05 08:09:01'),
(52, 35, 7, 'add_location', '{\"name_ar\":\"معمل : 8\",\"name_en\":\"\",\"type\":\"Lab\",\"parent_id\":35,\"name_err\":\"\"}', '2026-01-05 08:09:13'),
(53, 35, 7, 'add_location', '{\"name_ar\":\"معمل : 9\",\"name_en\":\"\",\"type\":\"Lab\",\"parent_id\":35,\"name_err\":\"\"}', '2026-01-05 08:09:27'),
(54, 35, 7, 'update_location', '{\"id\":35,\"name_ar\":\"مبنى : 8\",\"name_en\":\"\",\"type\":\"Building\",\"parent_id\":14}', '2026-01-05 08:10:26'),
(55, 14, 7, 'update_permissions', 'Updated location permissions', '2026-01-05 10:45:06');

-- --------------------------------------------------------

--
-- Table structure for table `locations_permissions`
--

CREATE TABLE `locations_permissions` (
  `id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `can_manage` tinyint(1) NOT NULL DEFAULT 0,
  `can_add_children` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations_permissions`
--

INSERT INTO `locations_permissions` (`id`, `location_id`, `role`, `user_id`, `can_manage`, `can_add_children`, `can_edit`, `can_delete`, `created_at`) VALUES
(1, 34, 'admin', NULL, 1, 0, 0, 0, '2026-01-02 19:25:50'),
(2, 34, 'manager', NULL, 1, 1, 1, 1, '2026-01-02 19:25:50'),
(3, 34, 'user', NULL, 0, 1, 0, 0, '2026-01-02 19:25:50'),
(4, 34, NULL, 9, 1, 1, 1, 1, '2026-01-02 19:25:50'),
(14, 29, 'manager', NULL, 0, 0, 0, 0, '2026-01-03 10:59:12'),
(15, 29, 'user', NULL, 0, 0, 0, 0, '2026-01-03 10:59:12'),
(16, 29, NULL, 8, 1, 1, 1, 1, '2026-01-03 10:59:12'),
(17, 14, 'manager', NULL, 1, 1, 0, 1, '2026-01-03 10:59:53'),
(18, 14, 'user', NULL, 1, 1, 1, 1, '2026-01-03 10:59:53'),
(47, 14, NULL, 7, 1, 0, 0, 0, '2026-01-05 10:45:06');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `code` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `code`, `description`) VALUES
(1, 'dashboard.view', 'عرض لوحة التحكم'),
(2, 'assets.view', 'عرض الأصول'),
(3, 'assets.add', 'إضافة أصل'),
(4, 'assets.edit', 'تعديل أصل'),
(5, 'assets.delete', 'حذف أصل'),
(6, 'assets.assign', 'تعيين عهدة لمستخدم'),
(7, 'spare_parts.view', 'عرض قطع الغيار'),
(8, 'spare_parts.manage', 'إضافة/تعديل/حذف قطع الغيار'),
(9, 'locations.manage', 'إدارة المواقع (فرع/مبنى/معمل)'),
(10, 'users.manage', 'إدارة المستخدمين'),
(11, 'tickets.view', 'عرض التذاكر'),
(12, 'tickets.add', 'فتح تذكرة'),
(13, 'tickets.assign', 'تعيين تذكرة لموظف'),
(14, 'tickets.escalate', 'تصعيد تذكرة لقسم'),
(15, 'reports.print', 'طباعة/تصدير تقارير');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role` varchar(30) NOT NULL,
  `permission_code` varchar(100) NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role`, `permission_code`, `allowed`) VALUES
('manager', 'assets.add', 1),
('manager', 'assets.assign', 1),
('manager', 'assets.edit', 1),
('manager', 'assets.view', 1),
('manager', 'dashboard.view', 1),
('manager', 'spare_parts.manage', 1),
('manager', 'spare_parts.view', 1),
('manager', 'tickets.add', 1),
('manager', 'tickets.assign', 1),
('manager', 'tickets.escalate', 1),
('manager', 'tickets.view', 1),
('superadmin', 'assets.add', 1),
('superadmin', 'assets.assign', 1),
('superadmin', 'assets.delete', 1),
('superadmin', 'assets.edit', 1),
('superadmin', 'assets.view', 1),
('superadmin', 'dashboard.view', 1),
('superadmin', 'locations.manage', 1),
('superadmin', 'reports.print', 1),
('superadmin', 'spare_parts.manage', 1),
('superadmin', 'spare_parts.view', 1),
('superadmin', 'tickets.add', 1),
('superadmin', 'tickets.assign', 1),
('superadmin', 'tickets.escalate', 1),
('superadmin', 'tickets.view', 1),
('superadmin', 'users.manage', 1),
('user', 'assets.add', 1),
('user', 'assets.view', 1),
('user', 'dashboard.view', 1),
('user', 'spare_parts.view', 1),
('user', 'tickets.add', 1),
('user', 'tickets.view', 1);

-- --------------------------------------------------------

--
-- Table structure for table `spare_movements`
--

CREATE TABLE `spare_movements` (
  `id` int(11) NOT NULL,
  `spare_part_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `delta` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `spare_movements`
--

INSERT INTO `spare_movements` (`id`, `spare_part_id`, `location_id`, `delta`, `note`, `created_at`, `created_by`) VALUES
(1, 8, 14, 1, NULL, '2026-01-05 14:01:55', 7),
(2, 8, 14, 1, NULL, '2026-01-05 14:01:59', 7),
(3, 8, 14, 1, NULL, '2026-01-05 14:01:59', 7),
(4, 8, 14, 1, NULL, '2026-01-05 14:02:00', 7),
(5, 8, 14, -1, NULL, '2026-01-05 14:02:08', 7),
(6, 8, 14, -1, NULL, '2026-01-05 14:02:10', 7),
(7, 8, 14, -1, NULL, '2026-01-05 14:02:11', 7),
(8, 8, 14, -1, NULL, '2026-01-05 14:02:11', 7),
(9, 8, 14, -1, NULL, '2026-01-05 14:03:18', 7),
(10, 8, 14, -1, NULL, '2026-01-05 14:03:18', 7),
(11, 8, 14, -1, NULL, '2026-01-05 14:03:18', 7),
(12, 8, 14, 1, NULL, '2026-01-05 14:25:56', 7),
(13, 8, 14, -1, NULL, '2026-01-05 14:25:58', 7),
(14, 8, 14, -1, NULL, '2026-01-05 14:25:59', 7),
(15, 8, 14, -1, NULL, '2026-01-05 14:25:59', 7),
(16, 8, 14, 1, NULL, '2026-01-05 14:26:00', 7),
(17, 8, 14, 1, NULL, '2026-01-05 14:26:00', 7),
(18, 8, 14, 1, NULL, '2026-01-05 14:26:00', 7);

-- --------------------------------------------------------

--
-- Table structure for table `spare_parts`
--

CREATE TABLE `spare_parts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `part_number` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `min_quantity` int(11) DEFAULT 5,
  `location_id` int(11) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `spare_parts`
--

INSERT INTO `spare_parts` (`id`, `name`, `part_number`, `quantity`, `min_quantity`, `location_id`, `location`, `description`, `created_at`) VALUES
(1, 'RAM 8GB DDR4', 'KM-8GB-2400', 10, 5, NULL, 'Cabinet A', NULL, '2025-12-22 23:39:31'),
(2, 'SSD 500GB Samsung', 'MZ-FV400', 5, 5, NULL, 'Cabinet B', NULL, '2025-12-22 23:39:31'),
(3, 'ssd 2', NULL, 20, NULL, NULL, NULL, '', '2025-12-23 17:42:39'),
(4, 'ddr3', '', 20, 3, 11, NULL, '', '2025-12-24 17:47:56'),
(5, 'a', NULL, 0, 4, NULL, NULL, NULL, '2025-12-24 17:48:34'),
(6, 'abdulaziz', NULL, 6, 5, 36, NULL, NULL, '2025-12-24 17:49:53'),
(7, 'super', NULL, 6, 5, 36, NULL, NULL, '2026-01-05 09:27:36'),
(8, 'RAM 4502', NULL, 13, 5, 14, NULL, NULL, '2026-01-05 10:34:31'),
(9, 'RAM 4502', '', 1, 5, 45, NULL, '', '2026-01-05 13:46:07');

-- --------------------------------------------------------

--
-- Table structure for table `spare_part_moves`
--

CREATE TABLE `spare_part_moves` (
  `id` int(11) NOT NULL,
  `spare_part_id` int(11) NOT NULL,
  `move_type` enum('IN','OUT','TRANSFER','ADJUST','COUNT') NOT NULL,
  `qty` int(11) NOT NULL,
  `from_location_id` int(11) DEFAULT NULL,
  `to_location_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spare_part_stocks`
--

CREATE TABLE `spare_part_stocks` (
  `id` int(11) NOT NULL,
  `spare_part_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `min_qty` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` varchar(50) DEFAULT 'Open',
  `priority` varchar(50) DEFAULT 'Medium',
  `contact_info` varchar(255) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `team` varchar(50) NOT NULL DEFAULT 'field_it',
  `requested_for_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `created_by`, `asset_id`, `subject`, `description`, `status`, `priority`, `contact_info`, `assigned_to`, `created_at`, `team`, `requested_for_user_id`) VALUES
(3, 8, NULL, 'تت', 'تت', 'Open', 'Medium', '05807654', NULL, '2025-12-30 18:36:18', 'field_it', NULL),
(4, 9, NULL, 'تت', 'k', 'Open', 'Medium', '0580203498', NULL, '2026-01-02 14:14:05', 'field_it', 9);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','manager','user') NOT NULL DEFAULT 'user',
  `created_at` datetime DEFAULT current_timestamp(),
  `job_title` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default_avatar.png',
  `lang` varchar(5) DEFAULT 'ar',
  `dark_mode` tinyint(1) DEFAULT 0,
  `name` varchar(100) DEFAULT 'User',
  `manager_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `job_title`, `department`, `phone`, `avatar`, `lang`, `dark_mode`, `name`, `manager_id`) VALUES
(7, 's', 's@s.com', '$2y$10$BrR1FzJMvMV.8N6.ag9SeeT8tTjiFWu4WY0WxOcK.icuEbI8nAIC.', 'super_admin', '2025-12-27 18:27:49', NULL, NULL, '', 'default_avatar.png', 'en', 1, 'aziz', NULL),
(8, 'a', 'u@u.com', '$2y$10$aateE9OTp.9fL8FrbWRIUeewa7T9ZV7QX9FEuFXcGgm611Y0Sor.6', 'user', '2025-12-27 18:36:40', NULL, NULL, '0580203498', 'user_8_1767537628.jpeg', 'en', 0, 'صديق فلاته', NULL),
(9, 'M', 'm@m.com', '$2y$10$dl8t5rOV7NF30Pu5tZYEs.s246vbwvf2Y.iNNDadfT3Z.MrkLyGSa', 'manager', '2025-12-27 19:13:14', NULL, NULL, NULL, 'default_avatar.png', 'ar', 0, 'a', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `user_id` int(11) NOT NULL,
  `permission_code` varchar(100) NOT NULL,
  `allowed` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_tag` (`asset_tag`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `asset_logs`
--
ALTER TABLE `asset_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `locations_audit`
--
ALTER TABLE `locations_audit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `locations_permissions`
--
ALTER TABLE `locations_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_loc_role` (`location_id`,`role`),
  ADD UNIQUE KEY `uniq_loc_user` (`location_id`,`user_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role`,`permission_code`),
  ADD KEY `fk_rp_perm` (`permission_code`);

--
-- Indexes for table `spare_movements`
--
ALTER TABLE `spare_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `spare_part_id` (`spare_part_id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `spare_parts`
--
ALTER TABLE `spare_parts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `spare_part_moves`
--
ALTER TABLE `spare_part_moves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_moves_part` (`spare_part_id`),
  ADD KEY `idx_moves_from` (`from_location_id`),
  ADD KEY `idx_moves_to` (`to_location_id`),
  ADD KEY `idx_moves_user` (`user_id`),
  ADD KEY `idx_moves_type` (`move_type`);

--
-- Indexes for table `spare_part_stocks`
--
ALTER TABLE `spare_part_stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_stock_part_location` (`spare_part_id`,`location_id`),
  ADD KEY `idx_stock_location` (`location_id`),
  ADD KEY `idx_stock_part` (`spare_part_id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_user_manager` (`manager_id`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`user_id`,`permission_code`),
  ADD KEY `fk_up_perm` (`permission_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `asset_logs`
--
ALTER TABLE `asset_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `locations_audit`
--
ALTER TABLE `locations_audit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `locations_permissions`
--
ALTER TABLE `locations_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `spare_movements`
--
ALTER TABLE `spare_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `spare_parts`
--
ALTER TABLE `spare_parts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `spare_part_moves`
--
ALTER TABLE `spare_part_moves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spare_part_stocks`
--
ALTER TABLE `spare_part_stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_code`) REFERENCES `permissions` (`code`) ON DELETE CASCADE;

--
-- Constraints for table `spare_part_moves`
--
ALTER TABLE `spare_part_moves`
  ADD CONSTRAINT `fk_moves_from_location` FOREIGN KEY (`from_location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_moves_part` FOREIGN KEY (`spare_part_id`) REFERENCES `spare_parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_moves_to_location` FOREIGN KEY (`to_location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_moves_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `spare_part_stocks`
--
ALTER TABLE `spare_part_stocks`
  ADD CONSTRAINT `fk_stock_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stock_part` FOREIGN KEY (`spare_part_id`) REFERENCES `spare_parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `fk_up_perm` FOREIGN KEY (`permission_code`) REFERENCES `permissions` (`code`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
