<?php /** HostelEase — Record Payment View */ ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div><h2 class="mb-1">Record Payment</h2><p class="text-muted mb-0">Register a cash payment from a student</p></div>
        <a href="<?php echo BASE_URL; ?>?url=payments/index" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card card-glass">
    <div class="card-body p-4">
        <form method="POST" action="<?php echo BASE_URL; ?>?url=payments/store" novalidate>
            <?php echo csrfField(); ?>
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="student_id" class="form-label">Student <span class="text-danger">*</span></label>
                    <select class="form-select" id="student_id" name="student_id" required>
                        <option value="">Select Student...</option>
                        <?php foreach ($students as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo ($data['student_id'] ?? '') == $s['id'] ? 'selected' : ''; ?>>
                            <?php echo e($s['full_name']); ?> (<?php echo e($s['student_id_no']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="fee_id" class="form-label">Fee Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="fee_id" name="fee_id" required onchange="updateAmount(this)">
                        <option value="">Select Fee...</option>
                        <?php foreach ($feeStructures as $f): ?>
                        <option value="<?php echo $f['id']; ?>" data-amount="<?php echo $f['amount']; ?>">
                            <?php echo e($f['name']); ?> — ৳<?php echo number_format($f['amount'], 2); ?> (<?php echo ucfirst(e($f['frequency'])); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="amount_paid" class="form-label">Amount Paid (৳) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="amount_paid" name="amount_paid" step="0.01" min="0" value="<?php echo e($data['amount_paid'] ?? ''); ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="payment_date" class="form-label">Payment Date</label>
                    <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?php echo e($data['payment_date'] ?? date('Y-m-d')); ?>">
                </div>
                <div class="col-md-4">
                    <label for="month_year" class="form-label">For Month</label>
                    <input type="month" class="form-control" id="month_year" name="month_year" value="<?php echo e($data['month_year'] ?? date('Y-m')); ?>">
                </div>
                <div class="col-md-4">
                    <label for="payment_method" class="form-label">Payment Method</label>
                    <select class="form-select" id="payment_method" name="payment_method">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="online">Online</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label for="notes" class="form-label">Notes</label>
                    <input type="text" class="form-control" id="notes" name="notes" value="<?php echo e($data['notes'] ?? ''); ?>" placeholder="Optional notes...">
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>?url=payments/index" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary-gradient"><i class="bi bi-check-circle me-2"></i>Record Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateAmount(select) {
    const option = select.options[select.selectedIndex];
    const amount = option.getAttribute('data-amount');
    if (amount) document.getElementById('amount_paid').value = amount;
}
</script>
