-- ================================================
-- سیستم مدیریت اینستاگرام - نسخه 2.0 (Wizard Style)
-- ================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ================================================
-- جدول کاربران
-- ================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` varchar(20) NOT NULL COMMENT 'admin or manager',
  `full_name` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ================================================
-- کاربران پیش‌فرض
-- ================================================
INSERT INTO `users` (`role`, `full_name`, `password`) VALUES
('admin', 'ادمین اینستاگرام', '$2y$10$JHvGZ5nX7YhRZ0eL8qKqxO6Y8qm.YxJ0FhQNZ3vqBqxJZ0eL8qKqx'),
('manager', 'مدیریت', '$2y$10$ZhRZ0eL8qKqxO6Y8qm.YxJ0FhQNZ3vqBqxJZ0eL8qKqxO6Y8qm.Yx');
-- پسورد admin: admin123
-- پسورد manager: manager123

-- ================================================
-- جدول گزارش‌های روزانه
-- ================================================
CREATE TABLE IF NOT EXISTS `daily_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `score` decimal(5,2) DEFAULT 0 COMMENT 'امتیاز کل (0-100)',
  `status` enum('draft','completed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_date` (`admin_id`, `report_date`),
  KEY `admin_id` (`admin_id`),
  KEY `report_date` (`report_date`),
  CONSTRAINT `fk_reports_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ================================================
-- جدول تسک‌های گزارش
-- ================================================
CREATE TABLE IF NOT EXISTS `report_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `task_category` varchar(50) NOT NULL COMMENT 'posts, stories, directs, comments, content, engagement, planning',
  `task_name` varchar(100) NOT NULL,
  `task_value` int(11) DEFAULT 0 COMMENT 'مقدار عددی تسک',
  `max_value` int(11) DEFAULT 0 COMMENT 'حداکثر مقدار',
  `weight` decimal(5,2) DEFAULT 0 COMMENT 'وزن در امتیازدهی',
  `score` decimal(5,2) DEFAULT 0 COMMENT 'امتیاز این تسک',
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  KEY `task_category` (`task_category`),
  CONSTRAINT `fk_tasks_report` FOREIGN KEY (`report_id`) REFERENCES `daily_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ================================================
-- جدول لاگ فعالیت‌ها
-- ================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ================================================
-- ویوی آمار کلی
-- ================================================
CREATE OR REPLACE VIEW `reports_summary` AS
SELECT
    dr.id,
    dr.admin_id,
    u.full_name,
    dr.report_date,
    dr.score,
    dr.status,
    COUNT(rt.id) as total_tasks,
    dr.created_at,
    dr.updated_at
FROM daily_reports dr
LEFT JOIN users u ON dr.admin_id = u.id
LEFT JOIN report_tasks rt ON dr.id = rt.report_id
GROUP BY dr.id;
