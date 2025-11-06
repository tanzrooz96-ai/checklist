<?php
// ================================================
// اتصال به دیتابیس و توابع کمکی
// ================================================

session_start();

// تنظیمات دیتابیس
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// اتصال به دیتابیس
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_persian_ci"
        ]
    );
} catch (PDOException $e) {
    die("خطا در اتصال به دیتابیس: " . $e->getMessage());
}

// ================================================
// توابع کمکی
// ================================================

/**
 * بررسی لاگین بودن کاربر
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * بررسی ادمین بودن
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * بررسی مدیر بودن
 */
function isManager() {
    return isLoggedIn() && $_SESSION['role'] === 'manager';
}

/**
 * دریافت اطلاعات کاربر لاگین شده
 */
function getCurrentUser($pdo) {
    if (!isLoggedIn()) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * ثبت لاگ فعالیت
 */
function logActivity($pdo, $userId, $action, $description = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $action, $description]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * تبدیل تاریخ میلادی به شمسی
 */
function toPersianDate($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    } elseif (is_string($timestamp)) {
        $timestamp = strtotime($timestamp);
        if ($timestamp === false) {
            $timestamp = time();
        }
    }

    $persian_months = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
        5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
        9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
    ];

    $persian_days = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه', 'شنبه'];

    $gYear = (int)date('Y', $timestamp);
    $gMonth = (int)date('m', $timestamp);
    $gDay = (int)date('d', $timestamp);

    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

    $gy = $gYear - 1600;
    $gm = $gMonth - 1;
    $gd = $gDay - 1;

    $g_day_no = 365 * $gy + floor(($gy + 3) / 4) - floor(($gy + 99) / 100) + floor(($gy + 399) / 400);

    for ($i = 0; $i < $gm; ++$i) {
        $g_day_no += $g_d_m[$i];
    }

    if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0))) {
        $g_day_no++;
    }

    $g_day_no += $gd;
    $j_day_no = $g_day_no - 79;
    $j_np = floor($j_day_no / 12053);
    $j_day_no = $j_day_no % 12053;
    $jy = 979 + 33 * $j_np + 4 * floor($j_day_no / 1461);
    $j_day_no %= 1461;

    if ($j_day_no >= 366) {
        $jy += floor(($j_day_no - 1) / 365);
        $j_day_no = ($j_day_no - 1) % 365;
    }

    if ($j_day_no < 186) {
        $jm = 1 + floor($j_day_no / 31);
        $jd = 1 + ($j_day_no % 31);
    } else {
        $jm = 7 + floor(($j_day_no - 186) / 30);
        $jd = 1 + (($j_day_no - 186) % 30);
    }

    $jm = max(1, min(12, (int)$jm));
    $jd = max(1, (int)$jd);

    $dayOfWeek = $persian_days[(int)date('w', $timestamp)];

    return [
        'year' => $jy,
        'month' => $jm,
        'day' => $jd,
        'month_name' => $persian_months[$jm],
        'day_name' => $dayOfWeek,
        'full' => $jd . ' ' . $persian_months[$jm] . ' ' . $jy,
        'full_with_day' => $dayOfWeek . ' ' . $jd . ' ' . $persian_months[$jm] . ' ' . $jy
    ];
}

/**
 * تبدیل تاریخ شمسی به میلادی (برای ذخیره در دیتابیس)
 */
function toGregorianDate($jy, $jm, $jd) {
    $jy = (int)$jy;
    $jm = (int)$jm;
    $jd = (int)$jd;

    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $jy += 1595;
    $days = 365 * $jy + floor($jy / 33) * 8 + floor(($jy % 33 + 3) / 4);

    for ($i = 0; $i < $jm - 1; ++$i) {
        $days += ($i < 6) ? 31 : 30;
    }

    $gy = 400 * floor($days / 146097);
    $days %= 146097;

    $leap = true;
    if ($days >= 36525) {
        $days--;
        $gy += 100 * floor($days / 36524);
        $days %= 36524;

        if ($days >= 365) {
            $days++;
        }
    }

    $gy += 4 * floor($days / 1461);
    $days %= 1461;

    $gy += floor(($days - 1) / 365);
    if ($days > 365) {
        $days = ($days - 1) % 365;
    }

    $gm = 0;
    for ($i = 0; $gm < 11 && $days >= $g_d_m[$gm + 1]; ++$gm) {}

    $gd = $days - $g_d_m[$gm] + 1;
    $gm++;

    return sprintf('%04d-%02d-%02d', $gy, $gm, $gd + $jd);
}

/**
 * محاسبه امتیاز بر اساس تسک‌ها
 */
function calculateScore($tasks) {
    $totalScore = 0;

    foreach ($tasks as $task) {
        if ($task['max_value'] > 0) {
            $percentage = min(100, ($task['task_value'] / $task['max_value']) * 100);
            $taskScore = ($percentage / 100) * $task['weight'];
            $totalScore += $taskScore;
        } else {
            // برای تسک‌های بله/خیر
            if ($task['task_value'] > 0) {
                $totalScore += $task['weight'];
            }
        }
    }

    return round($totalScore, 2);
}

/**
 * دریافت رنگ بر اساس امتیاز
 */
function getScoreColor($score) {
    if ($score >= 90) return '#27AE60'; // سبز - عالی
    if ($score >= 75) return '#3498DB'; // آبی - خوب
    if ($score >= 60) return '#F39C12'; // نارنجی - متوسط
    return '#E74C3C'; // قرمز - ضعیف
}

/**
 * دریافت متن وضعیت بر اساس امتیاز
 */
function getScoreLabel($score) {
    if ($score >= 90) return 'عالی';
    if ($score >= 75) return 'خوب';
    if ($score >= 60) return 'متوسط';
    return 'نیاز به بهبود';
}

/**
 * فرمت عدد فارسی
 */
function toPersianNumber($number) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($english, $persian, $number);
}
