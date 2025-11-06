// =====================================================
// سیستم مدیریت اینستاگرام - نسخه 2.0
// JavaScript برای Wizard و تعاملات
// =====================================================

// متغیرهای سراسری
let currentStep = 0;
const totalSteps = 7;

// =====================================================
// WIZARD NAVIGATION
// =====================================================

function changeStep(direction) {
    const steps = document.querySelectorAll('.wizard-step');
    const progressSteps = document.querySelectorAll('.progress-step');

    // بروزرسانی مرحله فعلی
    steps[currentStep].classList.remove('active');
    progressSteps[currentStep].classList.remove('active');
    if (currentStep > 0) {
        progressSteps[currentStep].classList.add('completed');
    }

    // محاسبه مرحله جدید
    currentStep += direction;
    currentStep = Math.max(0, Math.min(currentStep, totalSteps - 1));

    // فعال کردن مرحله جدید
    steps[currentStep].classList.add('active');
    progressSteps[currentStep].classList.add('active');

    // بروزرسانی دکمه‌ها
    updateButtons();

    // اسکرول به بالا
    window.scrollTo({ top: 0, behavior: 'smooth' });

    // اگر مرحله آخر است، نمایش خلاصه
    if (currentStep === totalSteps - 1) {
        showSummary();
    }
}

function updateButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');

    // دکمه قبلی
    prevBtn.style.display = currentStep === 0 ? 'none' : 'inline-flex';

    // دکمه بعدی و ثبت
    if (currentStep === totalSteps - 1) {
        nextBtn.style.display = 'none';
        submitBtn.style.display = 'inline-flex';
    } else {
        nextBtn.style.display = 'inline-flex';
        submitBtn.style.display = 'none';
    }
}

// =====================================================
// COUNTER FUNCTIONS
// =====================================================

function incrementValue(fieldId, step = 1) {
    const input = document.getElementById(fieldId);
    const max = parseInt(input.getAttribute('max'));
    let value = parseInt(input.value) || 0;

    value = Math.min(value + step, max);
    input.value = value;

    // افکت vibration (اگر پشتیبانی می‌شود)
    if ('vibrate' in navigator) {
        navigator.vibrate(10);
    }
}

function decrementValue(fieldId, step = 1) {
    const input = document.getElementById(fieldId);
    const min = parseInt(input.getAttribute('min'));
    let value = parseInt(input.value) || 0;

    value = Math.max(value - step, min);
    input.value = value;

    // افکت vibration (اگر پشتیبانی می‌شود)
    if ('vibrate' in navigator) {
        navigator.vibrate(10);
    }
}

// =====================================================
// SUMMARY DISPLAY
// =====================================================

function showSummary() {
    const summaryContent = document.getElementById('summaryContent');

    // دریافت مقادیر فرم
    const formData = {
        posts: parseInt(document.getElementById('posts').value) || 0,
        stories: parseInt(document.getElementById('stories').value) || 0,
        reels: parseInt(document.getElementById('reels').value) || 0,
        highlights: parseInt(document.getElementById('highlights').value) || 0,
        directs: parseInt(document.getElementById('directs').value) || 0,
        pending_directs: parseInt(document.getElementById('pending_directs').value) || 0,
        comments: parseInt(document.getElementById('comments').value) || 0,
        likes: parseInt(document.getElementById('likes').value) || 0,
        prepared_content: parseInt(document.getElementById('prepared_content').value) || 0,
        content_ideas: parseInt(document.getElementById('content_ideas').value) || 0,
        content_calendar: document.getElementById('content_calendar').checked ? 'بله' : 'خیر',
        new_followers: parseInt(document.getElementById('new_followers').value) || 0,
        followed_pages: parseInt(document.getElementById('followed_pages').value) || 0,
        analytics_checked: document.getElementById('analytics_checked').checked ? 'بله' : 'خیر',
        competitors_checked: document.getElementById('competitors_checked').checked ? 'بله' : 'خیر',
    };

    // محاسبه پیش‌بینی امتیاز
    let estimatedScore = 0;
    estimatedScore += (formData.posts / 15) * 15;
    estimatedScore += (formData.stories / 15) * 15;
    estimatedScore += (formData.reels / 10) * 10;
    estimatedScore += (formData.highlights / 10) * 5;
    estimatedScore += (formData.directs / 1000) * 15;
    estimatedScore -= (formData.pending_directs > 0) ? 5 : 0;
    estimatedScore += (formData.comments / 500) * 10;
    estimatedScore += (formData.likes / 1000) * 5;
    estimatedScore += (formData.prepared_content / 10) * 10;
    estimatedScore += (formData.content_ideas / 10) * 5;
    estimatedScore += formData.content_calendar === 'بله' ? 5 : 0;
    estimatedScore += (formData.new_followers / 100) * 5;
    estimatedScore += (formData.followed_pages / 50) * 3;
    estimatedScore += formData.analytics_checked === 'بله' ? 3 : 0;
    estimatedScore += formData.competitors_checked === 'بله' ? 4 : 0;

    estimatedScore = Math.min(100, Math.max(0, estimatedScore));

    const scoreColor = getScoreColor(estimatedScore);
    const scoreLabel = getScoreLabel(estimatedScore);

    // ساخت HTML خلاصه
    const html = `
        <div style="background: white; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="display: inline-block; padding: 12px 32px; background: ${scoreColor}20; border-radius: 30px;">
                    <span style="font-size: 14px; color: ${scoreColor}; font-weight: 600;">پیش‌بینی امتیاز</span>
                    <div style="font-size: 42px; font-weight: 700; color: ${scoreColor}; margin-top: 8px;">
                        ${toPersianNumber(Math.round(estimatedScore))}
                    </div>
                    <span style="font-size: 14px; color: ${scoreColor};">${scoreLabel}</span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                <div class="summary-item">
                    <span class="summary-label">پست‌ها:</span>
                    <span class="summary-value">${toPersianNumber(formData.posts)}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">استوری‌ها:</span>
                    <span class="summary-value">${toPersianNumber(formData.stories)}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">ریلز:</span>
                    <span class="summary-value">${toPersianNumber(formData.reels)}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">هایلایت:</span>
                    <span class="summary-value">${toPersianNumber(formData.highlights)}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">پاسخ دایرکت:</span>
                    <span class="summary-value">${toPersianNumber(formData.directs)}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">دایرکت معوق:</span>
                    <span class="summary-value">${toPersianNumber(formData.pending_directs)}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">پاسخ کامنت:</span>
                    <span class="summary-value">${toPersianNumber(formData.comments)}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">لایک‌ها:</span>
                    <span class="summary-value">${toPersianNumber(formData.likes)}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">محتوای آماده:</span>
                    <span class="summary-value">${toPersianNumber(formData.prepared_content)}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">ایده‌های جدید:</span>
                    <span class="summary-value">${toPersianNumber(formData.content_ideas)}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">تقویم محتوا:</span>
                    <span class="summary-value">${formData.content_calendar}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">فالوورهای جدید:</span>
                    <span class="summary-value">${toPersianNumber(formData.new_followers)}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">فالو صفحات:</span>
                    <span class="summary-value">${toPersianNumber(formData.followed_pages)}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">بررسی آمار:</span>
                    <span class="summary-value">${formData.analytics_checked}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">بررسی رقبا:</span>
                    <span class="summary-value">${formData.competitors_checked}</span>
                </div>
            </div>
        </div>
    `;

    summaryContent.innerHTML = html;
}

