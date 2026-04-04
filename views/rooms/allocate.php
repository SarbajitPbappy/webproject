<?php /** HostelEase — Room Allocations View */ ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="mb-1">Room Allocations</h2>
            <p class="text-muted mb-0">Assign <strong>waitlisted</strong> students to rooms; the table on the right lists <strong>current residents</strong> only.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?php echo BASE_URL; ?>?url=allocations/occupancy" class="btn btn-outline-secondary">
                <i class="bi bi-people me-1"></i>Room roster
            </a>
            <a href="<?php echo BASE_URL; ?>?url=allocations/transfer" class="btn btn-outline-info">
                <i class="bi bi-arrow-left-right me-1"></i>Transfer
            </a>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Allocation Form -->
    <div class="col-lg-5">
        <div class="card card-glass">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-person-plus me-2"></i>New allocation (waitlist only)</h5></div>
            <div class="card-body">
                <p class="small text-muted mb-3">Only students on the <strong>waitlist</strong> who do <strong>not</strong> yet have a room appear here. They may only be placed in rooms that match their <strong>paid tier</strong> or their <strong>waitlist category</strong>.</p>
                <div id="tierHint" class="alert alert-secondary py-2 small d-none mb-3"></div>
                <form method="POST" action="<?php echo BASE_URL; ?>?url=allocations/allocate" novalidate>
                    <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Waitlisted student <span class="text-danger">*</span></label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="" data-tier="">Select student...</option>
                            <?php foreach ($students as $s): ?>
                            <?php
                            $effTier = trim((string) ($s['entitled_room_type'] ?? ''));
                            if ($effTier === '' && !empty($s['waitlist_preferred_room_type'])) {
                                $effTier = (string) $s['waitlist_preferred_room_type'];
                            }
                            ?>
                            <option value="<?php echo $s['id']; ?>" data-tier="<?php echo e($effTier); ?>">
                                <?php echo e($s['full_name']); ?> (<?php echo e($s['student_id_no']); ?>)
                                <?php if ($effTier !== ''): ?> — <?php echo e($effTier); ?><?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($students)): ?>
                        <div class="form-text text-warning">No waitlisted students without a room. Add students to the waitlist first, or use <strong>Transfer</strong> for residents.</div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="room_id" class="form-label">Room <span class="text-danger">*</span></label>
                        <select class="form-select" id="room_id" name="room_id" required>
                            <option value="" data-room-type="">Select Available Room...</option>
                            <?php foreach ($rooms as $r): ?>
                            <option value="<?php echo $r['id']; ?>" data-room-type="<?php echo e($r['type']); ?>">
                                <?php echo e($r['room_number']); ?> — <?php echo ucfirst(e($r['type'])); ?>
                                (<?php echo $r['current_occupancy']; ?>/<?php echo $r['capacity']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary-gradient w-100">
                        <i class="bi bi-check-circle me-2"></i>Allocate Room
                    </button>
                </form>
            </div>
        </div>

        <!-- Waitlist -->
        <?php if (!empty($waitlist)): ?>
        <div class="card card-glass mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Waitlist (<?php echo count($waitlist); ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($waitlist as $w): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo e($w['full_name']); ?></strong>
                            <br><small class="text-muted"><?php echo e($w['student_id_no']); ?> ·
                                Tier <?php echo e($w['preferred_room_type'] ?? $w['entitled_room_type'] ?? '—'); ?>
                                · Since <?php echo date('M d', strtotime($w['requested_at'])); ?></small>
                        </div>
                        <span class="badge bg-warning-subtle text-warning">Waiting</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Current Allocations -->
    <div class="col-lg-7">
        <div class="card card-glass">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Current residents</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="allocationsTable">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Room</th>
                                <th>Type</th>
                                <th>Start Date</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $activeAllocations = array_filter($allocations, fn($a) => $a['status'] === 'active');
                            foreach ($activeAllocations ?? [] as $a): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($a['full_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo e($a['student_id_no']); ?></small>
                                </td>
                                <td><span class="badge bg-info-subtle text-info"><?php echo e($a['room_number']); ?></span></td>
                                <td><small><?php echo ucfirst(e($a['room_type'] ?? '')); ?></small></td>
                                <td><?php echo date('M d, Y', strtotime($a['start_date'])); ?></td>
                                <td><span class="badge bg-success-subtle text-success">Active</span></td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="<?php echo BASE_URL; ?>?url=allocations/transfer&student_id=<?php echo $a['student_id']; ?>" class="btn btn-sm btn-outline-info" title="Transfer">
                                            <i class="bi bi-arrow-left-right"></i>
                                        </a>
                                        <form method="POST" action="<?php echo BASE_URL; ?>?url=allocations/vacate" class="d-inline" onsubmit="return confirm('Vacate this student?');">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="student_id" value="<?php echo $a['student_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Vacate">
                                                <i class="bi bi-box-arrow-right"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
(function(){
  const st = document.getElementById('student_id');
  const rm = document.getElementById('room_id');
  const hint = document.getElementById('tierHint');
  function tier() { return (st.options[st.selectedIndex] && st.options[st.selectedIndex].getAttribute('data-tier')) || ''; }
  function filterRooms() {
    const t = tier();
    if (!rm) return;
    for (let i = 0; i < rm.options.length; i++) {
      const opt = rm.options[i];
      if (!opt.value) { opt.hidden = false; continue; }
      const rt = opt.getAttribute('data-room-type') || '';
      opt.hidden = t && rt && t !== rt;
    }
    if (hint) {
      if (!t) { hint.classList.add('d-none'); }
      else {
        hint.classList.remove('d-none');
        hint.textContent = 'Showing only ' + t + ' rooms for this student. Server-side checks still apply.';
      }
    }
  }
  if (st) st.addEventListener('change', filterRooms);
  filterRooms();
})();
</script>
<script>$(function(){ if($.fn.DataTable) $("#allocationsTable").DataTable({pageLength:10,order:[[3,"desc"]]}); });</script>
JS;
