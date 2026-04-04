<?php
/**
 * Pay outstanding slips and/or prepay monthly fees before slips are issued.
 */
$grandTotal = 0.0;
foreach ($pendingCharges ?? [] as $c) {
    $grandTotal += (float) $c['amount_due'];
}
$slipCount = count($pendingCharges ?? []);
$prepayFees = $prepayFees ?? [];
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="mb-1">Payment portal</h2>
            <p class="text-muted mb-0">Pay <strong>posted slips</strong> and optionally <strong>prepay</strong> eligible fees for a future billing month—duplicates are skipped when the office issues bills.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?url=payments/balanceSheet" class="btn btn-outline-secondary btn-sm">Balance sheet</a>
    </div>
</div>

<div class="row g-4">
    <?php if (!empty($pendingCharges)): ?>
    <div class="col-lg-5">
        <div class="card card-glass border-primary border-2 h-100">
            <div class="card-header bg-primary-subtle">
                <strong><i class="bi bi-wallet2 me-2"></i>Pay all posted slips</strong>
            </div>
            <div class="card-body">
                <p class="display-5 mb-1">৳<?php echo number_format($grandTotal, 2); ?></p>
                <p class="text-muted mb-4"><?php echo (int) $slipCount; ?> bill<?php echo $slipCount === 1 ? '' : 's'; ?> · one transaction</p>

                <form method="POST" action="<?php echo BASE_URL; ?>?url=payments/processPortalAll" id="payAllForm">
                    <?php echo csrfField(); ?>

                    <h6 class="mb-3">Card (simulated)</h6>
                    <div class="mb-2">
                        <label class="form-label small">Name on card</label>
                        <input type="text" class="form-control" name="card_name" required autocomplete="cc-name"
                               placeholder="<?php echo e($student['full_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Card number</label>
                        <input type="text" class="form-control" name="card_number" required maxlength="19" inputmode="numeric" pattern="[\d\s]*" autocomplete="cc-number">
                    </div>
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <label class="form-label small">Expiry</label>
                            <input type="text" class="form-control" name="expiry" placeholder="MM/YY" required autocomplete="cc-exp">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">CVC</label>
                            <input type="text" class="form-control" name="cvv" maxlength="4" required autocomplete="cc-csc">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary-gradient w-100 btn-lg">
                        Pay all ৳<?php echo number_format($grandTotal, 2); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card card-glass h-100">
            <div class="card-header">
                <strong>Outstanding slips</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Fee</th>
                                <th>Period</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingCharges as $c): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo e($c['fee_name']); ?></div>
                                    <small class="text-muted"><?php echo e($c['fee_category'] ?? '—'); ?> · <?php echo e($c['frequency'] ?? ''); ?></small>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-secondary"><?php echo e($c['period_month']); ?></span></td>
                                <td class="text-end fw-semibold">৳<?php echo number_format((float) $c['amount_due'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2">Total</th>
                                <th class="text-end">৳<?php echo number_format($grandTotal, 2); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="col-12">
        <div class="alert alert-info mb-0">
            <strong>No posted slips right now.</strong> You can still use advance payment below for eligible monthly fees. After the warden runs monthly billing, fees you already prepaid for that month will not be charged again.
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($prepayFees)): ?>
<hr class="my-5">
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-glass border-success border-2 h-100">
            <div class="card-header bg-success-subtle">
                <strong><i class="bi bi-calendar-check me-2"></i>Advance payment</strong>
            </div>
            <div class="card-body">
                <p class="text-muted small">Choose a fee and the <strong>billing month</strong> (YYYY-MM) it applies to—usually before the office posts that month&rsquo;s slips. Room rent prepay matches your <strong>current room tier</strong>.</p>
                <form method="POST" action="<?php echo BASE_URL; ?>?url=payments/processPrepay" novalidate>
                    <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label class="form-label">Fee</label>
                        <select class="form-select" name="fee_id" required>
                            <?php foreach ($prepayFees as $f): ?>
                            <option value="<?php echo (int) $f['id']; ?>">
                                <?php echo e($f['name']); ?> — ৳<?php echo number_format((float) $f['amount'], 2); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Billing month</label>
                        <input type="month" class="form-control" name="period_month" required
                               value="<?php echo e(date('Y-m')); ?>"
                               min="<?php echo e(date('Y-m', strtotime('-1 month'))); ?>">
                    </div>
                    <h6 class="mb-2">Card (simulated)</h6>
                    <div class="mb-2">
                        <label class="form-label small">Name on card</label>
                        <input type="text" class="form-control" name="card_name" required autocomplete="cc-name"
                               placeholder="<?php echo e($student['full_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Card number</label>
                        <input type="text" class="form-control" name="card_number" required maxlength="19" inputmode="numeric" autocomplete="cc-number">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small">Expiry</label>
                            <input type="text" class="form-control" name="expiry" placeholder="MM/YY" required autocomplete="cc-exp">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">CVC</label>
                            <input type="text" class="form-control" name="cvv" maxlength="4" required autocomplete="cc-csc">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Pay advance (catalog amount)</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-glass h-100">
            <div class="card-header"><strong>Eligible fees for prepay</strong></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($prepayFees as $f): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?php echo e($f['name']); ?> <small class="text-muted">(<?php echo e($f['frequency'] ?? ''); ?>)</small></span>
                        <span class="fw-semibold">৳<?php echo number_format((float) $f['amount'], 2); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
