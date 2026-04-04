<?php
/**
 * HostelEase — Edit Profile View
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1">Edit Profile</h2>
            <p class="text-muted mb-0">Update your account information</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?url=profile/index" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Profile
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
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

                <form action="<?php echo BASE_URL; ?>?url=profile/edit" method="POST" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo e($_POST['full_name'] ?? $user['full_name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo e($_POST['phone'] ?? $user['phone'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="profile_photo" class="form-label">Profile Photo</label>
                        <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                        <div class="form-text">Leave empty to keep current photo.</div>
                    </div>

                    <?php if (!empty($studentProfile) && !empty($canChangeRoomPref)): ?>
                    <?php
                    $vac = $vacancyByType ?? ['single' => 0, 'double' => 0, 'triple' => 0, 'dormitory' => 0];
                    $wpt = $waitlistPreferredType ?? '';
                    $rp = $_POST['preferred_room_type'] ?? ($wpt !== '' ? $wpt : 'single');
                    ?>
                    <hr class="my-4">
                    <h5 class="mb-3"><i class="bi bi-door-open me-2"></i>Room waitlist preference</h5>
                    <p class="text-muted small">You have not been allocated a room yet. Change this if you picked the wrong tier at registration (it is used for your enrollment room-rent slip).</p>
                    <div class="mb-3">
                        <label for="preferred_room_type" class="form-label">Preferred room type</label>
                        <select class="form-select" id="preferred_room_type" name="preferred_room_type" required>
                            <option value="single" <?php echo $rp === 'single' ? 'selected' : ''; ?>>Single (<?php echo (int) $vac['single']; ?> beds free)</option>
                            <option value="double" <?php echo $rp === 'double' ? 'selected' : ''; ?>>Double (<?php echo (int) $vac['double']; ?> beds free)</option>
                            <option value="triple" <?php echo $rp === 'triple' ? 'selected' : ''; ?>>Triple (<?php echo (int) $vac['triple']; ?> beds free)</option>
                            <option value="dormitory" <?php echo $rp === 'dormitory' ? 'selected' : ''; ?>>Dormitory (<?php echo (int) $vac['dormitory']; ?> beds free)</option>
                        </select>
                        <div class="form-text">Save the form to apply. If you already have pending bills for another tier, contact the office to cancel and re-issue slips.</div>
                    </div>
                    <?php elseif (!empty($studentProfile) && empty($canChangeRoomPref)): ?>
                    <hr class="my-4">
                    <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>Room category changes after allocation must go through the hostel office.</p>
                    <?php endif; ?>

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
