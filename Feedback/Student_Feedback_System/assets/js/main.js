// =============================================
// Student Feedback Management System - JS
// =============================================

// Dark/Light Mode Toggle
const themeToggle = document.getElementById('themeToggle');
const html = document.documentElement;
const savedTheme = localStorage.getItem('theme') || 'light';
html.setAttribute('data-theme', savedTheme);
if (themeToggle) {
    themeToggle.textContent = savedTheme === 'dark' ? '☀️ Light' : '🌙 Dark';
    themeToggle.addEventListener('click', () => {
        const current = html.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        themeToggle.textContent = next === 'dark' ? '☀️ Light' : '🌙 Dark';
    });
}

// Anonymous Feedback Toggle
const anonCheckbox = document.getElementById('is_anonymous');
const personalFields = document.getElementById('personalFields');
if (anonCheckbox && personalFields) {
    anonCheckbox.addEventListener('change', function () {
        personalFields.style.display = this.checked ? 'none' : 'block';
        personalFields.querySelectorAll('input').forEach(inp => {
            inp.required = !this.checked;
        });
    });
}

// Client-Side Form Validation
const feedbackForm = document.getElementById('feedbackForm');
if (feedbackForm) {
    feedbackForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!validateForm()) return;
        this.submit();
    });
}

function validateForm() {
    let valid = true;
    clearErrors();

    const isAnon = document.getElementById('is_anonymous')?.checked;

    if (!isAnon) {
        const studentName = document.getElementById('student_name');
        if (studentName && !studentName.value.trim()) {
            showError('student_name', 'Student name is required.');
            valid = false;
        }

        const regNo = document.getElementById('register_number');
        if (regNo && !regNo.value.trim()) {
            showError('register_number', 'Register number is required.');
            valid = false;
        } else if (regNo && !/^[0-9A-Za-z]{4,15}$/.test(regNo.value.trim())) {
            showError('register_number', 'Enter a valid register number (4-15 alphanumeric characters).');
            valid = false;
        }

        const email = document.getElementById('email');
        if (email && email.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
            showError('email', 'Enter a valid email address.');
            valid = false;
        }
    }

    // Required fields
    ['department', 'year', 'section', 'faculty_name', 'subject_name'].forEach(field => {
        const el = document.getElementById(field);
        if (el && !el.value.trim()) {
            showError(field, 'This field is required.');
            valid = false;
        }
    });

    // Star ratings
    const ratings = ['teaching_quality', 'subject_knowledge', 'communication_skills',
        'doubt_clarification', 'classroom_interaction', 'punctuality'];
    ratings.forEach(r => {
        const checked = document.querySelector(`input[name="${r}"]:checked`);
        if (!checked) {
            showError(r + '_error', 'Please select a rating.');
            valid = false;
        }
    });

    return valid;
}

function showError(fieldId, message) {
    const el = document.getElementById(fieldId) || document.getElementById(fieldId + '_error');
    if (el) {
        el.classList.add('is-invalid');
        let err = el.nextElementSibling;
        if (!err || !err.classList.contains('invalid-feedback')) {
            err = document.createElement('div');
            err.className = 'invalid-feedback';
            el.parentNode.insertBefore(err, el.nextSibling);
        }
        err.textContent = message;
        err.style.display = 'block';
    }
    // For rating groups
    const errDiv = document.getElementById(fieldId);
    if (errDiv && errDiv.classList.contains('rating-error')) {
        errDiv.textContent = message;
        errDiv.style.color = 'red';
        errDiv.style.fontSize = '0.8rem';
    }
}

function clearErrors() {
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(el => { el.textContent = ''; el.style.display = 'none'; });
    document.querySelectorAll('.rating-error').forEach(el => { el.textContent = ''; });
}

// Admin Sidebar Toggle (mobile)
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.querySelector('.admin-sidebar');
if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
}

// Auto-dismiss alerts
setTimeout(() => {
    document.querySelectorAll('.alert-auto-dismiss').forEach(el => {
        el.style.opacity = '0';
        el.style.transition = 'opacity 0.5s';
        setTimeout(() => el.remove(), 500);
    });
}, 4000);

// Confirm Delete
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this feedback? This action cannot be undone.')) {
        window.location.href = `feedbacks.php?delete=${id}`;
    }
}

// Counter Animation for Stat Cards
function animateCounters() {
    document.querySelectorAll('.stat-number[data-target]').forEach(el => {
        const target = parseInt(el.getAttribute('data-target'));
        const duration = 1500;
        const step = target / (duration / 16);
        let current = 0;
        const timer = setInterval(() => {
            current += step;
            if (current >= target) { current = target; clearInterval(timer); }
            el.textContent = el.classList.contains('decimal') ? current.toFixed(1) : Math.floor(current);
        }, 16);
    });
}
document.addEventListener('DOMContentLoaded', animateCounters);
