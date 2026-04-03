<?php
/**
 * HostelEase — Change Password View
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1">Change Password</h2>
            <p class="text-muted mb-0">Update your account security</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?url=profile/index" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-8 mx-auto">
        <div class="card card-glass">
            <div class="card-body p-4 p-md-5">
                
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger px-3 py-2 text-sm">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo BASE_URL; ?>?url=profile/changePassword" novalidate id="passwordForm">
                    <?php echo csrfField(); ?>

                    <div class="mb-4">
                        <label class="form-label">Current Password <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                    </div>
                    
                    <hr class="my-4 text-muted">

                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                        <div class="form-text">Must be at least 6 characters long.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="confirm_password" required minlength="6">
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary-gradient py-2">
                            <i class="bi bi-shield-lock me-2"></i>Update Password
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
