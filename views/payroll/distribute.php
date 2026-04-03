<?php
/**
 * HostelEase — Payroll Distribute View for Super Admin
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1"><i class="bi bi-cash-stack me-2 text-warning"></i>Distribute Salaries</h2>
            <p class="text-muted mb-0">Disburse payments for approved pay slips.</p>
        </div>
    </div>
</div>

<div class="card card-glass border-warning border-top border-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">Approved Slips Ready For Payment</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Staff Member</th>
                        <th>Month</th>
                        <th>Basic</th>
                        <th>Bonus</th>
                        <th>Deduct</th>
                        <th>Net Payable</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($approvedSlips)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No approved slips pending payment.</td></tr>
                    <?php else: ?>
                        <?php foreach($approvedSlips as $slip): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold"><?php echo e($slip['full_name']); ?></div>
                                <div class="small text-muted"><span class="badge bg-secondary-subtle text-secondary me-2"><?php echo e(ucfirst($slip['role'])); ?></span></div>
                            </td>
                            <td><span class="badge bg-warning-subtle text-warning"><?php echo date('F Y', strtotime($slip['month_year'].'-01')); ?></span></td>
                            <td>$<?php echo number_format($slip['basic_salary'], 2); ?></td>
                            <td class="text-success">+$<?php echo number_format($slip['performance_bonus'], 2); ?></td>
                            <td class="text-danger">-$<?php echo number_format($slip['deductions'], 2); ?></td>
                            <td class="fw-bold fs-5">$<?php echo number_format($slip['net_salary'], 2); ?></td>
                            <td class="text-end pe-4">
                                <form method="POST" action="<?php echo BASE_URL; ?>?url=payroll/pay" class="d-inline">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="slip_id" value="<?php echo $slip['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary-gradient shadow-sm" onclick="return confirm('Disburse $<?php echo number_format($slip['net_salary'], 2); ?> to <?php echo e($slip['full_name']); ?>? This will deduct from hostel funds.');">
                                        Payout Now
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
