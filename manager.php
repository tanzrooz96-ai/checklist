<?php
require_once 'db.php';
requireLogin();

if (!isManager()) {
    header('Location: admin.php');
    exit;
}

$user = getCurrentUser($pdo);
$today = date('Y-m-d');
$persianDate = toPersianDate();

// Get all admins
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'admin'");
$admins = $stmt->fetchAll();

// Get today's metrics for all admins
$stmt = $pdo->prepare("
    SELECT m.*, u.full_name
    FROM daily_metrics m
    JOIN users u ON m.admin_id = u.id
    WHERE m.date = ?
");
$stmt->execute([$today]);
$todayMetrics = $stmt->fetchAll();

// Get task completion stats for all admins
$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.full_name,
        COUNT(DISTINCT dt.id) as total_tasks,
        COUNT(DISTINCT tc.task_id) as completed_tasks,
        ROUND(COUNT(DISTINCT tc.task_id) / COUNT(DISTINCT dt.id) * 100, 2) as completion_rate,
        SUM(CASE WHEN tc.task_id IS NOT NULL THEN dt.estimated_minutes ELSE 0 END) as completed_minutes,
        SUM(dt.estimated_minutes) as total_minutes
    FROM users u
    CROSS JOIN daily_tasks dt
    LEFT JOIN task_completions tc ON tc.admin_id = u.id AND tc.date = ? AND tc.task_id = dt.id
    WHERE u.role = 'admin'
    GROUP BY u.id, u.full_name
");
$stmt->execute([$today]);
$adminStats = $stmt->fetchAll();

// Get recent activities
$stmt = $pdo->prepare("
    SELECT a.*, u.full_name
    FROM activity_log a
    JOIN users u ON a.admin_id = u.id
    ORDER BY a.timestamp DESC
    LIMIT 20
");
$stmt->execute();
$recentActivities = $stmt->fetchAll();

// Get last 7 days comparison
$stmt = $pdo->prepare("
    SELECT
        m.date,
        u.full_name,
        m.followers,
        m.impressions,
        m.profile_visits
    FROM daily_metrics m
    JOIN users u ON m.admin_id = u.id
    WHERE m.date >= DATE_SUB(?, INTERVAL 7 DAY)
    ORDER BY m.date DESC, u.full_name
");
$stmt->execute([$today]);
$weeklyMetrics = $stmt->fetchAll();

// Get milestone progress
$stmt = $pdo->query("
    SELECT * FROM milestones
    ORDER BY display_order ASC
");
$milestones = $stmt->fetchAll();

// Calculate overall metrics
$totalFollowers = 0;
$totalImpressions = 0;
foreach ($todayMetrics as $metric) {
    $totalFollowers += $metric['followers'];
    $totalImpressions += $metric['impressions'];
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل مدیریت - نظارت بر ادمین</title>
    <link rel="stylesheet" href="style-light.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="dashboard">
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="header-right">
                <div class="header-logo">
                    <div class="header-logo-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="header-title">
                        <h1>پنل مدیریت</h1>
                        <p>نظارت و گزارش‌گیری</p>
                    </div>
                </div>
            </div>
            <div class="header-left">
                <div class="user-info">
                    <p><?php echo htmlspecialchars($user['full_name']); ?></p>
                    <span>مدیر کل</span>
                </div>
                <a href="logout.php" class="btn btn-secondary">
                    <span>خروج</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Persian Date Banner -->
        <div class="date-banner">
            <h2><?php echo $persianDate['formatted']; ?></h2>
            <p>گزارش عملکرد تیم اینستاگرام</p>
        </div>

        <!-- Overall Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">تعداد ادمین‌ها</span>
                    <div class="stat-icon primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo convertEnglishToPersian(count($admins)); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">مجموع فالوورها</span>
                    <div class="stat-icon success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo convertEnglishToPersian(formatNumber($totalFollowers)); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">مجموع ایمپرشن امروز</span>
                    <div class="stat-icon warning">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo convertEnglishToPersian(formatNumber($totalImpressions)); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">گزارش‌های ثبت شده</span>
                    <div class="stat-icon primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo convertEnglishToPersian(count($todayMetrics)); ?></div>
            </div>
        </div>

        <!-- Admin Performance Cards -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    عملکرد ادمین‌ها امروز
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($adminStats)): ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <p>هنوز داده‌ای ثبت نشده است</p>
                    </div>
                <?php else: ?>
                    <div class="stats-grid">
                        <?php foreach ($adminStats as $stat): ?>
                            <div class="stat-card">
                                <div class="stat-header">
                                    <span class="stat-title"><?php echo htmlspecialchars($stat['full_name']); ?></span>
                                    <div class="stat-icon <?php echo $stat['completion_rate'] >= 80 ? 'success' : ($stat['completion_rate'] >= 50 ? 'warning' : 'primary'); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                    </div>
                                </div>
                                <div class="stat-value"><?php echo convertEnglishToPersian(round($stat['completion_rate'])); ?>٪</div>
                                <div class="stat-change positive">
                                    <span>
                                        <?php echo convertEnglishToPersian($stat['completed_tasks']); ?>/<?php echo convertEnglishToPersian($stat['total_tasks']); ?> وظیفه -
                                        <?php echo convertEnglishToPersian($stat['completed_minutes']); ?> دقیقه
                                    </span>
                                </div>
                                <div class="progress-bar-wrapper mt-2">
                                    <div class="progress-bar" style="width: <?php echo $stat['completion_rate']; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Metrics Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        آمار ثبت شده امروز
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (empty($todayMetrics)): ?>
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <p>هنوز آماری ثبت نشده است</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: var(--surface); border-bottom: 2px solid var(--border);">
                                        <th style="padding: 12px; text-align: right; font-size: 13px; font-weight: 700;">ادمین</th>
                                        <th style="padding: 12px; text-align: center; font-size: 13px; font-weight: 700;">فالوور</th>
                                        <th style="padding: 12px; text-align: center; font-size: 13px; font-weight: 700;">فالووینگ</th>
                                        <th style="padding: 12px; text-align: center; font-size: 13px; font-weight: 700;">ایمپرشن</th>
                                        <th style="padding: 12px; text-align: center; font-size: 13px; font-weight: 700;">بازدید پروفایل</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($todayMetrics as $metric): ?>
                                        <tr style="border-bottom: 1px solid var(--border);">
                                            <td style="padding: 12px; font-size: 14px; font-weight: 600;">
                                                <?php echo htmlspecialchars($metric['full_name']); ?>
                                            </td>
                                            <td style="padding: 12px; text-align: center; font-size: 14px;">
                                                <?php echo convertEnglishToPersian(formatNumber($metric['followers'])); ?>
                                            </td>
                                            <td style="padding: 12px; text-align: center; font-size: 14px;">
                                                <?php echo convertEnglishToPersian(formatNumber($metric['following'])); ?>
                                            </td>
                                            <td style="padding: 12px; text-align: center; font-size: 14px;">
                                                <?php echo convertEnglishToPersian(formatNumber($metric['impressions'])); ?>
                                            </td>
                                            <td style="padding: 12px; text-align: center; font-size: 14px;">
                                                <?php echo convertEnglishToPersian(formatNumber($metric['profile_visits'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        فعالیت‌های اخیر
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recentActivities)): ?>
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <p>هیچ فعالیتی ثبت نشده است</p>
                        </div>
                    <?php else: ?>
                        <div class="activity-feed">
                            <?php foreach ($recentActivities as $activity): ?>
                                <?php
                                    $timestamp = strtotime($activity['timestamp']);
                                    $timeDiff = time() - $timestamp;
                                    if ($timeDiff < 60) {
                                        $timeAgo = 'همین الان';
                                    } elseif ($timeDiff < 3600) {
                                        $mins = floor($timeDiff / 60);
                                        $timeAgo = convertEnglishToPersian($mins) . ' دقیقه پیش';
                                    } else {
                                        $hours = floor($timeDiff / 3600);
                                        $timeAgo = convertEnglishToPersian($hours) . ' ساعت پیش';
                                    }
                                ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <?php if (strpos($activity['action'], 'task') !== false): ?>
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            <?php elseif (strpos($activity['action'], 'metrics') !== false): ?>
                                                <line x1="18" y1="20" x2="18" y2="10"></line>
                                                <line x1="12" y1="20" x2="12" y2="4"></line>
                                                <line x1="6" y1="20" x2="6" y2="14"></line>
                                            <?php elseif (strpos($activity['action'], 'login') !== false): ?>
                                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                                <polyline points="10 17 15 12 10 7"></polyline>
                                                <line x1="15" y1="12" x2="3" y2="12"></line>
                                            <?php else: ?>
                                                <circle cx="12" cy="12" r="10"></circle>
                                            <?php endif; ?>
                                        </svg>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-text">
                                            <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong>:
                                            <?php echo htmlspecialchars($activity['description'] ?: $activity['action']); ?>
                                        </div>
                                        <div class="activity-time"><?php echo $timeAgo; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Milestones Progress -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
                        <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path>
                        <path d="M4 22h16"></path>
                        <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path>
                        <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path>
                        <path d="M18 2H6v7a6 6 0 0 0 12 0V2z"></path>
                    </svg>
                    وضعیت چالش‌ها و اهداف
                </h3>
            </div>
            <div class="card-body">
                <div class="milestones">
                    <?php foreach ($milestones as $milestone): ?>
                        <div class="milestone <?php echo $milestone['is_achieved'] ? 'achieved' : ''; ?>">
                            <div class="milestone-icon">
                                <?php echo $milestone['is_achieved'] ? '🏆' : '🎯'; ?>
                            </div>
                            <div class="milestone-content">
                                <div class="milestone-title"><?php echo htmlspecialchars($milestone['title']); ?></div>
                                <div class="milestone-target">
                                    <?php echo convertEnglishToPersian(formatNumber($milestone['target_followers'])); ?> فالوور
                                    <?php if ($milestone['is_achieved'] && $milestone['achieved_at']): ?>
                                        - ✅ محقق شده در <?php echo toPersianDate(strtotime($milestone['achieved_at']))['formatted']; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="script-light.js"></script>
</body>
</html>
