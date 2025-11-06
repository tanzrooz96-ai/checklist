<?php
require_once 'db.php';

// اگر لاگین است، به صفحه مربوطه هدایت شود
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin.php');
    } else {
        header('Location: manager.php');
    }
    exit;
}

// پردازش لاگین
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($role && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE role = ? LIMIT 1");
        $stmt->execute([$role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            // به‌روزرسانی آخرین ورود
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            // ثبت لاگ
            logActivity($pdo, $user['id'], 'login', 'ورود به سیستم');

            // هدایت
            if ($user['role'] === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: manager.php');
            }
            exit;
        } else {
            $error = 'رمز عبور اشتباه است!';
        }
    } else {
        $error = 'لطفا تمام فیلدها را پر کنید!';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سیستم | مدیریت اینستاگرام</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                    </svg>
                </div>
                <h1>سیستم مدیریت اینستاگرام</h1>
                <p>پخش مواد غذایی</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label class="form-label">انتخاب نقش</label>
                    <div class="role-grid">
                        <label class="role-card">
                            <input type="radio" name="role" value="admin" required>
                            <div class="role-content">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span>ادمین اینستاگرام</span>
                            </div>
                        </label>

                        <label class="role-card">
                            <input type="radio" name="role" value="manager" required>
                            <div class="role-content">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                <span>مدیریت</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">رمز عبور</label>
                    <div class="input-group">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <input type="password" name="password" placeholder="رمز عبور خود را وارد کنید" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    ورود به سیستم
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                </button>
            </form>

            <div class="login-footer">
                <p>رمز پیش‌فرض: <strong>admin123</strong> / <strong>manager123</strong></p>
            </div>
        </div>
    </div>
</body>
</html>
