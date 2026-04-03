<?php /** HostelEase — Edit Room View */ ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div><h2 class="mb-1">Edit Room</h2><p class="text-muted mb-0">Update room information</p></div>
        <a href="<?php echo BASE_URL; ?>?url=rooms/index" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?php echo e($e); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card card-glass">
    <div class="card-body p-4">
        <form method="POST" action="<?php echo BASE_URL; ?>?url=rooms/update/<?php echo $room['id']; ?>" novalidate>
            <?php echo csrfField(); ?>
            <input type="hidden" name="id" value="<?php echo $room['id']; ?>">
            <div class="row g-4">
                <div class="col-md-4">
                    <label for="room_number" class="form-label">Room Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="room_number" name="room_number" value="<?php echo e($data['room_number'] ?? $room['room_number']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="floor" class="form-label">Floor</label>
                    <input type="number" class="form-control" id="floor" name="floor" value="<?php echo e($data['floor'] ?? $room['floor']); ?>" min="0">
                </div>
                <div class="col-md-4">
                    <label for="type" class="form-label">Room Type</label>
                    <select class="form-select" id="type" name="type">
                        <?php $t = $data['type'] ?? $room['type']; ?>
                        <option value="single" <?php echo $t === 'single' ? 'selected' : ''; ?>>Single</option>
                        <option value="double" <?php echo $t === 'double' ? 'selected' : ''; ?>>Double</option>
                        <option value="triple" <?php echo $t === 'triple' ? 'selected' : ''; ?>>Triple</option>
                        <option value="dormitory" <?php echo $t === 'dormitory' ? 'selected' : ''; ?>>Dormitory</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="capacity" class="form-label">Capacity</label>
                    <input type="number" class="form-control" id="capacity" name="capacity" value="<?php echo e($data['capacity'] ?? $room['capacity']); ?>" min="1" required>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <?php $s = $data['status'] ?? $room['status']; ?>
                        <option value="available" <?php echo $s === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="full" <?php echo $s === 'full' ? 'selected' : ''; ?>>Full</option>
                        <option value="maintenance" <?php echo $s === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>
                <div class="col-12">
                    <label for="facilities" class="form-label">Facilities</label>
                    <textarea class="form-control" id="facilities" name="facilities" rows="3"><?php echo e($data['facilities'] ?? $room['facilities']); ?></textarea>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>?url=rooms/index" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary-gradient"><i class="bi bi-check-circle me-2"></i>Update Room</button>
            </div>
        </form>
    </div>
</div>
