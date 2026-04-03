<?php
/**
 * HostelEase — General Profile View (For Admin/Staff/Super Admin)
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1">My Profile</h2>
            <p class="text-muted mb-0">Manage your account information</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?url=profile/edit" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-2"></i>Edit Profile
        </a>
        <a href="<?php echo BASE_URL; ?>?url=profile/changePassword" class="btn btn-primary-gradient">
            <i class="bi bi-key me-2"></i>Change Password
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card card-glass text-center">
            <div class="card-body p-5">
                <div class="avatar-lg mx-auto mb-3">
                    <?php if (!empty($user['profile_photo'])): ?>
                        <img src="<?php echo BASE_URL . 'public/uploads/students/' . e($user['profile_photo']); ?>" alt="Photo" class="avatar-img-lg">
                    <?php else: ?>
                        <div class="avatar-initials-lg bg-primary-subtle text-primary">
                            <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <h3 class="mb-1"><?php echo e($user['full_name']); ?></h3>
                <p class="text-muted mb-3"><?php echo e($user['email']); ?></p>
                
                <?php
                $rClass = match($user['role']) { 'super_admin'=>'danger', 'admin'=>'primary', 'staff'=>'warning', default=>'secondary' };
                ?>
                <span class="badge bg-<?php echo $rClass; ?>-subtle text-<?php echo $rClass; ?> fs-6 p-2 px-3">
                    <i class="bi bi-briefcase me-2"></i><?php echo ucfirst(str_replace('_',' ',e($user['role']))); ?>
                </span>
                
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card card-glass h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>Account Status</h5>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <div class="mb-4">
                    <label class="detail-label text-uppercase small fw-bold text-muted mb-1">Status</label>
                    <div>
                        <span class="badge bg-success-subtle text-success fs-6"><i class="bi bi-check-circle me-1"></i> Active</span>
                    </div>
                </div>
                
                <div>
                    <label class="detail-label text-uppercase small fw-bold text-muted mb-1">Account Created On</label>
                    <p class="detail-value fs-5"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
