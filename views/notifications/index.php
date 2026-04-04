<?php /** In-app notifications */ ?>

<div class="content-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h2 class="mb-1">Notifications</h2>
        <p class="text-muted mb-0">Billing and other alerts for your account.</p>
    </div>
    <?php if (!empty($items)): ?>
    <form method="POST" action="<?php echo BASE_URL; ?>?url=notifications/markAllRead" class="m-0">
        <?php echo csrfField(); ?>
        <button type="submit" class="btn btn-outline-secondary btn-sm">Mark all read</button>
    </form>
    <?php endif; ?>
</div>

<div class="card card-glass">
    <div class="list-group list-group-flush">
        <?php if (empty($items)): ?>
        <div class="list-group-item text-center text-muted py-5">No notifications yet.</div>
        <?php else: ?>
        <?php foreach ($items as $n): ?>
        <div class="list-group-item <?php echo empty($n['is_read']) ? 'bg-primary-subtle bg-opacity-10' : ''; ?>">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <strong><?php echo e($n['title']); ?></strong>
                    <?php if (empty($n['is_read'])): ?>
                    <span class="badge bg-primary ms-1">New</span>
                    <?php endif; ?>
                    <p class="mb-0 mt-1 small"><?php echo nl2br(e($n['body'])); ?></p>
                    <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($n['created_at'])); ?></small>
                </div>
                <?php if (empty($n['is_read'])): ?>
                <form method="POST" action="<?php echo BASE_URL; ?>?url=notifications/markRead/<?php echo (int) $n['id']; ?>" class="flex-shrink-0">
                    <?php echo csrfField(); ?>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Mark read</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
