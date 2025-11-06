// ===============================================
// 🎨 ULTRA MODERN PREMIUM JAVASCRIPT
// با Advanced Animations & Particle Effects
// ===============================================

// ===== PREMIUM TOAST NOTIFICATIONS =====
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
                top: 24px;
                left: 50%;
                transform: translateX(-50%);
                z-index: 99999;
                display: flex;
                flex-direction: column;
                gap: 12px;
                min-width: 320px;
                max-width: 500px;
                pointer-events: none;
            `;
            document.body.appendChild(container);
        }
        return container;
    }

    show(message, type = 'success', duration = 4000) {
        const toast = document.createElement('div');
        toast.style.cssText = `
            padding: 18px 24px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(20px) saturate(180%);
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            pointer-events: all;
            position: relative;
            overflow: hidden;
        `;

        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };

        const colors = {
            success: {
                bg: 'rgba(240, 255, 244, 0.95)',
                border: 'rgba(39, 174, 96, 0.4)',
                color: '#27AE60',
                shadow: 'rgba(39, 174, 96, 0.25)'
            },
            error: {
                bg: 'rgba(255, 240, 240, 0.95)',
                border: 'rgba(231, 76, 60, 0.4)',
                color: '#E74C3C',
                shadow: 'rgba(231, 76, 60, 0.25)'
            },
            warning: {
                bg: 'rgba(255, 251, 235, 0.95)',
                border: 'rgba(243, 156, 18, 0.4)',
                color: '#F39C12',
                shadow: 'rgba(243, 156, 18, 0.25)'
            },
            info: {
                bg: 'rgba(230, 244, 255, 0.95)',
                border: 'rgba(0, 102, 204, 0.4)',
                color: '#0066CC',
                shadow: 'rgba(0, 102, 204, 0.25)'
            }
        };

        const style = colors[type] || colors.info;
        toast.style.background = style.bg;
        toast.style.border = `2px solid ${style.border}`;
        toast.style.color = style.color;
        toast.style.boxShadow = `0 10px 40px ${style.shadow}`;

        // Progress bar
        const progress = document.createElement('div');
        progress.style.cssText = `
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: ${style.color};
            width: 100%;
            transform-origin: left;
            animation: shrink ${duration}ms linear forwards;
        `;

        const icon = document.createElement('div');
        icon.style.cssText = `
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: ${style.color};
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            font-weight: bold;
            box-shadow: 0 4px 15px ${style.shadow};
        `;
        icon.textContent = icons[type];

        const text = document.createElement('span');
        text.textContent = message;
        text.style.flex = '1';
        text.style.letterSpacing = '0.3px';

        toast.appendChild(icon);
        toast.appendChild(text);
        toast.appendChild(progress);
        this.container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Animate out
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-20px) scale(0.95)';
            setTimeout(() => toast.remove(), 400);
        }, duration);

        // Add shrink animation
        const style2 = document.createElement('style');
        style2.textContent = `
            @keyframes shrink {
                from { transform: scaleX(1); }
                to { transform: scaleX(0); }
            }
        `;
        if (!document.getElementById('toast-animations')) {
            style2.id = 'toast-animations';
            document.head.appendChild(style2);
        }
    }
}

const toast = new Toast();

// ===== PREMIUM CONFETTI EFFECT =====
function createPremiumConfetti(x, y, count = 30) {
    const colors = ['#C41E3A', '#E8445E', '#F39C12', '#27AE60', '#3498DB', '#9B59B6'];

    for (let i = 0; i < count; i++) {
        const confetti = document.createElement('div');
        const size = Math.random() * 10 + 5;

        confetti.style.cssText = `
            position: fixed;
            left: ${x}px;
            top: ${y}px;
            width: ${size}px;
            height: ${size}px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            border-radius: ${Math.random() > 0.5 ? '50%' : '2px'};
            pointer-events: none;
            z-index: 9999;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        `;

        document.body.appendChild(confetti);

        const angle = (Math.random() * 360) * Math.PI / 180;
        const velocity = Math.random() * 15 + 10;
        const vx = Math.cos(angle) * velocity;
        const vy = Math.sin(angle) * velocity - 10;
        const gravity = 0.5;
        const rotation = Math.random() * 720 - 360;

        let posX = x;
        let posY = y;
        let velX = vx;
        let velY = vy;
        let currentRotation = 0;
        let opacity = 1;

        function animate() {
            velY += gravity;
            posX += velX;
            posY += velY;
            currentRotation += rotation / 30;
            opacity -= 0.02;

            confetti.style.transform = `translate(${posX - x}px, ${posY - y}px) rotate(${currentRotation}deg)`;
            confetti.style.opacity = opacity;

            if (opacity > 0 && posY < window.innerHeight + 100) {
                requestAnimationFrame(animate);
            } else {
                confetti.remove();
            }
        }

        requestAnimationFrame(animate);
    }
}

// ===== FIREWORKS EFFECT =====
function createFireworks(x, y) {
    createPremiumConfetti(x, y, 50);

    // Sound effect (optional)
    try {
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZURMQV6vn77BfGgU1ktjx1IY0Bx9uwPDonlYTEFis5++wYhoFNJHY8dOFNAcfbsHw6J5WExBYrOfvsGIaBTSR2PHThTQHH27B8OieVhMQWKzn77BiGgU0kdjx04U0Bx9uwfDonlYTEFis5++wYhoFNJHY8dOFNAcfbsHw6J5WExBYrOfvsGIaBTSR2PHThTQHH27B8OieVhMQWKzn77BiGgU0kdjx04U0Bx9uwfDonlYTEFis5++wYhoFNJHY8dOFNAcfbsHw6J5WExBYrOfvsGIaBTSR2PHThTQHH27B8OieVhMQWKzn77BiGgU0kdjx04U0Bx9uwfDonlYTEFis5++wYhoFNJHY8dOFNAcfbsHw6J5WExBYrOfvsGIaBTSR2PHThTQHH27B8OieVhMQWKzn77BiGgU0kdjx04U0Bx9uwfDonlYTEFis5++wYhoFNJHY8dOFNAcfbsHw6J5WExBYrOfvsGIaBTSR2PHThTQHH27B8OieVhMQWKzn77BiGgU0kdjx04U0Bx9uwfDonlYTEFis5++wYhoFNJHY8dOFNAcfbsHw6J5WExBYrOfvsGIaBTSR2PHThTQHH27B8OieVhMQWKzn77BiGgU0kdjx04U0Bx9uwfDonlYTEFis5++wYhoFNJHY8dOFNAcfbsHw6J5WExBYrOfvsGIaBTSR2PHThTQHH27B8OieVhMQWKzn77BiGgU0kdjx04U0Bx9uwfDonlYTEFis5++wYhoFNJHY8dOFNAcfbsHw6J5WExBYrOfvsGIaBTSR2PHThTQHH27B8OieVhMQWKzn77BiGgU0kdjx04U0Bx9uwfDonlYTEFis5++wYhoFNJHY8dOFNAcfbsHw6J5WExBYrOfvsGIaBTSR2PHThTQHH27B8OieVhMQWKzn77BiGgU0kdjx04U0Bx9uwfDonlYTEFis5++wYhoFNJHY8dOFNAcfbsHw6J5WExBYrOfvsGIaBTSR2PHThTQHH27B8OieVhMQWKzn77BiGgU0kdjx04U0Bx9uwfDonlYTEFis5++wYhoFNJHY8dOFNAcfbsHw6J5WExBYrOfvsGIaBTSR2PHThTQHH27B8OieVhMQWKzn77BiGgU0kdjx04U0Bx9uwfDonlYTEFis5++wYhoFNJHY8dOFNAcfbsHw6J5WExBYrOfvsGIaBTSR2PHThTQHH27B8OieVhMQWKzn77BiGgU0kdjx04U0Bx9uwfDonlYTEFis5++wYhoFNJHY8dOFNAcfbsHw6J5WExBYrOfvsGIaBTSR2PHThTQHH27B8OieVhMQWKzn77BiGgU0kdjx04U0Bx9uwfDonlYTEFis5++wYhoFNJHY8dOFNAcfbsHw6J5WExBYrOfvsGIaBTSR2PHThTQHH27B8OieVhMQWKzn77BiGgU0kdjx04U0Bx9uwfDonlYTEFis5++wYhoFNJHY8dOFNAcfbsHw6J5WExBYrO==');
        audio.volume = 0.1;
        audio.play().catch(() => {});
    } catch (e) {}
}

// ===== SMOOTH PAGE ANIMATIONS =====
document.addEventListener('DOMContentLoaded', () => {
    // Animate elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0) scale(1)';
            }
        });
    }, observerOptions);

    // Observe all cards and stat-cards
    document.querySelectorAll('.card, .stat-card, .checklist-item').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px) scale(0.95)';
        el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
        observer.observe(el);
    });

    // Task checkbox handlers
    const taskCheckboxes = document.querySelectorAll('.task-checkbox');
    taskCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', async (e) => {
            const taskId = e.target.dataset.taskId;
            const isCompleted = e.target.checked;
            const item = e.target.closest('.checklist-item');

            // Add loading state
            item.style.pointerEvents = 'none';
            item.style.opacity = '0.7';

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
                    // Success animation
                    item.style.pointerEvents = '';
                    item.style.opacity = '1';

                    if (isCompleted) {
                        item.classList.add('completed');
                        toast.show('عالی! یک وظیفه دیگه تمام شد! 🎉', 'success');

                        // Fireworks at checkbox position
                        const rect = checkbox.getBoundingClientRect();
                        createFireworks(
                            rect.left + rect.width / 2,
                            rect.top + rect.height / 2
                        );
                    } else {
                        item.classList.remove('completed');
                        toast.show('وظیفه از حالت تکمیل خارج شد', 'info');
                    }

                    // Update progress
                    if (data.progress) {
                        updateProgress(data.progress);
                    }

                    // Check for milestone
                    if (data.milestone_achieved && data.milestone_message) {
                        setTimeout(() => {
                            showMilestoneModal(data.milestone_message);
                        }, 500);
                    }
                } else {
                    e.target.checked = !isCompleted;
                    item.style.pointerEvents = '';
                    item.style.opacity = '1';
                    toast.show(data.message || 'خطا در ذخیره‌سازی', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                e.target.checked = !isCompleted;
                item.style.pointerEvents = '';
                item.style.opacity = '1';
                toast.show('خطا در ارتباط با سرور', 'error');
            }
        });
    });

    // Metrics form submission
    const metricsForm = document.getElementById('metricsForm');
    if (metricsForm) {
        metricsForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(metricsForm);
            formData.append('action', 'save_metrics');

            const submitBtn = metricsForm.querySelector('button[type="submit"]');
            const originalHTML = submitBtn.innerHTML;

            // Loading state with spinner
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <div style="display: inline-block; width: 18px; height: 18px; border: 3px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <span>در حال ذخیره...</span>
            `;

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    toast.show('آمار با موفقیت ذخیره شد! 📊', 'success');

                    // Check for milestone
                    if (data.milestone_achieved && data.milestone_message) {
                        setTimeout(() => {
                            showMilestoneModal(data.milestone_message);
                        }, 500);
                    }

                    // Reload page after delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    toast.show(data.message || 'خطا در ذخیره‌سازی', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHTML;
                }
            } catch (error) {
                console.error('Error:', error);
                toast.show('خطا در ارتباط با سرور', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;
            }
        });
    }
});

