<?php /** Issue monthly / recurring hall billing slips */ ?>

<div class="content-header">
    <h2 class="mb-1">Issue monthly bills</h2>
    <p class="text-muted mb-0">
        After month-end (or on your schedule), select fees and create <strong>pending slips</strong> for residents.
        <strong>Room rent</strong> lines (Single / Double / Triple / Dormitory) are issued only to students currently allocated to that room type; meal, utility, and other fees go to everyone you include in the scope.
    </p>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card card-glass">
    <div class="card-body p-4">
        <form method="POST" action="<?php echo BASE_URL; ?>?url=billing/issueMonthly">
            <?php echo csrfField(); ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Billing month <span class="text-danger">*</span></label>
                    <input type="month" name="period_month" class="form-control" required
                           value="<?php echo e($_POST['period_month'] ?? date('Y-m')); ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Who receives slips <span class="text-danger">*</span></label>
                    <select name="scope" class="form-select">
                        <option value="residents" <?php echo (($_POST['scope'] ?? '') === 'all_active') ? '' : 'selected'; ?>>
                            Only students with an active room allocation
                        </option>
                        <option value="all_active" <?php echo (($_POST['scope'] ?? '') === 'all_active') ? 'selected' : ''; ?>>
                            All active student accounts (including not yet allocated)
                        </option>
                    </select>
                </div>
            </div>

            <label class="form-label">Fees to include <span class="text-danger">*</span></label>
            <p class="text-muted small">Tick every component you want on this run (e.g. all four monthly rent tiers plus dining and utility). Each student receives only the rent line that matches their allocated room.</p>

            <?php if (empty($fees)): ?>
            <div class="alert alert-warning">No eligible fee types found. Add active fees in the database (monthly/yearly, not only security deposit).</div>
            <?php else: ?>
            <div class="row g-2 mb-4">
                <?php foreach ($fees as $fee): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="form-check border rounded p-3 h-100">
                        <input class="form-check-input" type="checkbox" name="fee_ids[]" value="<?php echo (int) $fee['id']; ?>"
                               id="fee_<?php echo (int) $fee['id']; ?>"
                            <?php echo !empty($_POST['fee_ids']) && in_array((string) $fee['id'], array_map('strval', (array) $_POST['fee_ids']), true) ? 'checked' : ''; ?>>
                        <label class="form-check-label w-100" for="fee_<?php echo (int) $fee['id']; ?>">
                            <strong><?php echo e($fee['name']); ?></strong>
                            <br><small class="text-muted">৳<?php echo number_format((float) $fee['amount'], 2); ?>
                            · <?php echo e($fee['frequency']); ?>
                            · <?php echo e($fee['fee_category'] ?? 'other'); ?></small>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary-gradient"><i class="bi bi-receipt me-2"></i>Generate slips</button>
        </form>
    </div>
</div>
