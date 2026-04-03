<?php
/** HostelEase — Complaint Detail View */
$priorityClass = match($complaint['priority']) { 'high' => 'danger', 'medium' => 'warning', 'low' => 'info', default => 'secondary' };
$statusClass = match($complaint['status']) { 'open' => 'primary', 'in_progress' => 'warning', 'resolved' => 'success', 'closed' => 'secondary', default => 'secondary' };
$hoursOpen = (time() - strtotime($complaint['created_at'])) / 3600;
$isOverdue = false;
if (in_array($complaint['status'], ['open', 'in_progress'])) {
    if (($complaint['priority'] === 'high' && $hoursOpen > SLA_HIGH_PRIORITY_HOURS) ||
        ($complaint['priority'] === 'medium' && $hoursOpen > SLA_MEDIUM_PRIORITY_HOURS)) {
        $isOverdue = true;
    }
}
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div><h2 class="mb-1">Complaint #<?php echo $complaint['id']; ?></h2><p class="text-muted mb-0"><?php echo e($complaint['category']); ?></p></div>
        <a href="<?php echo BASE_URL; ?>?url=complaints/index" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<?php if ($isOverdue): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><strong>SLA Overdue!</strong> This ticket has exceeded its resolution deadline (<?php echo round($hoursOpen); ?> hours open).</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-glass">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Complaint Details</h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-<?php echo $priorityClass; ?>-subtle text-<?php echo $priorityClass; ?> fs-6"><?php echo ucfirst(e($complaint['priority'])); ?> Priority</span>
                    <span class="badge bg-<?php echo $statusClass; ?>-subtle text-<?php echo $statusClass; ?> fs-6"><?php echo ucfirst(str_replace('_', ' ', e($complaint['status']))); ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <label class="detail-label">Student</label>
                        <p class="detail-value"><?php echo e($complaint['student_name']); ?> (<?php echo e($complaint['student_id_no']); ?>)</p>
                    </div>
                    <div class="col-sm-6">
                        <label class="detail-label">Submitted</label>
                        <p class="detail-value"><?php echo date('F d, Y H:i A', strtotime($complaint['created_at'])); ?></p>
                    </div>
                    <div class="col-sm-6">
                        <label class="detail-label">Assigned To</label>
                        <p class="detail-value"><?php echo e($complaint['assigned_to_name'] ?? 'Unassigned'); ?></p>
                    </div>
                    <div class="col-sm-6">
                        <label class="detail-label">Resolved At</label>
                        <p class="detail-value"><?php echo $complaint['resolved_at'] ? date('F d, Y H:i A', strtotime($complaint['resolved_at'])) : 'Not yet resolved'; ?></p>
                    </div>
                </div>
                <hr>
                <label class="detail-label">Description</label>
                <div class="p-3 bg-light rounded mt-2">
                    <?php echo nl2br(e($complaint['description'])); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions Sidebar -->
    <div class="col-lg-4">
        <?php if (hasRole(['super_admin', 'admin'])): ?>
        <!-- Assign to Staff -->
        <div class="card card-glass mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-person-check me-2"></i>Assign Staff</h6></div>
            <div class="card-body">
                <form method="POST" action="<?php echo BASE_URL; ?>?url=complaints/assign/<?php echo $complaint['id']; ?>">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="id" value="<?php echo $complaint['id']; ?>">
                    <select class="form-select mb-3" name="assigned_to" required>
                        <option value="">Select Staff...</option>
                        <?php foreach ($staff as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo ($complaint['assigned_to'] ?? 0) == $s['id'] ? 'selected' : ''; ?>>
                            <?php echo e($s['full_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-check me-1"></i>Assign</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if (hasRole(['super_admin', 'admin', 'staff'])): ?>
        <!-- Update Status -->
        <div class="card card-glass">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-gear me-2"></i>Update Status</h6></div>
            <div class="card-body">
                <form method="POST" action="<?php echo BASE_URL; ?>?url=complaints/update/<?php echo $complaint['id']; ?>">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="id" value="<?php echo $complaint['id']; ?>">
                    <select class="form-select mb-3" name="status" required>
                        <option value="open" <?php echo $complaint['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="in_progress" <?php echo $complaint['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $complaint['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $complaint['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                    <button type="submit" class="btn btn-outline-warning w-100"><i class="bi bi-arrow-repeat me-1"></i>Update Status</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Timeline -->
        <div class="card card-glass mt-4">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Timeline</h6></div>
            <div class="card-body">
                <div class="timeline-item">
                    <div class="timeline-dot bg-primary"></div>
                    <div class="timeline-content">
                        <strong>Created</strong>
                        <small class="text-muted d-block"><?php echo date('M d, Y H:i', strtotime($complaint['created_at'])); ?></small>
                    </div>
                </div>
                <?php if ($complaint['assigned_to']): ?>
                <div class="timeline-item">
                    <div class="timeline-dot bg-warning"></div>
                    <div class="timeline-content">
                        <strong>Assigned to <?php echo e($complaint['assigned_to_name']); ?></strong>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($complaint['resolved_at']): ?>
                <div class="timeline-item">
                    <div class="timeline-dot bg-success"></div>
                    <div class="timeline-content">
                        <strong>Resolved</strong>
                        <small class="text-muted d-block"><?php echo date('M d, Y H:i', strtotime($complaint['resolved_at'])); ?></small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
