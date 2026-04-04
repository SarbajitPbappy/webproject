<?php /** HostelEase — Student: room change / cancellation */ ?>

<div class="content-header">
    <div>
        <h2 class="mb-1">Room change / cancellation</h2>
        <p class="text-muted mb-0">Submit a request to the warden. <strong>Cancellation</strong> is only available when you have no pending hall fees.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if (empty($hasRoom)): ?>
<div class="alert alert-warning">You do not have an active room allocation. This form is for residents who need a different room or to leave the hall.</div>
<?php else: ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card card-glass">
            <div class="card-header"><h5 class="mb-0">New request</h5></div>
            <div class="card-body">
                <?php if (!empty($hasPending)): ?>
                <div class="alert alert-warning py-2 small mb-3">You already have a pending request. Wait for the office to respond.</div>
                <?php else: ?>
                <form method="POST" action="<?php echo BASE_URL; ?>?url=students/roomRequests" novalidate>
                    <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label class="form-label">Request type <span class="text-danger">*</span></label>
                        <select class="form-select" name="request_type" id="request_type" required>
                            <option value="">Choose...</option>
                            <option value="room_change">Room change (different room / tier)</option>
                            <option value="room_cancellation">Room cancellation (vacate hall)</option>
                        </select>
                    </div>
                    <div class="mb-3" id="prefWrap">
                        <label class="form-label" for="preferred_room_type">Preferred room category</label>
                        <select class="form-select" name="preferred_room_type" id="preferred_room_type">
                            <option value="">No preference (explain below)</option>
                            <option value="single">Single</option>
                            <option value="double">Double</option>
                            <option value="triple">Triple</option>
                            <option value="dormitory">Dormitory</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="message">Message</label>
                        <textarea class="form-control" name="message" id="message" rows="3" placeholder="Reason or details..."></textarea>
                    </div>
                    <div class="alert alert-secondary py-2 small mb-3" id="cancelHint" style="display:none;">
                        Cancellation requests require <strong>all pending fees to be paid</strong> first.
                        <?php if (($pendingDue ?? 0) > 0.009): ?>
                        Your current pending balance: <strong>৳<?php echo number_format((float) $pendingDue, 2); ?></strong>.
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary-gradient">Submit request</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-glass">
            <div class="card-header"><h5 class="mb-0">Your requests</h5></div>
            <div class="card-body p-0">
                <?php if (empty($existing)): ?>
                <p class="text-muted p-3 mb-0">No requests yet.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($existing as $row): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <strong><?php echo $row['request_type'] === 'room_cancellation' ? 'Cancellation' : 'Room change'; ?></strong>
                                <span class="badge bg-secondary ms-1"><?php echo e($row['status']); ?></span>
                                <?php if (!empty($row['preferred_room_type'])): ?>
                                <br><small class="text-muted">Preferred: <?php echo e($row['preferred_room_type']); ?></small>
                                <?php endif; ?>
                                <?php if (!empty($row['message'])): ?>
                                <br><small><?php echo e($row['message']); ?></small>
                                <?php endif; ?>
                                <?php if (!empty($row['admin_notes'])): ?>
                                <br><small class="text-primary">Office: <?php echo e($row['admin_notes']); ?></small>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted text-nowrap"><?php echo e(date('M j, Y', strtotime($row['created_at']))); ?></small>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php if (($pendingDue ?? 0) > 0.009): ?>
        <div class="alert alert-info mt-3 small mb-0">
            Pending hall balance: <strong>৳<?php echo number_format((float) $pendingDue, 2); ?></strong>.
            Pay all slips from <a href="<?php echo BASE_URL; ?>?url=payments/makePayment">Pay fees online</a> before requesting cancellation.
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function(){
  const t = document.getElementById('request_type');
  const pref = document.getElementById('prefWrap');
  const hint = document.getElementById('cancelHint');
  function sync() {
    const v = t && t.value;
    if (pref) pref.style.display = (v === 'room_change') ? '' : 'none';
    if (hint) hint.style.display = (v === 'room_cancellation') ? '' : 'none';
  }
  if (t) { t.addEventListener('change', sync); sync(); }
})();
</script>

<?php endif; ?>
