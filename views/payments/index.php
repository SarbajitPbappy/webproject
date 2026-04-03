<?php
/** HostelEase — Payments List View */
$role = currentUser()['role'] ?? '';
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="mb-1">Payments</h2>
            <p class="text-muted mb-0">Track all payment transactions</p>
        </div>
        <?php if ($role === 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>?url=payments/record" class="btn btn-primary-gradient">
                <i class="bi bi-plus-circle me-2"></i>Record Payment
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter Bar -->
<div class="card card-glass mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?php echo BASE_URL; ?>" class="row g-3 align-items-end">
            <input type="hidden" name="url" value="payments/index">
            <div class="col-md-3">
                <label class="form-label">Student</label>
                <select class="form-select" name="student_id">
                    <option value="">All Students</option>
                    <?php foreach ($students as $s): ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo ($_GET['student_id'] ?? '') == $s['id'] ? 'selected' : ''; ?>>
                        <?php echo e($s['full_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Month</label>
                <input type="month" class="form-control" name="month_year" value="<?php echo e($_GET['month_year'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
            <div class="col-md-2">
                <a href="<?php echo BASE_URL; ?>?url=payments/index" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Payments Table -->
<div class="card card-glass">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="paymentsTable">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Student</th>
                        <th>Fee Type</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Month</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments ?? [] as $p): ?>
                    <tr>
                        <td><code><?php echo e($p['receipt_no']); ?></code></td>
                        <td>
                            <strong><?php echo e($p['full_name']); ?></strong>
                            <br><small class="text-muted"><?php echo e($p['student_id_no']); ?></small>
                        </td>
                        <td><?php echo e($p['fee_name']); ?></td>
                        <td class="fw-semibold">₹<?php echo number_format($p['amount_paid'], 2); ?></td>
                        <td><span class="badge bg-secondary-subtle text-secondary"><?php echo ucfirst(e($p['payment_method'])); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($p['payment_date'])); ?></td>
                        <td><?php echo e($p['month_year']); ?></td>
                        <td class="text-end">
                            <a href="<?php echo BASE_URL; ?>?url=payments/receiptView/<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Receipt">
                                <i class="bi bi-receipt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $extraScripts = '<script>$(function(){ if($.fn.DataTable) $("#paymentsTable").DataTable({pageLength:15,order:[[5,"desc"]]}); });</script>'; ?>
