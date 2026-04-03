<?php /** HostelEase — Create Room View */ ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1">Add New Room</h2>
            <p class="text-muted mb-0">Register a new hostel room</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?url=rooms/index" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?php echo e($e); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card card-glass">
    <div class="card-body p-4">
        <form method="POST" action="<?php echo BASE_URL; ?>?url=rooms/store" novalidate>
            <?php echo csrfField(); ?>
            <div class="row g-4">
                <div class="col-md-4">
                    <label for="room_number" class="form-label">Room Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="room_number" name="room_number" value="<?php echo e($data['room_number'] ?? ''); ?>" required placeholder="e.g. A-101">
                </div>
                <div class="col-md-4">
                    <label for="floor" class="form-label">Floor</label>
                    <input type="number" class="form-control" id="floor" name="floor" value="<?php echo e($data['floor'] ?? ''); ?>" min="0" max="50">
                </div>
                <div class="col-md-4">
                    <label for="type" class="form-label">Room Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="single" <?php echo ($data['type'] ?? '') === 'single' ? 'selected' : ''; ?>>Single</option>
                        <option value="double" <?php echo ($data['type'] ?? '') === 'double' ? 'selected' : ''; ?>>Double</option>
                        <option value="triple" <?php echo ($data['type'] ?? '') === 'triple' ? 'selected' : ''; ?>>Triple</option>
                        <option value="dormitory" <?php echo ($data['type'] ?? '') === 'dormitory' ? 'selected' : ''; ?>>Dormitory</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="capacity" class="form-label">Capacity <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="capacity" name="capacity" value="<?php echo e($data['capacity'] ?? '1'); ?>" min="1" max="20" required>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="available" <?php echo ($data['status'] ?? '') === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="full" <?php echo ($data['status'] ?? '') === 'full' ? 'selected' : ''; ?>>Full</option>
                        <option value="maintenance" <?php echo ($data['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label for="facilities" class="form-label">Facilities</label>
                    <textarea class="form-control" id="facilities" name="facilities" rows="3" placeholder="AC, Attached Bathroom, WiFi, etc."><?php echo e($data['facilities'] ?? ''); ?></textarea>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>?url=rooms/index" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary-gradient"><i class="bi bi-check-circle me-2"></i>Create Room</button>
            </div>
        </form>
    </div>
</div>
