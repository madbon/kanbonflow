-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.27-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.12.0.7122
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for taskviewer
CREATE DATABASE IF NOT EXISTS `taskviewer` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `taskviewer`;

-- Dumping structure for table taskviewer.kanban_columns
CREATE TABLE IF NOT EXISTS `kanban_columns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `status_key` varchar(50) NOT NULL,
  `color` varchar(7) DEFAULT '#6c757d',
  `icon` varchar(50) DEFAULT 'fa fa-list',
  `position` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `status_key` (`status_key`),
  KEY `idx-kanban_columns-status_key` (`status_key`),
  KEY `idx-kanban_columns-position` (`position`),
  KEY `idx-kanban_columns-is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table taskviewer.kanban_columns: ~9 rows (approximately)
INSERT INTO `kanban_columns` (`id`, `name`, `status_key`, `color`, `icon`, `position`, `is_active`, `created_at`, `updated_at`) VALUES
	(1, 'To Do', 'pending', '#6c757d', 'fa fa-list', 0, 1, 1762388795, 1762397425),
	(3, 'Done', 'done', '#28a745', 'fa fa-check', 2, 1, 1762388795, 1762398844),
	(4, 'Test Case', 'testcase', '#000000', 'fas fa-tasks', 3, 1, 1762390929, 1762415776),
	(5, 'Ongoing', 'ongoing', '#fff700', 'fas fa-code', 1, 1, 1762398572, 1762399139),
	(6, 'Staging', 'staging', '#000000', 'fas fa-cloud-upload-alt', 4, 1, 1762399023, 1762415509),
	(7, 'Production', 'production', '#000000', 'fas fa-cloud', 6, 1, 1762399569, 1762415521),
	(8, 'Completed', 'completed', '#2eff62', 'fas fa-check-double', 8, 1, 1762406159, 1762417686),
	(9, 'Verifying', 'verifying', '#000000', 'fas fa-bug', 7, 1, 1762417639, 1762821656),
	(11, 'Debugging', 'debugging', '#000000', 'fa fa-bug', 5, 1, 1762820944, 1762821666);

