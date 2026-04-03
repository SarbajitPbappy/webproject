<?php
/**
 * HostelEase — User Management Controller (Super Admin ONLY)
 * Admins/Wardens CANNOT access this controller at all.
 */
require_once APP_ROOT . '/app/models/User.php';
require_once APP_ROOT . '/app/models/Student.php';
require_once APP_ROOT . '/app/models/AuditLog.php';
require_once APP_ROOT . '/app/helpers/upload.php';

class UserController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /** List all users */
    public function index(): void
    {
        requireRole(['super_admin']);

        $role   = sanitize($_GET['role'] ?? '');
        $status = sanitize($_GET['status'] ?? '');
        $search = sanitize($_GET['search'] ?? '');

        $db = Database::getInstance();

        $where  = ['1=1'];
        $params = [];
        if ($role)   { $where[] = 'u.role = :role';   $params[':role'] = $role; }
        if ($status) { $where[] = 'u.status = :status'; $params[':status'] = $status; }
        if ($search) {
            $where[] = '(u.full_name LIKE :search OR u.email LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $sql = "SELECT u.id, u.full_name, u.email, u.role, u.status, u.created_at,
                       s.student_id_no
                FROM users u
                LEFT JOIN students s ON s.user_id = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY FIELD(u.role,'super_admin','admin','staff','student'), u.full_name";

        $users = $db->query($sql, $params)->fetchAll();

        // Counts per role
        $roleCounts = [];
        foreach (['super_admin','admin','staff','student'] as $r) {
            $roleCounts[$r] = (int)$db->query("SELECT COUNT(*) as c FROM users WHERE role=:r", [':r'=>$r])->fetch()['c'];
        }

        $pageTitle = 'User Management';
        ob_start();
        require_once APP_ROOT . '/views/users/index.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /** Show create user form */
    public function create(): void
    {
        requireRole(['super_admin']);
        $errors = [];
        $pageTitle = 'Create User';
        ob_start();
        require_once APP_ROOT . '/views/users/create.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /** Store new user — auto-generates role-specific IDs */
    public function store(): void
    {
        requireRole(['super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?url=users/create');
            exit;
        }

        if (!verifyToken()) {
            setFlash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '?url=users/create');
            exit;
        }

        $fullName = sanitize($_POST['full_name'] ?? '');
        $email    = sanitizeEmail($_POST['email'] ?? '');
        $role     = sanitize($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';
        $status   = sanitize($_POST['status'] ?? 'active');

        $errors = [];
        if (empty($fullName))  $errors[] = 'Full name is required.';
        if (empty($email))     $errors[] = 'Valid email is required.';
        if (!in_array($role, ['super_admin','admin','staff','student'])) $errors[] = 'Invalid role.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($this->userModel->findByEmail($email)) $errors[] = 'Email already in use.';

        if (!empty($errors)) {
            $pageTitle = 'Create User';
            ob_start();
            require_once APP_ROOT . '/views/users/create.php';
            $viewContent = ob_get_clean();
            require_once APP_ROOT . '/views/layouts/main.php';
            return;
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $createOccupantProfile = isset($_POST['create_occupant_profile']) && (string)$_POST['create_occupant_profile'] === '1';

            // Handle optional photo upload
            $photoName = null;
            if (!empty($_FILES['profile_photo']['name'])) {
                $upload = uploadFile($_FILES['profile_photo'], 'students');
                if ($upload['success']) $photoName = $upload['filename'];
            }

            $userId = $this->userModel->create([
                'full_name'    => $fullName,
                'email'        => $email,
                'password'     => $password,
                'role'         => $role,
                'status'       => $status,
                'profile_photo'=> $photoName,
            ]);

            $userData = $this->userModel->findById($userId);
            $systemId = $userData['system_id_no'];

            // Auto-generate extra records
            if ($role === 'student') {
                $phone        = sanitizePhone($_POST['phone'] ?? '');
                $guardianName = sanitize($_POST['guardian_name'] ?? '');
                $guardianPhone= sanitizePhone($_POST['guardian_phone'] ?? '');
                $enrolledDate = sanitizeDate($_POST['enrolled_date'] ?? date('Y-m-d'));

                $db->query(
                    "INSERT INTO students (user_id, student_id_no, phone, guardian_name, guardian_phone, enrolled_date)
                     VALUES (:uid, :sid, :phone, :gname, :gphone, :edate)",
                    [':uid'=>$userId, ':sid'=>$systemId, ':phone'=>$phone,
                     ':gname'=>$guardianName, ':gphone'=>$guardianPhone, ':edate'=>$enrolledDate]
                );
            }

            // For staff/admin, create staff_details with default salary
            if (in_array($role, ['staff', 'admin'])) {
                $salary = ($role === 'admin') ? 25000.00 : 12000.00;
                $db->query(
                    "INSERT INTO staff_details (user_id, basic_salary, join_date) VALUES (:uid, :salary, CURDATE())",
                    [':uid' => $userId, ':salary' => $salary]
                );
            }

            // Optional: register admin/staff as hostel occupants (enables room allocation + fee billing).
            if (in_array($role, ['staff', 'admin'], true) && $createOccupantProfile) {
                $db->query(
                    "INSERT INTO students (user_id, student_id_no, enrolled_date)
                     VALUES (:uid, :sid, CURDATE())",
                    [':uid' => $userId, ':sid' => $systemId]
                );
            }

            $db->commit();

            AuditLog::log($_SESSION['user_id'], 'CREATE', 'users', $userId, "Created {$role} user: {$email}");
            setFlash('success', ucfirst(str_replace('_',' ',$role)) . " '{$fullName}' created successfully! ID: {$systemId}");
            header('Location: ' . BASE_URL . '?url=users/index');
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            error_log('Create user failed: ' . $e->getMessage());
            setFlash('error', 'Failed to create user: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '?url=users/create');
            exit;
        }
    }

    /** Toggle user status */
    public function toggleStatus(): void
    {
        requireRole(['super_admin']);

        if (!verifyToken()) {
            setFlash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '?url=users/index');
            exit;
        }

        $userId    = sanitizeInt($_POST['user_id'] ?? 0);
        $newStatus = sanitize($_POST['status'] ?? 'active');

        if (!in_array($newStatus, ['active','suspended','inactive'])) {
            setFlash('error', 'Invalid status.');
            header('Location: ' . BASE_URL . '?url=users/index');
            exit;
        }

        $db = Database::getInstance();
        $db->query("UPDATE users SET status=:s WHERE id=:id", [':s'=>$newStatus, ':id'=>$userId]);
        AuditLog::log($_SESSION['user_id'], 'UPDATE', 'users', $userId, "Status changed to {$newStatus}");

        setFlash('success', 'User status updated.');
        header('Location: ' . BASE_URL . '?url=users/index');
        exit;
    }
}
