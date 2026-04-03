<?php /** HostelEase — Transfer Student View */ ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div><h2 class="mb-1">Transfer Student</h2><p class="text-muted mb-0">Move a student to a different room</p></div>
        <a href="<?php echo BASE_URL; ?>?url=allocations/allocate" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card card-glass">
    <div class="card-body p-4">
        <form method="POST" action="<?php echo BASE_URL; ?>?url=allocations/transfer" novalidate>
            <?php echo csrfField(); ?>
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="student_id" class="form-label">Student <span class="text-danger">*</span></label>
                    <select class="form-select" id="student_id" name="student_id" required>
                        <option value="">Select Student...</option>
                        <?php foreach ($students as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo ($s['id'] == ($preSelectedStudentId ?? 0)) ? 'selected' : ''; ?>>
                            <?php echo e($s['full_name']); ?> (<?php echo e($s['student_id_no']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="room_id" class="form-label">New Room <span class="text-danger">*</span></label>
                    <select class="form-select" id="room_id" name="room_id" required>
                        <option value="">Select New Room...</option>
                        <?php foreach ($rooms as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo e($r['room_number']); ?> (<?php echo $r['current_occupancy']; ?>/<?php echo $r['capacity']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Reason for transfer..."></textarea>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>?url=allocations/allocate" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary-gradient"><i class="bi bi-arrow-left-right me-2"></i>Transfer Student</button>
            </div>
        </form>
    </div>
</div>
