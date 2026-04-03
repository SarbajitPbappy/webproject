<?php /** HostelEase — Student Dashboard */ ?>

<div class="content-header">
    <h2 class="mb-1">My Dashboard</h2>
    <p class="text-muted mb-0">Welcome, <?php echo e(currentUser()['full_name']); ?>!</p>
</div>

<div class="row g-4">
    <!-- Room Info -->
    <div class="col-md-6">
        <div class="card card-glass">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-door-open me-2"></i>My Room</h5></div>
            <div class="card-body">
                <?php if ($allocation): ?>
                <div class="row g-3">
                    <div class="col-6"><label class="detail-label">Room</label><p class="detail-value fw-bold fs-4"><?php echo e($allocation['room_number']); ?></p></div>
                    <div class="col-6"><label class="detail-label">Type</label><p class="detail-value"><?php echo ucfirst(e($allocation['room_type'])); ?></p></div>
                    <div class="col-6"><label class="detail-label">Floor</label><p class="detail-value"><?php echo e($allocation['floor']); ?></p></div>
                    <div class="col-6"><label class="detail-label">Since</label><p class="detail-value"><?php echo date('M d, Y', strtotime($allocation['start_date'])); ?></p></div>
                </div>
                <?php else: ?>
                <div class="text-center text-muted py-3"><i class="bi bi-house-x display-4 d-block mb-2"></i>No room allocated yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Profile Quick View -->
    <div class="col-md-6">
        <div class="card card-glass">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-person me-2"></i>My Profile</h5></div>
            <div class="card-body">
                <?php if ($student): ?>
                <div class="row g-3">
                    <div class="col-6"><label class="detail-label">Student ID</label><p class="detail-value"><code><?php echo e($student['student_id_no']); ?></code></p></div>
                    <div class="col-6"><label class="detail-label">Phone</label><p class="detail-value"><?php echo e($student['phone'] ?? '—'); ?></p></div>
                    <div class="col-6"><label class="detail-label">Status</label><p class="detail-value"><span class="badge bg-success-subtle text-success"><?php echo ucfirst(e($student['status'])); ?></span></p></div>
                    <div class="col-6"><label class="detail-label">Enrolled</label><p class="detail-value"><?php echo $student['enrolled_date'] ? date('M d, Y', strtotime($student['enrolled_date'])) : '—'; ?></p></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="col-md-6">
        <div class="card card-glass">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Recent Payments</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (empty($payments)): ?>
                    <div class="list-group-item text-center text-muted py-3">No payments yet.</div>
                    <?php else: ?>
                    <?php foreach (array_slice($payments, 0, 5) as $p): ?>
                    <div class="list-group-item d-flex justify-content-between">
                        <div>
                            <strong><?php echo e($p['fee_name']); ?></strong>
                            <br><small class="text-muted"><?php echo e($p['receipt_no']); ?> · <?php echo date('M d', strtotime($p['payment_date'])); ?></small>
                        </div>
                        <span class="fw-semibold text-success">₹<?php echo number_format($p['amount_paid'], 0); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- My Complaints -->
    <div class="col-md-6">
        <div class="card card-glass">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0"><i class="bi bi-chat-square me-2"></i>My Complaints</h5>
                <a href="<?php echo BASE_URL; ?>?url=complaints/create" class="btn btn-sm btn-outline-primary">New</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (empty($complaints)): ?>
                    <div class="list-group-item text-center text-muted py-3">No complaints.</div>
                    <?php else: ?>
                    <?php foreach (array_slice($complaints, 0, 5) as $c): ?>
                    <?php $sc = match($c['status']) { 'open' => 'primary', 'in_progress' => 'warning', 'resolved' => 'success', default => 'secondary' }; ?>
                    <a href="<?php echo BASE_URL; ?>?url=complaints/show/<?php echo $c['id']; ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <strong><?php echo e($c['category']); ?></strong>
                            <span class="badge bg-<?php echo $sc; ?>-subtle text-<?php echo $sc; ?>"><?php echo ucfirst(str_replace('_', ' ', e($c['status']))); ?></span>
                        </div>
                        <small class="text-muted"><?php echo date('M d', strtotime($c['created_at'])); ?></small>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Notices -->
    <div class="col-12">
        <div class="card card-glass">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-megaphone me-2"></i>Latest Notices</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($notices as $n): ?>
                    <div class="list-group-item">
                        <strong><?php if ($n['is_pinned']): ?><i class="bi bi-pin-fill text-warning me-1"></i><?php endif; ?><?php echo e($n['title']); ?></strong>
                        <p class="mb-0 text-muted small"><?php echo e(mb_strimwidth($n['body'], 0, 120, '...')); ?></p>
                        <small class="text-muted"><?php echo date('M d, Y', strtotime($n['created_at'])); ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
