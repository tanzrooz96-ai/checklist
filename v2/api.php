<?php
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

// بررسی لاگین
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'لطفا وارد سیستم شوید']);
    exit;
}

$action = $_GET['action'] ?? '';
$user = getCurrentUser($pdo);

try {
    switch ($action) {
        case 'submit_report':
            if (!isAdmin()) {
                throw new Exception('دسترسی غیرمجاز');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $reportDate = date('Y-m-d');

            // بررسی وجود گزارش امروز
            $stmt = $pdo->prepare("SELECT id FROM daily_reports WHERE admin_id = ? AND report_date = ?");
            $stmt->execute([$user['id'], $reportDate]);
            $existing = $stmt->fetch();

            if ($existing && $existing['status'] === 'completed') {
                throw new Exception('گزارش امروز قبلاً ثبت شده است');
            }

            // تعریف تسک‌ها با وزن‌ها
            $tasks = [
                ['category' => 'posts', 'name' => 'پست‌های منتشر شده', 'value' => $input['posts'] ?? 0, 'max' => 15, 'weight' => 15],
                ['category' => 'stories', 'name' => 'استوری‌های منتشر شده', 'value' => $input['stories'] ?? 0, 'max' => 15, 'weight' => 15],
                ['category' => 'reels', 'name' => 'ریلز منتشر شده', 'value' => $input['reels'] ?? 0, 'max' => 10, 'weight' => 10],
                ['category' => 'highlights', 'name' => 'استوری هایلایت', 'value' => $input['highlights'] ?? 0, 'max' => 10, 'weight' => 5],
                ['category' => 'directs', 'name' => 'پاسخ دایرکت', 'value' => $input['directs'] ?? 0, 'max' => 1000, 'weight' => 15],
                ['category' => 'pending_directs', 'name' => 'دایرکت‌های معوق', 'value' => $input['pending_directs'] ?? 0, 'max' => 0, 'weight' => -5], // منفی
                ['category' => 'comments', 'name' => 'پاسخ کامنت', 'value' => $input['comments'] ?? 0, 'max' => 500, 'weight' => 10],
                ['category' => 'likes', 'name' => 'لایک‌ها', 'value' => $input['likes'] ?? 0, 'max' => 1000, 'weight' => 5],
                ['category' => 'prepared_content', 'name' => 'محتوای آماده', 'value' => $input['prepared_content'] ?? 0, 'max' => 10, 'weight' => 10],
                ['category' => 'content_ideas', 'name' => 'ایده‌های جدید', 'value' => $input['content_ideas'] ?? 0, 'max' => 10, 'weight' => 5],
                ['category' => 'content_calendar', 'name' => 'تقویم محتوا', 'value' => ($input['content_calendar'] ?? 0) ? 1 : 0, 'max' => 1, 'weight' => 5],
                ['category' => 'new_followers', 'name' => 'فالوورهای جدید', 'value' => $input['new_followers'] ?? 0, 'max' => 100, 'weight' => 5],
                ['category' => 'followed_pages', 'name' => 'فالو صفحات', 'value' => $input['followed_pages'] ?? 0, 'max' => 50, 'weight' => 3],
                ['category' => 'analytics_checked', 'name' => 'بررسی آمار', 'value' => ($input['analytics_checked'] ?? 0) ? 1 : 0, 'max' => 1, 'weight' => 3],
                ['category' => 'competitors_checked', 'name' => 'بررسی رقبا', 'value' => ($input['competitors_checked'] ?? 0) ? 1 : 0, 'max' => 1, 'weight' => 4],
            ];

            // محاسبه امتیاز کل
            $totalScore = calculateScore($tasks);

            $pdo->beginTransaction();

            // ثبت یا به‌روزرسانی گزارش
            if ($existing) {
                $stmt = $pdo->prepare("
                    UPDATE daily_reports
                    SET score = ?, status = 'completed', updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$totalScore, $existing['id']]);
                $reportId = $existing['id'];

                // حذف تسک‌های قبلی
                $stmt = $pdo->prepare("DELETE FROM report_tasks WHERE report_id = ?");
                $stmt->execute([$reportId]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO daily_reports (admin_id, report_date, score, status)
                    VALUES (?, ?, ?, 'completed')
                ");
                $stmt->execute([$user['id'], $reportDate, $totalScore]);
                $reportId = $pdo->lastInsertId();
            }

            // ثبت تسک‌ها
            $stmt = $pdo->prepare("
                INSERT INTO report_tasks (report_id, task_category, task_name, task_value, max_value, weight, score)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($tasks as $task) {
                $taskScore = 0;
                if ($task['max'] > 0) {
                    $percentage = min(100, ($task['value'] / $task['max']) * 100);
                    $taskScore = ($percentage / 100) * $task['weight'];
                } elseif ($task['weight'] < 0) {
                    // تسک منفی (مثل دایرکت‌های معوق)
                    if ($task['value'] > 0) {
                        $taskScore = $task['weight'];
                    }
                } else {
                    // تسک boolean
                    if ($task['value'] > 0) {
                        $taskScore = $task['weight'];
                    }
                }

                $stmt->execute([
                    $reportId,
                    $task['category'],
                    $task['name'],
                    $task['value'],
                    $task['max'],
                    $task['weight'],
                    round($taskScore, 2)
                ]);
            }

            // ثبت یادداشت (اگر وجود داشت)
            if (!empty($input['notes'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO activity_logs (user_id, action, description)
                    VALUES (?, 'report_note', ?)
                ");
                $stmt->execute([$user['id'], $input['notes']]);
            }

            // ثبت لاگ
            logActivity($pdo, $user['id'], 'report_submitted', 'ثبت گزارش روزانه با امتیاز ' . $totalScore);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'گزارش با موفقیت ثبت شد',
                'report_id' => $reportId,
                'score' => $totalScore
            ]);
            break;

        case 'get_reports':
            if (!isManager()) {
                throw new Exception('دسترسی غیرمجاز');
            }

            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

            $stmt = $pdo->prepare("
                SELECT
                    dr.*,
                    u.full_name as admin_name,
                    COUNT(rt.id) as task_count
                FROM daily_reports dr
                LEFT JOIN users u ON dr.admin_id = u.id
                LEFT JOIN report_tasks rt ON dr.id = rt.report_id
                WHERE dr.status = 'completed'
                GROUP BY dr.id
                ORDER BY dr.report_date DESC, dr.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $reports = $stmt->fetchAll();

            // تبدیل تاریخ‌ها به شمسی
            foreach ($reports as &$report) {
                $persianDate = toPersianDate(strtotime($report['report_date']));
                $report['persian_date'] = $persianDate['full'];
                $report['persian_date_full'] = $persianDate['full_with_day'];
                $report['score_color'] = getScoreColor($report['score']);
                $report['score_label'] = getScoreLabel($report['score']);
            }

            echo json_encode([
                'success' => true,
                'reports' => $reports
            ]);
            break;

        case 'get_stats':
            if (!isManager()) {
                throw new Exception('دسترسی غیرمجاز');
            }

            // آمار کلی
            $stmt = $pdo->query("
                SELECT
                    COUNT(*) as total_reports,
                    AVG(score) as avg_score,
                    MAX(score) as max_score,
                    MIN(score) as min_score
                FROM daily_reports
                WHERE status = 'completed'
            ");
            $stats = $stmt->fetch();

            // آمار 30 روز اخیر
            $stmt = $pdo->query("
                SELECT
                    report_date,
                    score
                FROM daily_reports
                WHERE status = 'completed' AND report_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ORDER BY report_date ASC
            ");
            $dailyScores = $stmt->fetchAll();

            // میانگین هر دسته تسک
            $stmt = $pdo->query("
                SELECT
                    task_category,
                    AVG(score) as avg_score,
                    AVG(task_value) as avg_value
                FROM report_tasks
                GROUP BY task_category
                ORDER BY avg_score DESC
            ");
            $taskStats = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'daily_scores' => $dailyScores,
                'task_stats' => $taskStats
            ]);
            break;

        default:
            throw new Exception('عملیات نامعتبر');
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
