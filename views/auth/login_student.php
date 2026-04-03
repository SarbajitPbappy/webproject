<?php
/**
 * HostelEase — Student Login & Signup
 */
ob_start();
?>

<style>
    .auth-container { display: flex; min-height: 100vh; width: 100%; }
    .auth-left { flex: 1; background: linear-gradient(135deg, #059669, #10b981); display: flex; flex-direction: column; align-items: center; justify-content: center; color: #fff; padding: 3rem; text-align: center; }
    .auth-right { flex: 1.2; background: #fff; display: flex; flex-direction: column; justify-content: center; padding: 4rem; position: relative; }
    .auth-toggle { position: absolute; top: 2rem; right: 2rem; }
    .auth-form-card { max-width: 450px; width: 100%; margin: 0 auto; }
    .form-floating input { border-radius: 12px; border: 1.5px solid #e2e8f0; }
    .btn-auth { padding: 0.8rem; border-radius: 12px; font-weight: 700; transition: all 0.3s; }
    .btn-student { background: #10b981; color: #fff; border: none; }
    .btn-student:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(16,185,129,0.2); }
    
    @media (max-width: 991px) {
        .auth-container { flex-direction: column; }
        .auth-left { padding: 2rem; min-height: auto; }
        .auth-right { padding: 2rem; }
    }
</style>

<div class="auth-container">
    <div class="auth-left">
        <div class="mb-4"><i class="bi bi-mortarboard-fill" style="font-size: 4rem;"></i></div>
        <h2 class="fw-bold">Student Portal</h2>
        <p class="opacity-75">Your smart companion for hostel life. Login to manage your room, payments, and more.</p>
        
        <div class="mt-5 d-none d-lg-block">
            <div class="d-flex align-items-center gap-3 mb-3 text-start">
                <i class="bi bi-check-circle-fill text-warning"></i>
                <span>Easy Room Allocation</span>
            </div>
            <div class="d-flex align-items-center gap-3 mb-3 text-start">
                <i class="bi bi-check-circle-fill text-warning"></i>
                <span>Online Fee Payments</span>
            </div>
            <div class="d-flex align-items-center gap-3 text-start">
                <i class="bi bi-check-circle-fill text-warning"></i>
                <span>Maintenance Requests</span>
            </div>
        </div>
    </div>
    
    <div class="auth-right">
        <div class="auth-toggle">
            <a href="<?php echo BASE_URL; ?>?url=auth/login" class="btn btn-sm btn-outline-secondary rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Not a Student?
            </a>
        </div>

        <div class="auth-form-card">
            <h3 class="fw-bold mb-1">Sign In</h3>
            <p class="text-muted mb-4">Enter your student credentials</p>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger px-3 py-2 small">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>?url=auth/loginAs/student" autocomplete="off">
                <?php echo csrfField(); ?>
                <input type="hidden" name="login_role" value="student">

                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" value="<?php echo e($email ?? ''); ?>" required autocomplete="off">
                    <label for="email"><i class="bi bi-envelope me-2 text-muted"></i>Email Address</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required autocomplete="new-password">
                    <label for="password"><i class="bi bi-lock me-2 text-muted"></i>Password</label>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember">
                        <label class="form-check-label small text-muted" for="remember">Remember me</label>
                    </div>
                    <a href="<?php echo BASE_URL; ?>?url=auth/forgotPassword" class="small text-decoration-none fw-semibold">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-student btn-auth w-100 mb-4">
                    Sign In <i class="bi bi-arrow-right ms-2"></i>
                </button>

                <div class="text-center p-3 rounded-4 bg-light">
                    <p class="small mb-2">Don't have an account yet?</p>
                    <a href="<?php echo BASE_URL; ?>?url=auth/register" class="btn btn-outline-success btn-sm rounded-pill px-4 fw-bold">
                        <i class="bi bi-person-plus me-1"></i> Register as Student
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$viewContent = ob_get_clean();
require_once APP_ROOT . '/views/layouts/auth.php';
?>
