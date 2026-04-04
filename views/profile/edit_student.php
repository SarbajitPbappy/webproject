<?php
/**
 * HostelEase — Edit Student Profile View (Self Service)
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1">Edit My Profile</h2>
            <p class="text-muted mb-0">Update your account information</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?url=students/show" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Profile
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-glass">
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

    <form action="<?php echo BASE_URL; ?>?url=students/editSelf" method="POST" enctype="multipart/form-data">
        <?php echo csrfField(); ?>
                    
                    <h5 class="mb-3 text-primary">Personal Details</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo e($_POST['full_name'] ?? $student['full_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo e($_POST['phone'] ?? $student['phone']); ?>" required>
                        </div>
                    </div>
                    
                    <h5 class="mb-3 mt-4 text-primary">Guardian Information</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                             <label for="guardian_name" class="form-label">Guardian Name</label>
                             <input type="text" class="form-control" id="guardian_name" name="guardian_name" value="<?php echo e($_POST['guardian_name'] ?? $student['guardian_name']); ?>">
                        </div>
                        <div class="col-md-6">
                             <label for="guardian_phone" class="form-label">Guardian Phone</label>
                             <input type="text" class="form-control" id="guardian_phone" name="guardian_phone" value="<?php echo e($_POST['guardian_phone'] ?? $student['guardian_phone']); ?>">
                        </div>
                    </div>

                    <?php if (!empty($canChangeRoomPref)): ?>
                    <?php
                    $vac = $vacancyByType ?? ['single' => 0, 'double' => 0, 'triple' => 0, 'dormitory' => 0];
                    $wpt = $waitlistPreferredType ?? '';
                    $rp = $_POST['preferred_room_type'] ?? ($wpt !== '' ? $wpt : 'single');
                    ?>
                    <h5 class="mb-3 mt-4 text-primary"><i class="bi bi-door-open me-2"></i>Room waitlist preference</h5>
                    <p class="text-muted small">You are not allocated a room yet. Update this if you chose the wrong tier at registration (used for your enrollment rent slip).</p>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="preferred_room_type" class="form-label">Preferred room type</label>
                            <select class="form-select" id="preferred_room_type" name="preferred_room_type" required>
                                <option value="single" <?php echo $rp === 'single' ? 'selected' : ''; ?>>Single (<?php echo (int) $vac['single']; ?> beds free)</option>
                                <option value="double" <?php echo $rp === 'double' ? 'selected' : ''; ?>>Double (<?php echo (int) $vac['double']; ?> beds free)</option>
                                <option value="triple" <?php echo $rp === 'triple' ? 'selected' : ''; ?>>Triple (<?php echo (int) $vac['triple']; ?> beds free)</option>
                                <option value="dormitory" <?php echo $rp === 'dormitory' ? 'selected' : ''; ?>>Dormitory (<?php echo (int) $vac['dormitory']; ?> beds free)</option>
                            </select>
                            <div class="form-text">If bills were already issued for another tier, ask the office to remove and re-issue them.</div>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="text-muted small mt-4 mb-0"><i class="bi bi-info-circle me-1"></i>You have an active room allocation. To change category, contact the hostel office.</p>
                    <?php endif; ?>

                    <h5 class="mb-3 mt-4 text-primary">Profile Image</h5>
                    <div class="mb-3">
                        <label for="profile_photo" class="form-label">Update Profile Photo</label>
                        <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                        <div class="form-text">Leave empty to keep current photo. JPG/PNG format only.</div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary-gradient px-4">
                            <i class="bi bi-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
