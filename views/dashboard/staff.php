<?php /** HostelEase — Staff Dashboard */ ?>

<div class="content-header">
    <h2 class="mb-1">Staff Dashboard</h2>
    <p class="text-muted mb-0">Welcome, <?php echo e(currentUser()['full_name']); ?>!</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-glass kpi-card kpi-primary">
            <div class="card-body">
                <div class="kpi-icon"><i class="bi bi-list-check"></i></div>
                <div class="kpi-info">
                    <span class="kpi-value"><?php echo count($assignedComplaints); ?></span>
                    <span class="kpi-label">Assigned Tickets</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-glass kpi-card kpi-warning">
            <div class="card-body">
                <div class="kpi-icon"><i class="bi bi-clock"></i></div>
                <div class="kpi-info">
                    <span class="kpi-value"><?php echo count(array_filter($assignedComplaints, fn($c) => $c['status'] === 'in_progress')); ?></span>
                    <span class="kpi-label">In Progress</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-glass kpi-card kpi-success">
            <div class="card-body">
                <div class="kpi-icon"><i class="bi bi-check-circle"></i></div>
                <div class="kpi-info">
                    <span class="kpi-value"><?php echo count(array_filter($assignedComplaints, fn($c) => in_array($c['status'], ['resolved','closed']))); ?></span>
                    <span class="kpi-label">Resolved</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-glass">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-list-task me-2"></i>My Assigned Tickets</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>#</th><th>Category</th><th>Priority</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php if (empty($assignedComplaints)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No tickets assigned.</td></tr>
                            <?php else: ?>
                            <?php foreach ($assignedComplaints as $c): ?>
                            <?php $pc = match($c['priority']) { 'high' => 'danger', 'medium' => 'warning', default => 'info' }; ?>
                            <?php $sc = match($c['status']) { 'open' => 'primary', 'in_progress' => 'warning', 'resolved' => 'success', default => 'secondary' }; ?>
                            <tr>
                                <td><?php echo $c['id']; ?></td>
                                <td><?php echo e($c['category']); ?></td>
                                <td><span class="badge bg-<?php echo $pc; ?>-subtle text-<?php echo $pc; ?>"><?php echo ucfirst(e($c['priority'])); ?></span></td>
                                <td><span class="badge bg-<?php echo $sc; ?>-subtle text-<?php echo $sc; ?>"><?php echo ucfirst(str_replace('_',' ',e($c['status']))); ?></span></td>
                                <td><?php echo date('M d', strtotime($c['created_at'])); ?></td>
                                <td><a href="<?php echo BASE_URL; ?>?url=complaints/show/<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-glass">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-megaphone me-2"></i>Notices</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($notices as $n): ?>
                    <div class="list-group-item">
                        <strong><?php echo e($n['title']); ?></strong>
                        <p class="mb-0 text-muted small"><?php echo e(mb_strimwidth($n['body'], 0, 80, '...')); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