// =====================================================
// FORM SUBMISSION
// =====================================================

document.getElementById('wizardForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <svg style="animation: spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="23 4 23 10 17 10"></polyline>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
        </svg>
        در حال ثبت...
    `;

    // جمع‌آوری داده‌ها
    const formData = {
        posts: parseInt(document.getElementById('posts').value) || 0,
        stories: parseInt(document.getElementById('stories').value) || 0,
        reels: parseInt(document.getElementById('reels').value) || 0,
        highlights: parseInt(document.getElementById('highlights').value) || 0,
        directs: parseInt(document.getElementById('directs').value) || 0,
        pending_directs: parseInt(document.getElementById('pending_directs').value) || 0,
        comments: parseInt(document.getElementById('comments').value) || 0,
        likes: parseInt(document.getElementById('likes').value) || 0,
        prepared_content: parseInt(document.getElementById('prepared_content').value) || 0,
        content_ideas: parseInt(document.getElementById('content_ideas').value) || 0,
        content_calendar: document.getElementById('content_calendar').checked ? 1 : 0,
        new_followers: parseInt(document.getElementById('new_followers').value) || 0,
        followed_pages: parseInt(document.getElementById('followed_pages').value) || 0,
        analytics_checked: document.getElementById('analytics_checked').checked ? 1 : 0,
        competitors_checked: document.getElementById('competitors_checked').checked ? 1 : 0,
        notes: document.getElementById('notes').value || ''
    };

    try {
        const response = await fetch('api.php?action=submit_report', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            // افکت vibration موفقیت
            if ('vibrate' in navigator) {
                navigator.vibrate([100, 50, 100]);
            }

            // هدایت به صفحه گزارش
            window.location.href = 'report-view.php?id=' + data.report_id;
        } else {
            alert('خطا: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = `
                ثبت گزارش
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            `;
        }
    } catch (error) {
        console.error('Error:', error);
        alert('خطا در ثبت گزارش. لطفا دوباره تلاش کنید.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = `
            ثبت گزارش
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        `;
    }
});

// =====================================================
// UTILITY FUNCTIONS
// =====================================================

function toPersianNumber(num) {
    const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return num.toString().replace(/\d/g, x => persian[parseInt(x)]);
}

function getScoreColor(score) {
    if (score >= 90) return '#27AE60';
    if (score >= 75) return '#3498DB';
    if (score >= 60) return '#F39C12';
    return '#E74C3C';
}

function getScoreLabel(score) {
    if (score >= 90) return 'عالی';
    if (score >= 75) return 'خوب';
    if (score >= 60) return 'متوسط';
    return 'نیاز به بهبود';
}

// =====================================================
// ANIMATIONS
// =====================================================

// Add spin animation
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
