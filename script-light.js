// ===============================================
// سیستم مدیریت اینستاگرام - نسخه نهایی ULTIMATE
// بهینه شده برای بهترین عملکرد
// ===============================================

// ===== VIBRATION UTILITY =====
function vibrate(pattern = [100]) {
    if ('vibrate' in navigator) {
        try {
            navigator.vibrate(pattern);
        } catch (e) {
            // Vibration not supported, silently ignore
        }
    }
}

// ===== TOAST NOTIFICATIONS (بهینه نهایی) =====
class Toast {
    constructor() {
        this.container = this.createContainer();
    }

    createContainer() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                z-index: 10000;
                display: flex;
                flex-direction: column;
                gap: 10px;
                min-width: 300px;
                max-width: 90%;
            `;
            document.body.appendChild(container);
        }
        return container;
    }

    show(message, type = 'success', duration = 3000) {
        // Vibrate based on type
        const vibrationPatterns = {
            success: [50],
            error: [100, 50, 100],
            warning: [75],
            info: [30]
        };
        vibrate(vibrationPatterns[type] || [50]);

        const toast = document.createElement('div');
        const colors = {
            success: { bg: '#27AE60', icon: '✓' },
            error: { bg: '#E74C3C', icon: '✕' },
            warning: { bg: '#F39C12', icon: '⚠' },
            info: { bg: '#3498DB', icon: 'ℹ' }
        };

        const style = colors[type] || colors.info;

        toast.style.cssText = `
            padding: 14px 18px;
            background: ${style.bg};
            color: white;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        `;

        toast.innerHTML = `<span style="font-size: 18px;">${style.icon}</span><span>${message}</span>`;
        this.container.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
}

const toast = new Toast();

// Add animations
if (!document.getElementById('toast-animations')) {
    const style = document.createElement('style');
    style.id = 'toast-animations';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateY(0); opacity: 1; }
            to { transform: translateY(-20px); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

// ===== TASK COMPLETION =====
document.addEventListener('DOMContentLoaded', function() {
    // Task checkboxes
    const checkboxes = document.querySelectorAll('.task-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', async function(e) {
            // Prevent bubbling
            e.stopPropagation();

            const taskId = this.dataset.taskId;
            const completed = this.checked;

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=toggle_task&task_id=${taskId}&completed=${completed ? 1 : 0}`
                });

                const result = await response.json();

                if (result.success) {
                    // Update UI
                    const item = this.closest('.checklist-item');
                    if (completed) {
                        item.classList.add('completed');
                        toast.show('وظیفه تکمیل شد', 'success');
                    } else {
                        item.classList.remove('completed');
                        toast.show('وظیفه به حالت ناتمام برگشت', 'info');
                    }

                    // Update progress if exists
                    if (result.progress) {
                        updateProgress(result.progress);
                    }
                } else {
                    this.checked = !completed;
                    toast.show(result.message || 'خطا در ثبت', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.checked = !completed;
                toast.show('خطا در ارتباط با سرور', 'error');
            }
        });
    });

    // Metrics form
    const metricsForm = document.getElementById('metricsForm');
    if (metricsForm) {
        metricsForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'save_metrics');

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                });

                const result = await response.json();

                if (result.success) {
                    toast.show('آمار با موفقیت ذخیره شد', 'success');
                    if (result.milestone_achieved) {
                        toast.show(result.milestone_message, 'success', 5000);
                    }
                } else {
                    toast.show(result.message || 'خطا در ذخیره‌سازی', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                toast.show('خطا در ارتباط با سرور', 'error');
            }
        });
    }
});

// Update progress bars
function updateProgress(progress) {
    const progressBars = document.querySelectorAll('.progress-bar');
    const progressStats = document.querySelectorAll('.progress-stats');

    progressBars.forEach(bar => {
        bar.style.width = progress.percentage + '%';
    });

    progressStats.forEach(stat => {
        stat.textContent = progress.percentage + '٪';
    });
}

// ===============================================
// TASK DETAIL MODAL SYSTEM (بهینه شده)
// ===============================================

let currentTaskId = null;
let currentTaskType = null;

