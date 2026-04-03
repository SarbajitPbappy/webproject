<?php /** HostelEase — Submit Complaint View */ ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div><h2 class="mb-1">Submit Complaint</h2><p class="text-muted mb-0">Report an issue or request</p></div>
        <a href="<?php echo BASE_URL; ?>?url=complaints/index" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card card-glass">
    <div class="card-body p-4">
        <form method="POST" action="<?php echo BASE_URL; ?>?url=complaints/store" novalidate>
            <?php echo csrfField(); ?>
            <div class="row g-4">
                <?php if (hasRole(['super_admin', 'admin'])): ?>
                <div class="col-md-6">
                    <label for="student_id" class="form-label">Student <span class="text-danger">*</span></label>
                    <select class="form-select" id="student_id" name="student_id" required>
                        <option value="">Select Student...</option>
                        <?php
                        $studentModel = new Student();
                        foreach ($studentModel->getForDropdown() as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo e($s['full_name']); ?> (<?php echo e($s['student_id_no']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-6">
                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="">Select Category...</option>
                        <option value="Plumbing" <?php echo ($data['category'] ?? '') === 'Plumbing' ? 'selected' : ''; ?>>Plumbing</option>
                        <option value="Electrical" <?php echo ($data['category'] ?? '') === 'Electrical' ? 'selected' : ''; ?>>Electrical</option>
                        <option value="Furniture" <?php echo ($data['category'] ?? '') === 'Furniture' ? 'selected' : ''; ?>>Furniture</option>
                        <option value="Cleaning" <?php echo ($data['category'] ?? '') === 'Cleaning' ? 'selected' : ''; ?>>Cleaning</option>
                        <option value="Internet/WiFi" <?php echo ($data['category'] ?? '') === 'Internet/WiFi' ? 'selected' : ''; ?>>Internet/WiFi</option>
                        <option value="Security" <?php echo ($data['category'] ?? '') === 'Security' ? 'selected' : ''; ?>>Security</option>
                        <option value="Noise" <?php echo ($data['category'] ?? '') === 'Noise' ? 'selected' : ''; ?>>Noise Complaint</option>
                        <option value="Other" <?php echo ($data['category'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="priority" class="form-label">Priority</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="description" name="description" rows="5" required placeholder="Describe the issue in detail..."><?php echo e($data['description'] ?? ''); ?></textarea>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>?url=complaints/index" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary-gradient"><i class="bi bi-send me-2"></i>Submit Complaint</button>
            </div>
        </form>
    </div>
</div>
