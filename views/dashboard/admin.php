<?php /** HostelEase — Admin Dashboard View */ ?>

<div class="content-header">
    <h2 class="mb-1">Dashboard</h2>
    <p class="text-muted mb-0">Welcome back, <?php echo e(currentUser()['full_name']); ?>!</p>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card card-glass kpi-card kpi-primary">
            <div class="card-body">
                <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
                <div class="kpi-info">
                    <span class="kpi-value"><?php echo $kpi['total_students']; ?></span>
                    <span class="kpi-label">Total Students</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-glass kpi-card kpi-success">
            <div class="card-body">
                <div class="kpi-icon"><i class="bi bi-door-open-fill"></i></div>
                <div class="kpi-info">
                    <span class="kpi-value"><?php echo $kpi['occupancy']['occupancy_percent']; ?>%</span>
                    <span class="kpi-label">Occupancy Rate</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-glass kpi-card kpi-warning">
            <div class="card-body">
                <div class="kpi-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
                <div class="kpi-info">
                    <span class="kpi-value"><?php echo $kpi['outstanding_count']; ?></span>
                    <span class="kpi-label">Outstanding Payments</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-glass kpi-card kpi-danger">
            <div class="card-body">
                <div class="kpi-icon"><i class="bi bi-chat-square-text-fill"></i></div>
                <div class="kpi-info">
                    <span class="kpi-value"><?php echo $kpi['open_complaints']; ?></span>
                    <span class="kpi-label">Open Complaints</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Revenue + Occupancy Row -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-glass">
            <div class="card-body text-center">
                <i class="bi bi-currency-dollar text-success fs-1"></i>
                <h3 class="mt-2 mb-0">₹<?php echo number_format($kpi['monthly_revenue'], 0); ?></h3>
                <p class="text-muted">Revenue This Month</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-glass">
            <div class="card-body text-center">
                <i class="bi bi-graph-up text-primary fs-1"></i>
                <h3 class="mt-2 mb-0">₹<?php echo number_format($kpi['total_balance'], 0); ?></h3>
                <p class="text-muted">Total Balance (Ledger)</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-glass">
            <div class="card-body text-center">
                <i class="bi bi-building text-info fs-1"></i>
                <h3 class="mt-2 mb-0"><?php echo $kpi['occupancy']['total_occupied']; ?> / <?php echo $kpi['occupancy']['total_capacity']; ?></h3>
                <p class="text-muted">Beds Occupied</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- SLA Overdue Alerts -->
    <?php if (!empty($overdueComplaints)): ?>
    <div class="col-12">
        <div class="alert alert-danger d-flex align-items-start">
            <i class="bi bi-exclamation-triangle-fill me-3 fs-4 mt-1"></i>
            <div>
                <strong><?php echo count($overdueComplaints); ?> SLA Overdue Complaint(s)</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach (array_slice($overdueComplaints, 0, 3) as $oc): ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>?url=complaints/show/<?php echo $oc['id']; ?>" class="text-danger">
                            #<?php echo $oc['id']; ?> — <?php echo e($oc['category']); ?> (<?php echo ucfirst(e($oc['priority'])); ?>, <?php echo round($oc['hours_open']); ?>h open)
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="col-lg-4">
        <div class="card card-glass">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h5></div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo BASE_URL; ?>?url=students/create" class="btn btn-outline-primary text-start"><i class="bi bi-person-plus me-2"></i>Register Student</a>
                    <a href="<?php echo BASE_URL; ?>?url=rooms/create" class="btn btn-outline-info text-start"><i class="bi bi-door-open me-2"></i>Add Room</a>
                    <a href="<?php echo BASE_URL; ?>?url=allocations/allocate" class="btn btn-outline-success text-start"><i class="bi bi-diagram-3 me-2"></i>Allocate Room</a>
                    <a href="<?php echo BASE_URL; ?>?url=payments/record" class="btn btn-outline-warning text-start"><i class="bi bi-credit-card me-2"></i>Record Payment</a>
                    <a href="<?php echo BASE_URL; ?>?url=notices/create" class="btn btn-outline-secondary text-start"><i class="bi bi-megaphone me-2"></i>Post Notice</a>
                </div>
            </div>
        </div>

        <!-- Recent Notices -->
        <div class="card card-glass mt-4">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0"><i class="bi bi-megaphone me-2"></i>Notices</h5>
                <a href="<?php echo BASE_URL; ?>?url=notices/index" class="btn btn-sm btn-link">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (empty($recentNotices)): ?>
                    <div class="list-group-item text-center text-muted py-3">No notices yet.</div>
                    <?php else: ?>
                    <?php foreach ($recentNotices as $n): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <strong>
                                <?php if ($n['is_pinned']): ?><i class="bi bi-pin-angle-fill text-warning me-1"></i><?php endif; ?>
                                <?php echo e($n['title']); ?>
                            </strong>
                            <small class="text-muted"><?php echo date('M d', strtotime($n['created_at'])); ?></small>
                        </div>
                        <small class="text-muted"><?php echo e(mb_strimwidth($n['body'], 0, 80, '...')); ?></small>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-lg-8">
        <div class="card card-glass">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Activity</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>User</th><th>Action</th><th>Table</th><th>Details</th><th>Time</th></tr></thead>
                        <tbody>
                            <?php if (empty($recentActivity)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No recent activity.</td></tr>
                            <?php else: ?>
                            <?php foreach ($recentActivity as $act): ?>
                            <tr>
                                <td><?php echo e($act['user_name'] ?? 'System'); ?></td>
                                <td><span class="badge bg-secondary-subtle text-secondary"><?php echo e($act['action']); ?></span></td>
                                <td><code><?php echo e($act['table_name']); ?></code></td>
                                <td><small><?php echo e(mb_strimwidth($act['details'] ?? '', 0, 50, '...')); ?></small></td>
                                <td><small class="text-muted"><?php echo date('M d H:i', strtotime($act['created_at'])); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
