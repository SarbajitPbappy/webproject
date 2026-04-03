<?php
/**
 * HostelEase — Authentication Controller
 */

require_once APP_ROOT . '/app/models/User.php';
require_once APP_ROOT . '/app/models/AuditLog.php';

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Show main login selection page
     */
    public function login(): void
    {
        if (isLoggedIn()) {
            $this->redirectToDashboard();
            return;
        }

        $pageTitle = 'Login';
        require_once APP_ROOT . '/views/auth/login.php';
    }

    /**
     * Show role-specific login form & handle submission
     */
    public function loginAs(?string $role = null): void
    {
        if (isLoggedIn()) {
            $this->redirectToDashboard();
            return;
        }

        $allowedRoles = ['super_admin', 'admin', 'student', 'staff'];
        $loginRole = in_array($role, $allowedRoles) ? $role : 'student';

        $errors = [];
        $email = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken()) {
                $errors[] = 'Invalid security token. Please try again.';
            } else {
                $email = sanitizeEmail($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $submittedRole = $_POST['login_role'] ?? $loginRole;

                if (empty($email) || empty($password)) {
                    $errors[] = 'Please fill in all fields.';
                } else {
                    // Check if it's super admin from config
                    if ($email === SUPER_ADMIN_EMAIL && $submittedRole === 'super_admin') {
                        if (password_verify($password, SUPER_ADMIN_PASS)) {
                            // Virtual login for SA config fallback
                            $_SESSION['user_id'] = 0;
                            $_SESSION['user_role'] = 'super_admin';
                            $_SESSION['user_name'] = 'System Administrator';
                            $_SESSION['user_email'] = $email;
                            $_SESSION['user_photo'] = '';
                            $_SESSION['last_activity'] = time();

                            AuditLog::log(0, 'LOGIN', 'system', null, 'Super Admin fallback login');
                            $this->redirectToDashboard();
                            return;
                        } else {
                            $errors[] = 'Invalid credentials.';
                        }
                    } else {
                        // DB check
                        $user = $this->userModel->findByEmail($email);
                        if (!$user || !password_verify($password, $user['password_hash'])) {
                            $this->userModel->recordLoginAttempt($email, $_SERVER['REMOTE_ADDR']);
                            $errors[] = 'Invalid email or password.';
                        } elseif ($user['status'] !== 'active') {
                            $errors[] = 'Your account is ' . e($user['status']) . '. Please contact administration.';
                        } elseif ($user['role'] !== $submittedRole) {
                            $errors[] = 'Account does not have access to this portal.';
                        } else {
                            // Success
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_role'] = $user['role'];
                            $_SESSION['user_name'] = $user['full_name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['user_photo'] = $user['profile_photo'] ?? '';
                            $_SESSION['last_activity'] = time();

                            AuditLog::log($user['id'], 'LOGIN', 'users', $user['id'], 'User logged in successfully');
                            $this->redirectToDashboard();
                            return;
                        }
                    }
                }
            }
        }

        $pageTitle = 'Sign In | ' . ucfirst(str_replace('_', ' ', $loginRole));
        if ($loginRole === 'student') {
            require_once APP_ROOT . '/views/auth/login_student.php';
        } else {
            require_once APP_ROOT . '/views/auth/login_admin.php';
        }
    }

    /**
     * Handle logout
     */
    public function logout(): void
    {
        if (isLoggedIn()) {
            AuditLog::log($_SESSION['user_id'], 'LOGOUT', 'users', $_SESSION['user_id'], 'User logged out');
        }

        $_SESSION = [];
        session_destroy();
        
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );

        header('Location: ' . BASE_URL . '?url=auth/login');
        exit;
    }

    /**
     * Student Self-Registration
     */
    public function register(): void
    {
        if (isLoggedIn()) {
            $this->redirectToDashboard();
            return;
        }

        $errors = [];
        $data = ['full_name' => '', 'email' => '', 'phone' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken()) {
                $errors[] = 'Invalid security token. Please try again.';
            } else {
                $data['full_name'] = sanitize($_POST['full_name'] ?? '');
                $data['email']     = sanitizeEmail($_POST['email'] ?? '');
                $data['phone']     = sanitizePhone($_POST['phone'] ?? '');
                $data['gender']    = sanitize($_POST['gender'] ?? '');
                $data['course']    = sanitize($_POST['course'] ?? '');
                $data['guardian_name']  = sanitize($_POST['guardian_name'] ?? '');
                $data['guardian_phone'] = sanitizePhone($_POST['guardian_phone'] ?? '');
                $password          = $_POST['password'] ?? '';
                $confirmPassword   = $_POST['confirm_password'] ?? '';

                if (empty($data['full_name'])) $errors[] = 'Full name is required.';
                if (empty($data['email']))     $errors[] = 'Email address is required.';
                if (empty($password) || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
                if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';
                if (empty($data['phone'])) $errors[] = 'Phone number is required.';
                if (empty($data['gender'])) $errors[] = 'Gender selection is required.';
                if (empty($data['course'])) $errors[] = 'Course name is required.';

                if (empty($errors)) {
                    if ($this->userModel->findByEmail($data['email'])) {
                        $errors[] = 'This email is already registered. Please log in.';
                    } else {
                        $db = Database::getInstance();
                        $db->beginTransaction();

                        try {
                            $userId = $this->userModel->create([
                                'full_name' => $data['full_name'],
                                'email'     => $data['email'],
                                'password'  => $password,
                                'role'      => 'student',
                                'status'    => 'active',
                            ]);

                            $userData = $this->userModel->findById($userId);
                            $studentIdNo = $userData['system_id_no'];

                            $db->query(
                                "INSERT INTO students (user_id, student_id_no, phone, gender, course, guardian_name, guardian_phone, enrolled_date) 
                                 VALUES (:uid, :sid, :phone, :gender, :course, :gn, :gp, CURDATE())",
                                [
                                    ':uid' => $userId, 
                                    ':sid' => $studentIdNo, 
                                    ':phone' => $data['phone'],
                                    ':gender' => $data['gender'],
                                    ':course' => $data['course'],
                                    ':gn' => $data['guardian_name'],
                                    ':gp' => $data['guardian_phone']
                                ]
                            );
                            $studentId = $db->lastInsertId();

                            $db->query(
                                "INSERT INTO waitlist (student_id, status) VALUES (:sid, 'waiting')",
                                [':sid' => $studentId]
                            );

                            $db->commit();

                            AuditLog::log($userId, 'REGISTER', 'users', $userId, 'Student self-registered and placed in waitlist');
                            setFlash('success', 'Registration successful! You have been placed in the room allocation waitlist. You may now log in.');
                            header('Location: ' . BASE_URL . '?url=auth/loginAs/student');
                            exit;
                        } catch (Exception $e) {
                            $db->rollBack();
                            error_log("Registration Error: " . $e->getMessage());
                            $errors[] = 'An error occurred during registration. Please try again.';
                        }
                    }
                }
            }
        }

        $pageTitle = 'Student Registration';
        require_once APP_ROOT . '/views/auth/register.php';
    }

    /**
     * Forgot password
     */
    public function forgotPassword(): void
    {
        $errors = [];
        $success = '';
        $email = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken()) {
                $errors[] = 'Invalid security token.';
            } else {
                $action = $_POST['action'] ?? 'request';

                if ($action === 'request') {
                    $email = sanitizeEmail($_POST['email'] ?? '');
                    if (empty($email)) {
                        $errors[] = 'Please enter your email address.';
                    } else {
                        $user = $this->userModel->findByEmail($email);
                        if ($user) {
                            $token = $this->userModel->createPasswordResetToken($user['id']);
                            $success = 'Password reset token generated. Copy this token and use it below: ' . $token;
                        } else {
                            $success = 'If that email exists in our system, a reset token has been generated.';
                        }
                    }
                } elseif ($action === 'reset') {
                    $token = sanitize($_POST['token'] ?? '');
                    $newPassword = $_POST['new_password'] ?? '';
                    $confirmPassword = $_POST['confirm_password'] ?? '';

                    if (empty($token)) $errors[] = 'Reset token is required.';
                    if (empty($newPassword) || strlen($newPassword) < 6) $errors[] = 'Password must be at least 6 characters.';
                    if ($newPassword !== $confirmPassword) $errors[] = 'Passwords do not match.';

                    if (empty($errors)) {
                        $resetRecord = $this->userModel->validateResetToken($token);
                        if ($resetRecord) {
                            $this->userModel->updatePassword($resetRecord['user_id'], $newPassword);
                            $this->userModel->markTokenUsed($resetRecord['id']);

                            AuditLog::log($resetRecord['user_id'], 'PASSWORD_RESET', 'users', $resetRecord['user_id'], 'Password reset via token');
                            setFlash('success', 'Password reset successfully. You can now log in.');
                            header('Location: ' . BASE_URL . '?url=auth/login');
                            exit;
                        } else {
                            $errors[] = 'Invalid or expired reset token.';
                        }
                    }
                }
            }
        }

        $pageTitle = 'Forgot Password';
        require_once APP_ROOT . '/views/auth/forgot-password.php';
    }

    /**
     * Redirect to the appropriate dashboard
     */
    private function redirectToDashboard(): void
    {
        $role = $_SESSION['user_role'] ?? 'student';
        switch ($role) {
            case 'super_admin':
            case 'admin':
                header('Location: ' . BASE_URL . '?url=dashboard/index');
                break;
            case 'staff':
                header('Location: ' . BASE_URL . '?url=dashboard/staff');
                break;
            case 'student':
                header('Location: ' . BASE_URL . '?url=dashboard/student');
                break;
            default:
                header('Location: ' . BASE_URL . '?url=auth/login');
        }
        exit;
    }
}
