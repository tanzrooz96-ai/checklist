-- Instagram Admin Monitoring System Database Schema
-- MySQL 8.0+
-- ADVANCED REPORTING SYSTEM

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `instagram_admin` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `instagram_admin`;

-- Users table (Admin and Manager)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','manager') NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily metrics
CREATE TABLE IF NOT EXISTS `daily_metrics` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `date` DATE NOT NULL,
  `followers` INT(11) NOT NULL DEFAULT 0,
  `following` INT(11) NOT NULL DEFAULT 0,
  `impressions` INT(11) NOT NULL DEFAULT 0,
  `profile_visits` INT(11) DEFAULT 0,
  `reach` INT(11) DEFAULT 0,
  `story_views` INT(11) DEFAULT 0,
  `post_likes` INT(11) DEFAULT 0,
  `post_comments` INT(11) DEFAULT 0,
  `admin_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_admin` (`date`, `admin_id`),
  KEY `admin_id` (`admin_id`),
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily tasks checklist
CREATE TABLE IF NOT EXISTS `daily_tasks` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `priority` ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `estimated_minutes` INT(11) NOT NULL DEFAULT 30,
  `category` VARCHAR(50) DEFAULT 'general',
  `task_type` VARCHAR(50) DEFAULT 'simple',
  `is_recurring` TINYINT(1) NOT NULL DEFAULT 1,
  `display_order` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== NEW: Daily Reports Table =====
CREATE TABLE IF NOT EXISTS `daily_reports` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `admin_id` INT(11) NOT NULL,
  `date` DATE NOT NULL,
  `status` ENUM('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
  `total_score` INT(11) DEFAULT 0,
  `completion_rate` DECIMAL(5,2) DEFAULT 0,
  `submitted_at` TIMESTAMP NULL DEFAULT NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `manager_notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_date` (`admin_id`, `date`),
  KEY `admin_id` (`admin_id`),
  KEY `date` (`date`),
  KEY `status` (`status`),
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== NEW: Task Details (Advanced) =====
CREATE TABLE IF NOT EXISTS `task_details` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `report_id` INT(11) NOT NULL,
  `task_id` INT(11) NOT NULL,
  `admin_id` INT(11) NOT NULL,
  `completed` TINYINT(1) NOT NULL DEFAULT 0,
  `time_spent_minutes` INT(11) DEFAULT 0,
  `quality_score` INT(11) DEFAULT 0,
  `details_json` JSON,
  `general_notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_task` (`report_id`, `task_id`),
  KEY `report_id` (`report_id`),
  KEY `task_id` (`task_id`),
  KEY `admin_id` (`admin_id`),
  FOREIGN KEY (`report_id`) REFERENCES `daily_reports`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`task_id`) REFERENCES `daily_tasks`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Task completion tracking (Legacy - keeping for compatibility)
