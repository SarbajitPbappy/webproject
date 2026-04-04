<?php /** HostelEase — Admin queue: room change / cancellation requests */ ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="mb-1"><?php echo e($pageTitle ?? 'Room requests'); ?></h2>
            <p class="text-muted mb-0">Approve or reject student requests. Cancellation requires the student to have no pending slips.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?url=allocations/index" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Allocations</a>
    </div>
</div>

<?php if (empty($requests)): ?>
<div class="alert alert-info mb-0">No pending room requests.</div>
<?php else: ?>
<div class="card card-glass">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student</th>
                    <th>Type</th>
                    <th>Current room</th>
                    <th>Preferred tier</th>
                    <th>Message</th>
                    <th>Since</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $req): ?>
                <tr>
                    <td>
                        <strong><?php echo e($req['full_name']); ?></strong><br>
                        <small class="text-muted"><?php echo e($req['student_id_no']); ?></small>
                    </td>
                    <td>
                        <?php if ($req['request_type'] === 'room_cancellation'): ?>
                        <span class="badge bg-danger-subtle text-danger">Cancellation</span>
                        <?php else: ?>
                        <span class="badge bg-info-subtle text-info">Room change</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e($req['current_room'] ?? '—'); ?></td>
                    <td><?php echo e($req['preferred_room_type'] ?? '—'); ?></td>
                    <td><small><?php echo e($req['message'] ?? '—'); ?></small></td>
                    <td><small><?php echo e(date('M j, Y g:i a', strtotime($req['created_at']))); ?></small></td>
                    <td class="text-end">
                        <form method="POST" action="<?php echo BASE_URL; ?>?url=allocations/roomRequestProcess" class="d-inline">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="request_id" value="<?php echo (int) $req['id']; ?>">
                            <input type="hidden" name="decision" value="approve">
                            <?php if ($req['request_type'] === 'room_change' && !empty($transferFee)): ?>
                            <div class="form-check form-check-inline text-start mb-1">
                                <input class="form-check-input" type="checkbox" name="auto_issue_transfer_fee" value="1" id="auto<?php echo (int) $req['id']; ?>" checked>
                                <label class="form-check-label small" for="auto<?php echo (int) $req['id']; ?>">Issue transfer fee slip</label>
                            </div>
                            <?php endif; ?>
                            <input type="text" class="form-control form-control-sm mb-1" name="admin_notes" placeholder="Notes (optional)">
                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                        </form>
                        <form method="POST" action="<?php echo BASE_URL; ?>?url=allocations/roomRequestProcess" class="d-inline ms-1" onsubmit="return confirm('Reject this request?');">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="request_id" value="<?php echo (int) $req['id']; ?>">
                            <input type="hidden" name="decision" value="reject">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
