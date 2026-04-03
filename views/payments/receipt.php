<?php /** HostelEase — Payment Receipt View */ ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div><h2 class="mb-1">Payment Receipt</h2><p class="text-muted mb-0"><?php echo e($payment['receipt_no']); ?></p></div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-primary"><i class="bi bi-printer me-1"></i>Print</button>
            <a href="<?php echo BASE_URL; ?>?url=payments/index" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
    </div>
</div>

<div class="card card-glass" id="receiptCard">
    <div class="card-body p-5">
        <!-- Receipt Header -->
        <div class="text-center mb-4 pb-3 border-bottom">
            <h2 class="fw-bold text-primary"><?php echo APP_NAME; ?></h2>
            <p class="text-muted mb-1">Hostel Management System</p>
            <h4 class="mt-3">PAYMENT RECEIPT</h4>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <table class="table table-borderless table-sm">
                    <tr><td class="text-muted">Receipt No:</td><td class="fw-semibold"><?php echo e($payment['receipt_no']); ?></td></tr>
                    <tr><td class="text-muted">Date:</td><td><?php echo date('F d, Y', strtotime($payment['payment_date'])); ?></td></tr>
                    <tr><td class="text-muted">Period:</td><td><?php echo e($payment['month_year']); ?></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless table-sm">
                    <tr><td class="text-muted">Student:</td><td class="fw-semibold"><?php echo e($payment['full_name']); ?></td></tr>
                    <tr><td class="text-muted">Student ID:</td><td><?php echo e($payment['student_id_no']); ?></td></tr>
                    <tr><td class="text-muted">Phone:</td><td><?php echo e($payment['phone'] ?? '—'); ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Payment Details -->
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Description</th>
                    <th>Frequency</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo e($payment['fee_name']); ?></td>
                    <td><?php echo ucfirst(e($payment['frequency'])); ?></td>
                    <td class="text-end fw-semibold">₹<?php echo number_format($payment['amount_paid'], 2); ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="table-primary">
                    <td colspan="2" class="text-end fw-bold">Total Paid:</td>
                    <td class="text-end fw-bold fs-5">₹<?php echo number_format($payment['amount_paid'], 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="row mt-4">
            <div class="col-md-6">
                <p class="text-muted small mb-1">Payment Method: <strong><?php echo ucfirst(e($payment['payment_method'])); ?></strong></p>
                <?php if ($payment['notes']): ?>
                <p class="text-muted small mb-0">Notes: <?php echo e($payment['notes']); ?></p>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-end">
                <p class="text-muted small mb-1">Recorded by: <?php echo e($payment['recorded_by_name']); ?></p>
                <p class="text-muted small mb-0">Generated: <?php echo date('F d, Y H:i A'); ?></p>
            </div>
        </div>

        <div class="text-center mt-5 pt-4 border-top">
            <p class="text-muted small">This is a computer-generated receipt. No signature is required.</p>
            <p class="text-muted small">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?></p>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .top-navbar, .main-footer, .content-header, .no-print { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; }
    .content-area { padding: 0 !important; }
    #receiptCard { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>