CREATE TABLE IF NOT EXISTS `task_completions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `task_id` INT(11) NOT NULL,
  `admin_id` INT(11) NOT NULL,
  `date` DATE NOT NULL,
  `completed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT,
  `time_spent_minutes` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_admin_date` (`task_id`, `admin_id`, `date`),
  KEY `task_id` (`task_id`),
  KEY `admin_id` (`admin_id`),
  FOREIGN KEY (`task_id`) REFERENCES `daily_tasks`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity log
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `admin_id` INT(11) NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Milestones and challenges
CREATE TABLE IF NOT EXISTS `milestones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `target_followers` INT(11) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `reward_message` TEXT,
  `is_achieved` TINYINT(1) NOT NULL DEFAULT 0,
  `achieved_at` TIMESTAMP NULL DEFAULT NULL,
  `display_order` INT(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Motivational messages
CREATE TABLE IF NOT EXISTS `motivational_messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `message` TEXT NOT NULL,
  `type` ENUM('morning','afternoon','evening','achievement','encouragement') NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== NEW: Report Edit Logs =====
CREATE TABLE IF NOT EXISTS `report_edit_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `report_id` INT(11) NOT NULL,
  `edited_by` INT(11) NOT NULL,
  `edit_type` ENUM('create','update','submit','approve','reject') NOT NULL,
  `password_used` TINYINT(1) DEFAULT 0,
  `changes_json` JSON,
  `ip_address` VARCHAR(45),
  `user_agent` VARCHAR(255),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  KEY `edited_by` (`edited_by`),
  FOREIGN KEY (`report_id`) REFERENCES `daily_reports`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`edited_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin and manager users (password: admin123 and manager123)
INSERT INTO `users` (`username`, `password`, `role`, `full_name`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'مدیر اینستاگرام'),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'مدیر کل');

-- Update existing tasks to ensure they have task_type
UPDATE `daily_tasks` SET `task_type` = 'general' WHERE `task_type` IS NULL OR `task_type` = '';

-- Insert default daily tasks with task_type
INSERT INTO `daily_tasks` (`title`, `description`, `priority`, `estimated_minutes`, `category`, `task_type`, `display_order`) VALUES
('بررسی و پاسخ به دایرکت‌ها', 'پاسخگویی به تمام پیام‌های مشتریان در دایرکت و ارائه اطلاعات محصولات', 'high', 60, 'customer_service', 'direct_messages', 1),
('پست کردن محتوای روزانه', 'آپلود حداقل 1-2 پست جدید با کیفیت بالا از محصولات غذایی', 'high', 45, 'content', 'posts', 2),
('ایجاد استوری (3-5 عدد)', 'ساخت و انتشار استوری‌های جذاب شامل معرفی محصول، تخفیف‌ها و پشت صحنه', 'high', 40, 'content', 'stories', 3),
('لایک و کامنت در صفحات مرتبط', 'تعامل با 20-30 پست در صفحات مشابه و مخاطبان هدف', 'medium', 30, 'engagement', 'engagement', 4),
('پاسخ به کامنت‌ها', 'پاسخگویی به تمام کامنت‌های کاربران زیر پست‌ها', 'high', 25, 'customer_service', 'comments', 5),
('فالو کردن مخاطبان هدف', 'فالو کردن 30-50 اکانت مرتبط با صنعت غذایی', 'medium', 20, 'growth', 'follow', 6),
('آنفالو کردن اکانت‌های غیرفعال', 'حذف اکانت‌هایی که فالو بک نکرده‌اند (حداکثر 20-30 روز)', 'low', 15, 'growth', 'unfollow', 7),
('بررسی رقبا', 'تحلیل محتوا و استراتژی 3-5 صفحه رقیب', 'medium', 35, 'research', 'research', 8),
('جستجوی هشتگ‌های ترند', 'یافتن و ذخیره هشتگ‌های پرطرفدار مرتبط با غذا', 'medium', 20, 'research', 'hashtags', 9),
('طراحی محتوای فردا', 'آماده‌سازی و برنامه‌ریزی محتوای روز بعد', 'high', 40, 'planning', 'planning', 10),
('ثبت آمار روزانه', 'وارد کردن تعداد فالوور، فالووینگ، ایمپرشن و سایر معیارها', 'critical', 10, 'reporting', 'metrics', 11),
('بررسی اینسایت‌ها', 'تحلیل آمار پست‌ها، استوری‌ها و بهترین زمان انتشار', 'high', 25, 'analytics', 'insights', 12),
('پاسخ به سوالات محصولات', 'ارائه اطلاعات کامل درباره قیمت، موجودی و نحوه سفارش', 'high', 30, 'customer_service', 'product_questions', 13),
('ایجاد ریلز (اگر امکان دارد)', 'ساخت ویدیوهای کوتاه جذاب از محصولات', 'medium', 50, 'content', 'reels', 14),
('برنامه‌ریزی کمپین هفتگی', 'طراحی تخفیف‌ها، مسابقات یا رویدادهای ویژه', 'medium', 45, 'planning', 'campaign', 15);

-- Insert milestones
INSERT INTO `milestones` (`target_followers`, `title`, `description`, `reward_message`, `display_order`) VALUES
(100, 'اولین صد فالوور', 'رسیدن به 100 فالوور اولیه', 'تبریک! شما اولین قدم را برداشتید. به این مسیر ادامه دهید! 🎉', 1),
(250, 'ربع هزار', 'جذب 250 فالوور', 'عالی! شما در حال رشد هستید. همینطور ادامه دهید! 🚀', 2),
(500, 'نیم هزار', 'رسیدن به 500 فالوور', 'فوق‌العاده! صفحه شما در حال محبوب شدن است! 🌟', 3),
(1000, 'هزار فالوور', 'رسیدن به 1000 فالوور', 'واو! شما به یک نقطه عطف مهم رسیدید! 🏆', 4),
(2500, 'دو و نیم هزار', 'جذب 2500 فالوور', 'باورنکردنی! شما یک اینفلوئنسر واقعی هستید! 💎', 5),
(5000, 'پنج هزار', 'رسیدن به 5000 فالوور', 'استثنایی! صفحه شما یک برند معتبر شده است! 👑', 6),
(10000, 'ده هزار (تیک آبی)', 'رسیدن به 10000 فالوور', 'افسانه‌ای! شما آماده دریافت تیک آبی هستید! 🔥', 7);

-- Insert motivational messages
INSERT INTO `motivational_messages` (`message`, `type`) VALUES
('سلام! آماده یک روز پرانرژی و موفق هستی؟ بیا امروز رکوردهای جدیدی بزنیم! 💪', 'morning'),
('صبح بخیر! هر روز فرصتی تازه برای رشد است. امروز بهترین نسخه خودت باش! ☀️', 'morning'),
('روز جدید، فرصت جدید! بیا امروز را با انرژی مثبت شروع کنیم! 🌱', 'morning'),
('نصف روز گذشت! داری عالی پیش میری. استراحت کوتاه کن و با انگیزه بیشتر ادامه بده! 💼', 'afternoon'),
('وقت چک کردن پیشرفت‌هاست! ببین چقدر امروز کارت باحال شده! 📊', 'afternoon'),
('تو داری خوب کار می‌کنی! یادت باشه هر تعامل کوچک، یک قدم به سمت موفقیته! 🎯', 'afternoon'),
('روز کاری تموم شد! امروز چیزهای خوبی یاد گرفتی. به خودت افتخار کن! 🌙', 'evening'),
('کارت امروز عالی بود! حالا استراحت کن و برای فردا انرژی بگیر! ⭐', 'evening'),
('یک روز دیگه با موفقیت پشت سر گذاشتی! آفرین به تلاشت! 🎊', 'evening'),
('وااای چه خبره! یه هدف دیگه رو فتح کردی! این فقط شروعه! 🏆', 'achievement'),
('تبریک میگم! این موفقیت نتیجه تلاش و پشتکار توئه! 🎉', 'achievement'),
('همینطور که داری پیش میری، زودی به اهدافت میرسی! ادامه بده! 💪', 'encouragement'),
('یادت باشه: رشد واقعی یه شبه نمیاد. تو داری خیلی خوب پیش میری! 🌟', 'encouragement'),
('هر تعامل، هر پست، هر استوری... همشون مهم هستن. تو داری عالی کار می‌کنی! 🚀', 'encouragement');

COMMIT;
