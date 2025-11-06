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

// Get today's metrics
$stmt = $pdo->prepare("SELECT * FROM daily_metrics WHERE admin_id = ? AND date = ?");
$stmt->execute([$user['id'], $today]);
$todayMetrics = $stmt->fetch();

// Get yesterday's metrics for comparison
$yesterday = date('Y-m-d', strtotime('-1 day'));
$stmt = $pdo->prepare("SELECT * FROM daily_metrics WHERE admin_id = ? AND date = ?");
$stmt->execute([$user['id'], $yesterday]);
$yesterdayMetrics = $stmt->fetch();

// Calculate changes
$followersChange = $todayMetrics && $yesterdayMetrics ? $todayMetrics['followers'] - $yesterdayMetrics['followers'] : 0;
$impressionsChange = $todayMetrics && $yesterdayMetrics ? $todayMetrics['impressions'] - $yesterdayMetrics['impressions'] : 0;

// Get current followers for progress
$currentFollowers = $todayMetrics ? $todayMetrics['followers'] : 0;

// Get all tasks
$stmt = $pdo->query("SELECT * FROM daily_tasks ORDER BY display_order ASC");
$allTasks = $stmt->fetchAll();

// Get today's completed tasks
$stmt = $pdo->prepare("
    SELECT task_id FROM task_completions
    WHERE admin_id = ? AND date = ?
");
$stmt->execute([$user['id'], $today]);
$completedTaskIds = array_column($stmt->fetchAll(), 'task_id');

// Calculate completion stats
$totalTasks = count($allTasks);
$completedTasks = count($completedTaskIds);
$completionPercentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
$totalMinutes = array_sum(array_column($allTasks, 'estimated_minutes'));
$completedMinutes = 0;
foreach ($allTasks as $task) {
    if (in_array($task['id'], $completedTaskIds)) {
        $completedMinutes += $task['estimated_minutes'];
    }
}

// Get milestones
$stmt = $pdo->query("SELECT * FROM milestones ORDER BY display_order ASC");
$milestones = $stmt->fetchAll();

// Update milestone achievements
foreach ($milestones as &$milestone) {
    if ($currentFollowers >= $milestone['target_followers'] && !$milestone['is_achieved']) {
        $stmt = $pdo->prepare("UPDATE milestones SET is_achieved = 1, achieved_at = NOW() WHERE id = ?");
        $stmt->execute([$milestone['id']]);
        $milestone['is_achieved'] = 1;
    }
}

// Find next milestone
$nextMilestone = null;
foreach ($milestones as $milestone) {
    if (!$milestone['is_achieved']) {
        $nextMilestone = $milestone;
        break;
    }
}

// Get motivational message
$hour = (int)date('H');
if ($hour < 12) {
    $messageType = 'morning';
} elseif ($hour < 18) {
    $messageType = 'afternoon';
} else {
    $messageType = 'evening';
}
$motivationalMessage = getMotivationalMessage($pdo, $messageType);

// Get recent activities
$stmt = $pdo->prepare("
    SELECT * FROM activity_log
    WHERE admin_id = ?
    ORDER BY timestamp DESC
    LIMIT 10
");
$stmt->execute([$user['id']]);
$recentActivities = $stmt->fetchAll();

// Get last 7 days metrics for chart
$stmt = $pdo->prepare("
    SELECT date, followers, impressions, profile_visits
    FROM daily_metrics
    WHERE admin_id = ?
    ORDER BY date DESC
    LIMIT 7
");
$stmt->execute([$user['id']]);
$chartData = array_reverse($stmt->fetchAll());
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد ادمین - مدیریت اینستاگرام</title>
    <link rel="stylesheet" href="style.css">
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
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                    </div>
                    <div class="header-title">
                        <h1>مدیریت اینستاگرام</h1>
                        <p>پخش مواد غذایی</p>
                    </div>
                </div>
            </div>
            <div class="header-left">
                <div class="user-info">
                    <p><?php echo htmlspecialchars($user['full_name']); ?></p>
                    <span>ادمین اینستاگرام</span>
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
            <p><?php echo convertEnglishToPersian(date('H:i')); ?> - وقت کار کردن و رشد کردن است! 💪</p>
        </div>

        <!-- Motivational Message -->
        <div class="motivational-message">
            <div class="motivational-icon">🚀</div>
            <div class="motivational-text"><?php echo $motivationalMessage; ?></div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">فالوورها</span>
                    <div class="stat-icon primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo convertEnglishToPersian(formatNumber($currentFollowers)); ?></div>
                <?php if ($followersChange != 0): ?>
                    <div class="stat-change <?php echo $followersChange > 0 ? 'positive' : 'negative'; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <?php if ($followersChange > 0): ?>
                                <polyline points="18 15 12 9 6 15"></polyline>
                            <?php else: ?>
                                <polyline points="6 9 12 15 18 9"></polyline>
                            <?php endif; ?>
                        </svg>
                        <span><?php echo convertEnglishToPersian(abs($followersChange)); ?> نسبت به دیروز</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">فالووینگ</span>
                    <div class="stat-icon success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo convertEnglishToPersian(formatNumber($todayMetrics ? $todayMetrics['following'] : 0)); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">ایمپرشن امروز</span>
                    <div class="stat-icon warning">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo convertEnglishToPersian(formatNumber($todayMetrics ? $todayMetrics['impressions'] : 0)); ?></div>
                <?php if ($impressionsChange != 0): ?>
                    <div class="stat-change <?php echo $impressionsChange > 0 ? 'positive' : 'negative'; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <?php if ($impressionsChange > 0): ?>
                                <polyline points="18 15 12 9 6 15"></polyline>
                            <?php else: ?>
                                <polyline points="6 9 12 15 18 9"></polyline>
                            <?php endif; ?>
                        </svg>
                        <span><?php echo convertEnglishToPersian(abs($impressionsChange)); ?> نسبت به دیروز</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">وظایف تکمیل شده</span>
                    <div class="stat-icon primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 11 12 14 22 4"></polyline>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo convertEnglishToPersian($completedTasks); ?>/<?php echo convertEnglishToPersian($totalTasks); ?></div>
                <div class="stat-change positive">
                    <span><?php echo convertEnglishToPersian($completionPercentage); ?>٪ پیشرفت</span>
                </div>
            </div>
        </div>

        <!-- Progress to Next Milestone -->
        <?php if ($nextMilestone): ?>
            <?php
                $prevTarget = 0;
                foreach ($milestones as $m) {
                    if ($m['id'] == $nextMilestone['id']) break;
                    if ($m['is_achieved']) $prevTarget = $m['target_followers'];
                }
                $range = $nextMilestone['target_followers'] - $prevTarget;
                $progress = $currentFollowers - $prevTarget;
                $progressPercent = $range > 0 ? min(100, max(0, ($progress / $range) * 100)) : 0;
            ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        چالش بعدی: <?php echo htmlspecialchars($nextMilestone['title']); ?>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="progress-container">
                        <div class="progress-header">
                            <span class="progress-title"><?php echo htmlspecialchars($nextMilestone['description']); ?></span>
                            <span class="progress-stats">
                                <?php echo convertEnglishToPersian(formatNumber($currentFollowers)); ?> /
                                <?php echo convertEnglishToPersian(formatNumber($nextMilestone['target_followers'])); ?>
                            </span>
                        </div>
                        <div class="progress-bar-wrapper">
                            <div class="progress-bar" style="width: <?php echo $progressPercent; ?>%"></div>
                        </div>
                        <p class="mt-2 text-center" style="font-size: 13px; color: var(--text-secondary);">
                            فقط <?php echo convertEnglishToPersian(formatNumber($nextMilestone['target_followers'] - $currentFollowers)); ?> فالوور تا هدف بعدی! 🎯
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Daily Checklist -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"></path>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                        چک‌لیست وظایف روزانه
                    </h3>
                    <span class="badge badge-high">
                        <?php echo convertEnglishToPersian($completedMinutes); ?>/<?php echo convertEnglishToPersian($totalMinutes); ?> دقیقه
                    </span>
                </div>
                <div class="card-body">
                    <div class="progress-container">
                        <div class="progress-header">
                            <span class="progress-title">پیشرفت امروز</span>
                            <span class="progress-stats"><?php echo convertEnglishToPersian($completionPercentage); ?>٪</span>
                        </div>
                        <div class="progress-bar-wrapper">
                            <div class="progress-bar" style="width: <?php echo $completionPercentage; ?>%"></div>
                        </div>
                    </div>

                    <div class="checklist mt-3">
                        <?php foreach ($allTasks as $task): ?>
                            <?php
                                $isCompleted = in_array($task['id'], $completedTaskIds);
                                $priorityClass = 'badge-' . $task['priority'];
                            ?>
                            <div class="checklist-item <?php echo $isCompleted ? 'completed' : ''; ?>" data-task-id="<?php echo $task['id']; ?>">
                                <div class="checkbox-wrapper">
                                    <input type="checkbox"
                                           class="checkbox task-checkbox"
                                           data-task-id="<?php echo $task['id']; ?>"
                                           <?php echo $isCompleted ? 'checked' : ''; ?>>
                                </div>
                                <div class="checklist-content" onclick="openTaskDetailModal(<?php echo $task['id']; ?>, '<?php echo htmlspecialchars($task['task_type'] ?? 'general'); ?>')" style="cursor: pointer;">
                                    <div class="checklist-title"><?php echo htmlspecialchars($task['title']); ?></div>
                                    <div class="checklist-meta">
                                        <span class="badge <?php echo $priorityClass; ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                            </svg>
                                            <?php
                                                $priorities = ['low' => 'کم', 'medium' => 'متوسط', 'high' => 'بالا', 'critical' => 'بحرانی'];
                                                echo $priorities[$task['priority']];
                                            ?>
                                        </span>
                                        <span class="badge badge-medium">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                            <?php echo convertEnglishToPersian($task['estimated_minutes']); ?> دقیقه
                                        </span>
                                        <button class="btn-detail" onclick="event.stopPropagation(); openTaskDetailModal(<?php echo $task['id']; ?>, '<?php echo htmlspecialchars($task['task_type'] ?? 'general'); ?>')">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                            جزئیات
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Metrics Entry -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            ثبت آمار امروز
                        </h3>
                    </div>
                    <div class="card-body">
                        <form id="metricsForm">
                            <div class="form-group">
                                <label class="form-label">فالوورها</label>
                                <input type="number" name="followers" class="form-input form-input-small"
                                       value="<?php echo $todayMetrics ? $todayMetrics['followers'] : ''; ?>"
                                       placeholder="تعداد فالوور" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">فالووینگ</label>
                                <input type="number" name="following" class="form-input form-input-small"
                                       value="<?php echo $todayMetrics ? $todayMetrics['following'] : ''; ?>"
                                       placeholder="تعداد فالووینگ" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">ایمپرشن</label>
                                <input type="number" name="impressions" class="form-input form-input-small"
                                       value="<?php echo $todayMetrics ? $todayMetrics['impressions'] : ''; ?>"
                                       placeholder="تعداد ایمپرشن" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">بازدید پروفایل</label>
                                <input type="number" name="profile_visits" class="form-input form-input-small"
                                       value="<?php echo $todayMetrics ? $todayMetrics['profile_visits'] : ''; ?>"
                                       placeholder="تعداد بازدید">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <span>ذخیره آمار</span>
                                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Milestones -->
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
                            جوایز و چالش‌ها
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
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    نمودار رشد ۷ روز گذشته
                </h3>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="metricsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Report Generation Section -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    گزارش روزانه
                </h3>
            </div>
            <div class="card-body">
                <p class="text-secondary mb-3">برای ثبت گزارش نهایی روزانه، ابتدا تمام جزئیات وظایف را تکمیل کنید سپس گزارش را ثبت نمایید.</p>
                <div class="btn-group">
                    <button onclick="window.location.href='report.php'" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        مشاهده/ثبت گزارش امروز
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Task Detail Modal -->
    <div id="taskDetailModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3 id="modalTaskTitle">جزئیات وظیفه</h3>
                <button class="modal-close" onclick="closeTaskDetailModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" id="taskDetailForm">
                <!-- Dynamic form will be inserted here -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeTaskDetailModal()">انصراف</button>
                <button class="btn btn-primary" onclick="saveTaskDetails()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    ذخیره جزئیات
                </button>
            </div>
        </div>
    </div>

    <script>
        // Tasks Data
        const allTasksData = <?php echo json_encode($allTasks); ?>;

        // Chart Data
        const chartData = <?php echo json_encode($chartData); ?>;
        const labels = chartData.map(d => {
            const date = new Date(d.date);
            return date.toLocaleDateString('fa-IR', { month: 'short', day: 'numeric' });
        });
        const followersData = chartData.map(d => d.followers);
        const impressionsData = chartData.map(d => d.impressions);

        // Create Chart
        const ctx = document.getElementById('metricsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'فالوورها',
                        data: followersData,
                        borderColor: '#C41E3A',
                        backgroundColor: 'rgba(196, 30, 58, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'ایمپرشن',
                        data: impressionsData,
                        borderColor: '#F39C12',
                        backgroundColor: 'rgba(243, 156, 18, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        rtl: true,
                        labels: {
                            font: { family: 'Vazirmatn', size: 12 },
                            padding: 15
                        }
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
    </script>
    <script src="script.js"></script>
</body>
</html>
