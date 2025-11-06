<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'instagram_admin');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Create database connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'خطا در اتصال به پایگاه داده: ' . $e->getMessage()]));
}

// Session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function for Persian date
function toPersianDate($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    } elseif (is_string($timestamp)) {
        $timestamp = strtotime($timestamp);
    }

    $persian_months = [
        1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
    ];

    $persian_days = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه', 'شنبه'];

    // Convert to Jalali
    $gYear = (int)date('Y', $timestamp);
    $gMonth = (int)date('m', $timestamp);
    $gDay = (int)date('d', $timestamp);

    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

    if ($gMonth > 2) {
        $jy = $gYear + 1600;
        $jd = 365 * $gYear + floor(($gYear + 3) / 4) - floor(($gYear + 99) / 100) + floor(($gYear + 399) / 400) - 80 + $gDay + $g_d_m[$gMonth - 1];
    } else {
        $jy = $gYear + 1599;
        $jd = 365 * $gYear + floor(($gYear + 2) / 4) - floor(($gYear + 98) / 100) + floor(($gYear + 398) / 400) - 80 + $gDay + $g_d_m[$gMonth - 1];
    }

    $jy_day = $jd - (365 * $jy + floor($jy / 33) * 8 + floor(($jy % 33 + 3) / 4));

    if ($jy_day < 1) {
        $jy--;
        $jy_day = $jd - (365 * $jy + floor($jy / 33) * 8 + floor(($jy % 33 + 3) / 4));
    }

    if ($jy_day <= 186) {
        $jm = 1 + floor(($jy_day - 1) / 31);
        $jd_final = 1 + (($jy_day - 1) % 31);
    } else {
        $jm = 7 + floor(($jy_day - 187) / 30);
        $jd_final = 1 + (($jy_day - 187) % 30);
    }

    $day_of_week = $persian_days[date('w', $timestamp)];

    return [
        'year' => $jy,
        'month' => $jm,
        'day' => (int)$jd_final,
        'month_name' => $persian_months[$jm],
        'day_name' => $day_of_week,
        'formatted' => $day_of_week . ' ' . (int)$jd_final . ' ' . $persian_months[$jm] . ' ' . $jy
    ];
}

// Helper function to convert Persian numbers to English
function convertPersianToEnglish($string) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($persian, $english, $string);
}

// Helper function to convert English numbers to Persian
function convertEnglishToPersian($string) {
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return str_replace($english, $persian, $string);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Check if user is admin
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

// Check if user is manager
function isManager() {
    return isLoggedIn() && $_SESSION['role'] === 'manager';
}

// Redirect to login if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

// Get current user info
function getCurrentUser($pdo) {
    if (!isLoggedIn()) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT id, username, role, full_name, created_at, last_login FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Log activity
function logActivity($pdo, $admin_id, $action, $description = null) {
    $stmt = $pdo->prepare("INSERT INTO activity_log (admin_id, action, description) VALUES (?, ?, ?)");
    return $stmt->execute([$admin_id, $action, $description]);
}

// Get random motivational message
function getMotivationalMessage($pdo, $type = null) {
    if ($type) {
        $stmt = $pdo->prepare("SELECT message FROM motivational_messages WHERE type = ? AND is_active = 1 ORDER BY RAND() LIMIT 1");
        $stmt->execute([$type]);
    } else {
        $stmt = $pdo->query("SELECT message FROM motivational_messages WHERE is_active = 1 ORDER BY RAND() LIMIT 1");
    }

    $result = $stmt->fetch();
    return $result ? $result['message'] : 'تو عالی هستی! ادامه بده! 💪';
}

// Format number with thousand separator
function formatNumber($number) {
    return number_format($number);
}
