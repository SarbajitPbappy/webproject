<?php
/** HostelEase — Create/Edit Notice */
$isEdit = isset($notice) && !empty($notice['id']);
$formAction = $isEdit
    ? BASE_URL . '?url=notices/update/' . $notice['id']
    : BASE_URL . '?url=notices/store';
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div><h2 class="mb-1"><?php echo $isEdit ? 'Edit' : 'Create'; ?> Notice</h2></div>
        <a href="<?php echo BASE_URL; ?>?url=notices/index" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card card-glass">
    <div class="card-body p-4">
        <form method="POST" action="<?php echo $formAction; ?>" novalidate>
            <?php echo csrfField(); ?>
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?php echo $notice['id']; ?>"><?php endif; ?>

            <div class="mb-3">
                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo e($data['title'] ?? ($notice['title'] ?? '')); ?>" required>
            </div>
            <div class="mb-3">
                <label for="body" class="form-label">Content <span class="text-danger">*</span></label>
                <textarea class="form-control" id="body" name="body" rows="8" required><?php echo e($data['body'] ?? ($notice['body'] ?? '')); ?></textarea>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="is_pinned" name="is_pinned" value="1"
                    <?php echo ($data['is_pinned'] ?? ($notice['is_pinned'] ?? false)) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="is_pinned"><i class="bi bi-pin me-1"></i>Pin this notice</label>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>?url=notices/index" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary-gradient"><i class="bi bi-check-circle me-2"></i><?php echo $isEdit ? 'Update' : 'Post'; ?> Notice</button>
            </div>
        </form>
    </div>
</div>
