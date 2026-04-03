<?php /** HostelEase — User Management (List) */ ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="mb-1">User Management</h2>
            <p class="text-muted mb-0">Manage system administrators, staff, and students</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?url=users/create" class="btn btn-primary-gradient">
            <i class="bi bi-person-plus me-2"></i>Create User
        </a>
    </div>
</div>

<!-- Role Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-glass stat-card border-danger-subtle">
            <div class="card-body">
                <div class="stat-icon bg-danger-subtle"><i class="bi bi-shield-lock text-danger"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Super Admins</span>
                    <span class="stat-value"><?php echo current(array_filter($roleCounts, fn($k) => $k === 'super_admin', ARRAY_FILTER_USE_KEY)) ?? $roleCounts['super_admin']; ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-glass stat-card border-primary-subtle">
            <div class="card-body">
                <div class="stat-icon bg-primary-subtle"><i class="bi bi-person-badge text-primary"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Admins</span>
                    <span class="stat-value"><?php echo $roleCounts['admin'] ?? 0; ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-glass stat-card border-warning-subtle">
            <div class="card-body">
                <div class="stat-icon bg-warning-subtle"><i class="bi bi-wrench text-warning"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Staff</span>
                    <span class="stat-value"><?php echo $roleCounts['staff'] ?? 0; ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-glass stat-card border-success-subtle">
            <div class="card-body">
                <div class="stat-icon bg-success-subtle"><i class="bi bi-mortarboard text-success"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Students</span>
                    <span class="stat-value"><?php echo $roleCounts['student'] ?? 0; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card card-glass mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?php echo BASE_URL; ?>" class="row g-3 align-items-end">
            <input type="hidden" name="url" value="users/index">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="Name or email..." value="<?php echo e($_GET['search'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Role</label>
                <select class="form-select" name="role">
                    <option value="">All Roles</option>
                    <option value="super_admin" <?php echo ($_GET['role'] ?? '') === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                    <option value="admin" <?php echo ($_GET['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="staff" <?php echo ($_GET['role'] ?? '') === 'staff' ? 'selected' : ''; ?>>Staff</option>
                    <option value="student" <?php echo ($_GET['role'] ?? '') === 'student' ? 'selected' : ''; ?>>Student</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo ($_GET['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="suspended" <?php echo ($_GET['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    <option value="inactive" <?php echo ($_GET['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100 mb-2"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="<?php echo BASE_URL; ?>?url=users/index" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card card-glass">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="usersTable">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Type Info</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-people display-4 d-block mb-2"></i>
                            No users found.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?php echo e($user['full_name']); ?></div>
                            <small class="text-muted"><?php echo e($user['email']); ?></small>
                        </td>
                        <td>
                            <?php
                            $rClass = match($user['role']) { 'super_admin'=>'danger', 'admin'=>'primary', 'staff'=>'warning', 'student'=>'success', default=>'secondary' };
                            ?>
                            <span class="badge bg-<?php echo $rClass; ?>-subtle text-<?php echo $rClass; ?>">
                                <?php echo ucfirst(str_replace('_',' ',e($user['role']))); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['role'] === 'student' && !empty($user['student_id_no'])): ?>
                                <code><?php echo e($user['student_id_no']); ?></code>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $sClass = match($user['status']) { 'active'=>'success', 'suspended'=>'warning', 'inactive'=>'secondary', default=>'secondary' };
                            ?>
                            <span class="badge bg-<?php echo $sClass; ?>-subtle text-<?php echo $sClass; ?>">
                                <?php echo ucfirst(e($user['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td class="text-end">
                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                            <form method="POST" action="<?php echo BASE_URL; ?>?url=users/toggleStatus" class="d-inline">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $user['status'] === 'active' ? 'suspended' : 'active'; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-<?php echo $user['status'] === 'active' ? 'warning' : 'success'; ?>" title="<?php echo $user['status'] === 'active' ? 'Suspend' : 'Activate'; ?>">
                                    <i class="bi bi-<?php echo $user['status'] === 'active' ? 'pause-circle' : 'play-circle'; ?>"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extraScripts = '<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $("#usersTable").DataTable({ pageLength: 15, order: [] });
    }
});
</script>';
?>
