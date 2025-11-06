<?php
require_once 'db.php';

if (isLoggedIn()) {
    // Log logout activity
    logActivity($pdo, $_SESSION['user_id'], 'logout', 'خروج از سیستم');
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: index.php');
exit;