// ===== PREMIUM MILESTONE MODAL =====
function showMilestoneModal(message) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999999;
        opacity: 0;
        transition: opacity 0.4s;
        padding: 20px;
    `;

    const content = document.createElement('div');
    content.style.cssText = `
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 248, 231, 0.95));
        backdrop-filter: blur(20px) saturate(180%);
        padding: 50px 40px;
        border-radius: 32px;
        text-align: center;
        max-width: 450px;
        width: 100%;
        box-shadow: 0 25px 60px rgba(196, 30, 58, 0.3);
        border: 2px solid rgba(196, 30, 58, 0.2);
        transform: scale(0.8) translateY(-50px);
        transition: transform 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    `;

    const trophy = document.createElement('div');
    trophy.style.cssText = `
        font-size: 100px;
        margin-bottom: 24px;
        animation: bounce 1s ease-in-out infinite;
        filter: drop-shadow(0 10px 30px rgba(196, 30, 58, 0.4));
    `;
    trophy.textContent = '🏆';

    const title = document.createElement('h2');
    title.style.cssText = `
        font-size: 32px;
        font-weight: 900;
        background: linear-gradient(135deg, #C41E3A, #9B1630);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 16px;
        letter-spacing: -0.5px;
    `;
    title.textContent = 'تبریک! 🎉';

    const text = document.createElement('p');
    text.style.cssText = `
        font-size: 17px;
        color: #2C2C2C;
        line-height: 1.8;
        margin-bottom: 36px;
        font-weight: 600;
    `;
    text.textContent = message;

    const button = document.createElement('button');
    button.style.cssText = `
        padding: 18px 40px;
        background: linear-gradient(135deg, #C41E3A, #9B1630);
        color: white;
        border: none;
        border-radius: 16px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 10px 30px rgba(196, 30, 58, 0.4);
        transition: all 0.3s;
        letter-spacing: 0.5px;
    `;
    button.textContent = 'عالی! ادامه می‌دهم 🚀';

    button.onmouseover = () => {
        button.style.transform = 'translateY(-3px) scale(1.05)';
        button.style.boxShadow = '0 15px 40px rgba(196, 30, 58, 0.5)';
    };

    button.onmouseout = () => {
        button.style.transform = '';
        button.style.boxShadow = '0 10px 30px rgba(196, 30, 58, 0.4)';
    };

    button.onclick = () => {
        modal.style.opacity = '0';
        content.style.transform = 'scale(0.8) translateY(-50px)';
        setTimeout(() => modal.remove(), 400);
    };

    content.appendChild(trophy);
    content.appendChild(title);
    content.appendChild(text);
    content.appendChild(button);
    modal.appendChild(content);
    document.body.appendChild(modal);

    // Animate in
    requestAnimationFrame(() => {
        modal.style.opacity = '1';
        content.style.transform = 'scale(1) translateY(0)';
    });

    // Massive fireworks
    setTimeout(() => {
        for (let i = 0; i < 10; i++) {
            setTimeout(() => {
                createFireworks(
                    Math.random() * window.innerWidth,
                    Math.random() * window.innerHeight / 2
                );
            }, i * 200);
        }
    }, 300);
}

// ===== UPDATE PROGRESS BARS =====
function updateProgress(progress) {
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        if (progress.percentage !== undefined) {
            bar.style.width = progress.percentage + '%';
        }
    });

    // Update stat cards
    if (progress.completed !== undefined && progress.total !== undefined) {
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            const cardText = card.textContent;
            if (cardText.includes('وظایف تکمیل شده')) {
                const valueEl = card.querySelector('.stat-value');
                if (valueEl) {
                    // Animate number change
                    animateValue(valueEl, progress.completed, progress.total);
                }
            }
        });
    }
}

// ===== ANIMATE NUMBER COUNTER =====
function animateValue(element, newValue, total) {
    const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    const currentText = element.textContent;
    const currentValue = parseInt(currentText.split('/')[0].replace(/[۰-۹]/g, (d) => {
        return persianNumbers.indexOf(d);
    }));

    if (currentValue === newValue) return;

    const duration = 600;
    const startTime = Date.now();
    const diff = newValue - currentValue;

    function update() {
        const now = Date.now();
        const progress = Math.min((now - startTime) / duration, 1);
        const value = Math.floor(currentValue + diff * progress);

        const persianValue = String(value).split('').map(d => persianNumbers[parseInt(d)]).join('');
        const persianTotal = String(total).split('').map(d => persianNumbers[parseInt(d)]).join('');
        element.textContent = `${persianValue}/${persianTotal}`;

        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }

    requestAnimationFrame(update);
}

// ===== AUTO-SAVE FORMS =====
let autoSaveTimer;
function enableAutoSave() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        if (!form.id) return;

        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    const formData = new FormData(form);
                    const data = {};
                    formData.forEach((value, key) => {
                        data[key] = value;
                    });
                    localStorage.setItem('form_draft_' + form.id, JSON.stringify(data));
                }, 2000);
            });
        });

        // Restore draft
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
            } catch (e) {}
        }
    });
}

enableAutoSave();

// ===== SMOOTH SCROLL =====
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

// ===== PREVENT DOUBLE SUBMISSION =====
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function (e) {
        if (this.dataset.submitting === 'true') {
            e.preventDefault();
            return false;
        }
        this.dataset.submitting = 'true';

        setTimeout(() => {
            this.dataset.submitting = 'false';
        }, 3000);
    });
});

// ===== TYPING EFFECT FOR MOTIVATIONAL MESSAGES =====
function typeWriter(element, text, speed = 50) {
    let i = 0;
    element.textContent = '';

    function type() {
        if (i < text.length) {
            element.textContent += text.charAt(i);
            i++;
            setTimeout(type, speed);
        }
    }

    type();
}

// Apply typing effect to motivational messages
const motivationalText = document.querySelector('.motivational-text');
if (motivationalText) {
    const originalText = motivationalText.textContent;
    setTimeout(() => {
        typeWriter(motivationalText, originalText, 30);
    }, 500);
}

// ===== HOVER EFFECTS FOR CARDS =====
document.querySelectorAll('.stat-card, .card, .checklist-item').forEach(card => {
    card.addEventListener('mouseenter', function(e) {
        const rect = this.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const ripple = document.createElement('div');
        ripple.style.cssText = `
            position: absolute;
            left: ${x}px;
            top: ${y}px;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(196, 30, 58, 0.1) 0%, transparent 70%);
            transform: translate(-50%, -50%);
            animation: ripple 0.8s ease-out;
            pointer-events: none;
        `;

        this.style.position = 'relative';
        this.style.overflow = 'hidden';
        this.appendChild(ripple);

        setTimeout(() => ripple.remove(), 800);
    });
});

// Add ripple animation
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            width: 400px;
            height: 400px;
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// ===== NUMBER FORMATTER =====
function convertToPersianNumber(num) {
    const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return String(num).split('').map(char => {
        const digit = parseInt(char);
        return isNaN(digit) ? char : persian[digit];
    }).join('');
}

// ===== KEYBOARD SHORTCUTS =====
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + S to save (prevent default)
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        const form = document.querySelector('form');
        if (form) {
            form.dispatchEvent(new Event('submit'));
        }
    }
});

// ===== PERFORMANCE: Lazy Load Images =====
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            }
        });
    });

    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// ===== VISUAL FEEDBACK ON BUTTON CLICKS =====
document.querySelectorAll('.btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
        const rect = this.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const ripple = document.createElement('span');
        ripple.style.cssText = `
            position: absolute;
            left: ${x}px;
            top: ${y}px;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transform: translate(-50%, -50%);
            animation: buttonRipple 0.6s ease-out;
            pointer-events: none;
        `;

        this.style.position = 'relative';
        this.style.overflow = 'hidden';
        this.appendChild(ripple);

        setTimeout(() => ripple.remove(), 600);
    });
});

// Button ripple animation
const btnStyle = document.createElement('style');
btnStyle.textContent = `
    @keyframes buttonRipple {
        to {
            width: 300px;
            height: 300px;
            opacity: 0;
        }
    }
`;
document.head.appendChild(btnStyle);

// ===== CONSOLE WELCOME MESSAGE =====
console.log('%c🎨 Instagram Admin Management System', 'font-size: 20px; font-weight: bold; background: linear-gradient(135deg, #C41E3A, #9B1630); color: white; padding: 10px 20px; border-radius: 10px;');
console.log('%c✨ Ultra Modern Premium Design', 'font-size: 14px; color: #C41E3A; font-weight: 600;');
console.log('%c💎 با Glassmorphism & Advanced Animations', 'font-size: 12px; color: #666;');

// ===== PREVENT CONSOLE SPAM =====
let lastLog = Date.now();
const originalConsoleLog = console.log;
console.log = function(...args) {
    if (Date.now() - lastLog < 100) return;
    lastLog = Date.now();
    originalConsoleLog.apply(console, args);
};

// ===============================================
// 📝 TASK DETAIL MODAL SYSTEM
// ===============================================

let currentTaskId = null;
let currentTaskType = null;
let currentTaskData = {};

// Open task detail modal
function openTaskDetailModal(taskId, taskType) {
    currentTaskId = taskId;
    currentTaskType = taskType;

    // Get task info
    const task = allTasksData.find(t => t.id == taskId);
    if (!task) {
        toast.show('وظیفه پیدا نشد', 'error');
        return;
    }

    // Update modal title
    document.getElementById('modalTaskTitle').textContent = task.title;

    // Generate form based on task type
    const formHTML = generateTaskForm(task);
    document.getElementById('taskDetailForm').innerHTML = formHTML;

    // Load existing data if any
    loadExistingTaskData(taskId);

    // Show modal
    const modal = document.getElementById('taskDetailModal');
    modal.classList.add('active');
    modal.style.display = 'flex';

    // Add ripple effect
    createRipple(event);
}

// Close modal
function closeTaskDetailModal() {
    const modal = document.getElementById('taskDetailModal');
    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
        currentTaskId = null;
        currentTaskType = null;
        currentTaskData = {};
    }, 300);
}

// Generate form HTML based on task type
function generateTaskForm(task) {
    let html = `<div class="task-detail-section">
        <h4>⏱️ زمان صرف شده</h4>
        <div class="form-group">
            <label class="form-label">چند دقیقه برای این وظیفه وقت گذاشتید؟</label>
            <input type="number" id="timeSpent" class="form-input"
                   placeholder="زمان به دقیقه" min="0" max="480"
                   value="${task.estimated_minutes}">
            <small class="form-hint">زمان پیشنهادی: ${task.estimated_minutes} دقیقه</small>
        </div>
    </div>`;

    // Generate specific form based on task type
    switch(task.task_type) {
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
        case 'direct_messages':
        case 'comments':
        case 'engagement':
        case 'research':
        case 'planning':
        case 'metrics':
        case 'insights':
        case 'reels':
        case 'campaign':
        default:
            html += generateGeneralForm();
            break;
    }

    return html;
}

// Generate Stories Form (0-5)
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

// Generate Posts Form (0-2)
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

// Generate Follow Form
function generateFollowForm() {
    return `
        <div class="task-detail-section">
            <h4>➕ فالو کردن</h4>
            <div class="form-group">
                <label class="form-label">چند نفر فالو کردید؟</label>
                <input type="number" id="followCount" class="form-input"
                       placeholder="تعداد فالو" min="0">
            </div>
            <label class="form-label">نوع اکانت‌ها:</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="followType" value="regular">
                    <span>افراد عادی</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="followType" value="business">
                    <span>کسب‌وکارها و فروشگاه‌ها</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="followType" value="brands">
                    <span>برندها و صفحات بزرگ</span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">توضیحات</label>
                <textarea id="followDescription" class="form-input" rows="4"
                          placeholder="توضیح دهید چه نوع اکانت‌هایی را فالو کردید و چرا؟"></textarea>
            </div>
        </div>
    `;
}

// Generate Unfollow Form
function generateUnfollowForm() {
    return `
        <div class="task-detail-section">
            <h4>➖ آنفالو کردن</h4>
            <div class="form-group">
                <label class="form-label">چند نفر آنفالو کردید؟</label>
                <input type="number" id="unfollowCount" class="form-input"
                       placeholder="تعداد آنفالو" min="0">
            </div>
            <label class="form-label">نوع اکانت‌ها:</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="unfollowType" value="inactive">
                    <span>اکانت‌های غیرفعال</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="unfollowType" value="irrelevant">
                    <span>اکانت‌های نامرتبط</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="unfollowType" value="low_engagement">
                    <span>اکانت‌های با تعامل پایین</span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">توضیحات</label>
                <textarea id="unfollowDescription" class="form-input" rows="4"
                          placeholder="توضیح دهید چه نوع اکانت‌هایی را آنفالو کردید و چرا؟"></textarea>
            </div>
        </div>
    `;
}

// Generate General Form for other tasks
function generateGeneralForm() {
    return `
        <div class="task-detail-section">
            <h4>📝 جزئیات وظیفه</h4>
            <div class="form-group">
                <label class="form-label">توضیحات کامل</label>
                <textarea id="generalNotes" class="form-input" rows="6"
                          placeholder="جزئیات کامل انجام این وظیفه را بنویسید..."></textarea>
            </div>
        </div>
    `;
}

// Update counter (for stories and posts)
function updateCounter(type, delta) {
    const countEl = document.getElementById(`${type}Count`);
    const containerEl = document.getElementById(`${type}Container`);
    let currentCount = parseInt(countEl.textContent) || 0;

    const max = type === 'stories' ? 5 : 2;
    const newCount = Math.max(0, Math.min(max, currentCount + delta));

    countEl.textContent = newCount;

    // Update form fields
    if (newCount === 0) {
        // Show reason field
        containerEl.innerHTML = `
            <div class="item-details">
                <h5>چرا ${type === 'stories' ? 'استوری' : 'پست'} نگذاشتید؟</h5>
                <textarea id="${type}Reason" class="form-input" rows="4"
                          placeholder="دلیل را توضیح دهید..."></textarea>
            </div>
        `;
    } else {
        // Show fields for each item
        let html = '';
        for (let i = 1; i <= newCount; i++) {
            html += `
                <div class="item-details">
                    <h5>${type === 'stories' ? 'استوری' : 'پست'} شماره ${convertToPersianNumber(i)}</h5>
                    <div class="form-group">
                        <label class="form-label">لینک</label>
                        <input type="url" id="${type}Link${i}" class="form-input"
                               placeholder="https://instagram.com/...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">توضیحات</label>
                        <textarea id="${type}Desc${i}" class="form-input" rows="3"
                                  placeholder="محتوا درباره چه بود؟ چه واکنشی دریافت کرد؟"></textarea>
                    </div>
                </div>
            `;
        }
        containerEl.innerHTML = html;
    }
}

// Convert to Persian numbers
function convertToPersianNumber(num) {
    const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return num.toString().split('').map(d => persianDigits[parseInt(d)] || d).join('');
}

// Save task details
async function saveTaskDetails() {
    const taskData = collectTaskData();

    if (!taskData) {
        toast.show('لطفاً تمام فیلدهای ضروری را پر کنید', 'error');
        return;
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
            toast.show('جزئیات با موفقیت ذخیره شد', 'success');
            closeTaskDetailModal();

            // Update checklist item to show it has details
            const checklistItem = document.querySelector(`[data-task-id="${currentTaskId}"]`);
            if (checklistItem) {
                checklistItem.classList.add('has-details');
            }
        } else {
            toast.show(result.message || 'خطا در ذخیره‌سازی', 'error');
        }
    } catch (error) {
        console.error('Error saving task details:', error);
        toast.show('خطا در ارتباط با سرور', 'error');
    }
}

// Collect data from form
function collectTaskData() {
    const timeSpent = document.getElementById('timeSpent')?.value;
    if (!timeSpent) return null;

    const data = {
        time_spent_minutes: parseInt(timeSpent),
        details: {}
    };

    switch(currentTaskType) {
        case 'stories':
        case 'posts':
            const count = parseInt(document.getElementById(`${currentTaskType}Count`)?.textContent || 0);
            data.details.count = count;

            if (count === 0) {
                data.details.reason = document.getElementById(`${currentTaskType}Reason`)?.value;
            } else {
                data.details.items = [];
                for (let i = 1; i <= count; i++) {
                    data.details.items.push({
                        link: document.getElementById(`${currentTaskType}Link${i}`)?.value,
                        description: document.getElementById(`${currentTaskType}Desc${i}`)?.value
                    });
                }
            }
            break;

        case 'follow':
            data.details.count = parseInt(document.getElementById('followCount')?.value || 0);
            data.details.type = document.querySelector('input[name="followType"]:checked')?.value;
            data.details.description = document.getElementById('followDescription')?.value;
            break;

        case 'unfollow':
            data.details.count = parseInt(document.getElementById('unfollowCount')?.value || 0);
            data.details.type = document.querySelector('input[name="unfollowType"]:checked')?.value;
            data.details.description = document.getElementById('unfollowDescription')?.value;
            break;

        default:
            data.details.notes = document.getElementById('generalNotes')?.value;
            break;
    }

    return data;
}

// Load existing task data
async function loadExistingTaskData(taskId) {
    try {
        const response = await fetch(`api.php?action=get_task_details&task_id=${taskId}`);
        const result = await response.json();

        if (result.success && result.data) {
            // Populate form with existing data
            if (result.data.time_spent_minutes) {
                document.getElementById('timeSpent').value = result.data.time_spent_minutes;
            }

            // Populate type-specific fields
            // This will be implemented based on the data structure
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
