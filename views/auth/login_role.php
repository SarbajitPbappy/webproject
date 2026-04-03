<?php
/**
 * HostelEase — Role-Specific Login Page
 * $loginRole is set by AuthController::loginAs()
 */
ob_start();

$roleConfig = [
    'super_admin' => ['title'=>'Super Admin Login','icon'=>'bi-shield-lock-fill','color'=>'danger','desc'=>'Full System Control'],
    'admin'       => ['title'=>'Warden / Admin Login','icon'=>'bi-person-badge-fill','color'=>'primary','desc'=>'Hostel Operations'],
    'student'     => ['title'=>'Student Portal','icon'=>'bi-mortarboard-fill','color'=>'success','desc'=>'Self-Service Portal'],
    'staff'       => ['title'=>'Staff Login','icon'=>'bi-wrench-adjustable','color'=>'warning','desc'=>'Task Management'],
];
$rc = $roleConfig[$loginRole] ?? $roleConfig['student'];

// Only re-fill email on POST (validation failure), NOT on fresh page load
$emailValue = ($_SERVER['REQUEST_METHOD'] === 'POST') ? e($email ?? '') : '';
?>

<style>
    .login-left { background: linear-gradient(135deg, #f8fafc, #eff6ff); padding: 3rem; width: 100%; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
    @media (min-width: 992px) { .login-left { width: 45%; } }
    .login-right { padding: 3rem; width: 100%; display: flex; flex-direction: column; justify-content: center; }
    @media (min-width: 992px) { .login-right { width: 55%; padding: 4rem; } }
    .role-hero-icon { font-size: 4rem; margin-bottom: 1rem; }
    .register-banner { background: linear-gradient(135deg,#d1fae5,#a7f3d0); border: 1.5px solid #34d399; border-radius: 14px; padding: 1.2rem 1.5rem; margin-top: 1.5rem; }
</style>

<!-- LEFT: Role Info -->
<div class="login-left">
    <a href="<?php echo BASE_URL; ?>?url=auth/login" class="btn btn-sm btn-outline-secondary mb-4 rounded-pill align-self-start">
        <i class="bi bi-arrow-left me-1"></i>All Roles
    </a>
    <div class="role-hero-icon text-<?php echo $rc['color']; ?>">
        <i class="bi <?php echo $rc['icon']; ?>"></i>
    </div>
    <h3 class="fw-bold mb-1"><?php echo $rc['title']; ?></h3>
    <p class="text-muted"><?php echo $rc['desc']; ?></p>

    <?php if ($loginRole === 'student'): ?>
    <div class="register-banner w-100 mt-3">
        <p class="fw-bold text-success mb-2"><i class="bi bi-mortarboard me-1"></i>New Student?</p>
        <p class="small text-muted mb-3">Register to apply for a room and access the self-service portal.</p>
        <a href="<?php echo BASE_URL; ?>?url=auth/register" class="btn btn-success w-100 rounded-pill fw-bold">
            <i class="bi bi-person-plus me-1"></i>Create Student Account
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- RIGHT: Login Form -->
<div class="login-right">
    <h4 class="fw-bold mb-1">Sign In</h4>
    <p class="text-muted mb-4">Enter your credentials to continue</p>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger px-3 py-2">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $error): ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo BASE_URL; ?>?url=auth/loginAs/<?php echo $loginRole; ?>" id="loginForm" novalidate autocomplete="off">
        <?php echo csrfField(); ?>
        <input type="hidden" name="login_role" value="<?php echo $loginRole; ?>">

        <div class="form-floating mb-3">
            <input type="email" class="form-control" id="loginEmail" name="email"
                   placeholder="Email address"
                   value="<?php echo $emailValue; ?>"
                   required
                   autocomplete="off">
            <label for="loginEmail"><i class="bi bi-envelope me-2 text-muted"></i>Email Address</label>
        </div>

        <div class="form-floating mb-3 position-relative">
            <input type="password" class="form-control" id="loginPassword" name="password"
                   placeholder="Password"
                   required
                   autocomplete="new-password">
            <label for="loginPassword"><i class="bi bi-lock me-2 text-muted"></i>Password</label>
        </div>

        <div class="d-flex justify-content-end mb-4">
            <a href="<?php echo BASE_URL; ?>?url=auth/forgotPassword" class="text-decoration-none fs-7 fw-semibold">Forgot Password?</a>
        </div>

        <button type="submit" class="btn btn-<?php echo $rc['color']; ?> w-100 py-2 fw-bold" id="loginBtn">
            Sign In <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </form>
    
    <div class="text-center mt-auto pt-4">
        <p class="text-muted small mb-0"><i class="bi bi-shield-check me-1"></i> Secure End-to-End Encryption</p>
    </div>
</div>

<?php
$viewContent = ob_get_clean();
require_once APP_ROOT . '/views/layouts/auth.php';
?>
