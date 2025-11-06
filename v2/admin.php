<?php
require_once 'db.php';

// بررسی لاگین و دسترسی ادمین
if (!isLoggedIn() || !isAdmin()) {
    header('Location: index.php');
    exit;
}

$user = getCurrentUser($pdo);
$persianDate = toPersianDate();
$today = date('Y-m-d');

// بررسی وجود گزارش امروز
$stmt = $pdo->prepare("SELECT * FROM daily_reports WHERE admin_id = ? AND report_date = ?");
$stmt->execute([$user['id'], $today]);
$existingReport = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>گزارش روزانه | سیستم مدیریت اینستاگرام</title>
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
            <?php if ($existingReport && $existingReport['status'] === 'completed'): ?>
                <!-- نمایش پیام گزارش تکمیل شده -->
                <div class="completion-card">
                    <div class="completion-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <h2>گزارش امروز ثبت شده است!</h2>
                    <p>شما قبلاً گزارش امروز خود را با موفقیت ثبت کرده‌اید.</p>
                    <div class="score-display">
                        <span>امتیاز شما:</span>
                        <strong style="color: <?php echo getScoreColor($existingReport['score']); ?>">
                            <?php echo toPersianNumber($existingReport['score']); ?>
                        </strong>
                    </div>
                    <a href="report-view.php?id=<?php echo $existingReport['id']; ?>" class="btn btn-primary">
                        مشاهده گزارش
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </a>
                </div>
            <?php else: ?>
                <!-- ویزارد گزارش‌گیری -->
                <div class="wizard-container">
                    <!-- Progress Bar -->
                    <div class="progress-bar">
                        <div class="progress-step active" data-step="0">
                            <div class="step-number">۱</div>
                            <span>شروع</span>
                        </div>
                        <div class="progress-step" data-step="1">
                            <div class="step-number">۲</div>
                            <span>پست و استوری</span>
                        </div>
                        <div class="progress-step" data-step="2">
                            <div class="step-number">۳</div>
                            <span>دایرکت</span>
                        </div>
                        <div class="progress-step" data-step="3">
                            <div class="step-number">۴</div>
                            <span>تعامل</span>
                        </div>
                        <div class="progress-step" data-step="4">
                            <div class="step-number">۵</div>
                            <span>محتوا</span>
                        </div>
                        <div class="progress-step" data-step="5">
                            <div class="step-number">۶</div>
                            <span>فعالیت</span>
                        </div>
                        <div class="progress-step" data-step="6">
                            <div class="step-number">۷</div>
                            <span>خلاصه</span>
                        </div>
                    </div>

                    <!-- Wizard Steps -->
                    <form id="wizardForm" class="wizard-form">
                        <!-- Step 0: خوش‌آمدگویی -->
                        <div class="wizard-step active" data-step="0">
                            <div class="welcome-screen">
                                <div class="welcome-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 11l3 3L22 4"></path>
                                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                                    </svg>
                                </div>
                                <h1>گزارش روزانه</h1>
                                <h2><?php echo $persianDate['full_with_day']; ?></h2>
                                <p>سلام <?php echo htmlspecialchars($user['full_name']); ?> عزیز!</p>
                                <p>برای ثبت گزارش روزانه خود، روی دکمه "شروع" کلیک کنید.</p>
                                <div class="welcome-note">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="16" x2="12" y2="12"></line>
                                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                    </svg>
                                    <span>گزارش شامل 6 مرحله است که در هر مرحله اطلاعات مربوط به فعالیت‌های روزانه را وارد می‌کنید.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Step 1: پست‌ها و استوری‌ها -->
                        <div class="wizard-step" data-step="1">
                            <h2 class="step-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                پست‌ها و استوری‌ها
                            </h2>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>تعداد پست‌های منتشر شده</label>
                                    <div class="counter-input">
                                        <button type="button" onclick="decrementValue('posts')">-</button>
                                        <input type="number" id="posts" name="posts" value="0" min="0" max="15" readonly>
                                        <button type="button" onclick="incrementValue('posts')">+</button>
                                    </div>
                                    <small>حداکثر 15 پست</small>
                                </div>

                                <div class="form-group">
                                    <label>تعداد استوری‌های منتشر شده</label>
                                    <div class="counter-input">
                                        <button type="button" onclick="decrementValue('stories')">-</button>
                                        <input type="number" id="stories" name="stories" value="0" min="0" max="15" readonly>
                                        <button type="button" onclick="incrementValue('stories')">+</button>
                                    </div>
                                    <small>حداکثر 15 استوری</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>تعداد ریلز منتشر شده</label>
                                    <div class="counter-input">
                                        <button type="button" onclick="decrementValue('reels')">-</button>
                                        <input type="number" id="reels" name="reels" value="0" min="0" max="10" readonly>
                                        <button type="button" onclick="incrementValue('reels')">+</button>
                                    </div>
                                    <small>حداکثر 10 ریلز</small>
                                </div>

                                <div class="form-group">
                                    <label>استوری هایلایت شده</label>
                                    <div class="counter-input">
                                        <button type="button" onclick="decrementValue('highlights')">-</button>
                                        <input type="number" id="highlights" name="highlights" value="0" min="0" max="10" readonly>
                                        <button type="button" onclick="incrementValue('highlights')">+</button>
                                    </div>
                                    <small>حداکثر 10 هایلایت</small>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: دایرکت‌ها -->
                        <div class="wizard-step" data-step="2">
                            <h2 class="step-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                پاسخ به دایرکت‌ها
                            </h2>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>تعداد دایرکت‌های پاسخ داده شده</label>
                                    <div class="counter-input">
                                        <button type="button" onclick="decrementValue('directs', 10)">-</button>
                                        <input type="number" id="directs" name="directs" value="0" min="0" max="2000" step="10" readonly>
                                        <button type="button" onclick="incrementValue('directs', 10)">+</button>
                                    </div>
                                    <small>هدف روزانه: حداقل 1000 دایرکت</small>
                                </div>

                                <div class="form-group">
                                    <label>دایرکت‌های معوق (بدون پاسخ)</label>
                                    <div class="counter-input">
                                        <button type="button" onclick="decrementValue('pending_directs', 5)">-</button>
                                        <input type="number" id="pending_directs" name="pending_directs" value="0" min="0" max="500" step="5" readonly>
                                        <button type="button" onclick="incrementValue('pending_directs', 5)">+</button>
                                    </div>
                                    <small>هرچه کمتر، بهتر</small>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: تعامل -->
                        <div class="wizard-step" data-step="3">
                            <h2 class="step-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                                کامنت‌ها و لایک‌ها
                            </h2>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>تعداد کامنت‌های پاسخ داده شده</label>
                                    <div class="counter-input">
                                        <button type="button" onclick="decrementValue('comments', 5)">-</button>
                                        <input type="number" id="comments" name="comments" value="0" min="0" max="1000" step="5" readonly>
                                        <button type="button" onclick="incrementValue('comments', 5)">+</button>
                                    </div>
                                    <small>هدف روزانه: حداقل 500 کامنت</small>
                                </div>

                                <div class="form-group">
                                    <label>تعداد لایک‌های ثبت شده</label>
                                    <div class="counter-input">
                                        <button type="button" onclick="decrementValue('likes', 10)">-</button>
                                        <input type="number" id="likes" name="likes" value="0" min="0" max="2000" step="10" readonly>
                                        <button type="button" onclick="incrementValue('likes', 10)">+</button>
                                    </div>
                                    <small>تعامل با فالوورها</small>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: محتوا -->
                        <div class="wizard-step" data-step="4">
                            <h2 class="step-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                                تولید و برنامه‌ریزی محتوا
                            </h2>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>محتوای آماده شده برای فردا</label>
                                    <div class="counter-input">
                                        <button type="button" onclick="decrementValue('prepared_content')">-</button>
                                        <input type="number" id="prepared_content" name="prepared_content" value="0" min="0" max="20" readonly>
                                        <button type="button" onclick="incrementValue('prepared_content')">+</button>
                                    </div>
                                    <small>پست‌ها و استوری‌های آماده</small>
                                </div>

                                <div class="form-group">
                                    <label>ایده‌های جدید محتوا</label>
                                    <div class="counter-input">
                                        <button type="button" onclick="decrementValue('content_ideas')">-</button>
                                        <input type="number" id="content_ideas" name="content_ideas" value="0" min="0" max="20" readonly>
                                        <button type="button" onclick="incrementValue('content_ideas')">+</button>
                                    </div>
                                    <small>ایده‌های ثبت شده</small>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label>
                                    <input type="checkbox" id="content_calendar" name="content_calendar" value="1">
                                    <span>تقویم محتوایی هفته آینده تکمیل شد</span>
                                </label>
                            </div>
                        </div>

                        <!-- Step 5: فعالیت‌ها -->
                        <div class="wizard-step" data-step="5">
                            <h2 class="step-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                </svg>
                                سایر فعالیت‌ها
                            </h2>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>تعداد فالوورهای جدید</label>
                                    <div class="counter-input">
                                        <button type="button" onclick="decrementValue('new_followers', 5)">-</button>
                                        <input type="number" id="new_followers" name="new_followers" value="0" min="0" max="500" step="5" readonly>
                                        <button type="button" onclick="incrementValue('new_followers', 5)">+</button>
                                    </div>
                                    <small>رشد فالوور</small>
                                </div>

                                <div class="form-group">
                                    <label>فالو کردن صفحات مرتبط</label>
                                    <div class="counter-input">
                                        <button type="button" onclick="decrementValue('followed_pages', 5)">-</button>
                                        <input type="number" id="followed_pages" name="followed_pages" value="0" min="0" max="100" step="5" readonly>
                                        <button type="button" onclick="incrementValue('followed_pages', 5)">+</button>
                                    </div>
                                    <small>شبکه‌سازی</small>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label>
                                    <input type="checkbox" id="analytics_checked" name="analytics_checked" value="1">
                                    <span>آمار و تحلیل‌های اینستاگرام بررسی شد</span>
                                </label>
                            </div>

                            <div class="form-group full-width">
                                <label>
                                    <input type="checkbox" id="competitors_checked" name="competitors_checked" value="1">
                                    <span>صفحات رقبا بررسی شد</span>
                                </label>
                            </div>
                        </div>

                        <!-- Step 6: خلاصه -->
                        <div class="wizard-step" data-step="6">
                            <h2 class="step-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 11 12 14 22 4"></polyline>
                                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                                </svg>
                                خلاصه گزارش
                            </h2>

                            <div id="summaryContent" class="summary-content">
                                <!-- محتوا با JavaScript پر می‌شود -->
                            </div>

                            <div class="form-group full-width">
                                <label>یادداشت‌ها (اختیاری)</label>
                                <textarea id="notes" name="notes" rows="4" placeholder="توضیحات تکمیلی، چالش‌ها، یا نکات مهم..."></textarea>
                            </div>
                        </div>

                        <!-- دکمه‌های ناوبری -->
                        <div class="wizard-actions">
                            <button type="button" id="prevBtn" class="btn btn-secondary" onclick="changeStep(-1)" style="display: none;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="19" y1="12" x2="5" y2="12"></line>
                                    <polyline points="12 19 5 12 12 5"></polyline>
                                </svg>
                                قبلی
                            </button>
                            <button type="button" id="nextBtn" class="btn btn-primary" onclick="changeStep(1)">
                                بعدی
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </button>
                            <button type="submit" id="submitBtn" class="btn btn-success" style="display: none;">
                                ثبت گزارش
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html>
