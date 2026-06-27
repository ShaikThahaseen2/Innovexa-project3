// InnovExa LMS - Main JavaScript

// ---- Mobile Menu Toggle ----
document.addEventListener('DOMContentLoaded', function () {
    const menuBtn = document.getElementById('menuBtn');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebar = document.querySelector('.sidebar');

    if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            if (sidebarOverlay) sidebarOverlay.classList.toggle('visible');
        });
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('visible');
            });
        }
    }

    // ---- Auto-dismiss alerts ----
    const alerts = document.querySelectorAll('.alert[data-auto-dismiss]');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            setTimeout(() => alert.remove(), 400);
        }, 3500);
    });

    // ---- Role Toggle (register page) ----
    const roleBtns = document.querySelectorAll('.role-btn');
    const roleInput = document.getElementById('roleInput');
    roleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            roleBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if (roleInput) roleInput.value = btn.dataset.role;
        });
    });

    // ---- Counter animation ----
    const counters = document.querySelectorAll('[data-count]');
    const animateCounter = (el) => {
        const target = parseInt(el.dataset.count);
        const suffix = el.dataset.suffix || '';
        let current = 0;
        const step = target / 60;
        const timer = setInterval(() => {
            current += step;
            if (current >= target) { current = target; clearInterval(timer); }
            el.textContent = Math.floor(current).toLocaleString() + suffix;
        }, 16);
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => { if (entry.isIntersecting) { animateCounter(entry.target); observer.unobserve(entry.target); } });
    }, { threshold: 0.5 });

    counters.forEach(el => observer.observe(el));

    // ---- Active Nav Link ----
    const currentPath = window.location.pathname;
    document.querySelectorAll('.navbar-nav a, .sidebar-nav a').forEach(link => {
        if (link.getAttribute('href') && currentPath.endsWith(link.getAttribute('href').split('/').pop())) {
            link.classList.add('active');
        }
    });
});

// ---- Mark Lesson Complete (AJAX) ----
function toggleLessonComplete(lessonId, checkbox, progressBar, progressText) {
    const isChecked = checkbox.checked;
    const lessonNumEl = document.querySelector(`.lesson-num[data-lesson="${lessonId}"]`);

    fetch('/FS project3/api/mark-complete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `lesson_id=${lessonId}&completed=${isChecked ? 1 : 0}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (lessonNumEl) {
                lessonNumEl.classList.toggle('done', isChecked);
                lessonNumEl.textContent = isChecked ? '✓' : lessonNumEl.dataset.order;
            }
            if (progressBar && data.progress !== undefined) {
                progressBar.style.width = data.progress + '%';
                if (progressText) progressText.textContent = Math.round(data.progress) + '%';
            }
            // Animate
            const lessonItem = checkbox.closest('.lesson-item');
            if (lessonItem) {
                lessonItem.classList.toggle('completed', isChecked);
                lessonItem.style.transform = 'scale(1.02)';
                setTimeout(() => lessonItem.style.transform = '', 200);
            }
        }
    })
    .catch(() => {
        checkbox.checked = !isChecked;
    });
}

// ---- Show Toast Notification ----
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.style.cssText = 'position:fixed;bottom:2rem;right:2rem;z-index:9999;min-width:280px;animation:slideInRight 0.3s ease;box-shadow:0 8px 24px rgba(0,0,0,0.3)';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 400); }, 3000);
}

// Slide-in animation for toast
const style = document.createElement('style');
style.textContent = '@keyframes slideInRight { from { transform: translateX(120%); opacity:0; } to { transform: translateX(0); opacity:1; } }';
document.head.appendChild(style);
