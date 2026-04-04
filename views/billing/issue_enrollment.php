<?php /** Issue enrollment billing for one student (deposit + room tier rent) */ ?>

<div class="content-header">
    <h2 class="mb-1">Issue enrollment billing</h2>
    <p class="text-muted mb-0">
        For new students: issue <strong>security deposit</strong> and the correct <strong>room rent tier</strong> (single / double / triple / dormitory).
        Paying the room rent slip sets which room types they may be allocated to.
    </p>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card card-glass">
    <div class="card-body p-4">
        <form method="POST" action="<?php echo BASE_URL; ?>?url=billing/issueEnrollment">
            <?php echo csrfField(); ?>

            <div class="mb-3">
                <label class="form-label">Student <span class="text-danger">*</span></label>
                <select name="student_id" class="form-select" required>
                    <option value="">Select student…</option>
                    <?php foreach ($students as $s): ?>
                    <option value="<?php echo (int) $s['id']; ?>" <?php echo (int) ($_POST['student_id'] ?? 0) === (int) $s['id'] ? 'selected' : ''; ?>>
                        <?php echo e($s['full_name']); ?> (<?php echo e($s['student_id_no']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label">Period label (YYYY-MM) <span class="text-danger">*</span></label>
                <input type="month" name="period_month" class="form-control" required
                       value="<?php echo e($_POST['period_month'] ?? date('Y-m')); ?>">
                <small class="text-muted">Used to group charges; use the month they enroll.</small>
            </div>

            <label class="form-label">Fees to issue <span class="text-danger">*</span></label>
            <?php if (empty($fees)): ?>
            <div class="alert alert-warning">No enrollment fees configured (security_deposit / room_rent categories).</div>
            <?php else: ?>
            <div class="row g-2 mb-4">
                <?php foreach ($fees as $fee): ?>
                <div class="col-md-6">
                    <div class="form-check border rounded p-3">
                        <input class="form-check-input" type="checkbox" name="fee_ids[]" value="<?php echo (int) $fee['id']; ?>"
                               id="efee_<?php echo (int) $fee['id']; ?>"
                            <?php echo !empty($_POST['fee_ids']) && in_array((string) $fee['id'], array_map('strval', (array) $_POST['fee_ids']), true) ? 'checked' : ''; ?>>
                        <label class="form-check-label w-100" for="efee_<?php echo (int) $fee['id']; ?>">
                            <strong><?php echo e($fee['name']); ?></strong>
                            <br><small class="text-muted">৳<?php echo number_format((float) $fee['amount'], 2); ?>
                            · <?php echo e($fee['fee_category'] ?? ''); ?>
                            <?php if (!empty($fee['maps_room_type'])): ?>
                            → tier <strong><?php echo e($fee['maps_room_type']); ?></strong>
                            <?php endif; ?></small>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary-gradient"><i class="bi bi-person-plus me-2"></i>Issue slips</button>
        </form>
    </div>
</div>
