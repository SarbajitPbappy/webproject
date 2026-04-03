<?php
/**
 * HostelEase — Forgot Password Page
 */
ob_start();
?>

<style>
    .forgot-container { padding: 3rem; width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; }
</style>

<div class="forgot-container" style="max-width:600px; margin: 0 auto;">
    <a href="<?php echo BASE_URL; ?>?url=auth/login" class="btn btn-sm btn-outline-secondary mb-4 rounded-pill align-self-start">
        <i class="bi bi-arrow-left me-1"></i>Back to Login
    </a>

    <h3 class="fw-bold mb-1 text-center w-100"><i class="bi bi-key me-2 text-primary"></i>Reset Password</h3>
    <p class="text-muted text-center mb-4">Enter your email to receive a reset token</p>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger w-100">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $error): ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
    <div class="alert alert-success w-100">
        <i class="bi bi-check-circle me-2"></i><?php echo e($success); ?>
    </div>
    <?php endif; ?>

    <!-- Step 1: Request Reset Token -->
    <div id="requestResetSection" class="w-100">
        <form method="POST" action="<?php echo BASE_URL; ?>?url=auth/forgotPassword" novalidate>
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="request">

            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="resetEmail" name="email" placeholder="Email address" value="<?php echo e($email ?? ''); ?>" required>
                <label for="resetEmail"><i class="bi bi-envelope me-2"></i>Email Address</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3 py-2">
                <i class="bi bi-send me-2"></i>Send Reset Token
            </button>
        </form>

        <div class="text-center">
            <button type="button" class="btn btn-link" onclick="toggleResetSection()">Already have a reset token?</button>
        </div>
    </div>

    <!-- Step 2: Reset Password with Token -->
    <div id="resetPasswordSection" class="w-100" style="display:none;">
        <form method="POST" action="<?php echo BASE_URL; ?>?url=auth/forgotPassword" novalidate>
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="reset">

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="resetToken" name="token" placeholder="Paste your token" required>
                <label for="resetToken"><i class="bi bi-key me-2"></i>Reset Token</label>
            </div>

            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="newPassword" name="new_password" placeholder="New Password" required minlength="6">
                <label for="newPassword"><i class="bi bi-lock me-2"></i>New Password (min 6 chars)</label>
            </div>

            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="Confirm Password" required>
                <label for="confirmPassword"><i class="bi bi-lock-fill me-2"></i>Confirm Password</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3 py-2">
                <i class="bi bi-shield-check me-2"></i>Reset Password
            </button>
        </form>

        <div class="text-center">
            <button type="button" class="btn btn-link" onclick="toggleResetSection()">Request a new token</button>
        </div>
    </div>
</div>

<script>
function toggleResetSection() {
    const request = document.getElementById('requestResetSection');
    const reset = document.getElementById('resetPasswordSection');
    if (request.style.display === 'none') {
        request.style.display = 'block';
        reset.style.display = 'none';
    } else {
        request.style.display = 'none';
        reset.style.display = 'block';
    }
}
</script>

<?php
$viewContent = ob_get_clean();
require_once APP_ROOT . '/views/layouts/auth.php';
?>
