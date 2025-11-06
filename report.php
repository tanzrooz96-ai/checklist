<?php
require_once 'db.php';
requireLogin();

if (!isAdmin()) {
    header('Location: manager.php');
    exit;
}

$user = getCurrentUser($pdo);
$today = date('Y-m-d');
$persianDate = toPersianDate();

// Get or create today's report
$stmt = $pdo->prepare("
    SELECT * FROM daily_reports
    WHERE admin_id = ? AND date = ?
");
$stmt->execute([$user['id'], $today]);
$report = $stmt->fetch();

if (!$report) {
    // Create new draft report
    $stmt = $pdo->prepare("
        INSERT INTO daily_reports (admin_id, date, status)
        VALUES (?, ?, 'draft')
    ");
    $stmt->execute([$user['id'], $today]);
    $reportId = $pdo->lastInsertId();

    // Reload report
    $stmt = $pdo->prepare("SELECT * FROM daily_reports WHERE id = ?");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();
} else {
    $reportId = $report['id'];
}

// Get all tasks
$stmt = $pdo->query("SELECT * FROM daily_tasks ORDER BY display_order ASC");
$allTasks = $stmt->fetchAll();

// Get task details for this report
$stmt = $pdo->prepare("
    SELECT td.*, dt.title, dt.task_type, dt.estimated_minutes, dt.priority, dt.category
    FROM task_details td
    JOIN daily_tasks dt ON td.task_id = dt.id
    WHERE td.report_id = ?
");
$stmt->execute([$reportId]);
$taskDetails = $stmt->fetchAll();

// Process task details
$taskDetailsMap = [];
foreach ($taskDetails as $detail) {
    $detail['details'] = json_decode($detail['details_json'], true);
    $taskDetailsMap[$detail['task_id']] = $detail;
}

// Calculate statistics
$totalTasks = count($allTasks);
$completedTasks = count($taskDetails);
$totalEstimatedTime = array_sum(array_column($allTasks, 'estimated_minutes'));
$totalActualTime = array_sum(array_column($taskDetails, 'time_spent_minutes'));

// Category breakdown
$categoryStats = [];
foreach ($allTasks as $task) {
    if (!isset($categoryStats[$task['category']])) {
        $categoryStats[$task['category']] = [
            'total' => 0,
            'completed' => 0,
            'time_spent' => 0
        ];
    }
    $categoryStats[$task['category']]['total']++;

    if (isset($taskDetailsMap[$task['id']])) {
        $categoryStats[$task['category']]['completed']++;
        $categoryStats[$task['category']]['time_spent'] += $taskDetailsMap[$task['id']]['time_spent_minutes'];
    }
}

// Get today's metrics
$stmt = $pdo->prepare("SELECT * FROM daily_metrics WHERE admin_id = ? AND date = ?");
$stmt->execute([$user['id'], $today]);
$todayMetrics = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>گزارش روزانه - <?php echo $persianDate['formatted']; ?></title>
    <link rel="stylesheet" href="style-light.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            .header { display: none !important; }
            body { background: white !important; }
            .report-container { box-shadow: none !important; }
            @page { margin: 1.5cm; }
        }

        .report-container {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .report-header {
            text-align: center;
            padding-bottom: 30px;
            border-bottom: 3px solid var(--primary-color);
            margin-bottom: 40px;
        }

        .report-header h1 {
            font-size: 32px;
            color: var(--primary-color);
            margin: 0 0 10px 0;
        }

        .report-header .subtitle {
            font-size: 18px;
            color: var(--text-secondary);
        }

        .score-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .score-card {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 30px;
            border-radius: 16px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .score-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        .score-card .score-value {
            font-size: 48px;
            font-weight: 700;
            margin: 0;
        }

        .score-card .score-label {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 8px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin: 40px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(196, 30, 58, 0.2);
        }

        .task-detail-card {
            background: rgba(255, 248, 231, 0.3);
            border: 2px solid var(--glass-border);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .task-detail-card.completed {
            border-color: var(--success-color);
            background: rgba(39, 174, 96, 0.05);
        }

        .task-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .task-detail-header h4 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .task-score-badge {
            background: var(--primary-color);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 14px;
        }

        .task-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .task-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .task-content {
            background: white;
            padding: 16px;
            border-radius: 12px;
            margin-top: 12px;
        }

        .story-item, .post-item {
            padding: 12px;
            background: rgba(196, 30, 58, 0.05);
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .story-item h5, .post-item h5 {
            margin: 0 0 8px 0;
            color: var(--primary-color);
            font-size: 14px;
            font-weight: 700;
        }

        .story-item a, .post-item a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 13px;
            word-break: break-all;
        }

        .story-item a:hover, .post-item a:hover {
            text-decoration: underline;
        }

        .story-item p, .post-item p {
            margin: 8px 0 0 0;
            font-size: 14px;
            color: var(--text-secondary);
        }

        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin: 30px 0;
        }

        .chart-box {
            background: rgba(255, 255, 255, 0.8);
            padding: 24px;
            border-radius: 16px;
            border: 2px solid var(--glass-border);
        }

        .chart-box h4 {
            margin: 0 0 20px 0;
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .btn-toolbar {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .status-badge.draft { background: #95a5a6; color: white; }
        .status-badge.submitted { background: #3498db; color: white; }
        .status-badge.approved { background: #27ae60; color: white; }
        .status-badge.rejected { background: #e74c3c; color: white; }
    </style>
</head>
<body class="dashboard">
    <!-- Header (hidden in print) -->
    <header class="header no-print">
        <div class="header-content">
            <div class="header-right">
                <div class="header-logo">
                    <div class="header-logo-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                    </div>
                    <div class="header-title">
                        <h1>گزارش روزانه</h1>
                        <p>پخش مواد غذایی</p>
                    </div>
                </div>
            </div>
            <div class="header-left">
                <button onclick="window.location.href='admin.php'" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    بازگشت
                </button>
            </div>
        </div>
    </header>

    <main class="main-content">
        <!-- Action Buttons (hidden in print) -->
        <div class="btn-toolbar no-print">
            <?php if ($report['status'] == 'draft'): ?>
                <button onclick="submitReport()" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    ثبت نهایی گزارش
                </button>
            <?php elseif ($report['status'] == 'submitted'): ?>
                <button onclick="requestEditPassword()" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    ویرایش گزارش (نیاز به رمز)
                </button>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                چاپ گزارش
            </button>
        </div>

        <!-- Report Container -->
        <div class="report-container">
            <!-- Report Header -->
            <div class="report-header">
                <h1>📊 گزارش عملکرد روزانه</h1>
                <div class="subtitle">
                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                    <br>
                    <?php echo $persianDate['formatted']; ?>
                </div>
                <div style="margin-top: 16px;">
                    <?php
                    $statusLabels = [
                        'draft' => 'پیش‌نویس',
                        'submitted' => 'ثبت شده',
                        'approved' => 'تایید شده',
                        'rejected' => 'رد شده'
                    ];
                    ?>
                    <span class="status-badge <?php echo $report['status']; ?>">
                        <?php echo $statusLabels[$report['status']]; ?>
                    </span>
                </div>
            </div>

            <!-- Score Summary -->
            <div class="score-summary">
                <div class="score-card">
                    <div class="score-value"><?php echo convertEnglishToPersian($report['total_score']); ?></div>
                    <div class="score-label">امتیاز کلی</div>
                </div>
                <div class="score-card">
                    <div class="score-value"><?php echo convertEnglishToPersian(round($report['completion_rate'])); ?>٪</div>
                    <div class="score-label">میزان تکمیل</div>
                </div>
                <div class="score-card">
                    <div class="score-value"><?php echo convertEnglishToPersian($completedTasks); ?>/<?php echo convertEnglishToPersian($totalTasks); ?></div>
                    <div class="score-label">وظایف انجام شده</div>
                </div>
                <div class="score-card">
                    <div class="score-value"><?php echo convertEnglishToPersian($totalActualTime); ?></div>
                    <div class="score-label">دقیقه کار کرد</div>
                </div>
            </div>

            <!-- Charts -->
            <div class="chart-grid">
                <div class="chart-box">
                    <h4>📊 توزیع وظایف بر اساس دسته‌بندی</h4>
                    <canvas id="categoryChart" height="250"></canvas>
                </div>
                <div class="chart-box">
                    <h4>⏰ توزیع زمان صرف شده</h4>
                    <canvas id="timeChart" height="250"></canvas>
                </div>
            </div>

            <!-- Task Details -->
            <h2 class="section-title">📋 جزئیات وظایف</h2>

            <?php foreach ($allTasks as $task): ?>
                <?php
                $hasDetails = isset($taskDetailsMap[$task['id']]);
                $detail = $hasDetails ? $taskDetailsMap[$task['id']] : null;
                ?>
                <div class="task-detail-card <?php echo $hasDetails ? 'completed' : ''; ?>">
                    <div class="task-detail-header">
                        <h4>
                            <?php echo $hasDetails ? '✓' : '○'; ?>
                            <?php echo htmlspecialchars($task['title']); ?>
                        </h4>
                        <?php if ($hasDetails): ?>
                            <span class="task-score-badge">
                                امتیاز: <?php echo convertEnglishToPersian($detail['quality_score']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($hasDetails): ?>
                        <div class="task-meta">
                            <div class="task-meta-item">
                                <strong>⏱️ زمان:</strong>
                                <?php echo convertEnglishToPersian($detail['time_spent_minutes']); ?> دقیقه
                                (پیشنهادی: <?php echo convertEnglishToPersian($task['estimated_minutes']); ?>)
                            </div>
                            <div class="task-meta-item">
                                <strong>📌 دسته:</strong>
                                <?php echo htmlspecialchars($task['category']); ?>
                            </div>
                            <div class="task-meta-item">
                                <strong>🎯 اولویت:</strong>
                                <?php
                                $priorities = ['low' => 'کم', 'medium' => 'متوسط', 'high' => 'بالا', 'critical' => 'بحرانی'];
                                echo $priorities[$task['priority']];
                                ?>
                            </div>
                        </div>

                        <div class="task-content">
                            <?php
                            $details = $detail['details'];

                            switch($task['task_type']) {
                                case 'stories':
                                    if (($details['count'] ?? 0) == 0) {
                                        echo '<p><strong>دلیل عدم گذاشتن استوری:</strong></p>';
                                        echo '<p>' . nl2br(htmlspecialchars($details['reason'] ?? 'ذکر نشده')) . '</p>';
                                    } else {
                                        echo '<p><strong>تعداد استوری‌ها: ' . convertEnglishToPersian($details['count']) . '</strong></p>';
                                        if (!empty($details['items'])) {
                                            foreach ($details['items'] as $i => $item) {
                                                echo '<div class="story-item">';
                                                echo '<h5>استوری ' . convertEnglishToPersian($i + 1) . '</h5>';
                                                if (!empty($item['link'])) {
                                                    echo '<a href="' . htmlspecialchars($item['link']) . '" target="_blank">🔗 ' . htmlspecialchars($item['link']) . '</a>';
                                                }
                                                if (!empty($item['description'])) {
                                                    echo '<p>' . nl2br(htmlspecialchars($item['description'])) . '</p>';
                                                }
                                                echo '</div>';
                                            }
                                        }
                                    }
                                    break;

                                case 'posts':
                                    if (($details['count'] ?? 0) == 0) {
                                        echo '<p><strong>دلیل عدم پست گذاشتن:</strong></p>';
                                        echo '<p>' . nl2br(htmlspecialchars($details['reason'] ?? 'ذکر نشده')) . '</p>';
                                    } else {
                                        echo '<p><strong>تعداد پست‌ها: ' . convertEnglishToPersian($details['count']) . '</strong></p>';
                                        if (!empty($details['items'])) {
                                            foreach ($details['items'] as $i => $item) {
                                                echo '<div class="post-item">';
                                                echo '<h5>پست ' . convertEnglishToPersian($i + 1) . '</h5>';
                                                if (!empty($item['link'])) {
                                                    echo '<a href="' . htmlspecialchars($item['link']) . '" target="_blank">🔗 ' . htmlspecialchars($item['link']) . '</a>';
                                                }
                                                if (!empty($item['description'])) {
                                                    echo '<p>' . nl2br(htmlspecialchars($item['description'])) . '</p>';
                                                }
                                                echo '</div>';
                                            }
                                        }
                                    }
                                    break;

                                case 'follow':
                                    echo '<p><strong>تعداد فالو: </strong>' . convertEnglishToPersian($details['count'] ?? 0) . '</p>';
                                    if (!empty($details['type'])) {
                                        $types = [
                                            'regular' => 'افراد عادی',
                                            'business' => 'کسب‌وکارها و فروشگاه‌ها',
                                            'brands' => 'برندها و صفحات بزرگ'
                                        ];
                                        echo '<p><strong>نوع اکانت‌ها: </strong>' . ($types[$details['type']] ?? $details['type']) . '</p>';
                                    }
                                    if (!empty($details['description'])) {
                                        echo '<p><strong>توضیحات:</strong></p>';
                                        echo '<p>' . nl2br(htmlspecialchars($details['description'])) . '</p>';
                                    }
                                    break;

                                case 'unfollow':
                                    echo '<p><strong>تعداد آنفالو: </strong>' . convertEnglishToPersian($details['count'] ?? 0) . '</p>';
                                    if (!empty($details['type'])) {
                                        $types = [
                                            'inactive' => 'اکانت‌های غیرفعال',
                                            'irrelevant' => 'اکانت‌های نامرتبط',
                                            'low_engagement' => 'اکانت‌های با تعامل پایین'
                                        ];
                                        echo '<p><strong>نوع اکانت‌ها: </strong>' . ($types[$details['type']] ?? $details['type']) . '</p>';
                                    }
                                    if (!empty($details['description'])) {
                                        echo '<p><strong>توضیحات:</strong></p>';
                                        echo '<p>' . nl2br(htmlspecialchars($details['description'])) . '</p>';
                                    }
                                    break;

                                default:
                                    if (!empty($details['notes'])) {
                                        echo '<p>' . nl2br(htmlspecialchars($details['notes'])) . '</p>';
                                    }
                                    break;
                            }
                            ?>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--text-secondary); font-style: italic;">این وظیفه انجام نشده است</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Metrics Summary -->
            <?php if ($todayMetrics): ?>
                <h2 class="section-title">📈 آمار اینستاگرام</h2>
                <div class="score-summary">
                    <div class="score-card">
                        <div class="score-value"><?php echo convertEnglishToPersian(formatNumber($todayMetrics['followers'])); ?></div>
                        <div class="score-label">فالوورها</div>
                    </div>
                    <div class="score-card">
                        <div class="score-value"><?php echo convertEnglishToPersian(formatNumber($todayMetrics['following'])); ?></div>
                        <div class="score-label">فالووینگ</div>
                    </div>
                    <div class="score-card">
                        <div class="score-value"><?php echo convertEnglishToPersian(formatNumber($todayMetrics['impressions'])); ?></div>
                        <div class="score-label">ایمپرشن</div>
                    </div>
                    <div class="score-card">
                        <div class="score-value"><?php echo convertEnglishToPersian(formatNumber($todayMetrics['profile_visits'])); ?></div>
                        <div class="score-label">بازدید پروفایل</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Category Chart
        const categoryData = <?php echo json_encode($categoryStats); ?>;
        const categoryLabels = Object.keys(categoryData);
        const categoryCompleted = categoryLabels.map(cat => categoryData[cat].completed);

        const ctxCategory = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctxCategory, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryCompleted,
                    backgroundColor: [
                        '#C41E3A',
                        '#F39C12',
                        '#27AE60',
                        '#3498DB',
                        '#9B59B6',
                        '#E74C3C'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: true,
                        labels: {
                            font: { family: 'Vazirmatn', size: 12 },
                            padding: 10
                        }
                    }
                }
            }
        });

        // Time Chart
        const timeLabels = categoryLabels;
        const timeData = categoryLabels.map(cat => categoryData[cat].time_spent);

        const ctxTime = document.getElementById('timeChart').getContext('2d');
        new Chart(ctxTime, {
            type: 'bar',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'دقیقه',
                    data: timeData,
                    backgroundColor: 'rgba(196, 30, 58, 0.8)',
                    borderColor: '#C41E3A',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: { family: 'Vazirmatn' }
                        }
                    },
                    x: {
                        ticks: {
                            font: { family: 'Vazirmatn' }
                        }
                    }
                }
            }
        });

        // Submit report function
        async function submitReport() {
            if (!confirm('آیا از ثبت نهایی گزارش اطمینان دارید؟ پس از ثبت برای ویرایش نیاز به رمز عبور خواهید داشت.')) {
                return;
            }

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'submit_report',
                        report_id: <?php echo $reportId; ?>
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('گزارش با موفقیت ثبت شد!');
                    window.location.reload();
                } else {
                    alert(result.message || 'خطا در ثبت گزارش');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('خطا در ارتباط با سرور');
            }
        }

        // Request password for editing
        async function requestEditPassword() {
            const password = prompt('برای ویرایش گزارش ثبت شده، لطفاً رمز عبور را وارد کنید:');

            if (!password) {
                return;
            }

            const correctPassword = '14512';

            if (password !== correctPassword) {
                alert('رمز عبور اشتباه است!');
                return;
            }

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'unlock_report_edit',
                        report_id: <?php echo $reportId; ?>,
                        password: password
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('گزارش باز شد! می‌توانید ویرایش کنید.');
                    window.location.href = 'admin.php';
                } else {
                    alert(result.message || 'خطا در باز کردن گزارش');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('خطا در ارتباط با سرور');
            }
        }
    </script>
    <script src="script-light.js"></script>
</body>
</html>
