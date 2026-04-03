<?php
/**
 * HostelEase — Payroll Review View for Admin
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1"><i class="bi bi-clipboard-check me-2"></i>Review Pay Slips</h2>
            <p class="text-muted mb-0">Evaluate staff performance and approve salaries</p>
        </div>
    </div>
</div>

<div class="card card-glass">
    <div class="card-header bg-white">
        <h5 class="mb-0">Pending Applications</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Staff Member</th>
                        <th>Month</th>
                        <th>Base Salary</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pendingSlips)): ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">No pending slips to review.</td></tr>
                    <?php else: ?>
                        <?php foreach($pendingSlips as $slip): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold"><?php echo e($slip['full_name']); ?></div>
                                <div class="small text-muted"><span class="badge bg-secondary-subtle text-secondary me-2"><?php echo e(ucfirst($slip['role'])); ?></span></div>
                            </td>
                            <td><span class="badge bg-primary-subtle text-primary"><?php echo date('F Y', strtotime($slip['month_year'].'-01')); ?></span></td>
                            <td class="fw-semibold">$<?php echo number_format($slip['basic_salary'], 2); ?></td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reviewModal<?php echo $slip['id']; ?>">
                                    Review & Approve
                                </button>
                            </td>
                        </tr>

                        <!-- Review Modal -->
                        <div class="modal fade" id="reviewModal<?php echo $slip['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="<?php echo BASE_URL; ?>?url=payroll/processApproval">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="slip_id" value="<?php echo $slip['id']; ?>">
                                        <div class="modal-header border-bottom-0 pb-0">
                                            <h5 class="modal-title">Review Salary for <?php echo e($slip['full_name']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label text-muted small text-uppercase">Month</label>
                                                <div class="fw-bold fs-5"><?php echo date('F Y', strtotime($slip['month_year'].'-01')); ?></div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-muted small text-uppercase">Base Salary</label>
                                                <div class="fw-bold fs-5 text-success">$<?php echo number_format($slip['basic_salary'], 2); ?></div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Office Days</label>
                                                <input type="number" name="office_days" class="form-control" value="22" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Performance Bonus ($)</label>
                                                <input type="number" step="0.01" name="performance_bonus" class="form-control text-success" value="0.00" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Deductions ($)</label>
                                                <input type="number" step="0.01" name="deductions" class="form-control text-danger" value="0.00" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Notes</label>
                                                <textarea name="admin_notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary-gradient">Approve Slip</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