-- Dumping structure for table taskviewer.migration
CREATE TABLE IF NOT EXISTS `migration` (
  `version` varchar(180) NOT NULL,
  `apply_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table taskviewer.migration: ~16 rows (approximately)
INSERT INTO `migration` (`version`, `apply_time`) VALUES
	('m000000_000000_base', 1761202297),
	('m000000_000001_create_task_color_settings_table', 1761202297),
	('m000000_000002_create_task_categories_table', 1761202297),
	('m000000_000003_create_tasks_table', 1761202297),
	('m000000_000004_create_task_images_table', 1761202297),
	('m000000_000005_add_parent_id_to_task_categories', 1761203106),
	('m000000_000006_add_kanban_fields_to_tasks_table', 1762330241),
	('m000000_000007_create_kanban_columns_table', 1762388795),
	('m000000_000008_add_color_column_to_task_categories_table', 1762390469),
	('m000000_000010_add_include_in_export_to_tasks_table', 1762819232),
	('m000000_000010_create_task_history_table', 1762400395),
	('m000000_000011_add_old_values_column_to_task_history_table', 1762402659),
	('m000000_000012_add_include_in_export_to_tasks_table', 1762819292),
	('m130524_201442_init', 1761202297),
	('m190124_110200_add_verification_token_column_to_user_table', 1761202297),
	('m251106_053705_create_task_comments_table', 1762407598);

-- Dumping structure for table taskviewer.tasks
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'medium' COMMENT 'low, medium, high, critical',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, in_progress, completed, cancelled',
  `deadline` int(11) NOT NULL COMMENT 'Unix timestamp',
  `completed_at` int(11) DEFAULT NULL COMMENT 'Unix timestamp',
  `assigned_to` int(11) DEFAULT NULL COMMENT 'User ID',
  `created_by` int(11) NOT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `position` int(11) DEFAULT 0 COMMENT 'Position within status column',
  `include_in_export` tinyint(1) DEFAULT 1 COMMENT 'Whether to include this task in activity log exports (1=Yes, 0=No)',
  PRIMARY KEY (`id`),
  KEY `idx-tasks-category_id` (`category_id`),
  KEY `idx-tasks-deadline` (`deadline`),
  KEY `idx-tasks-status` (`status`),
  KEY `idx-tasks-priority` (`priority`),
  KEY `idx-tasks-status-position` (`status`,`position`),
  KEY `idx_tasks_include_in_export` (`include_in_export`),
  CONSTRAINT `fk-tasks-category_id` FOREIGN KEY (`category_id`) REFERENCES `task_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table taskviewer.tasks: ~7 rows (approximately)
INSERT INTO `tasks` (`id`, `category_id`, `title`, `description`, `priority`, `status`, `deadline`, `completed_at`, `assigned_to`, `created_by`, `created_at`, `updated_at`, `position`, `include_in_export`) VALUES
	(24, 1, 'Test Task - Hidden from Export 2025-11-11 01:16:04', 'This task should have include_in_export = 0', 'medium', 'To Do', 1763424964, NULL, NULL, 0, 1762820164, 1762820164, 0, 0),
	(25, 1, 'Test Task - Visible in Export 2025-11-11 01:16:04', 'This task should have include_in_export = 0', 'medium', 'To Do', 1763424964, NULL, NULL, 0, 1762820164, 1762820164, 0, 1),
	(30, 2, 'Issues encountered in EODB OMS reported by Ma\'am Shella Besigna', 'Click this link  for more details:\r\n\r\nhttps://docs.google.com/spreadsheets/d/1LKlwe8XuyTylNUIbbrCaOotD3kxK6gSR/edit?usp=drivesdk&ouid=113351111774346909736&rtpof=true&sd=true', 'high', 'ongoing', 1762873200, NULL, NULL, 1, 1762822335, 1762825801, 0, 1),
	(31, 13, 'File 3rd Quarter Tax', '', 'high', 'pending', 1763194440, NULL, NULL, 1, 1762823675, 1762823675, 0, 0),
	(32, 2, 'Sir Migs Concern re submission on EODB', '', 'medium', 'completed', 1762849560, 1762824715, NULL, 1, 1762824419, 1762824715, 0, 1),
	(33, 2, 'Inform Sir Patrick of MIMAROPA regarding the uploading of files, it exceeds to 5mb', '', 'medium', 'completed', 1762849860, 1762824725, NULL, 1, 1762824692, 1762824725, 1, 1),
	(34, 14, 'Maynilad', '', 'critical', 'pending', 1763282040, NULL, NULL, 1, 1762824883, 1762824883, 1, 0);

-- Dumping structure for table taskviewer.task_categories
CREATE TABLE IF NOT EXISTS `task_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL COMMENT 'Icon class (e.g., fa-folder)',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `color` varchar(7) DEFAULT '#007bff' COMMENT 'Hex color code for category',
  PRIMARY KEY (`id`),
  KEY `idx-task_categories-is_active` (`is_active`),
  KEY `idx-task_categories-sort_order` (`sort_order`),
  KEY `idx-task_categories-parent_id` (`parent_id`),
  KEY `idx-task_categories-color` (`color`),
  CONSTRAINT `fk-task_categories-parent_id` FOREIGN KEY (`parent_id`) REFERENCES `task_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table taskviewer.task_categories: ~10 rows (approximately)
INSERT INTO `task_categories` (`id`, `parent_id`, `name`, `description`, `icon`, `sort_order`, `is_active`, `created_at`, `updated_at`, `color`) VALUES
	(1, NULL, 'Development', 'Software development tasks', 'fa-code', 1, 1, 1761202297, 1761202297, '#007bff'),
	(2, 1, 'EODB OMS', 'Ease of Doing Business Online Monitoring System', 'fas fa-store', 2, 1, 1761202297, 1762405956, '#001361'),
	(5, NULL, 'Meeting', 'Meeting and discussion tasks', 'fa-users', 5, 1, 1761202297, 1761202297, '#6f42c1'),
	(7, 1, 'ECLIP', '', '', 1, 1, 1761204155, 1762391109, '#861313'),
	(8, 1, 'GADPBMS', 'Gender & Development Plan & Budget Monitoring System', '', NULL, 1, 1762388974, 1762390879, '#bd2eff'),
	(9, 1, 'HRIS', 'Human Resource Information System', '', NULL, 1, 1762396994, 1762396994, '#3c8cd7'),
	(10, NULL, 'Trainings / Workshops / Orientation', '', 'fas fa-calendar', NULL, 1, 1762406484, 1762406484, '#ffa200'),
	(12, NULL, 'Non-work-related Task', '', 'fas fa-hiking', NULL, 1, 1762823516, 1762823516, '#000000'),
	(13, 12, 'Filing of Tax', '', 'fas fa-paper-plane', NULL, 1, 1762823592, 1762824037, '#e4f500'),
	(14, 12, 'Pay Bills', '', '', NULL, 1, 1762824800, 1762824800, '#ffbb00');

-- Dumping structure for table taskviewer.task_color_settings
CREATE TABLE IF NOT EXISTS `task_color_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Setting name (e.g., Critical, Warning, Safe)',
  `days_before_deadline` int(11) NOT NULL COMMENT 'Number of days before deadline',
  `color` varchar(7) NOT NULL COMMENT 'Hex color code (e.g., #FF0000)',
  `sort_order` int(11) DEFAULT 0 COMMENT 'Display order',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-task_color_settings-days_before_deadline` (`days_before_deadline`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table taskviewer.task_color_settings: ~6 rows (approximately)
INSERT INTO `task_color_settings` (`id`, `name`, `days_before_deadline`, `color`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
	(1, 'Overdue', 0, '#D32F2F', 0, 1, 1761202297, 1762414059),
	(2, 'Critical', 3, '#F57C00', 2, 1, 1761202297, 1761202297),
	(3, 'Warning', 7, '#FBC02D', 3, 1, 1761202297, 1761202297),
	(4, 'Upcoming', 14, '#1976D2', 4, 1, 1761202297, 1761202297),
	(5, 'Safe', 30, '#388E3C', 5, 1, 1761202297, 1761202297),
	(6, 'Immediate', 1, '#ff0000', 1, 1, 1762413797, 1762414248);

-- Dumping structure for table taskviewer.task_comments
CREATE TABLE IF NOT EXISTS `task_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-task-comments-task-id` (`task_id`),
  KEY `idx-task-comments-user-id` (`user_id`),
  KEY `idx-task-comments-parent-id` (`parent_id`),
  KEY `idx-task-comments-created-at` (`created_at`),
  CONSTRAINT `fk-task-comments-parent-id` FOREIGN KEY (`parent_id`) REFERENCES `task_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk-task-comments-task-id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk-task-comments-user-id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table taskviewer.task_comments: ~0 rows (approximately)

-- Dumping structure for table taskviewer.task_history
CREATE TABLE IF NOT EXISTS `task_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `old_values` text DEFAULT NULL COMMENT 'JSON encoded old values for complex changes',
  PRIMARY KEY (`id`),
  KEY `idx-task_history-task_id` (`task_id`),
  KEY `idx-task_history-user_id` (`user_id`),
  KEY `idx-task_history-action_type` (`action_type`),
  KEY `idx-task_history-created_at` (`created_at`),
  KEY `idx-task_history-old_values` (`old_values`(768)),
  CONSTRAINT `fk-task_history-user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table taskviewer.task_history: ~15 rows (approximately)
INSERT INTO `task_history` (`id`, `task_id`, `user_id`, `action_type`, `field_name`, `old_value`, `new_value`, `description`, `ip_address`, `user_agent`, `created_at`, `old_values`) VALUES
	(120, 30, 1, 'created', NULL, '', '', 'Task "Issues encountered in EODB OMS reported by Ma\'am Shella Besigna" was created', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1762822335, NULL),
	(121, 30, 1, 'status_changed', 'status', 'pending', 'ongoing', 'Status changed from "To Do" to "ongoing"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1762823263, NULL),
	(122, 30, 1, 'status_changed', 'status', 'pending', 'ongoing', 'Task moved from "To Do" to "Ongoing"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1762823263, NULL),
	(123, 31, 1, 'created', NULL, '', '', 'Task "File 3rd Quarter Tax" was created', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1762823675, NULL),
	(124, 32, 1, 'created', NULL, '', '', 'Task "Sir Migs Concern re submission on EODB" was created', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1762824419, NULL),
	(125, 33, 1, 'created', NULL, '', '', 'Task "Inform Sir Patrick of MIMAROPA regarding the uploading of files, it exceeds to 5mb" was created', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1762824692, NULL),
	(126, 32, 1, 'status_changed', 'status', 'pending', 'done', 'Status changed from "To Do" to "done"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1762824700, NULL),
	(127, 32, 1, 'position_changed', 'position', '1', '0', 'Position changed from 2 to 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1762824700, NULL),
	(128, 32, 1, 'status_changed', 'status', 'pending', 'done', 'Task moved from "To Do" to "Done"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1762824700, NULL),
	(129, 33, 1, 'status_changed', 'status', 'pending', 'done', 'Status changed from "To Do" to "done"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1762824702, NULL),
	(130, 33, 1, 'status_changed', 'status', 'pending', 'done', 'Task moved from "To Do" to "Done"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1762824702, NULL),
	(131, 32, 1, 'completed', 'status', 'done', 'completed', 'Task was completed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1762824715, NULL),
	(132, 33, 1, 'completed', 'status', 'done', 'completed', 'Task was completed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1762824725, NULL),
	(133, 34, 1, 'created', NULL, '', '', 'Task "Maynilad" was created', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1762824883, NULL),
	(134, 30, 1, 'updated', 'description', 'https://docs.google.com/spreadsheets/d/1LKlwe8XuyTylNUIbbrCaOotD3kxK6gSR/edit?usp=drivesdk&ouid=113351111774346909736&rtpof=true&sd=true', 'Click this link  for more details:\r\n\r\nhttps://docs.google.com/spreadsheets/d/1LKlwe8XuyTylNUIbbrCaOotD3kxK6gSR/edit?usp=drivesdk&ouid=113351111774346909736&rtpof=true&sd=true', 'Description was updated', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1762825801, NULL);

-- Dumping structure for table taskviewer.task_images
CREATE TABLE IF NOT EXISTS `task_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-task_images-task_id` (`task_id`),
  CONSTRAINT `fk-task_images-task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table taskviewer.task_images: ~1 rows (approximately)
INSERT INTO `task_images` (`id`, `task_id`, `filename`, `original_name`, `file_path`, `file_size`, `mime_type`, `sort_order`, `created_at`) VALUES
	(4, 32, 'img_691290ec24be0_1762824428.png', 'clipboard_2025-11-11_02-27-08.png', 'uploads/tasks/32/img_691290ec24be0_1762824428.png', 49754, 'image/png', 1, 1762824428);

-- Dumping structure for table taskviewer.user
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `auth_key` varchar(32) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `status` smallint(6) NOT NULL DEFAULT 10,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `password_reset_token` (`password_reset_token`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table taskviewer.user: ~1 rows (approximately)
INSERT INTO `user` (`id`, `username`, `auth_key`, `password_hash`, `password_reset_token`, `email`, `status`, `created_at`, `updated_at`, `verification_token`) VALUES
	(1, 'admin', 'CXirvRNpjV67le_A89_LkDkAUqOfyeU5', '$2y$13$T4mw72z1ITmxtvOZqlBX5OnxEBXN38eCTA8cWyw8eW/IQzVhQlhqG', NULL, 'admin@gm.com', 10, 1762331488, 1762331488, 'FayQnrzUOoRTmOgrrebUj8J90NkFva34_1762331488');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
