<?php
/**
 * HostelEase — Create Student View
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1">Register New Student</h2>
            <p class="text-muted mb-0">Add a new student to the hostel system</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?url=students/index" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Please fix the following errors:</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($errors as $error): ?>
            <li><?php echo e($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card card-glass">
    <div class="card-body p-4">
        <form method="POST" action="<?php echo BASE_URL; ?>?url=students/store" enctype="multipart/form-data" novalidate>
            <?php echo csrfField(); ?>

            <div class="row g-4">
                <!-- Personal Information -->
                <div class="col-12">
                    <h5 class="section-title"><i class="bi bi-person me-2"></i>Personal Information</h5>
                </div>

                <div class="col-md-6">
                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo e($data['full_name'] ?? ''); ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo e($data['email'] ?? ''); ?>" required>
                </div>

                <div class="col-md-4">
                    <label for="student_id_no" class="form-label">Student ID <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="student_id_no" name="student_id_no" value="<?php echo e($data['student_id_no'] ?? ''); ?>" required placeholder="e.g. STU-2026-001">
                </div>

                <div class="col-md-4">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo e($data['phone'] ?? ''); ?>" placeholder="+880...">
                </div>

                <div class="col-md-4">
                    <label for="enrolled_date" class="form-label">Enrollment Date</label>
                    <input type="date" class="form-control" id="enrolled_date" name="enrolled_date" value="<?php echo e($data['enrolled_date'] ?? date('Y-m-d')); ?>">
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank for default: Student@123">
                    <small class="text-muted">Default password will be set if left blank.</small>
                </div>

                <!-- Guardian Information -->
                <div class="col-12 mt-4">
                    <h5 class="section-title"><i class="bi bi-people me-2"></i>Guardian Information</h5>
                </div>

                <div class="col-md-6">
                    <label for="guardian_name" class="form-label">Guardian Name</label>
                    <input type="text" class="form-control" id="guardian_name" name="guardian_name" value="<?php echo e($data['guardian_name'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label for="guardian_phone" class="form-label">Guardian Phone</label>
                    <input type="text" class="form-control" id="guardian_phone" name="guardian_phone" value="<?php echo e($data['guardian_phone'] ?? ''); ?>">
                </div>

                <!-- Documents & Photo -->
                <div class="col-12 mt-4">
                    <h5 class="section-title"><i class="bi bi-file-earmark me-2"></i>Documents & Photo</h5>
                </div>

                <div class="col-md-6">
                    <label for="profile_photo" class="form-label">Profile Photo</label>
                    <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept=".jpg,.jpeg,.png">
                    <small class="text-muted">Max 5MB. JPG, JPEG, or PNG only.</small>
                </div>

                <div class="col-md-6">
                    <label for="nid_or_card" class="form-label">NID / Student Card</label>
                    <input type="file" class="form-control" id="nid_or_card" name="nid_or_card" accept=".jpg,.jpeg,.png,.pdf">
                    <small class="text-muted">Max 5MB. JPG, PNG, or PDF.</small>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>?url=students/index" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary-gradient">
                    <i class="bi bi-check-circle me-2"></i>Register Student
                </button>
            </div>
        </form>
    </div>
</div>
