<?php
/**
 * HostelEase — Students List View
 * Renders inside main layout via $viewContent
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="mb-1">Students</h2>
            <p class="text-muted mb-0">Manage all registered students</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?url=students/create" class="btn btn-primary-gradient">
            <i class="bi bi-plus-circle me-2"></i>Register New Student
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card card-glass mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?php echo BASE_URL; ?>" class="row g-3 align-items-end">
            <input type="hidden" name="url" value="students/index">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="Name, ID, or email..." value="<?php echo e($_GET['search'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo ($_GET['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="suspended" <?php echo ($_GET['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    <option value="inactive" <?php echo ($_GET['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
            </div>
            <div class="col-md-2">
                <a href="<?php echo BASE_URL; ?>?url=students/index" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Students Table -->
<div class="card card-glass">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="studentsTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Student ID</th>
                        <th>Phone</th>
                        <th>Room</th>
                        <th>Status</th>
                        <th>Enrolled</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students ?? [] as $student): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-sm">
                                    <?php if (!empty($student['profile_photo'])): ?>
                                        <img src="<?php echo BASE_URL . 'public/uploads/students/' . e($student['profile_photo']); ?>" alt="Photo" class="avatar-img">
                                    <?php else: ?>
                                        <div class="avatar-initials"><?php echo strtoupper(substr($student['full_name'], 0, 2)); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?php echo e($student['full_name']); ?></div>
                                    <small class="text-muted"><?php echo e($student['email']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><code><?php echo e($student['student_id_no']); ?></code></td>
                        <td><?php echo e($student['phone'] ?? '—'); ?></td>
                        <td>
                            <?php if (!empty($student['room_number'])): ?>
                                <span class="badge bg-info-subtle text-info"><?php echo e($student['room_number']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $statusClass = match($student['status']) {
                                'active'    => 'success',
                                'suspended' => 'warning',
                                'inactive'  => 'secondary',
                                default     => 'secondary',
                            };
                            ?>
                            <span class="badge bg-<?php echo $statusClass; ?>-subtle text-<?php echo $statusClass; ?>">
                                <?php echo ucfirst(e($student['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo $student['enrolled_date'] ? date('M d, Y', strtotime($student['enrolled_date'])) : '—'; ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo BASE_URL; ?>?url=students/show/<?php echo $student['id']; ?>" class="btn btn-outline-primary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>?url=students/edit/<?php echo $student['id']; ?>" class="btn btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="<?php echo BASE_URL; ?>?url=students/delete/<?php echo $student['id']; ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extraScripts = '<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $("#studentsTable").DataTable({
            pageLength: 15,
            order: [[5, "desc"]],
            language: { search: "", searchPlaceholder: "Quick search..." },
            dom: "<\"row\"<\"col-sm-6\"l><\"col-sm-6\"f>>rtip"
        });
    }
});
</script>';
?>
