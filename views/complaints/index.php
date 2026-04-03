<?php /** HostelEase — Complaints List View */ ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="mb-1">Complaints</h2>
            <p class="text-muted mb-0">Manage support tickets</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?url=complaints/create" class="btn btn-primary-gradient">
            <i class="bi bi-plus-circle me-2"></i>Submit Complaint
        </a>
    </div>
</div>

<!-- SLA Overdue Alert -->
<?php if (!empty($overdueComplaints)): ?>
<div class="alert alert-danger d-flex align-items-center">
    <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
    <div>
        <strong><?php echo count($overdueComplaints); ?> SLA Overdue Ticket(s)!</strong>
        These tickets have exceeded their resolution deadline.
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card card-glass mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?php echo BASE_URL; ?>" class="row g-3 align-items-end">
            <input type="hidden" name="url" value="complaints/index">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">All</option>
                    <option value="open" <?php echo ($_GET['status'] ?? '') === 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="in_progress" <?php echo ($_GET['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="resolved" <?php echo ($_GET['status'] ?? '') === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    <option value="closed" <?php echo ($_GET['status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Priority</label>
                <select class="form-select" name="priority">
                    <option value="">All</option>
                    <option value="high" <?php echo ($_GET['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="medium" <?php echo ($_GET['priority'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="low" <?php echo ($_GET['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
                </select>
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
            <div class="col-md-2"><a href="<?php echo BASE_URL; ?>?url=complaints/index" class="btn btn-outline-secondary w-100">Clear</a></div>
        </form>
    </div>
</div>

<!-- Complaints Table -->
<div class="card card-glass">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="complaintsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($complaints ?? [] as $c): ?>
                    <?php
                        $priorityClass = match($c['priority']) { 'high' => 'danger', 'medium' => 'warning', 'low' => 'info', default => 'secondary' };
                        $statusClass = match($c['status']) { 'open' => 'primary', 'in_progress' => 'warning', 'resolved' => 'success', 'closed' => 'secondary', default => 'secondary' };
                        $isOverdue = false;
                        $hoursOpen = (time() - strtotime($c['created_at'])) / 3600;
                        if (in_array($c['status'], ['open', 'in_progress'])) {
                            if (($c['priority'] === 'high' && $hoursOpen > SLA_HIGH_PRIORITY_HOURS) ||
                                ($c['priority'] === 'medium' && $hoursOpen > SLA_MEDIUM_PRIORITY_HOURS)) {
                                $isOverdue = true;
                            }
                        }
                    ?>
                    <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                        <td><?php echo $c['id']; ?></td>
                        <td>
                            <strong><?php echo e($c['student_name']); ?></strong>
                            <br><small class="text-muted"><?php echo e($c['student_id_no']); ?></small>
                        </td>
                        <td><?php echo e($c['category']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $priorityClass; ?>-subtle text-<?php echo $priorityClass; ?>">
                                <?php echo ucfirst(e($c['priority'])); ?>
                            </span>
                            <?php if ($isOverdue): ?>
                            <i class="bi bi-clock text-danger ms-1" title="SLA Overdue"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $statusClass; ?>-subtle text-<?php echo $statusClass; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', e($c['status']))); ?>
                            </span>
                        </td>
                        <td><?php echo e($c['assigned_to_name'] ?? 'Unassigned'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                        <td class="text-end">
                            <a href="<?php echo BASE_URL; ?>?url=complaints/show/<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $extraScripts = '<script>$(function(){ if($.fn.DataTable) $("#complaintsTable").DataTable({pageLength:15,order:[[6,"desc"]]}); });</script>'; ?>
