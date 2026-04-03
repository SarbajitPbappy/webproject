/**
 * HostelEase — Main JavaScript
 * Vanilla JS for UI interactions.
 */

document.addEventListener('DOMContentLoaded', function () {

    // ─── Sidebar Toggle (Mobile) ────────────────
    const sidebarOpen = document.getElementById('sidebarOpen');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (sidebarOpen) {
        sidebarOpen.addEventListener('click', function () {
            sidebar.classList.add('show');
            overlay.classList.add('show');
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', closeSidebar);
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    }

    // ─── Auto-dismiss alerts after 5 seconds ────
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // ─── Confirm Delete Forms ────────────────────
    const deleteForms = document.querySelectorAll('form[data-confirm]');
    deleteForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // ─── Form Validation Visual Feedback ────────
    const forms = document.querySelectorAll('form[novalidate]');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // ─── Tooltip Initialization ─────────────────
    const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.map(function (el) {
        return new bootstrap.Tooltip(el);
    });

    // ─── Active sidebar link highlight ──────────
    const currentUrl = new URLSearchParams(window.location.search).get('url') || '';
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    sidebarLinks.forEach(function (link) {
        const href = link.getAttribute('href') || '';
        const linkUrl = new URL(href, window.location.origin);
        const linkUrlParam = new URLSearchParams(linkUrl.search).get('url') || '';
        if (currentUrl && linkUrlParam && currentUrl.startsWith(linkUrlParam.split('/')[0])) {
            link.closest('.sidebar-menu-item')?.classList.add('active');
        }
    });

});

/**
 * Toggle password visibility.
 */
function togglePassword(fieldId, btn) {
    const field = document.getElementById(fieldId);
    const icon = btn.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
