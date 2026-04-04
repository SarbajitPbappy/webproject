<?php /** HostelEase — Transfer Student View */ ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div><h2 class="mb-1">Transfer Student</h2><p class="text-muted mb-0">Move a student to a different room</p></div>
        <a href="<?php echo BASE_URL; ?>?url=allocations/allocate" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card card-glass">
    <div class="card-body p-4">
        <form method="POST" action="<?php echo BASE_URL; ?>?url=allocations/transfer" novalidate>
            <?php echo csrfField(); ?>
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="student_id" class="form-label">Student <span class="text-danger">*</span></label>
                    <select class="form-select" id="student_id" name="student_id" required>
                        <option value="" data-tier="">Select Student...</option>
                        <?php foreach ($students as $s): ?>
                        <option value="<?php echo $s['id']; ?>" data-tier="<?php echo e($s['entitled_room_type'] ?? ''); ?>"
                            <?php echo ($s['id'] == ($preSelectedStudentId ?? 0)) ? 'selected' : ''; ?>>
                            <?php echo e($s['full_name']); ?> (<?php echo e($s['student_id_no']); ?>)
                            <?php if (!empty($s['entitled_room_type'])): ?> — <?php echo e($s['entitled_room_type']); ?><?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="room_id" class="form-label">New Room <span class="text-danger">*</span></label>
                    <select class="form-select" id="room_id" name="room_id" required>
                        <option value="" data-room-type="">Select New Room...</option>
                        <?php foreach ($rooms as $r): ?>
                        <option value="<?php echo $r['id']; ?>" data-room-type="<?php echo e($r['type']); ?>">
                            <?php echo e($r['room_number']); ?> — <?php echo ucfirst(e($r['type'])); ?> (<?php echo $r['current_occupancy']; ?>/<?php echo $r['capacity']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Reason for transfer..."></textarea>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>?url=allocations/allocate" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary-gradient"><i class="bi bi-arrow-left-right me-2"></i>Transfer Student</button>
            </div>
        </form>
    </div>
</div>
<script>
(function(){
  const st = document.getElementById('student_id');
  const rm = document.getElementById('room_id');
  function tier() { return (st.options[st.selectedIndex] && st.options[st.selectedIndex].getAttribute('data-tier')) || ''; }
  function filterRooms() {
    const t = tier();
    for (let i = 0; i < rm.options.length; i++) {
      const opt = rm.options[i];
      if (!opt.value) { opt.hidden = false; continue; }
      const rt = opt.getAttribute('data-room-type') || '';
      opt.hidden = t && rt && t !== rt;
    }
  }
  if (st) st.addEventListener('change', filterRooms);
  filterRooms();
})();
</script>
