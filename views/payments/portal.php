<?php
/**
 * Pay against warden-issued billing slips only (meal, rent, utilities, deposit, etc.).
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1">Payment portal</h2>
            <p class="text-muted mb-0">Pay one issued bill at a time. Amounts come from the office—do not change them.</p>
        </div>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($pendingCharges as $c): ?>
    <div class="col-lg-6">
        <div class="card card-glass h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><?php echo e($c['fee_name']); ?></strong>
                <span class="badge bg-warning-subtle text-warning"><?php echo e($c['period_month']); ?></span>
            </div>
            <div class="card-body">
                <p class="display-6 mb-3">৳<?php echo number_format((float) $c['amount_due'], 2); ?></p>
                <p class="text-muted small mb-3">
                    Category: <?php echo e($c['fee_category'] ?? '—'); ?> · <?php echo e($c['frequency'] ?? ''); ?>
                </p>
                <form method="POST" action="<?php echo BASE_URL; ?>?url=payments/processPortal" class="border-top pt-3">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="billing_charge_id" value="<?php echo (int) $c['id']; ?>">

                    <h6 class="mb-3">Card (simulated)</h6>
                    <div class="mb-2">
                        <label class="form-label small">Name on card</label>
                        <input type="text" class="form-control form-control-sm" name="card_name" required
                               placeholder="<?php echo e($student['full_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Card number</label>
                        <input type="text" class="form-control form-control-sm" name="card_number" required maxlength="16" pattern="\d*">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small">Expiry</label>
                            <input type="text" class="form-control form-control-sm" name="expiry" placeholder="MM/YY" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">CVC</label>
                            <input type="text" class="form-control form-control-sm" name="cvv" maxlength="4" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary-gradient w-100">
                        Pay ৳<?php echo number_format((float) $c['amount_due'], 2); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
