// Smooth animations on page load
document.addEventListener('DOMContentLoaded', () => {
    // Add smooth fade-in to cards
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.card, .stat-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
        observer.observe(el);
    });

    // Handle task checkbox changes
    const taskCheckboxes = document.querySelectorAll('.task-checkbox');
    taskCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', async (e) => {
            const taskId = e.target.dataset.taskId;
            const isCompleted = e.target.checked;
            const checklist = e.target.closest('.checklist-item');

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle_task&task_id=${taskId}&completed=${isCompleted ? 1 : 0}`
                });

                const data = await response.json();

                if (data.success) {
                    // Add completion animation
                    if (isCompleted) {
                        checklist.classList.add('completed');
                        showNotification('وظیفه با موفقیت تکمیل شد! 🎉', 'success');

                        // Confetti effect
                        createConfetti(e.target);
                    } else {
                        checklist.classList.remove('completed');
                    }

                    // Update progress
                    if (data.progress) {
                        updateProgress(data.progress);
                    }

                    // Check for milestone achievement
                    if (data.milestone_achieved) {
                        showMilestoneAchievement(data.milestone_message);
                    }
                } else {
                    e.target.checked = !isCompleted;
                    showNotification(data.message || 'خطا در ذخیره‌سازی', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                e.target.checked = !isCompleted;
                showNotification('خطا در ارتباط با سرور', 'error');
            }
        });
    });

    // Handle metrics form submission
    const metricsForm = document.getElementById('metricsForm');
    if (metricsForm) {
        metricsForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(metricsForm);
            formData.append('action', 'save_metrics');

            const submitBtn = metricsForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>در حال ذخیره...</span>';

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('آمار با موفقیت ذخیره شد! 📊', 'success');

                    // Reload page to update stats
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message || 'خطا در ذخیره‌سازی', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('خطا در ارتباط با سرور', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }
});

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.left = '50%';
    notification.style.transform = 'translateX(-50%)';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.style.maxWidth = '90%';
    notification.style.boxShadow = 'var(--shadow-lg)';

    const icon = type === 'success'
        ? '<svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>'
        : '<svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';

    notification.innerHTML = icon + message;
    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(-50%) translateY(-20px)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Update progress bars
function updateProgress(progress) {
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        if (progress.percentage !== undefined) {
            bar.style.width = progress.percentage + '%';
        }
    });

    // Update stats
    if (progress.completed !== undefined && progress.total !== undefined) {
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            if (card.textContent.includes('وظایف تکمیل شده')) {
                const valueEl = card.querySelector('.stat-value');
                if (valueEl) {
                    valueEl.textContent = `${convertToPersianNumber(progress.completed)}/${convertToPersianNumber(progress.total)}`;
                }
            }
        });
    }
}

// Confetti effect
function createConfetti(element) {
    const colors = ['#C41E3A', '#F39C12', '#27AE60', '#3498DB'];
    const rect = element.getBoundingClientRect();

    for (let i = 0; i < 15; i++) {
        const confetti = document.createElement('div');
        confetti.style.position = 'fixed';
        confetti.style.left = rect.left + rect.width / 2 + 'px';
        confetti.style.top = rect.top + rect.height / 2 + 'px';
        confetti.style.width = '8px';
        confetti.style.height = '8px';
        confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.borderRadius = '50%';
        confetti.style.pointerEvents = 'none';
        confetti.style.zIndex = '9999';
        confetti.style.transition = 'all 1s ease-out';

        document.body.appendChild(confetti);

        const angle = (Math.random() * 360) * Math.PI / 180;
        const velocity = 50 + Math.random() * 100;
        const tx = Math.cos(angle) * velocity;
        const ty = Math.sin(angle) * velocity - 50;

        setTimeout(() => {
            confetti.style.transform = `translate(${tx}px, ${ty}px) rotate(${Math.random() * 360}deg)`;
            confetti.style.opacity = '0';
        }, 10);

        setTimeout(() => confetti.remove(), 1000);
    }
}

// Show milestone achievement
function showMilestoneAchievement(message) {
    const modal = document.createElement('div');
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.background = 'rgba(0, 0, 0, 0.8)';
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.zIndex = '99999';
    modal.style.animation = 'fadeIn 0.3s ease-out';

    modal.innerHTML = `
        <div style="background: white; padding: 40px; border-radius: 20px; text-align: center; max-width: 400px; animation: fadeInUp 0.5s ease-out;">
            <div style="font-size: 80px; margin-bottom: 20px; animation: bounce 1s ease-in-out;">🏆</div>
            <h2 style="font-size: 24px; color: #C41E3A; margin-bottom: 10px; font-weight: 700;">تبریک!</h2>
            <p style="font-size: 16px; color: #2C2C2C; line-height: 1.8;">${message}</p>
            <button onclick="this.parentElement.parentElement.remove()"
                    style="margin-top: 30px; padding: 12px 30px; background: linear-gradient(135deg, #C41E3A, #9B1630); color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer;">
                عالی! ادامه می‌دهم 🚀
            </button>
        </div>
    `;

    document.body.appendChild(modal);

    // Create more confetti
    for (let i = 0; i < 50; i++) {
        setTimeout(() => {
            createConfetti({ getBoundingClientRect: () => ({ left: window.innerWidth / 2, top: window.innerHeight / 2, width: 0, height: 0 }) });
        }, i * 30);
    }
}

// Convert English numbers to Persian
function convertToPersianNumber(num) {
    const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return String(num).split('').map(char => {
        const digit = parseInt(char);
        return isNaN(digit) ? char : persian[digit];
    }).join('');
}

// Auto-save draft every 30 seconds
let autoSaveTimer;
function enableAutoSave() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    // Save to localStorage
                    const formData = new FormData(form);
                    const data = {};
                    formData.forEach((value, key) => {
                        data[key] = value;
                    });
                    localStorage.setItem('form_draft_' + form.id, JSON.stringify(data));
                }, 2000);
            });
        });

        // Restore draft on page load
        const draft = localStorage.getItem('form_draft_' + form.id);
        if (draft) {
            try {
                const data = JSON.parse(draft);
                Object.keys(data).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input && !input.value) {
                        input.value = data[key];
                    }
                });
            } catch (e) {
                console.error('Error restoring draft:', e);
            }
        }
    });
}

enableAutoSave();

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add loading state to buttons
document.querySelectorAll('button[type="submit"]').forEach(button => {
    button.addEventListener('click', function() {
        if (this.form && this.form.checkValidity()) {
            this.style.opacity = '0.7';
            this.style.pointerEvents = 'none';
        }
    });
});

// Prevent double submission
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        if (this.dataset.submitting === 'true') {
            e.preventDefault();
            return false;
        }
        this.dataset.submitting = 'true';

        // Reset after 3 seconds
        setTimeout(() => {
            this.dataset.submitting = 'false';
        }, 3000);
    });
});
