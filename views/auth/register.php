<?php
/**
 * HostelEase — Student Self-Registration Page
 */
ob_start();
?>

<style>
    .login-left { background: linear-gradient(135deg, #f8fafc, #eff6ff); padding: 3rem; width: 100%; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; justify-content: center; }
    @media (min-width: 992px) { .login-left { width: 45%; } }
    .login-right { padding: 3rem; width: 100%; display: flex; flex-direction: column; justify-content: center; }
    @media (min-width: 992px) { .login-right { width: 55%; padding: 4rem; } }
    .feature-list { list-style: none; padding: 0; margin-top: 2rem; }
    .feature-list li { margin-bottom: 1rem; display: flex; align-items: center; gap: 10px; color: #475569; }
    .feature-list i { color: #10b981; font-size: 1.2rem; }
</style>

<!-- LEFT PANEL: INFO -->
<div class="login-left text-center text-md-start">
    <a href="<?php echo BASE_URL; ?>?url=auth/loginAs/student" class="btn btn-sm btn-outline-secondary mb-4 rounded-pill align-self-start">
        <i class="bi bi-arrow-left me-1"></i>Back to Login
    </a>
    
    <h3 class="fw-bold mb-3 text-success"><i class="bi bi-mortarboard-fill me-2"></i>Student Registration</h3>
    <p class="text-muted fs-5">Apply for a room and manage your accommodation.</p>
    
    <ul class="feature-list">
        <li><i class="bi bi-check-circle-fill"></i> Self-Service Online Payments</li>
        <li><i class="bi bi-check-circle-fill"></i> Instant Room Allocation Engine</li>
        <li><i class="bi bi-check-circle-fill"></i> 24/7 Support Ticketing System</li>
        <li><i class="bi bi-check-circle-fill"></i> Transparent Financial Records</li>
    </ul>
</div>

<!-- RIGHT PANEL: FORM -->
<div class="login-right">
    <h4 class="fw-bold mb-1">Create Your Account</h4>
    <p class="text-muted mb-4">Choose a room type and join the waitlist. After fees are issued and paid, the office allocates a matching room.</p>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger px-3 py-2 text-sm">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $error): ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo BASE_URL; ?>?url=auth/register" novalidate id="registerForm">
        <?php echo csrfField(); ?>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="form-floating">
                    <input type="text" class="form-control" id="regName" name="full_name" placeholder="John Doe" value="<?php echo e($data['full_name'] ?? ''); ?>" required>
                    <label for="regName">Full Name</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating">
                    <input type="text" class="form-control" id="regPhone" name="phone" placeholder="+123456789" value="<?php echo e($data['phone'] ?? ''); ?>" required>
                    <label for="regPhone">Phone Number</label>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="form-floating">
                    <select class="form-select" id="regGender" name="gender" required>
                        <option value="" disabled <?php echo empty($data['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                        <option value="Male" <?php echo ($data['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($data['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo ($data['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                    <label for="regGender">Gender</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating">
                    <input type="text" class="form-control" id="regCourse" name="course" placeholder="B.Tech CS" value="<?php echo e($data['course'] ?? ''); ?>" required>
                    <label for="regCourse">Course / Department</label>
                </div>
            </div>
        </div>

        <?php
        $vacancy = $vacancyByType ?? ['single' => 0, 'double' => 0, 'triple' => 0, 'dormitory' => 0];
        $prt = $data['preferred_room_type'] ?? '';
        ?>
        <div class="row g-3 mb-3">
            <div class="col-12">
                <label for="regRoomPref" class="form-label">Preferred room type <span class="text-danger">*</span></label>
                <select class="form-select form-select-lg" id="regRoomPref" name="preferred_room_type" required>
                    <option value="" disabled <?php echo $prt === '' ? 'selected' : ''; ?>>Choose based on availability (you can still join the waitlist if full)</option>
                    <option value="single" <?php echo $prt === 'single' ? 'selected' : ''; ?>>Single — <?php echo (int) ($vacancy['single'] ?? 0); ?> bed(s) free now</option>
                    <option value="double" <?php echo $prt === 'double' ? 'selected' : ''; ?>>Double — <?php echo (int) ($vacancy['double'] ?? 0); ?> bed(s) free now</option>
                    <option value="triple" <?php echo $prt === 'triple' ? 'selected' : ''; ?>>Triple — <?php echo (int) ($vacancy['triple'] ?? 0); ?> bed(s) free now</option>
                    <option value="dormitory" <?php echo $prt === 'dormitory' ? 'selected' : ''; ?>>Dormitory — <?php echo (int) ($vacancy['dormitory'] ?? 0); ?> bed(s) free now</option>
                </select>
                <small class="text-muted">Enrollment billing uses this to issue the correct room-rent slip before you are allocated.</small>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="form-floating">
                    <input type="text" class="form-control" id="regGuardianName" name="guardian_name" placeholder="Guardian Name" value="<?php echo e($data['guardian_name'] ?? ''); ?>" required>
                    <label for="regGuardianName">Guardian Name</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating">
                    <input type="text" class="form-control" id="regGuardianPhone" name="guardian_phone" placeholder="Guardian Phone" value="<?php echo e($data['guardian_phone'] ?? ''); ?>" required>
                    <label for="regGuardianPhone">Guardian Phone</label>
                </div>
            </div>
        </div>

        <div class="form-floating mb-3">
            <input type="email" class="form-control" id="regEmail" name="email" placeholder="Email address" value="<?php echo e($data['email'] ?? ''); ?>" required autocomplete="email">
            <label for="regEmail">Email Address</label>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="form-floating">
                    <input type="password" class="form-control" id="regPassword" name="password" placeholder="Password" required>
                    <label for="regPassword">Password (min 6 chars)</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating">
                    <input type="password" class="form-control" id="regConfirmPassword" name="confirm_password" placeholder="Confirm Password" required>
                    <label for="regConfirmPassword">Confirm Password</label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
            Create Account <i class="bi bi-person-plus ms-1"></i>
        </button>
    </form>
    
    <div class="text-center mt-4">
        <p class="text-muted small">Already registered? <a href="<?php echo BASE_URL; ?>?url=auth/loginAs/student" class="fw-semibold text-decoration-none">Log In</a></p>
    </div>
</div>

<?php
$viewContent = ob_get_clean();
require_once APP_ROOT . '/views/layouts/auth.php';
?>
