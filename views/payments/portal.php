<?php
/**
 * HostelEase — Student Payment Portal View
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1">Payment Portal</h2>
            <p class="text-muted mb-0">Pay your hostel fees securely online.</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mx-auto">
        <div class="card card-glass">
            <div class="card-header bg-primary-gradient text-white">
                <h5 class="mb-0 text-white"><i class="bi bi-credit-card me-2"></i>Online Payment Checkout</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?php echo BASE_URL; ?>?url=payments/processPortal" novalidate id="portalPaymentForm">
                    <?php echo csrfField(); ?>

                    <div class="mb-3">
                        <label class="form-label">Payment For</label>
                        <select name="fee_id" id="fee_id" class="form-select" required>
                            <option value="">-- Choose Fee Type --</option>
                            <?php foreach ($feeStructures as $fee): ?>
                                <option value="<?php echo $fee['id']; ?>" data-amount="<?php echo $fee['amount']; ?>">
                                    <?php echo e($fee['name']); ?> (৳<?php echo number_format($fee['amount'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Amount (৳)</label>
                        <input type="number" step="0.01" class="form-control" name="amount" id="amount" value="" required readonly>
                    </div>

                    <!-- Mock Credit Card Input for realistic UX -->
                    <h6 class="mb-3 mt-4 border-bottom pb-2">Card Details</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Name on Card</label>
                        <input type="text" class="form-control" name="card_name" placeholder="<?php echo e($student['full_name'] ?? 'Cardholder Name'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Card Number</label>
                        <input type="text" class="form-control" name="card_number" placeholder="0000 0000 0000 0000" pattern="\d*" maxlength="16" required>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-6">
                            <label class="form-label">Expiry</label>
                            <input type="text" class="form-control" name="expiry" placeholder="MM/YY" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">CVC</label>
                            <input type="text" class="form-control" name="cvv" placeholder="***" maxlength="3" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-gradient w-100 py-2 fs-5">
                        Confirm Payment <i class="bi bi-lock ms-2"></i>
                    </button>
                    <p class="text-muted text-center small mt-3 mb-0">Payments are safely simulated by the system</p>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('fee_id').addEventListener('change', function() {
    const amount = this.options[this.selectedIndex].getAttribute('data-amount');
    document.getElementById('amount').value = amount ? amount : '';
});
</script>
