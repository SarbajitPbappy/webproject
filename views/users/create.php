<?php /** HostelEase — Create User */ ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1">Create User</h2>
            <p class="text-muted mb-0">Add a new admin, staff, or student to the system</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?url=users/index" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Users
        </a>
    </div>
</div>

<div class="row">
    <div class="col-xl-8 col-lg-10">
        <div class="card card-glass">
            <div class="card-body p-4 p-md-5">

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo BASE_URL; ?>?url=users/store" enctype="multipart/form-data" novalidate id="createUserForm">
                    <?php echo csrfField(); ?>

                    <h5 class="mb-4 text-primary">Login Information</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="full_name" value="<?php echo e($_POST['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Temporary Password <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="password" value="Role@123" required>
                            <div class="form-text">User will be able to change this later.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" name="role" id="roleSelect" required>
                                <option value="">Select Role...</option>
                                <option value="super_admin" <?php echo ($_POST['role'] ?? '') === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="staff" <?php echo ($_POST['role'] ?? '') === 'staff' ? 'selected' : ''; ?>>Staff (Maintenance)</option>
                                <option value="student" <?php echo ($_POST['role'] ?? '') === 'student' ? 'selected' : ''; ?>>Student</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Student Specific Fields (Hidden by default) -->
                    <div id="studentFields" style="display: none;" class="mt-4 pt-3 border-top">
                        <h5 class="mb-4 text-success"><i class="bi bi-mortarboard me-2"></i>Student Details</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Student ID No.</label>
                                <input type="text" class="form-control" name="student_id_no" value="<?php echo e($_POST['student_id_no'] ?? ''); ?>" placeholder="Leave blank to auto-generate">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" name="phone" value="<?php echo e($_POST['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Guardian Name</label>
                                <input type="text" class="form-control" name="guardian_name" value="<?php echo e($_POST['guardian_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Guardian Phone</label>
                                <input type="text" class="form-control" name="guardian_phone" value="<?php echo e($_POST['guardian_phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Enrollment Date</label>
                                <input type="date" class="form-control" name="enrolled_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Profile Photo (Optional)</label>
                                <input type="file" class="form-control" name="profile_photo" accept="image/jpeg,image/png,image/webp">
                            </div>
                        </div>
                    </div>

                    <!-- Hostel Occupant Billing Fields (Hidden by default for admin/staff) -->
                    <div id="occupantFields" style="display: none;" class="mt-4 pt-3 border-top">
                        <h5 class="mb-3 text-info">
                            <i class="bi bi-wallet2 me-2"></i>Room Allocation & Fee Billing
                        </h5>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="create_occupant_profile" id="createOccupantProfile" value="1"
                                <?php echo isset($_POST['create_occupant_profile']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="createOccupantProfile">
                                Register this admin/staff as a hostel occupant (enables room allocation + fee payments)
                            </label>
                        </div>
                        <div class="form-text">
                            A students-profile record will be created automatically for room allocation and fee billing.
                        </div>
                    </div>

                    <div class="mt-5 text-end border-top pt-4">
                        <a href="<?php echo BASE_URL; ?>?url=users/index" class="btn btn-light me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary-gradient px-5">
                            <i class="bi bi-person-plus me-2"></i>Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = '<script>
document.addEventListener("DOMContentLoaded", function() {
    const roleSelect = document.getElementById("roleSelect");
    const studentFields = document.getElementById("studentFields");
    const occupantFields = document.getElementById("occupantFields");
    
    function toggleFields() {
        if (roleSelect.value === "student") {
            studentFields.style.display = "block";
            occupantFields.style.display = "none";
        } else {
            studentFields.style.display = "none";
            if (roleSelect.value === "admin" || roleSelect.value === "staff") {
                occupantFields.style.display = "block";
            } else {
                occupantFields.style.display = "none";
            }
        }
    }
    
    roleSelect.addEventListener("change", toggleFields);
    toggleFields(); // Run on load
});
</script>';
?>
