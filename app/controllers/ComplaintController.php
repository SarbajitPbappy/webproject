<?php
/**
 * HostelEase — Complaint Controller
 */

require_once APP_ROOT . '/app/models/Complaint.php';
require_once APP_ROOT . '/app/models/Student.php';
require_once APP_ROOT . '/app/models/User.php';
require_once APP_ROOT . '/app/models/AuditLog.php';

class ComplaintController
{
    private Complaint $complaintModel;
    private Student $studentModel;
    private User $userModel;

    public function __construct()
    {
        $this->complaintModel = new Complaint();
        $this->studentModel = new Student();
        $this->userModel = new User();
    }

    public function index(): void
    {
        requireAuth();

        $filters = [];
        if (!empty($_GET['status'])) $filters['status'] = sanitize($_GET['status']);
        if (!empty($_GET['priority'])) $filters['priority'] = sanitize($_GET['priority']);

        // Students see only their own; Staff see assigned; Admin sees all
        if (hasRole(['student'])) {
            $student = $this->studentModel->findByUserId($_SESSION['user_id']);
            if ($student) $filters['student_id'] = $student['id'];
        } elseif (hasRole(['staff'])) {
            $filters['assigned_to'] = $_SESSION['user_id'];
        }

        $complaints = $this->complaintModel->all($filters);
        $overdueComplaints = hasRole(['super_admin', 'admin']) ? $this->complaintModel->getSLAOverdue() : [];
        $pageTitle = 'Complaints';

        ob_start();
        require_once APP_ROOT . '/views/complaints/index.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    public function create(): void
    {
        requireAuth();

        $errors = [];
        $data = [];
        $pageTitle = 'Submit Complaint';

        ob_start();
        require_once APP_ROOT . '/views/complaints/create.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    public function store(): void
    {
        requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyToken()) {
            setFlash('error', 'Invalid request.');
            header('Location: ' . BASE_URL . '?url=complaints/create');
            exit;
        }

        // Determine student_id
        if (hasRole(['student'])) {
            $student = $this->studentModel->findByUserId($_SESSION['user_id']);
            $studentId = $student ? $student['id'] : 0;
        } else {
            $studentId = sanitizeInt($_POST['student_id'] ?? 0);
        }

        $data = [
            'student_id'  => $studentId,
            'category'    => sanitize($_POST['category'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'priority'    => sanitize($_POST['priority'] ?? 'medium'),
        ];

        $errors = [];
        if (!$data['student_id']) $errors[] = 'Student not identified.';
        if (empty($data['category'])) $errors[] = 'Category is required.';
        if (empty($data['description'])) $errors[] = 'Description is required.';

        if (!empty($errors)) {
            $pageTitle = 'Submit Complaint';
            ob_start();
            require_once APP_ROOT . '/views/complaints/create.php';
            $viewContent = ob_get_clean();
            require_once APP_ROOT . '/views/layouts/main.php';
            return;
        }

        try {
            $complaintId = $this->complaintModel->create($data);
            AuditLog::log($_SESSION['user_id'], 'CREATE', 'complaints', $complaintId, 'New complaint: ' . $data['category']);
            setFlash('success', 'Complaint submitted successfully!');
            header('Location: ' . BASE_URL . '?url=complaints/show/' . $complaintId);
            exit;
        } catch (\Exception $e) {
            error_log('ComplaintController::store() error: ' . $e->getMessage());
            $errors[] = 'An error occurred.';
            $pageTitle = 'Submit Complaint';
            ob_start();
            require_once APP_ROOT . '/views/complaints/create.php';
            $viewContent = ob_get_clean();
            require_once APP_ROOT . '/views/layouts/main.php';
        }
    }

    public function show(int $id = 0): void
    {
        requireAuth();
        $id = sanitizeInt($id ?: ($_GET['id'] ?? 0));

        $complaint = $this->complaintModel->findById($id);
        if (!$complaint) {
            setFlash('error', 'Complaint not found.');
            header('Location: ' . BASE_URL . '?url=complaints/index');
            exit;
        }

        // Students can only see their own
        if (hasRole(['student'])) {
            $student = $this->studentModel->findByUserId($_SESSION['user_id']);
            if (!$student || $complaint['student_id'] !== $student['id']) {
                setFlash('error', 'You can only view your own complaints.');
                header('Location: ' . BASE_URL . '?url=complaints/index');
                exit;
            }
        }

        $staff = $this->userModel->getStaff();
        $pageTitle = 'Complaint Details';

        ob_start();
        require_once APP_ROOT . '/views/complaints/show.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    public function update(int $id = 0): void
    {
        requireRole(['super_admin', 'admin', 'staff']);
        $id = sanitizeInt($id ?: ($_POST['id'] ?? 0));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyToken()) {
            setFlash('error', 'Invalid request.');
            header('Location: ' . BASE_URL . '?url=complaints/show/' . $id);
            exit;
        }

        $newStatus = sanitize($_POST['status'] ?? '');
        if ($newStatus && in_array($newStatus, ['open', 'in_progress', 'resolved', 'closed'])) {
            $this->complaintModel->updateStatus($id, $newStatus);
            AuditLog::log($_SESSION['user_id'], 'UPDATE', 'complaints', $id, 'Status changed to: ' . $newStatus);
            setFlash('success', 'Complaint status updated.');
        }

        header('Location: ' . BASE_URL . '?url=complaints/show/' . $id);
        exit;
    }

    public function assign(int $id = 0): void
    {
        requireRole(['super_admin', 'admin']);
        $id = sanitizeInt($id ?: ($_POST['id'] ?? 0));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyToken()) {
            setFlash('error', 'Invalid request.');
            header('Location: ' . BASE_URL . '?url=complaints/show/' . $id);
            exit;
        }

        $staffId = sanitizeInt($_POST['assigned_to'] ?? 0);
        if ($staffId) {
            $this->complaintModel->assignTo($id, $staffId);
            AuditLog::log($_SESSION['user_id'], 'UPDATE', 'complaints', $id, 'Assigned to staff #' . $staffId);
            setFlash('success', 'Complaint assigned to staff member.');
        }

        header('Location: ' . BASE_URL . '?url=complaints/show/' . $id);
        exit;
    }
}
