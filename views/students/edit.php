<?php
/**
 * HostelEase — Edit Student View
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1">Edit Student</h2>
            <p class="text-muted mb-0">Update student information</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?url=students/show/<?php echo e($student['id']); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Profile
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
            <li><?php echo e($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card card-glass">
    <div class="card-body p-4">
        <form method="POST" action="<?php echo BASE_URL; ?>?url=students/update/<?php echo $student['id']; ?>" enctype="multipart/form-data" novalidate>
            <?php echo csrfField(); ?>
            <input type="hidden" name="id" value="<?php echo $student['id']; ?>">

            <div class="row g-4">
                <div class="col-12">
                    <h5 class="section-title"><i class="bi bi-person me-2"></i>Personal Information</h5>
                </div>

                <div class="col-md-6">
                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo e($data['full_name'] ?? $student['full_name']); ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo e($data['email'] ?? $student['email']); ?>" required>
                </div>

                <div class="col-md-4">
                    <label for="student_id_no" class="form-label">Student ID <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="student_id_no" name="student_id_no" value="<?php echo e($data['student_id_no'] ?? $student['student_id_no']); ?>" required>
                </div>

                <div class="col-md-4">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo e($data['phone'] ?? $student['phone']); ?>">
                </div>

                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo ($data['status'] ?? $student['status']) === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="suspended" <?php echo ($data['status'] ?? $student['status']) === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        <option value="inactive" <?php echo ($data['status'] ?? $student['status']) === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="enrolled_date" class="form-label">Enrollment Date</label>
                    <input type="date" class="form-control" id="enrolled_date" name="enrolled_date" value="<?php echo e($data['enrolled_date'] ?? $student['enrolled_date']); ?>">
                </div>

                <div class="col-12 mt-4">
                    <h5 class="section-title"><i class="bi bi-people me-2"></i>Guardian Information</h5>
                </div>

                <div class="col-md-6">
                    <label for="guardian_name" class="form-label">Guardian Name</label>
                    <input type="text" class="form-control" id="guardian_name" name="guardian_name" value="<?php echo e($data['guardian_name'] ?? $student['guardian_name']); ?>">
                </div>

                <div class="col-md-6">
                    <label for="guardian_phone" class="form-label">Guardian Phone</label>
                    <input type="text" class="form-control" id="guardian_phone" name="guardian_phone" value="<?php echo e($data['guardian_phone'] ?? $student['guardian_phone']); ?>">
                </div>

                <div class="col-12 mt-4">
                    <h5 class="section-title"><i class="bi bi-file-earmark me-2"></i>Photo</h5>
                </div>

                <div class="col-md-6">
                    <?php if (!empty($student['profile_photo'])): ?>
                    <div class="mb-3">
                        <img src="<?php echo BASE_URL . 'public/uploads/students/' . e($student['profile_photo']); ?>" alt="Current Photo" class="img-thumbnail" style="max-height: 120px;">
                        <p class="text-muted small mt-1">Current photo</p>
                    </div>
                    <?php endif; ?>
                    <label for="profile_photo" class="form-label">Change Photo</label>
                    <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept=".jpg,.jpeg,.png">
                    <small class="text-muted">Leave blank to keep current photo.</small>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>?url=students/index" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary-gradient">
                    <i class="bi bi-check-circle me-2"></i>Update Student
                </button>
            </div>
        </form>
    </div>
</div>
