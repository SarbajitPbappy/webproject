<?php /** HostelEase — Audit Logs View (Super Admin Only) */ ?>

<div class="content-header">
    <h2 class="mb-1">Audit Logs</h2>
    <p class="text-muted mb-0">Complete system activity trail — Super Admin access only</p>
</div>

<!-- Filters -->
<div class="card card-glass mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?php echo BASE_URL; ?>" class="row g-3 align-items-end">
            <input type="hidden" name="url" value="audit/index">
            <div class="col-md-2">
                <label class="form-label">Action</label>
                <select class="form-select" name="action">
                    <option value="">All</option>
                    <option value="CREATE" <?php echo ($_GET['action'] ?? '') === 'CREATE' ? 'selected' : ''; ?>>Create</option>
                    <option value="UPDATE" <?php echo ($_GET['action'] ?? '') === 'UPDATE' ? 'selected' : ''; ?>>Update</option>
                    <option value="DELETE" <?php echo ($_GET['action'] ?? '') === 'DELETE' ? 'selected' : ''; ?>>Delete</option>
                    <option value="LOGIN" <?php echo ($_GET['action'] ?? '') === 'LOGIN' ? 'selected' : ''; ?>>Login</option>
                    <option value="LOGOUT" <?php echo ($_GET['action'] ?? '') === 'LOGOUT' ? 'selected' : ''; ?>>Logout</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Table</label>
                <select class="form-select" name="table_name">
                    <option value="">All</option>
                    <option value="users" <?php echo ($_GET['table_name'] ?? '') === 'users' ? 'selected' : ''; ?>>Users</option>
                    <option value="students" <?php echo ($_GET['table_name'] ?? '') === 'students' ? 'selected' : ''; ?>>Students</option>
                    <option value="rooms" <?php echo ($_GET['table_name'] ?? '') === 'rooms' ? 'selected' : ''; ?>>Rooms</option>
                    <option value="allocations" <?php echo ($_GET['table_name'] ?? '') === 'allocations' ? 'selected' : ''; ?>>Allocations</option>
                    <option value="payments" <?php echo ($_GET['table_name'] ?? '') === 'payments' ? 'selected' : ''; ?>>Payments</option>
                    <option value="complaints" <?php echo ($_GET['table_name'] ?? '') === 'complaints' ? 'selected' : ''; ?>>Complaints</option>
                    <option value="notices" <?php echo ($_GET['table_name'] ?? '') === 'notices' ? 'selected' : ''; ?>>Notices</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">From</label>
                <input type="date" class="form-control" name="date_from" value="<?php echo e($_GET['date_from'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">To</label>
                <input type="date" class="form-control" name="date_to" value="<?php echo e($_GET['date_to'] ?? ''); ?>">
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
            <div class="col-md-2"><a href="<?php echo BASE_URL; ?>?url=audit/index" class="btn btn-outline-secondary w-100">Clear</a></div>
        </form>
    </div>
</div>

<!-- Audit Table -->
<div class="card card-glass">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle" id="auditTable">
                <thead>
                    <tr><th>ID</th><th>User</th><th>Action</th><th>Table</th><th>Record</th><th>Details</th><th>IP</th><th>Timestamp</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($logs ?? [] as $log): ?>
                    <?php
                    $actionClass = match($log['action']) {
                        'CREATE' => 'success', 'UPDATE' => 'warning', 'DELETE' => 'danger',
                        'LOGIN' => 'info', 'LOGOUT' => 'secondary', default => 'secondary'
                    };
                    ?>
                    <tr>
                        <td><small><?php echo $log['id']; ?></small></td>
                        <td><?php echo e($log['user_name'] ?? 'System'); ?></td>
                        <td><span class="badge bg-<?php echo $actionClass; ?>-subtle text-<?php echo $actionClass; ?>"><?php echo e($log['action']); ?></span></td>
                        <td><code><?php echo e($log['table_name']); ?></code></td>
                        <td><?php echo $log['record_id'] ?? '—'; ?></td>
                        <td><small><?php echo e(mb_strimwidth($log['details'] ?? '', 0, 60, '...')); ?></small></td>
                        <td><small class="text-muted"><?php echo e($log['ip_address']); ?></small></td>
                        <td><small><?php echo date('M d H:i:s', strtotime($log['created_at'])); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $extraScripts = '<script>$(function(){ if($.fn.DataTable) $("#auditTable").DataTable({pageLength:25,order:[[0,"desc"]]}); });</script>'; ?>
