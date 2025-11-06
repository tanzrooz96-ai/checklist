<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'لطفا وارد شوید']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = getCurrentUser($pdo);
$today = date('Y-m-d');

try {
    switch ($action) {
        case 'toggle_task':
            $taskId = $_POST['task_id'] ?? 0;
            $completed = $_POST['completed'] ?? 0;

            if (!$taskId) {
                throw new Exception('شناسه وظیفه نامعتبر است');
            }

            // Check if task exists
            $stmt = $pdo->prepare("SELECT * FROM daily_tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();

            if (!$task) {
                throw new Exception('وظیفه یافت نشد');
            }

            if ($completed) {
                // Mark as completed
                $stmt = $pdo->prepare("
                    INSERT INTO task_completions (task_id, admin_id, date, time_spent_minutes)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE completed_at = NOW()
                ");
                $stmt->execute([$taskId, $user['id'], $today, $task['estimated_minutes']]);

                // Log activity
                logActivity($pdo, $user['id'], 'task_completed', 'تکمیل وظیفه: ' . $task['title']);
            } else {
                // Mark as incomplete
                $stmt = $pdo->prepare("DELETE FROM task_completions WHERE task_id = ? AND admin_id = ? AND date = ?");
                $stmt->execute([$taskId, $user['id'], $today]);

                // Log activity
                logActivity($pdo, $user['id'], 'task_uncompleted', 'لغو تکمیل وظیفه: ' . $task['title']);
            }

            // Calculate progress
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM daily_tasks");
            $totalTasks = $stmt->fetch()['total'];

            $stmt = $pdo->prepare("SELECT COUNT(*) as completed FROM task_completions WHERE admin_id = ? AND date = ?");
            $stmt->execute([$user['id'], $today]);
            $completedTasks = $stmt->fetch()['completed'];

            $percentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

            // Check for milestone
            $milestoneAchieved = false;
            $milestoneMessage = '';

            if ($completed && $percentage == 100) {
                $milestoneMessage = 'عالی! شما تمام وظایف امروز را انجام دادید! 🎉🏆';
                $milestoneAchieved = true;
            }

            echo json_encode([
                'success' => true,
                'progress' => [
                    'completed' => $completedTasks,
                    'total' => $totalTasks,
                    'percentage' => $percentage
                ],
                'milestone_achieved' => $milestoneAchieved,
                'milestone_message' => $milestoneMessage
            ]);
            break;

        case 'save_metrics':
            if (!isAdmin()) {
                throw new Exception('فقط ادمین می‌تواند آمار را ثبت کند');
            }

            $followers = (int)($_POST['followers'] ?? 0);
            $following = (int)($_POST['following'] ?? 0);
            $impressions = (int)($_POST['impressions'] ?? 0);
            $profileVisits = (int)($_POST['profile_visits'] ?? 0);

            if ($followers < 0 || $following < 0 || $impressions < 0) {
                throw new Exception('مقادیر نمی‌توانند منفی باشند');
            }

            // Insert or update metrics
            $stmt = $pdo->prepare("
                INSERT INTO daily_metrics (date, followers, following, impressions, profile_visits, admin_id)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    followers = VALUES(followers),
                    following = VALUES(following),
                    impressions = VALUES(impressions),
                    profile_visits = VALUES(profile_visits),
                    updated_at = NOW()
            ");
            $stmt->execute([$today, $followers, $following, $impressions, $profileVisits, $user['id']]);

            // Log activity
            logActivity($pdo, $user['id'], 'metrics_updated', sprintf(
                'ثبت آمار - فالوور: %d، فالووینگ: %d، ایمپرشن: %d',
                $followers, $following, $impressions
            ));

            // Check for follower milestones
            $stmt = $pdo->prepare("
                SELECT * FROM milestones
                WHERE target_followers <= ? AND is_achieved = 0
                ORDER BY target_followers ASC
            ");
            $stmt->execute([$followers]);
            $achievedMilestones = $stmt->fetchAll();

            $milestoneAchieved = false;
            $milestoneMessage = '';

            foreach ($achievedMilestones as $milestone) {
                $stmt = $pdo->prepare("UPDATE milestones SET is_achieved = 1, achieved_at = NOW() WHERE id = ?");
                $stmt->execute([$milestone['id']]);

                $milestoneMessage .= $milestone['reward_message'] . ' ';
                $milestoneAchieved = true;

                // Log milestone achievement
                logActivity($pdo, $user['id'], 'milestone_achieved', 'دستیابی به هدف: ' . $milestone['title']);
            }

            echo json_encode([
                'success' => true,
                'message' => 'آمار با موفقیت ثبت شد',
                'milestone_achieved' => $milestoneAchieved,
                'milestone_message' => trim($milestoneMessage)
            ]);
            break;

        case 'get_activity_log':
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = (int)($_GET['offset'] ?? 0);

            if (isManager()) {
                // Manager can see all activities
                $stmt = $pdo->prepare("
                    SELECT a.*, u.full_name
                    FROM activity_log a
                    JOIN users u ON a.admin_id = u.id
                    ORDER BY a.timestamp DESC
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute([$limit, $offset]);
            } else {
                // Admin can only see their own activities
                $stmt = $pdo->prepare("
                    SELECT a.*, u.full_name
                    FROM activity_log a
                    JOIN users u ON a.admin_id = u.id
                    WHERE a.admin_id = ?
                    ORDER BY a.timestamp DESC
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute([$user['id'], $limit, $offset]);
            }

            $activities = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'activities' => $activities
            ]);
            break;

        case 'get_metrics_range':
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');

            if (isManager()) {
                // Manager can see all metrics
                $stmt = $pdo->prepare("
                    SELECT m.*, u.full_name
                    FROM daily_metrics m
                    JOIN users u ON m.admin_id = u.id
                    WHERE m.date BETWEEN ? AND ?
                    ORDER BY m.date DESC
                ");
                $stmt->execute([$startDate, $endDate]);
            } else {
                // Admin can only see their own metrics
                $stmt = $pdo->prepare("
                    SELECT * FROM daily_metrics
                    WHERE admin_id = ? AND date BETWEEN ? AND ?
                    ORDER BY date DESC
                ");
                $stmt->execute([$user['id'], $startDate, $endDate]);
            }

            $metrics = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'metrics' => $metrics
            ]);
            break;

        case 'get_task_completion_rate':
            $date = $_GET['date'] ?? $today;

            if (isManager()) {
                // Get all admins' completion rates
                $stmt = $pdo->prepare("
                    SELECT
                        u.id,
                        u.full_name,
                        COUNT(DISTINCT dt.id) as total_tasks,
                        COUNT(DISTINCT tc.task_id) as completed_tasks,
                        ROUND(COUNT(DISTINCT tc.task_id) / COUNT(DISTINCT dt.id) * 100, 2) as completion_rate
                    FROM users u
                    CROSS JOIN daily_tasks dt
                    LEFT JOIN task_completions tc ON tc.admin_id = u.id AND tc.date = ? AND tc.task_id = dt.id
                    WHERE u.role = 'admin'
                    GROUP BY u.id, u.full_name
                ");
                $stmt->execute([$date]);
            } else {
                // Get own completion rate
                $stmt = $pdo->prepare("
                    SELECT
                        COUNT(DISTINCT dt.id) as total_tasks,
                        COUNT(DISTINCT tc.task_id) as completed_tasks,
                        ROUND(COUNT(DISTINCT tc.task_id) / COUNT(DISTINCT dt.id) * 100, 2) as completion_rate
                    FROM daily_tasks dt
                    LEFT JOIN task_completions tc ON tc.task_id = dt.id AND tc.admin_id = ? AND tc.date = ?
                ");
                $stmt->execute([$user['id'], $date]);
            }

            $result = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;

        case 'save_task_details':
            $input = json_decode(file_get_contents('php://input'), true);

            $taskId = $input['task_id'] ?? 0;
            $timeSpent = (int)($input['time_spent_minutes'] ?? 0);
            $details = $input['details'] ?? [];

            if (!$taskId || !$timeSpent) {
                throw new Exception('اطلاعات ناقص است');
            }

            // Get or create today's report (draft status)
            $stmt = $pdo->prepare("
                SELECT id FROM daily_reports
                WHERE admin_id = ? AND date = ?
            ");
            $stmt->execute([$user['id'], $today]);
            $report = $stmt->fetch();

            if (!$report) {
                // Create new report
                $stmt = $pdo->prepare("
                    INSERT INTO daily_reports (admin_id, date, status)
                    VALUES (?, ?, 'draft')
                ");
                $stmt->execute([$user['id'], $today]);
                $reportId = $pdo->lastInsertId();
            } else {
                $reportId = $report['id'];

                // Check if report is submitted or approved - require password
                $stmt = $pdo->prepare("SELECT status FROM daily_reports WHERE id = ?");
                $stmt->execute([$reportId]);
                $reportStatus = $stmt->fetch()['status'];

                if ($reportStatus != 'draft') {
                    throw new Exception('گزارش ثبت شده است. برای ویرایش به صفحه گزارش بروید.');
                }
            }

            // Calculate quality score (basic algorithm)
            $qualityScore = 0;
            if ($details['count'] ?? null) {
                $qualityScore = min(100, ($details['count'] * 20));
            } else {
                $qualityScore = !empty($details['notes']) ? 80 : 50;
            }

            // Save or update task details
            $stmt = $pdo->prepare("
                INSERT INTO task_details (report_id, task_id, admin_id, completed, time_spent_minutes, quality_score, details_json, general_notes)
                VALUES (?, ?, ?, 1, ?, ?, ?, '')
                ON DUPLICATE KEY UPDATE
                    completed = 1,
                    time_spent_minutes = VALUES(time_spent_minutes),
                    quality_score = VALUES(quality_score),
                    details_json = VALUES(details_json),
                    created_at = NOW()
            ");
            $stmt->execute([
                $reportId,
                $taskId,
                $user['id'],
                $timeSpent,
                $qualityScore,
                json_encode($details, JSON_UNESCAPED_UNICODE)
            ]);

            // Update report total score
            updateReportScore($pdo, $reportId);

            // Log activity
            logActivity($pdo, $user['id'], 'task_details_saved', 'ذخیره جزئیات وظیفه');

            echo json_encode([
                'success' => true,
                'message' => 'جزئیات با موفقیت ذخیره شد',
                'quality_score' => $qualityScore
            ]);
            break;

        case 'get_task_details':
            $taskId = $_GET['task_id'] ?? 0;

            if (!$taskId) {
                throw new Exception('شناسه وظیفه نامعتبر است');
            }

            // Get today's report
            $stmt = $pdo->prepare("
                SELECT id FROM daily_reports
                WHERE admin_id = ? AND date = ?
            ");
            $stmt->execute([$user['id'], $today]);
            $report = $stmt->fetch();

            if (!$report) {
                echo json_encode(['success' => true, 'data' => null]);
                break;
            }

            // Get task details
            $stmt = $pdo->prepare("
                SELECT * FROM task_details
                WHERE report_id = ? AND task_id = ? AND admin_id = ?
            ");
            $stmt->execute([$report['id'], $taskId, $user['id']]);
            $taskDetails = $stmt->fetch();

            if ($taskDetails && $taskDetails['details_json']) {
                $taskDetails['details'] = json_decode($taskDetails['details_json'], true);
            }

            echo json_encode([
                'success' => true,
                'data' => $taskDetails
            ]);
            break;

        case 'submit_report':
            $input = json_decode(file_get_contents('php://input'), true);
            $reportId = $input['report_id'] ?? 0;

            if (!$reportId) {
                throw new Exception('شناسه گزارش نامعتبر است');
            }

            // Verify report belongs to user
            $stmt = $pdo->prepare("SELECT * FROM daily_reports WHERE id = ? AND admin_id = ?");
            $stmt->execute([$reportId, $user['id']]);
            $report = $stmt->fetch();

            if (!$report) {
                throw new Exception('گزارش یافت نشد');
            }

            if ($report['status'] != 'draft') {
                throw new Exception('فقط گزارش‌های پیش‌نویس قابل ثبت هستند');
            }

            // Check if at least some tasks are completed
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM task_details WHERE report_id = ?");
            $stmt->execute([$reportId]);
            $taskCount = $stmt->fetch()['count'];

            if ($taskCount == 0) {
                throw new Exception('لطفاً حداقل یک وظیفه را با جزئیات کامل کنید');
            }

            // Update report status
            $stmt = $pdo->prepare("
                UPDATE daily_reports
                SET status = 'submitted', submitted_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$reportId]);

            // Log activity
            logActivity($pdo, $user['id'], 'report_submitted', 'ثبت گزارش روزانه');

            // Log in report_edit_logs
            $stmt = $pdo->prepare("
                INSERT INTO report_edit_logs (report_id, edited_by, edit_type, ip_address, user_agent)
                VALUES (?, ?, 'submit', ?, ?)
            ");
            $stmt->execute([
                $reportId,
                $user['id'],
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'گزارش با موفقیت ثبت شد'
            ]);
            break;

        case 'unlock_report_edit':
            $input = json_decode(file_get_contents('php://input'), true);
            $reportId = $input['report_id'] ?? 0;
            $password = $input['password'] ?? '';

            if (!$reportId || !$password) {
                throw new Exception('اطلاعات ناقص است');
            }

            // Verify password
            $correctPassword = '14512';
            if ($password !== $correctPassword) {
                throw new Exception('رمز عبور اشتباه است');
            }

            // Verify report belongs to user
            $stmt = $pdo->prepare("SELECT * FROM daily_reports WHERE id = ? AND admin_id = ?");
            $stmt->execute([$reportId, $user['id']]);
            $report = $stmt->fetch();

            if (!$report) {
                throw new Exception('گزارش یافت نشد');
            }

            if ($report['status'] != 'submitted' && $report['status'] != 'approved') {
                throw new Exception('فقط گزارش‌های ثبت شده قابل باز کردن هستند');
            }

            // Change status back to draft
            $stmt = $pdo->prepare("
                UPDATE daily_reports
                SET status = 'draft', submitted_at = NULL
                WHERE id = ?
            ");
            $stmt->execute([$reportId]);

            // Log activity
            logActivity($pdo, $user['id'], 'report_unlocked', 'باز کردن گزارش با رمز عبور');

            // Log in report_edit_logs with password flag
            $stmt = $pdo->prepare("
                INSERT INTO report_edit_logs (report_id, edited_by, edit_type, password_used, ip_address, user_agent)
                VALUES (?, ?, 'update', 1, ?, ?)
            ");
            $stmt->execute([
                $reportId,
                $user['id'],
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'گزارش باز شد و آماده ویرایش است'
            ]);
            break;

        default:
            throw new Exception('عملیات نامعتبر است');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Helper function to calculate and update report score
function updateReportScore($pdo, $reportId) {
    // Get all task details for this report
    $stmt = $pdo->prepare("
        SELECT
            td.quality_score,
            td.completed,
            dt.priority
        FROM task_details td
        JOIN daily_tasks dt ON td.task_id = dt.id
        WHERE td.report_id = ?
    ");
    $stmt->execute([$reportId]);
    $tasks = $stmt->fetchAll();

    if (empty($tasks)) {
        return;
    }

    // Calculate weighted score
    $totalWeightedScore = 0;
    $totalWeight = 0;

    $priorityWeights = [
        'critical' => 1.5,
        'high' => 1.2,
        'medium' => 1.0,
        'low' => 0.8
    ];

    foreach ($tasks as $task) {
        $weight = $priorityWeights[$task['priority']] ?? 1.0;
        $totalWeightedScore += $task['quality_score'] * $weight;
        $totalWeight += $weight;
    }

    $averageScore = $totalWeight > 0 ? round($totalWeightedScore / $totalWeight) : 0;

    // Get total tasks count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM daily_tasks");
    $totalTasks = $stmt->fetch()['total'];

    $completedTasks = count($tasks);
    $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;

    // Update report
    $stmt = $pdo->prepare("
        UPDATE daily_reports
        SET total_score = ?, completion_rate = ?
        WHERE id = ?
    ");
    $stmt->execute([$averageScore, $completionRate, $reportId]);
}
