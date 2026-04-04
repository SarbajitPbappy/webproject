<?php
/**
 * HostelEase — Top Navbar
 */
$user = currentUser();
$role = $user['role'] ?? '';
$pendingPaySlipCount = 0;
$unreadUserNotifs = 0;

// Show notification badge to Super Admin when new payroll slips are created (status='applied').
if ($role === 'super_admin') {
    try {
        $db = Database::getInstance();
        $row = $db->query("SELECT COUNT(*) as c FROM pay_slips WHERE status = 'applied'")->fetch();
        $pendingPaySlipCount = (int)($row['c'] ?? 0);
    } catch (Exception $e) {
        $pendingPaySlipCount = 0; // Never break navbar rendering.
    }
} else {
    $uid = (int) ($user['id'] ?? 0);
    if ($uid > 0) {
        require_once APP_ROOT . '/app/models/UserNotification.php';
        $unreadUserNotifs = UserNotification::unreadCountForUser($uid);
    }
}

$bellCount = $role === 'super_admin' ? $pendingPaySlipCount : $unreadUserNotifs;
$bellHref = $role === 'super_admin'
    ? BASE_URL . '?url=payroll/review'
    : BASE_URL . '?url=notifications/index';
?>
<header class="top-navbar">
    <div class="navbar-left">
        <button class="sidebar-toggle d-lg-none" id="sidebarOpen">
            <i class="bi bi-list"></i>
        </button>
        <div class="page-title">
            <h4><?php echo isset($pageTitle) ? e($pageTitle) : 'Dashboard'; ?></h4>
        </div>
    </div>

    <div class="navbar-right">
        <!-- Search (optional) -->
        <div class="navbar-search d-none d-md-block">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" placeholder="Search..." id="globalSearch">
            </div>
        </div>

        <!-- Payroll (super admin) or in-app alerts -->
        <a href="<?php echo e($bellHref); ?>" class="navbar-icon-btn text-decoration-none text-body" title="<?php echo $role === 'super_admin' ? 'Payroll review' : 'Notifications'; ?>">
            <i class="bi bi-bell"></i>
            <span class="notification-badge" id="notifBadge" style="<?php echo $bellCount > 0 ? 'display:flex;' : 'display:none;'; ?>">
                <?php echo (int) $bellCount; ?>
            </span>
        </a>

        <!-- User Dropdown -->
        <div class="dropdown">
            <button class="navbar-user-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="navbar-avatar">
                    <?php if (!empty($user['photo'])): ?>
                        <img src="<?php echo BASE_URL . 'public/uploads/students/' . e($user['photo']); ?>" alt="Avatar">
                    <?php else: ?>
                        <i class="bi bi-person-circle"></i>
                    <?php endif; ?>
                </div>
                <span class="navbar-username d-none d-md-inline"><?php echo e($user['full_name'] ?? 'Guest'); ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>?url=profile/index">
                        <i class="bi bi-person me-2"></i>My Profile
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>?url=auth/logout">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
