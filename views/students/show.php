<?php
/**
 * HostelEase — Student Profile View
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="mb-1">Student Profile</h2>
            <p class="text-muted mb-0"><?php echo e($student['full_name']); ?></p>
        </div>
        <?php if (hasRole(['student'])): ?>
        <div class="d-flex gap-2">
            <a href="<?php echo BASE_URL; ?>?url=profile/edit" class="btn btn-outline-primary btn-sm">Quick edit</a>
            <a href="<?php echo BASE_URL; ?>?url=students/editSelf" class="btn btn-primary-gradient btn-sm">Edit profile &amp; room preference</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Profile Card -->
    <div class="col-lg-4">
        <div class="card card-glass text-center">
            <div class="card-body p-4">
                <div class="avatar-lg mx-auto mb-3">
                    <?php if (!empty($student['profile_photo'])): ?>
                        <img src="<?php echo BASE_URL . 'public/uploads/students/' . e($student['profile_photo']); ?>" alt="Photo" class="avatar-img-lg">
                    <?php else: ?>
                        <div class="avatar-initials-lg"><?php echo strtoupper(substr($student['full_name'], 0, 2)); ?></div>
                    <?php endif; ?>
                </div>
                <h4 class="mb-1"><?php echo e($student['full_name']); ?></h4>
                <p class="text-muted mb-2"><?php echo e($student['email']); ?></p>
                <span class="badge bg-<?php echo $student['status'] === 'active' ? 'success' : ($student['status'] === 'suspended' ? 'warning' : 'secondary'); ?>-subtle text-<?php echo $student['status'] === 'active' ? 'success' : ($student['status'] === 'suspended' ? 'warning' : 'secondary'); ?> fs-6">
                    <?php echo ucfirst(e($student['status'])); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Details -->
    <div class="col-lg-8">
        <div class="card card-glass">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Student Details</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="detail-label">Student ID</label>
                        <p class="detail-value"><code><?php echo e($student['student_id_no']); ?></code></p>
                    </div>
                    <div class="col-sm-6">
                        <label class="detail-label">Phone</label>
                        <p class="detail-value"><?php echo e($student['phone'] ?? '—'); ?></p>
                    </div>
                    <div class="col-sm-6">
                        <label class="detail-label">Enrollment Date</label>
                        <p class="detail-value"><?php echo $student['enrolled_date'] ? date('F d, Y', strtotime($student['enrolled_date'])) : '—'; ?></p>
                    </div>
                    <div class="col-sm-6">
                        <label class="detail-label">Checkout Date</label>
                        <p class="detail-value"><?php echo $student['checkout_date'] ? date('F d, Y', strtotime($student['checkout_date'])) : 'Still Enrolled'; ?></p>
                    </div>
                    <?php if (empty($studentActiveAllocation) && !empty($studentWaitlistRoomPref)): ?>
                    <div class="col-sm-6">
                        <label class="detail-label">Waitlist room preference</label>
                        <p class="detail-value"><?php echo ucfirst(e($studentWaitlistRoomPref)); ?> <?php if (hasRole(['student'])): ?><a href="<?php echo BASE_URL; ?>?url=students/editSelf" class="small">Change</a><?php endif; ?></p>
                    </div>
                    <?php elseif (empty($studentActiveAllocation) && hasRole(['student'])): ?>
                    <div class="col-12">
                        <p class="text-muted small mb-0">No room preference on file. <a href="<?php echo BASE_URL; ?>?url=students/editSelf">Set your preferred room type</a> before enrollment billing.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card card-glass mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-people me-2"></i>Guardian Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="detail-label">Guardian Name</label>
                        <p class="detail-value"><?php echo e($student['guardian_name'] ?? '—'); ?></p>
                    </div>
                    <div class="col-sm-6">
                        <label class="detail-label">Guardian Phone</label>
                        <p class="detail-value"><?php echo e($student['guardian_phone'] ?? '—'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
