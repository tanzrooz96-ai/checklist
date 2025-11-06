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
