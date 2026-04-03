<?php
/**
 * HostelEase — Staff Payroll Dashboard
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1"><i class="bi bi-wallet2 me-2"></i>My Payroll</h2>
            <p class="text-muted mb-0">View your salary details and apply for payslips.</p>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Base Details -->
    <div class="col-md-5">
        <div class="card card-glass h-100 border-start border-primary border-5">
            <div class="card-body">
                <h5 class="text-muted text-uppercase small fw-bold mb-3">Employment Details</h5>
                <div class="mb-3">
                    <label class="text-muted small">Join Date</label>
                    <div class="fw-bold fs-5"><?php echo date('F d, Y', strtotime($staffDetails['join_date'])); ?></div>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">Raw Base Salary</label>
                    <div class="fw-semibold text-secondary">$<?php echo number_format($staffDetails['basic_salary'], 2); ?></div>
                </div>
                <div>
                    <label class="text-muted small d-block">Base Salary with +1.5% APY Compounding</label>
                    <span class="badge bg-success-subtle text-success fs-3 px-3 py-2 mt-1">
                        $<?php echo number_format($staffDetails['current_salary'], 2); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Application Call to Action -->
    <div class="col-md-7">
        <div class="card card-glass h-100 shadow-sm align-items-center justify-content-center text-center p-4">
            <?php if ($hasAppliedThisMonth): ?>
                <div class="mb-3"><i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i></div>
                <h4 class="mb-2">Application Submitted!</h4>
                <p class="text-muted">You have already applied for your <b><?php echo date('F Y'); ?></b> pay slip.</p>
                <p class="small text-muted mb-0">Admins will review and Super Admin will disburse it shortly.</p>
            <?php else: ?>
                <div class="mb-3"><i class="bi bi-calendar-check text-primary" style="font-size: 3rem;"></i></div>
                <h4 class="mb-2">Ready to Apply?</h4>
                <p class="text-muted mb-4">Request your payroll slip for the current month: <b class="text-dark"><?php echo date('F Y'); ?></b></p>
                
                <form method="POST" action="<?php echo BASE_URL; ?>?url=payroll/apply">
                    <?php echo csrfField(); ?>
                    <button type="submit" class="btn btn-primary-gradient px-4 py-2 fs-5 shadow-sm">
                        Submit Pay Slip Request <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- History -->
<div class="card card-glass border-0 shadow-sm mt-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Pay Slip History</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Month</th>
                        <th>Base Salary</th>
                        <th>Bonus</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th class="text-end pe-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No slip history found.</td></tr>
                    <?php else: ?>
                        <?php foreach($history as $h): ?>
                        <tr>
                            <td class="ps-4"><span class="fw-semibold text-primary"><?php echo date('F Y', strtotime($h['month_year'].'-01')); ?></span></td>
                            <td>$<?php echo number_format($h['basic_salary'], 2); ?></td>
                            <td class="text-success">+$<?php echo number_format($h['performance_bonus'], 2); ?></td>
                            <td class="text-danger">-$<?php echo number_format($h['deductions'], 2); ?></td>
                            <td class="fw-bold">$<?php echo number_format($h['net_salary'], 2); ?></td>
                            <td class="text-end pe-4">
                                <?php 
                                    $badge = match($h['status']) {
                                        'applied' => 'bg-info-subtle text-info',
                                        'approved' => 'bg-warning-subtle text-warning',
                                        'paid' => 'bg-success-subtle text-success',
                                        default => 'bg-secondary'
                                    };
                                ?>
                                <span class="badge <?php echo $badge; ?> px-2 py-1 fs-7">
                                    <?php echo strtoupper($h['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
