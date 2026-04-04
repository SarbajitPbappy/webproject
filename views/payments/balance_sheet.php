<?php /** HostelEase — Student fee balance sheet */ ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="mb-1">Fee balance sheet</h2>
            <p class="text-muted mb-0">Your room tier, office credits, open slips, prepayments, and paid slip history.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?url=payments/makePayment" class="btn btn-primary">Pay / prepay</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card card-glass h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small">Room rent credit</h6>
                <p class="display-6 mb-0">৳<?php echo number_format((float) $creditBalance, 2); ?></p>
                <p class="small text-muted mb-0 mt-2">Applied automatically to your next tier-matched room rent slip when bills are issued. Yearly fees (e.g. annual maintenance) are tracked per calendar year when you prepay or pay slips.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-glass h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small">Outstanding slips</h6>
                <p class="display-6 mb-0">৳<?php echo number_format((float) $pendingSlipTotal, 2); ?></p>
                <p class="small text-muted mb-0 mt-2"><?php echo count($pendingCharges); ?> bill line(s) waiting · <a href="<?php echo BASE_URL; ?>?url=payments/makePayment">Pay now</a></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-glass h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small">Current room</h6>
                <?php if (!empty($allocation)): ?>
                <p class="fs-4 fw-bold mb-0"><?php echo e($allocation['room_number']); ?></p>
                <p class="small text-muted mb-0"><?php echo ucfirst(e($allocation['room_type'] ?? '')); ?> · Floor <?php echo e($allocation['floor'] ?? '—'); ?></p>
                <?php else: ?>
                <p class="mb-0 text-muted">No active allocation</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card card-glass mb-4">
    <div class="card-header"><strong>Pending bills (warden slips)</strong></div>
    <div class="card-body p-0">
        <?php if (empty($pendingCharges)): ?>
        <p class="text-muted p-3 mb-0">None.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Fee</th><th>Period</th><th class="text-end">Due</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingCharges as $c): ?>
                    <tr>
                        <td><?php echo e($c['fee_name']); ?></td>
                        <td><code><?php echo e($c['period_month']); ?></code></td>
                        <td class="text-end">৳<?php echo number_format((float) $c['amount_due'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card card-glass mb-4">
    <div class="card-header"><strong>Payment history</strong> <span class="text-muted fw-normal small">(includes advance prepayments)</span></div>
    <div class="card-body p-0">
        <?php if (empty($payments)): ?>
        <p class="text-muted p-3 mb-0">No payments yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Fee</th>
                        <th>Billing month</th>
                        <th class="text-end">Amount</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?php echo e(date('M j, Y', strtotime($p['payment_date']))); ?></td>
                        <td><?php echo e($p['fee_name']); ?></td>
                        <td><code><?php echo e($p['month_year'] ?? '—'); ?></code></td>
                        <td class="text-end">৳<?php echo number_format((float) $p['amount_paid'], 2); ?></td>
                        <td><a href="<?php echo BASE_URL; ?>?url=payments/receiptView/<?php echo (int) $p['id']; ?>"><?php echo e($p['receipt_no']); ?></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card card-glass">
    <div class="card-header"><strong>Paid slips (history)</strong></div>
    <div class="card-body p-0">
        <?php if (empty($paidCharges)): ?>
        <p class="text-muted p-3 mb-0">No cleared slips yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Fee</th><th>Period</th><th class="text-end">Was due</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($paidCharges as $c): ?>
                    <tr>
                        <td><?php echo e($c['fee_name']); ?></td>
                        <td><code><?php echo e($c['period_month']); ?></code></td>
                        <td class="text-end">৳<?php echo number_format((float) $c['amount_due'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