// Open task detail modal - بهینه شده برای موبایل
function openTaskDetailModal(taskId, taskType) {
    // Prevent event bubbling issues
    event.preventDefault();
    event.stopPropagation();

    currentTaskId = taskId;
    currentTaskType = taskType || 'general';

    // Get task info from global data
    const task = typeof allTasksData !== 'undefined' ? allTasksData.find(t => t.id == taskId) : null;

    if (!task) {
        toast.show('وظیفه پیدا نشد', 'error');
        return;
    }

    // Update modal title
    document.getElementById('modalTaskTitle').textContent = task.title;

    // Generate form
    const formHTML = generateTaskForm(task);
    document.getElementById('taskDetailForm').innerHTML = formHTML;

    // Load existing data
    loadExistingTaskData(taskId);

    // Show modal
    const modal = document.getElementById('taskDetailModal');
    modal.classList.add('active');

    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

// Close modal
function closeTaskDetailModal() {
    const modal = document.getElementById('taskDetailModal');
    modal.classList.remove('active');

    // Restore body scroll
    document.body.style.overflow = '';

    currentTaskId = null;
    currentTaskType = null;
}

// Generate form HTML
function generateTaskForm(task) {
    let html = `<div class="task-detail-section">
        <h4>⏱️ زمان صرف شده</h4>
        <div class="form-group">
            <label class="form-label">چند دقیقه برای این وظیفه وقت گذاشتید؟</label>
            <input type="number" id="timeSpent" class="form-input"
                   placeholder="زمان به دقیقه" min="0" max="480"
                   value="${task.estimated_minutes || 30}">
        </div>
    </div>`;

    // Generate specific form
    switch(task.task_type || 'general') {
        case 'stories':
            html += generateStoriesForm();
            break;
        case 'posts':
            html += generatePostsForm();
            break;
        case 'follow':
            html += generateFollowForm();
            break;
        case 'unfollow':
            html += generateUnfollowForm();
            break;
        default:
            html += generateGeneralForm();
            break;
    }

    return html;
}

// Stories form
function generateStoriesForm() {
    return `
        <div class="task-detail-section">
            <h4>📱 استوری‌ها</h4>
            <label class="form-label">چند استوری گذاشتید؟ (0 تا 5)</label>
            <div class="counter-controls">
                <button type="button" class="counter-btn" onclick="updateCounter('stories', -1)">−</button>
                <div class="counter-display" id="storiesCount">0</div>
                <button type="button" class="counter-btn" onclick="updateCounter('stories', 1)">+</button>
            </div>
            <div id="storiesContainer"></div>
        </div>
    `;
}

// Posts form
function generatePostsForm() {
    return `
        <div class="task-detail-section">
            <h4>📸 پست‌ها</h4>
            <label class="form-label">چند پست گذاشتید؟ (0 تا 2)</label>
            <div class="counter-controls">
                <button type="button" class="counter-btn" onclick="updateCounter('posts', -1)">−</button>
                <div class="counter-display" id="postsCount">0</div>
                <button type="button" class="counter-btn" onclick="updateCounter('posts', 1)">+</button>
            </div>
            <div id="postsContainer"></div>
        </div>
    `;
}

// Follow form
function generateFollowForm() {
    return `
        <div class="task-detail-section">
            <h4>➕ فالو کردن</h4>
            <div class="form-group">
                <label class="form-label">چند نفر فالو کردید؟</label>
                <input type="number" id="followCount" class="form-input" placeholder="تعداد" min="0">
            </div>
            <label class="form-label">نوع اکانت‌ها:</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="followType" value="regular">
                    <span>افراد عادی</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="followType" value="business">
                    <span>کسب‌وکارها</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="followType" value="brands">
                    <span>برندها</span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">توضیحات</label>
                <textarea id="followDescription" class="form-input" rows="4"></textarea>
            </div>
        </div>
    `;
}

// Unfollow form
function generateUnfollowForm() {
    return `
        <div class="task-detail-section">
            <h4>➖ آنفالو کردن</h4>
            <div class="form-group">
                <label class="form-label">چند نفر آنفالو کردید؟</label>
                <input type="number" id="unfollowCount" class="form-input" placeholder="تعداد" min="0">
            </div>
            <label class="form-label">نوع اکانت‌ها:</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="unfollowType" value="inactive">
                    <span>غیرفعال</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="unfollowType" value="irrelevant">
                    <span>نامرتبط</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="unfollowType" value="low_engagement">
                    <span>تعامل پایین</span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">توضیحات</label>
                <textarea id="unfollowDescription" class="form-input" rows="4"></textarea>
            </div>
        </div>
    `;
}

// General form
function generateGeneralForm() {
    return `
        <div class="task-detail-section">
            <h4>📝 جزئیات</h4>
            <div class="form-group">
                <label class="form-label">توضیحات</label>
                <textarea id="generalNotes" class="form-input" rows="6"></textarea>
            </div>
        </div>
    `;
}

// Update counter
function updateCounter(type, delta) {
    const countEl = document.getElementById(`${type}Count`);
    const containerEl = document.getElementById(`${type}Container`);
    let currentCount = parseInt(countEl.textContent) || 0;

    const max = type === 'stories' ? 5 : 2;
    const newCount = Math.max(0, Math.min(max, currentCount + delta));

    countEl.textContent = newCount;

    // Update form
    if (newCount === 0) {
        containerEl.innerHTML = `
            <div class="item-details">
                <h5>چرا ${type === 'stories' ? 'استوری' : 'پست'} نگذاشتید؟</h5>
                <textarea id="${type}Reason" class="form-input" rows="3" placeholder="دلیل را توضیح دهید..."></textarea>
            </div>
        `;
    } else {
        let html = '';
        for (let i = 1; i <= newCount; i++) {
            html += `
                <div class="item-details">
                    <h5>${type === 'stories' ? 'استوری' : 'پست'} ${i}</h5>
                    <div class="form-group">
                        <label class="form-label">لینک</label>
                        <input type="url" id="${type}Link${i}" class="form-input" placeholder="https://instagram.com/...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">توضیحات</label>
                        <textarea id="${type}Desc${i}" class="form-input" rows="2"></textarea>
                    </div>
                </div>
            `;
        }
        containerEl.innerHTML = html;
    }
}

// Save task details
async function saveTaskDetails() {
    const timeSpent = document.getElementById('timeSpent')?.value;
    if (!timeSpent) {
        toast.show('لطفاً زمان را وارد کنید', 'error');
        return;
    }

    const taskData = {
        time_spent_minutes: parseInt(timeSpent),
        details: {}
    };

    // Collect data based on type
    switch(currentTaskType) {
        case 'stories':
        case 'posts':
            const count = parseInt(document.getElementById(`${currentTaskType}Count`)?.textContent || 0);
            taskData.details.count = count;

            if (count === 0) {
                taskData.details.reason = document.getElementById(`${currentTaskType}Reason`)?.value;
            } else {
                taskData.details.items = [];
                for (let i = 1; i <= count; i++) {
                    taskData.details.items.push({
                        link: document.getElementById(`${currentTaskType}Link${i}`)?.value,
                        description: document.getElementById(`${currentTaskType}Desc${i}`)?.value
                    });
                }
            }
            break;

        case 'follow':
            taskData.details.count = parseInt(document.getElementById('followCount')?.value || 0);
            taskData.details.type = document.querySelector('input[name="followType"]:checked')?.value;
            taskData.details.description = document.getElementById('followDescription')?.value;
            break;

        case 'unfollow':
            taskData.details.count = parseInt(document.getElementById('unfollowCount')?.value || 0);
            taskData.details.type = document.querySelector('input[name="unfollowType"]:checked')?.value;
            taskData.details.description = document.getElementById('unfollowDescription')?.value;
            break;

        default:
            taskData.details.notes = document.getElementById('generalNotes')?.value;
            break;
    }

    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'save_task_details',
                task_id: currentTaskId,
                task_type: currentTaskType,
                ...taskData
            })
        });

        const result = await response.json();

        if (result.success) {
            toast.show('جزئیات ذخیره شد', 'success');
            closeTaskDetailModal();
        } else {
            toast.show(result.message || 'خطا در ذخیره', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        toast.show('خطا در ارتباط با سرور', 'error');
    }
}

// Load existing data
async function loadExistingTaskData(taskId) {
    try {
        const response = await fetch(`api.php?action=get_task_details&task_id=${taskId}`);
        const result = await response.json();

        if (result.success && result.data) {
            // Populate form
            if (result.data.time_spent_minutes) {
                document.getElementById('timeSpent').value = result.data.time_spent_minutes;
            }
        }
    } catch (error) {
        console.error('Error loading task details:', error);
    }
}

// Close modal on outside click
document.addEventListener('click', function(e) {
    const modal = document.getElementById('taskDetailModal');
    if (modal && e.target === modal) {
        closeTaskDetailModal();
    }
});

// Prevent clicks inside modal from closing it
document.addEventListener('DOMContentLoaded', function() {
    const modalContent = document.querySelector('.modal-content');
    if (modalContent) {
        modalContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});

console.log('✨ Instagram Admin System - Light Version Loaded');
