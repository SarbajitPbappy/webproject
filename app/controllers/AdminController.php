<?php
/**
 * HostelEase — Admin Dashboard & Profile Controller
 */

require_once APP_ROOT . '/app/models/Student.php';
require_once APP_ROOT . '/app/models/Room.php';
require_once APP_ROOT . '/app/models/Payment.php';
require_once APP_ROOT . '/app/models/Complaint.php';
require_once APP_ROOT . '/app/models/Notice.php';
require_once APP_ROOT . '/app/models/AuditLog.php';
require_once APP_ROOT . '/app/models/Allocation.php';
require_once APP_ROOT . '/app/models/BillingCharge.php';

class AdminController
{
    public function index(): void
    {
        // Require authentication for any dashboard access
        requireAuth();
        
        $role = $_SESSION['user_role'] ?? '';
        
        // Internal routing based on user role
        switch ($role) {
            case 'super_admin':
            case 'admin':
                $this->dashboard();
                break;
            case 'student':
                $this->student();
                break;
            case 'staff':
                $this->staff();
                break;
            default:
                setFlash('error', 'Unauthorized role access.');
                header('Location: ' . BASE_URL . '?url=auth/login');
                exit;
        }
    }

    public function dashboard(): void
    {
        requireRole(['super_admin', 'admin']);

        $studentModel = new Student();
        $roomModel = new Room();
        $paymentModel = new Payment();
        $complaintModel = new Complaint();
        $noticeModel = new Notice();

        // Financial Totals from Transactions Ledger
        $db = Database::getInstance();
        $totalIncome = (float)($db->query("SELECT SUM(amount) as s FROM transactions WHERE type='income'")->fetch()['s'] ?? 0);
        $totalExpense = (float)($db->query("SELECT SUM(amount) as s FROM transactions WHERE type='expense'")->fetch()['s'] ?? 0);
        $totalBalance = $totalIncome - $totalExpense;

        // KPIs
        $billingModel = new BillingCharge();

        $kpi = [
            'total_students'           => $studentModel->count(),
            'active_students'          => $studentModel->count('active'),
            'total_rooms'              => $roomModel->count(),
            'occupancy'                => $roomModel->getOccupancyStats(),
            'outstanding_count'        => $paymentModel->outstandingCount(),
            'pending_billing_lines'    => $billingModel->countPendingGlobally(),
            'open_complaints'          => $complaintModel->countOpen(),
            'total_balance'            => $totalBalance,
            'monthly_revenue'          => $paymentModel->totalRevenue(date('Y-m-01'), date('Y-m-t')),
        ];

        $recentActivity = AuditLog::recent(8);
        $overdueComplaints = $complaintModel->getSLAOverdue();
        $recentNotices = $noticeModel->getRecent(5);

        $pageTitle = 'Dashboard';

        ob_start();
        require_once APP_ROOT . '/views/dashboard/admin.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    public function student(): void
    {
        requireRole(['student']);

        $studentModel = new Student();
        $allocationModel = new Allocation();
        $paymentModel = new Payment();
        $complaintModel = new Complaint();
        $noticeModel = new Notice();

        $billingModel = new BillingCharge();

        $student = $studentModel->findByUserId($_SESSION['user_id']);
        $allocation = $student ? $allocationModel->findActiveByStudent($student['id']) : null;
        $payments = $student ? $paymentModel->findByStudent($student['id']) : [];
        $billingPending = $student ? $billingModel->pendingForStudent((int) $student['id']) : [];
        $complaints = $student ? $complaintModel->findByStudent($student['id']) : [];
        $notices = $noticeModel->getRecent(5);

        $pageTitle = 'My Dashboard';
        ob_start();
        require_once APP_ROOT . '/views/dashboard/student.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    public function staff(): void
    {
        requireRole(['staff']);

        $complaintModel = new Complaint();
        $noticeModel = new Notice();

        $assignedComplaints = $complaintModel->findByStaff($_SESSION['user_id']);
        $notices = $noticeModel->getRecent(5);

        $pageTitle = 'Staff Dashboard';

        ob_start();
        require_once APP_ROOT . '/views/dashboard/staff.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    // Redundant profile method removed. Use ProfileController@index instead.
}
