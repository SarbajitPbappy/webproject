<?php /** HostelEase — Notice Board View */ ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><h2 class="mb-1">Notice Board</h2><p class="text-muted mb-0">Announcements and updates</p></div>
        <?php if (hasRole(['super_admin', 'admin'])): ?>
        <a href="<?php echo BASE_URL; ?>?url=notices/create" class="btn btn-primary-gradient"><i class="bi bi-plus-circle me-2"></i>Post Notice</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($notices)): ?>
    <div class="col-12 text-center py-5 text-muted">
        <i class="bi bi-megaphone display-3 d-block mb-3"></i>
        <h4>No notices posted yet.</h4>
    </div>
    <?php else: ?>
    <?php foreach ($notices as $n): ?>
    <div class="col-md-6">
        <div class="card card-glass <?php echo $n['is_pinned'] ? 'border-warning' : ''; ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <?php if ($n['is_pinned']): ?><i class="bi bi-pin-angle-fill text-warning me-2"></i><?php endif; ?>
                    <?php echo e($n['title']); ?>
                </h5>
                <?php if (hasRole(['super_admin', 'admin'])): ?>
                <div class="btn-group btn-group-sm">
                    <a href="<?php echo BASE_URL; ?>?url=notices/edit/<?php echo $n['id']; ?>" class="btn btn-outline-warning"><i class="bi bi-pencil"></i></a>
                    <form method="POST" action="<?php echo BASE_URL; ?>?url=notices/delete/<?php echo $n['id']; ?>" class="d-inline" onsubmit="return confirm('Delete this notice?');">
                        <?php echo csrfField(); ?>
                        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <p><?php echo nl2br(e($n['body'])); ?></p>
            </div>
            <div class="card-footer text-muted small">
                <i class="bi bi-person me-1"></i><?php echo e($n['posted_by_name'] ?? 'Admin'); ?>
                <span class="mx-2">·</span>
                <i class="bi bi-calendar me-1"></i><?php echo date('F d, Y', strtotime($n['created_at'])); ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
