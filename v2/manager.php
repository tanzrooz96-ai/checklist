<?php
require_once 'db.php';

// بررسی لاگین و دسترسی مدیر
if (!isLoggedIn() || !isManager()) {
    header('Location: index.php');
    exit;
}

$user = getCurrentUser($pdo);
$persianDate = toPersianDate();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد مدیریت | سیستم مدیریت اینستاگرام</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- هدر -->
    <header class="header">
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
                        <p><?php echo $persianDate['full_with_day']; ?></p>
                    </div>
                </div>
                <div class="header-left">
                    <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                    <a href="logout.php" class="btn btn-secondary btn-sm">خروج</a>
                </div>
            </div>
        </div>
    </header>

    <!-- محتوای اصلی -->
    <main class="main">
        <div class="container">
            <div class="page-header">
                <h1>داشبورد مدیریت</h1>
                <p>نمای کلی عملکرد و گزارش‌ها</p>
            </div>

            <!-- کارت‌های آمار -->
            <div class="stats-cards" id="statsCards">
                <div class="stat-card loading">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3498DB 0%, #2980B9 100%)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"></path>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="skeleton skeleton-text" style="width: 60px;"></div>
                        <div class="skeleton skeleton-text" style="width: 100px; margin-top: 8px;"></div>
                    </div>
                </div>

                <div class="stat-card loading">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #27AE60 0%, #229954 100%)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="skeleton skeleton-text" style="width: 60px;"></div>
                        <div class="skeleton skeleton-text" style="width: 100px; margin-top: 8px;"></div>
                    </div>
                </div>

                <div class="stat-card loading">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #F39C12 0%, #D68910 100%)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 20v-6M6 20V10M18 20V4"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="skeleton skeleton-text" style="width: 60px;"></div>
                        <div class="skeleton skeleton-text" style="width: 100px; margin-top: 8px;"></div>
                    </div>
                </div>

                <div class="stat-card loading">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #E74C3C 0%, #C0392B 100%)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="skeleton skeleton-text" style="width: 60px;"></div>
                        <div class="skeleton skeleton-text" style="width: 100px; margin-top: 8px;"></div>
                    </div>
                </div>
            </div>

            <!-- نمودارها -->
            <div class="charts-grid">
                <!-- نمودار روند عملکرد -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>روند عملکرد 30 روز اخیر</h3>
                        <p>تغییرات امتیاز روزانه</p>
                    </div>
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <!-- نمودار توزیع امتیاز -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>توزیع امتیاز بر اساس دسته</h3>
                        <p>میانگین امتیاز هر بخش</p>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- جدول گزارش‌ها -->
            <div class="table-card">
                <div class="table-header">
                    <h3>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        گزارش‌های ثبت شده
                    </h3>
                    <button onclick="loadReports()" class="btn btn-secondary btn-sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                        </svg>
                        به‌روزرسانی
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="data-table" id="reportsTable">
                        <thead>
                            <tr>
                                <th>تاریخ</th>
                                <th>ادمین</th>
                                <th>تعداد تسک‌ها</th>
                                <th>امتیاز</th>
                                <th>وضعیت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody id="reportsTableBody">
                            <tr>
                                <td colspan="6" class="text-center">در حال بارگذاری...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-footer" id="tablePagination" style="display: none;">
                    <button class="btn btn-secondary btn-sm" onclick="loadMore()">بارگذاری بیشتر</button>
                </div>
            </div>
        </div>
    </main>

    <script src="script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // بارگذاری داده‌ها و نمایش نمودارها
        let trendChart = null;
        let categoryChart = null;

        async function loadDashboardData() {
            try {
                // دریافت آمار
                const statsResponse = await fetch('api.php?action=get_stats');
                const statsData = await statsResponse.json();

                if (statsData.success) {
                    updateStatsCards(statsData.stats);
                    renderTrendChart(statsData.daily_scores);
                    renderCategoryChart(statsData.task_stats);
                }

                // بارگذاری گزارش‌ها
                loadReports();
            } catch (error) {
                console.error('Error loading dashboard:', error);
            }
        }

        function updateStatsCards(stats) {
            const cardsHTML = `
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3498DB 0%, #2980B9 100%)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"></path>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">${toPersianNumber(stats.total_reports)}</div>
                        <div class="stat-label">کل گزارش‌ها</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #27AE60 0%, #229954 100%)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">${toPersianNumber(Math.round(stats.avg_score))}</div>
                        <div class="stat-label">میانگین امتیاز</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #F39C12 0%, #D68910 100%)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 20v-6M6 20V10M18 20V4"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">${toPersianNumber(Math.round(stats.max_score))}</div>
                        <div class="stat-label">بالاترین امتیاز</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #E74C3C 0%, #C0392B 100%)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">${toPersianNumber(Math.round(stats.min_score))}</div>
                        <div class="stat-label">کمترین امتیاز</div>
                    </div>
                </div>
            `;
            document.getElementById('statsCards').innerHTML = cardsHTML;
        }

        function renderTrendChart(dailyScores) {
            const ctx = document.getElementById('trendChart').getContext('2d');

            if (trendChart) {
                trendChart.destroy();
            }

            const labels = dailyScores.map(d => {
                const date = new Date(d.report_date);
                return `${date.getDate()}/${date.getMonth() + 1}`;
            });
            const data = dailyScores.map(d => parseFloat(d.score));

            trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'امتیاز روزانه',
                        data: data,
                        borderColor: '#3498DB',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            rtl: true,
                            labels: {
                                font: { family: 'Vazirmatn', size: 12 }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
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
        }

        function renderCategoryChart(taskStats) {
            const ctx = document.getElementById('categoryChart').getContext('2d');

            if (categoryChart) {
                categoryChart.destroy();
            }

            const labels = taskStats.map(t => t.task_category);
            const data = taskStats.map(t => parseFloat(t.avg_score));

            const colors = [
                '#3498DB', '#27AE60', '#F39C12', '#E74C3C',
                '#9B59B6', '#1ABC9C', '#34495E', '#E67E22'
            ];

            categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            rtl: true,
                            labels: {
                                font: { family: 'Vazirmatn', size: 11 },
                                padding: 15
                            }
                        }
                    }
                }
            });
        }

        let currentOffset = 0;
        const limit = 20;

        async function loadReports(reset = true) {
            if (reset) {
                currentOffset = 0;
            }

            try {
                const response = await fetch(`api.php?action=get_reports&limit=${limit}&offset=${currentOffset}`);
                const data = await response.json();

                if (data.success) {
                    const tbody = document.getElementById('reportsTableBody');

                    if (reset) {
                        tbody.innerHTML = '';
                    }

                    if (data.reports.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center">گزارشی یافت نشد</td></tr>';
                        return;
                    }

                    data.reports.forEach(report => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${report.persian_date}</td>
                            <td>${report.admin_name}</td>
                            <td>${toPersianNumber(report.task_count)}</td>
                            <td>
                                <span class="score-badge" style="background: ${report.score_color}20; color: ${report.score_color}">
                                    ${toPersianNumber(Math.round(report.score))}
                                </span>
                            </td>
                            <td>
                                <span class="status-badge success">
                                    ${report.score_label}
                                </span>
                            </td>
                            <td>
                                <a href="report-view.php?id=${report.id}" class="btn btn-sm btn-primary">مشاهده</a>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });

                    currentOffset += data.reports.length;

                    if (data.reports.length === limit) {
                        document.getElementById('tablePagination').style.display = 'block';
                    }
                }
            } catch (error) {
                console.error('Error loading reports:', error);
            }
        }

        function loadMore() {
            loadReports(false);
        }

        // بارگذاری داده‌ها در شروع
        document.addEventListener('DOMContentLoaded', loadDashboardData);
    </script>
</body>
</html>
