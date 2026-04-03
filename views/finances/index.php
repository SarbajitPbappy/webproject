<?php
/**
 * HostelEase — Finances Dashboard View
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1"><i class="bi bi-bank2 me-2"></i>Global Finances</h2>
            <p class="text-muted mb-0">Track all income and expenditure ledgers</p>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card card-glass text-center border-bottom border-success border-5">
            <div class="card-body">
                <p class="text-muted text-uppercase fw-bold mb-1">Total Income (Student Fees)</p>
                <h3 class="text-success">$<?php echo number_format($income, 2); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-glass text-center border-bottom border-danger border-5">
            <div class="card-body">
                <p class="text-muted text-uppercase fw-bold mb-1">Total Expenditure (Salaries)</p>
                <h3 class="text-danger">$<?php echo number_format($expense, 2); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-glass text-center border-bottom border-primary border-5 bg-primary-subtle">
            <div class="card-body">
                <p class="text-primary-emphasis text-uppercase fw-bold mb-1">Hostel Bank Balance</p>
                <h3 class="text-primary">$<?php echo number_format($balance, 2); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card card-glass">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-pass me-2"></i>Recent Transactions</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th class="text-end pe-4">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No transactions found.</td></tr>
                    <?php else: ?>
                        <?php foreach($transactions as $t): ?>
                        <tr>
                            <td class="ps-4"><?php echo date('M d, Y h:i A', strtotime($t['created_at'])); ?></td>
                            <td>
                                <?php echo e($t['description']); ?><br>
                                <small class="text-muted">Recorded by <?php echo e($t['recorder_name'] ?? 'System'); ?></small>
                            </td>
                            <td>
                                <?php if ($t['type'] === 'income'): ?>
                                    <span class="badge bg-success-subtle text-success">Income</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger">Expense</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary-subtle text-secondary"><?php echo e($t['category']); ?></span></td>
                            <td class="text-end pe-4 fw-bold <?php echo $t['type'] === 'income' ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $t['type'] === 'income' ? '+' : '-'; ?>$<?php echo number_format($t['amount'], 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
