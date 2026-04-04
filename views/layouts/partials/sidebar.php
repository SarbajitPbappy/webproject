<?php
/**
 * HostelEase — Sidebar Navigation
 */
$user = currentUser();
$role = $user['role'] ?? '';
$currentUrl = $_GET['url'] ?? '';
$pendingPaySlipCount = 0;

// For Super Admin: show a notification dot when there are new pay slips awaiting approval.
if ($role === 'super_admin') {
    try {
        $db = Database::getInstance();
        $row = $db->query("SELECT COUNT(*) as c FROM pay_slips WHERE status = 'applied'")->fetch();
        $pendingPaySlipCount = (int)($row['c'] ?? 0);
    } catch (Exception $e) {
        $pendingPaySlipCount = 0;
    }
}

// Determine active menu item
function isActive(string $segment, string $currentUrl): string
{
    return (strpos($currentUrl, $segment) === 0) ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="bi bi-building"></i>
            <span class="sidebar-brand"><?php echo APP_NAME; ?></span>
        </div>
        <button class="sidebar-toggle d-lg-none" id="sidebarClose">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
            <!-- Dashboard -->
            <li class="sidebar-menu-item <?php echo isActive('dashboard', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=dashboard/index" class="sidebar-link">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Profile (Visible to Everyone) -->
            <li class="sidebar-menu-item <?php echo isActive('profile', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=profile/index" class="sidebar-link">
                    <i class="bi bi-person-fill"></i>
                    <span>My Profile</span>
                </a>
            </li>

            <?php if (in_array($role, ['super_admin', 'admin'])): ?>
            <!-- Student Management -->
            <li class="sidebar-menu-header">Management</li>
            <li class="sidebar-menu-item <?php echo isActive('students', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=students/index" class="sidebar-link">
                    <i class="bi bi-people-fill"></i>
                    <span>Students</span>
                </a>
            </li>

            <!-- Room Management -->
            <li class="sidebar-menu-item <?php echo isActive('rooms', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=rooms/index" class="sidebar-link">
                    <i class="bi bi-door-open-fill"></i>
                    <span>Rooms</span>
                </a>
            </li>

            <!-- Allocations -->
            <li class="sidebar-menu-item <?php echo isActive('allocations', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=allocations/index" class="sidebar-link">
                    <i class="bi bi-diagram-3-fill"></i>
                    <span>Allocations</span>
                </a>
            </li>

            <li class="sidebar-menu-item <?php echo isActive('allocations/roomRequests', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=allocations/roomRequests" class="sidebar-link">
                    <i class="bi bi-inboxes-fill"></i>
                    <span>Room requests</span>
                </a>
            </li>

            <li class="sidebar-menu-item <?php echo isActive('allocations/occupancy', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=allocations/occupancy" class="sidebar-link">
                    <i class="bi bi-house-door-fill"></i>
                    <span>Room roster</span>
                </a>
            </li>

            <li class="sidebar-menu-item <?php echo isActive('billing', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=billing/issueMonthly" class="sidebar-link">
                    <i class="bi bi-receipt-cutoff"></i>
                    <span>Issue monthly bills</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?php echo isActive('billing/issueEnrollment', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=billing/issueEnrollment" class="sidebar-link">
                    <i class="bi bi-person-badge"></i>
                    <span>Enrollment billing</span>
                </a>
            </li>

            <!-- Payments -->
            <li class="sidebar-menu-item <?php echo isActive('payments', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=payments/index" class="sidebar-link">
                    <i class="bi bi-credit-card-fill"></i>
                    <span>Payments</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Complaints (visible to all) -->
            <li class="sidebar-menu-header">Support</li>
            <li class="sidebar-menu-item <?php echo isActive('complaints', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=complaints/index" class="sidebar-link">
                    <i class="bi bi-chat-square-text-fill"></i>
                    <span>Complaints</span>
                </a>
            </li>

            <!-- Notices -->
            <li class="sidebar-menu-item <?php echo isActive('notices', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=notices/index" class="sidebar-link">
                    <i class="bi bi-megaphone-fill"></i>
                    <span>Notices</span>
                </a>
            </li>

            <li class="sidebar-menu-item <?php echo isActive('notifications', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=notifications/index" class="sidebar-link">
                    <i class="bi bi-bell-fill"></i>
                    <span class="d-flex align-items-center gap-2">
                        <span>Notifications</span>
                        <?php
                        $nUid = (int) ($user['id'] ?? 0);
                        if ($nUid > 0 && ($user['role'] ?? '') !== 'super_admin') {
                            require_once APP_ROOT . '/app/models/UserNotification.php';
                            $nb = UserNotification::unreadCountForUser($nUid);
                            if ($nb > 0) {
                                echo '<span class="badge rounded-pill bg-primary">' . (int) $nb . '</span>';
                            }
                        }
                        ?>
                    </span>
                </a>
            </li>

            <!-- PAYROLL SECTION (Dependent on Role) -->
            <li class="sidebar-menu-header">Payroll & Billing</li>
            
            <?php if (in_array($role, ['staff', 'admin'])): ?>
            <li class="sidebar-menu-item <?php echo isActive('payroll/index', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=payroll/index" class="sidebar-link">
                    <i class="bi bi-wallet2"></i>
                    <span class="text-success fw-bold">My Payroll</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($role === 'super_admin'): ?>
            <li class="sidebar-menu-item <?php echo isActive('payroll/review', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=payroll/review" class="sidebar-link">
                    <i class="bi bi-clipboard-check"></i>
                    <span class="d-flex align-items-center gap-2">
                        <span>Review Pay Slips</span>
                        <?php if ($pendingPaySlipCount > 0): ?>
                            <span title="New pay slips pending" style="display:inline-block;width:9px;height:9px;border-radius:50%;background:#ef4444;"></span>
                        <?php endif; ?>
                    </span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($role === 'super_admin'): ?>
            <li class="sidebar-menu-item <?php echo isActive('payroll/distribute', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=payroll/distribute" class="sidebar-link">
                    <i class="bi bi-cash-stack text-warning"></i>
                    <span class="text-warning fw-bold">Distribute Salaries</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?php echo isActive('finances', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=finances/index" class="sidebar-link">
                    <i class="bi bi-bank2 text-success"></i>
                    <span class="text-success fw-bold">Hostel Finances</span>
                </a>
            </li>
            <?php endif; ?>

            <?php
            // Show self-serve online fee payment if this account has a corresponding students profile.
            $canPayOnline = ($role === 'student');
            if (!$canPayOnline && in_array($role, ['admin', 'staff'], true)) {
                require_once APP_ROOT . '/app/models/Student.php';
                $studentProfile = (new Student())->findByUserId((int)($user['id'] ?? 0));
                $canPayOnline = !empty($studentProfile);
            }
            ?>
            <?php if ($canPayOnline): ?>
            <li class="sidebar-menu-header">Portal Actions</li>
            <li class="sidebar-menu-item <?php echo isActive('payments/makePayment', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=payments/makePayment" class="sidebar-link">
                    <i class="bi bi-credit-card-fill text-primary"></i>
                    <span class="text-primary fw-bold">Pay Fees Online</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?php echo isActive('payments/balanceSheet', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=payments/balanceSheet" class="sidebar-link">
                    <i class="bi bi-table"></i>
                    <span>Fee balance sheet</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?php echo isActive('students/roomRequests', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=students/roomRequests" class="sidebar-link">
                    <i class="bi bi-door-open"></i>
                    <span>Room change / cancel</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($role === 'super_admin'): ?>
            <!-- Super Admin Only -->
            <li class="sidebar-menu-header">Administration</li>
            <li class="sidebar-menu-item <?php echo isActive('users', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=users/index" class="sidebar-link">
                    <i class="bi bi-people"></i>
                    <span>User Management</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?php echo isActive('audit', $currentUrl); ?>">
                <a href="<?php echo BASE_URL; ?>?url=audit/index" class="sidebar-link">
                    <i class="bi bi-journal-text"></i>
                    <span>Audit Logs</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?php if (!empty($user['photo'])): ?>
                    <img src="<?php echo BASE_URL . 'public/uploads/students/' . e($user['photo']); ?>" alt="Avatar">
                <?php else: ?>
                    <i class="bi bi-person-circle"></i>
                <?php endif; ?>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo e($user['full_name'] ?? 'Guest'); ?></span>
                <span class="sidebar-user-role"><?php echo e(ucfirst(str_replace('_', ' ', $role))); ?></span>
            </div>
        </div>
    </div>
</aside>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
