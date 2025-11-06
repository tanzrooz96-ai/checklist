<?php
require_once 'db.php';

// بررسی لاگین
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$reportId = $_GET['id'] ?? 0;
if (!$reportId) {
    header('Location: ' . (isAdmin() ? 'admin.php' : 'manager.php'));
    exit;
}

// دریافت گزارش
$stmt = $pdo->prepare("
    SELECT dr.*, u.full_name as admin_name
    FROM daily_reports dr
    LEFT JOIN users u ON dr.admin_id = u.id
    WHERE dr.id = ?
");
$stmt->execute([$reportId]);
$report = $stmt->fetch();

if (!$report) {
    header('Location: ' . (isAdmin() ? 'admin.php' : 'manager.php'));
    exit;
}

// دریافت تسک‌های گزارش
$stmt = $pdo->prepare("
    SELECT * FROM report_tasks
    WHERE report_id = ?
    ORDER BY weight DESC, task_category
");
$stmt->execute([$reportId]);
$tasks = $stmt->fetchAll();

// گروه‌بندی تسک‌ها بر اساس دسته
$groupedTasks = [];
foreach ($tasks as $task) {
    $groupedTasks[$task['task_category']][] = $task;
}

$persianDate = toPersianDate(strtotime($report['report_date']));
$scoreColor = getScoreColor($report['score']);
$scoreLabel = getScoreLabel($report['score']);

// دریافت یادداشت (اگر وجود دارد)
$stmt = $pdo->prepare("
    SELECT description FROM activity_logs
    WHERE user_id = ? AND action = 'report_note' AND DATE(created_at) = ?
    ORDER BY created_at DESC LIMIT 1
");
$stmt->execute([$report['admin_id'], $report['report_date']]);
$note = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>گزارش روزانه - <?php echo $persianDate['full']; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="report-page">
    <!-- هدر (مخفی در چاپ) -->
    <header class="header no-print">
        <div class="container">
            <div class="header-content">
                <div class="header-right">
                    <div class="logo">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                    </div>
                    <div>
                        <h2>سیستم مدیریت اینستاگرام</h2>
                        <p>گزارش روزانه</p>
                    </div>
                </div>
                <div class="header-left">
                    <a href="<?php echo isAdmin() ? 'admin.php' : 'manager.php'; ?>" class="btn btn-secondary btn-sm">بازگشت</a>
                    <button onclick="window.print()" class="btn btn-primary btn-sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        چاپ / اسکرین‌شات
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- محتوای گزارش -->
    <main class="report-main">
        <div class="report-container">
            <!-- هدر گزارش -->
            <div class="report-header">
                <div class="report-logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                    </svg>
                </div>
                <h1>گزارش روزانه اینستاگرام</h1>
                <h2><?php echo $persianDate['full_with_day']; ?></h2>
                <p class="report-admin">گزارش: <?php echo htmlspecialchars($report['admin_name']); ?></p>
            </div>

            <!-- نمایش امتیاز -->
            <div class="score-card">
                <div class="score-circle" style="--score-color: <?php echo $scoreColor; ?>">
                    <svg viewBox="0 0 200 200">
                        <circle cx="100" cy="100" r="90" fill="none" stroke="#f0f0f0" stroke-width="12"/>
                        <circle cx="100" cy="100" r="90" fill="none" stroke="<?php echo $scoreColor; ?>" stroke-width="12"
                                stroke-dasharray="<?php echo round($report['score'] * 5.65); ?> 565"
                                stroke-linecap="round" transform="rotate(-90 100 100)"/>
                    </svg>
                    <div class="score-number">
                        <span class="score-value"><?php echo toPersianNumber(round($report['score'])); ?></span>
                        <span class="score-max">/ ۱۰۰</span>
                    </div>
                </div>
                <div class="score-info">
                    <h3 style="color: <?php echo $scoreColor; ?>"><?php echo $scoreLabel; ?></h3>
                    <p>عملکرد امروز</p>
                </div>
            </div>

            <!-- جزئیات تسک‌ها -->
            <div class="tasks-section">
                <h3 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11l3 3L22 4"></path>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                    جزئیات فعالیت‌ها
                </h3>

                <div class="tasks-grid">
                    <!-- پست‌ها و محتوا -->
                    <div class="task-group">
                        <h4>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            محتوا و انتشار
                        </h4>
                        <?php
                        $contentCategories = ['posts', 'stories', 'reels', 'highlights'];
                        foreach ($tasks as $task):
                            if (in_array($task['task_category'], $contentCategories)):
                        ?>
                            <div class="task-item">
                                <div class="task-info">
                                    <span class="task-name"><?php echo $task['task_name']; ?></span>
                                    <span class="task-value"><?php echo toPersianNumber($task['task_value']); ?> / <?php echo toPersianNumber($task['max_value']); ?></span>
                                </div>
                                <div class="task-progress">
                                    <div class="progress-bar-fill" style="width: <?php echo min(100, ($task['task_value'] / max(1, $task['max_value'])) * 100); ?>%; background: <?php echo getScoreColor($task['score'] * 10); ?>"></div>
                                </div>
                                <span class="task-score" style="color: <?php echo getScoreColor($task['score'] * 10); ?>">
                                    +<?php echo toPersianNumber(round($task['score'], 1)); ?>
                                </span>
                            </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>

                    <!-- تعاملات -->
                    <div class="task-group">
                        <h4>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            تعامل با فالوورها
                        </h4>
                        <?php
                        $engagementCategories = ['directs', 'pending_directs', 'comments', 'likes'];
                        foreach ($tasks as $task):
                            if (in_array($task['task_category'], $engagementCategories)):
                                $isNegative = $task['weight'] < 0;
                        ?>
                            <div class="task-item <?php echo $isNegative ? 'negative' : ''; ?>">
                                <div class="task-info">
                                    <span class="task-name"><?php echo $task['task_name']; ?></span>
                                    <span class="task-value"><?php echo toPersianNumber($task['task_value']); ?><?php if ($task['max_value'] > 0): ?> / <?php echo toPersianNumber($task['max_value']); ?><?php endif; ?></span>
                                </div>
                                <?php if ($task['max_value'] > 0): ?>
                                    <div class="task-progress">
                                        <div class="progress-bar-fill" style="width: <?php echo min(100, ($task['task_value'] / $task['max_value']) * 100); ?>%; background: <?php echo getScoreColor($task['score'] * 10); ?>"></div>
                                    </div>
                                <?php endif; ?>
                                <span class="task-score" style="color: <?php echo $isNegative ? '#E74C3C' : getScoreColor($task['score'] * 10); ?>">
                                    <?php echo $task['score'] >= 0 ? '+' : ''; ?><?php echo toPersianNumber(round($task['score'], 1)); ?>
                                </span>
                            </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>

                    <!-- برنامه‌ریزی -->
                    <div class="task-group">
                        <h4>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                            برنامه‌ریزی و آمادگی
                        </h4>
                        <?php
                        $planningCategories = ['prepared_content', 'content_ideas', 'content_calendar'];
                        foreach ($tasks as $task):
                            if (in_array($task['task_category'], $planningCategories)):
                        ?>
                            <div class="task-item">
                                <div class="task-info">
                                    <span class="task-name"><?php echo $task['task_name']; ?></span>
                                    <?php if ($task['max_value'] == 1): ?>
                                        <span class="task-badge <?php echo $task['task_value'] ? 'success' : 'gray'; ?>">
                                            <?php echo $task['task_value'] ? 'انجام شده' : 'انجام نشده'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="task-value"><?php echo toPersianNumber($task['task_value']); ?> / <?php echo toPersianNumber($task['max_value']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($task['max_value'] > 1): ?>
                                    <div class="task-progress">
                                        <div class="progress-bar-fill" style="width: <?php echo min(100, ($task['task_value'] / $task['max_value']) * 100); ?>%; background: <?php echo getScoreColor($task['score'] * 10); ?>"></div>
                                    </div>
                                <?php endif; ?>
                                <span class="task-score" style="color: <?php echo getScoreColor($task['score'] * 10); ?>">
                                    +<?php echo toPersianNumber(round($task['score'], 1)); ?>
                                </span>
                            </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>

                    <!-- سایر فعالیت‌ها -->
                    <div class="task-group">
                        <h4>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>
                            رشد و تحلیل
                        </h4>
                        <?php
                        $otherCategories = ['new_followers', 'followed_pages', 'analytics_checked', 'competitors_checked'];
                        foreach ($tasks as $task):
                            if (in_array($task['task_category'], $otherCategories)):
                        ?>
                            <div class="task-item">
                                <div class="task-info">
                                    <span class="task-name"><?php echo $task['task_name']; ?></span>
                                    <?php if ($task['max_value'] == 1): ?>
                                        <span class="task-badge <?php echo $task['task_value'] ? 'success' : 'gray'; ?>">
                                            <?php echo $task['task_value'] ? 'انجام شده' : 'انجام نشده'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="task-value"><?php echo toPersianNumber($task['task_value']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="task-score" style="color: <?php echo getScoreColor($task['score'] * 10); ?>">
                                    +<?php echo toPersianNumber(round($task['score'], 1)); ?>
                                </span>
                            </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>
            </div>

            <!-- یادداشت‌ها -->
            <?php if ($note && $note['description']): ?>
                <div class="notes-section">
                    <h3 class="section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        یادداشت‌ها
                    </h3>
                    <div class="notes-content">
                        <?php echo nl2br(htmlspecialchars($note['description'])); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- فوتر گزارش -->
            <div class="report-footer">
                <p>تاریخ ثبت: <?php echo toPersianDate(strtotime($report['created_at']))['full_with_day']; ?></p>
                <p>سیستم مدیریت اینستاگرام - پخش مواد غذایی</p>
            </div>
        </div>
    </main>
</body>
</html>
