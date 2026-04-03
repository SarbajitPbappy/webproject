<?php
/**
 * HostelEase — Role Selector Hub
 * Users pick their role, then go to the dedicated login page for that role.
 */
ob_start();
?>

<style>
    .role-hub { padding: 3rem; width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .role-hub h2 { font-weight: 800; color: #1e293b; margin-bottom: 0.5rem; }
    .role-hub p { color: #64748b; margin-bottom: 2rem; }
    .role-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; width: 100%; max-width: 600px; }
    .role-card-link {
        text-decoration: none; display: block;
        background: #fff; border: 2px solid #e2e8f0; border-radius: 20px;
        padding: 2rem 1.5rem; text-align: center;
        transition: all 0.3s; cursor: pointer;
    }
    .role-card-link:hover { border-color: #6366f1; transform: translateY(-5px); box-shadow: 0 15px 40px rgba(99,102,241,0.15); }
    .role-card-link .rc-icon { font-size: 2.5rem; margin-bottom: 0.75rem; display: block; }
    .role-card-link h5 { font-weight: 700; color: #1e293b; margin-bottom: 0.25rem; font-size: 1.1rem; }
    .role-card-link p { color: #64748b; font-size: 0.8rem; margin: 0; }
    @media (max-width: 576px) { .role-grid { grid-template-columns: 1fr; } }
</style>

<div class="role-hub">
    <a href="<?php echo BASE_URL; ?>" class="btn btn-sm btn-outline-secondary mb-4 rounded-pill">
        <i class="bi bi-arrow-left me-1"></i>Back to Home
    </a>
    <h2><i class="bi bi-building me-2 text-primary"></i>HostelEase</h2>
    <p>Select your role to continue</p>

    <div class="role-grid">
        <a href="<?php echo BASE_URL; ?>?url=auth/loginAs/super_admin" class="role-card-link">
            <span class="rc-icon"><i class="bi bi-shield-lock-fill text-danger"></i></span>
            <h5>Super Admin</h5>
            <p>Full System Control</p>
        </a>
        <a href="<?php echo BASE_URL; ?>?url=auth/loginAs/admin" class="role-card-link">
            <span class="rc-icon"><i class="bi bi-person-badge-fill text-primary"></i></span>
            <h5>Admin / Warden</h5>
            <p>Hostel Operations</p>
        </a>
        <a href="<?php echo BASE_URL; ?>?url=auth/loginAs/student" class="role-card-link">
            <span class="rc-icon"><i class="bi bi-mortarboard-fill text-success"></i></span>
            <h5>Student</h5>
            <p>Self-Service Portal</p>
        </a>
        <a href="<?php echo BASE_URL; ?>?url=auth/loginAs/staff" class="role-card-link">
            <span class="rc-icon"><i class="bi bi-wrench-adjustable text-warning"></i></span>
            <h5>Staff</h5>
            <p>Task Management</p>
        </a>
    </div>
</div>

<?php
$viewContent = ob_get_clean();
require_once APP_ROOT . '/views/layouts/auth.php';
?>
