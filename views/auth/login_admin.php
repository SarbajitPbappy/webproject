<?php
/**
 * HostelEase — Warden / Staff Login
 */
$roles = [
    'admin' => ['title'=>'Warden / Admin Login','icon'=>'bi-person-badge-fill','color'=>'primary','desc'=>'Hostel Management'],
    'staff' => ['title'=>'Staff Login','icon'=>'bi-wrench-adjustable','color'=>'warning','desc'=>'Maintenance & Support'],
    'super_admin' => ['title'=>'Super Admin Login','icon'=>'bi-shield-lock-fill','color'=>'danger','desc'=>'System Administration'],
];
$currentRole = (string) ($loginRole ?? 'admin');
$r = $roles[$currentRole] ?? $roles['admin'];
ob_start();
?>

<style>
    .login-container { display: flex; min-height: 100vh; background: #f8fafc; }
    .login-sidebar { flex: 1; background: linear-gradient(135deg, #1e1b4b, #312e81); color: #fff; padding: 4rem; display: flex; flex-direction: column; justify-content: space-between; }
    .login-main { flex: 1.5; background: #fff; display: flex; flex-direction: column; justify-content: center; padding: 4rem; position: relative; }
    .login-card { max-width: 480px; width: 100%; margin: 0 auto; }
    .role-indicator { font-size: 3.5rem; margin-bottom: 1.5rem; }
    
    @media (max-width: 991px) {
        .login-container { flex-direction: column; }
        .login-sidebar { padding: 2rem; min-height: auto; }
        .login-main { padding: 2rem; }
    }
</style>

<div class="login-container">
    <div class="login-sidebar">
        <div class="text-start">
            <a href="<?php echo BASE_URL; ?>" class="text-white text-decoration-none fw-bold fs-4">
                <i class="bi bi-building me-2"></i>HostelEase
            </a>
        </div>
        
        <div class="my-5">
            <div class="role-indicator text-warning"><i class="bi <?php echo $r['icon']; ?>"></i></div>
            <h2 class="fw-black mb-1"><?php echo $r['title']; ?></h2>
            <p class="text-white-50"><?php echo $r['desc']; ?></p>
        </div>
        
        <div class="small opacity-50">
            Powered by HostelEase Management System v1.0.0
        </div>
    </div>
    
    <div class="login-main">
        <div class="position-absolute top-0 end-0 p-4">
            <a href="<?php echo BASE_URL; ?>?url=auth/login" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Switch Role
            </a>
        </div>

        <div class="login-card">
            <h4 class="fw-bold mb-1">Welcome Back</h4>
            <p class="text-muted mb-4">Please log in to your <?php echo str_replace(' Login', '', $r['title']); ?> account.</p>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger px-3 py-2 small">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>?url=auth/loginAs/<?php echo $loginRole; ?>" autocomplete="off" novalidate>
                <?php echo csrfField(); ?>
                <input type="hidden" name="login_role" value="<?php echo $loginRole; ?>">

                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" value="<?php echo e($email ?? ''); ?>" required autocomplete="off">
                    <label for="email"><i class="bi bi-envelope me-2 text-muted"></i>Email Address</label>
                </div>

                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required autocomplete="new-password">
                    <label for="password"><i class="bi bi-lock me-2 text-muted"></i>Password</label>
                </div>

                <div class="d-flex justify-content-end mb-4">
                    <a href="<?php echo BASE_URL; ?>?url=auth/forgotPassword" class="small text-decoration-none">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-<?php echo $r['color']; ?> w-100 py-2 fw-bold rounded-pill">
                    Sign In <i class="bi bi-person-check ms-2"></i>
                </button>
            </form>
            
            <div class="mt-5 pt-5 text-center border-top">
                <p class="text-muted small mb-0"><i class="bi bi-shield-lock-fill me-1"></i> Secure Administrative Access Only</p>
            </div>
        </div>
    </div>
</div>

<?php
$viewContent = ob_get_clean();
require_once APP_ROOT . '/views/layouts/auth.php';
?>
