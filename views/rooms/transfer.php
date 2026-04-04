<?php /** HostelEase — Transfer Student View */ ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div><h2 class="mb-1">Transfer Student</h2><p class="text-muted mb-0">Move a resident to a different room</p></div>
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

                <?php if (!empty($transferFee)): ?>
                <div class="col-12">
                    <div class="border rounded p-3 bg-body-secondary bg-opacity-25">
                        <h6 class="mb-2"><i class="bi bi-receipt me-1"></i>Transfer fee (<?php echo e($transferFee['name']); ?>)</h6>
                        <p class="small text-muted mb-3">Amount: <strong>৳<?php echo number_format((float) $transferFee['amount'], 2); ?></strong>. Choose how to handle it before completing the move.</p>
                        <div class="d-flex flex-column gap-2">
                            <label class="d-flex gap-2 align-items-start mb-0">
                                <input type="radio" name="transfer_fee_action" value="slip" class="mt-1" checked>
                                <span><strong>Add fee to student portal</strong> — they can pay online later; you can also record cash/bank from Payments.</span>
                            </label>
                            <label class="d-flex gap-2 align-items-start mb-0">
                                <input type="radio" name="transfer_fee_action" value="slip_pay" class="mt-1">
                                <span><strong>Portal slip + record offline payment now</strong> (cash/bank).</span>
                            </label>
                            <label class="d-flex gap-2 align-items-start mb-0">
                                <input type="radio" name="transfer_fee_action" value="pay_only" class="mt-1">
                                <span><strong>Record offline payment only</strong> — no new slip on the portal.</span>
                            </label>
                            <?php if (!empty($isSuperAdmin)): ?>
                            <label class="d-flex gap-2 align-items-start mb-0">
                                <input type="radio" name="transfer_fee_action" value="waive" class="mt-1">
                                <span><strong>Waive fee</strong> (super admin only).</span>
                            </label>
                            <?php endif; ?>
                        </div>
                        <div class="row g-2 mt-3" id="transferPayFields">
                            <div class="col-md-4">
                                <label class="form-label small mb-0" for="payment_method">Payment method</label>
                                <select class="form-select form-select-sm" id="payment_method" name="payment_method">
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small mb-0" for="payment_reference">Reference (optional)</label>
                                <input type="text" class="form-control form-control-sm" id="payment_reference" name="payment_reference" placeholder="Txn ID, receipt no.">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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

  const payWrap = document.getElementById('transferPayFields');
  if (payWrap) {
    function syncPayFields() {
      const checked = document.querySelector('input[name="transfer_fee_action"]:checked');
      const v = checked ? checked.value : 'slip';
      payWrap.style.display = (v === 'slip_pay' || v === 'pay_only') ? '' : 'none';
    }
    document.querySelectorAll('input[name="transfer_fee_action"]').forEach(function(r) {
      r.addEventListener('change', syncPayFields);
    });
    syncPayFields();
  }
})();
</script>
