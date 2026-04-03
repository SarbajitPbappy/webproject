<?php
/**
 * HostelEase — Payroll Controller
 */

require_once APP_ROOT . '/app/models/Payroll.php';
require_once APP_ROOT . '/app/models/AuditLog.php';

class PayrollController
{
    private Payroll $payrollModel;

    public function __construct()
    {
        $this->payrollModel = new Payroll();
    }

    /**
     * Dashboard for staff to view their salary and apply
     */
    public function index(): void
    {
        requireRole(['staff', 'admin']);
        
        $userId = $_SESSION['user_id'];
        $staffDetails = $this->payrollModel->getStaffDetails($userId);
        
        // If somehow they don't have details, they can't see this yet
        if (!$staffDetails) {
            setFlash('error', 'Payroll details not configured for your account yet.');
            header('Location: ' . BASE_URL . '?url=dashboard/index');
            exit;
        }

        $history = $this->payrollModel->getUserSlipHistory($userId);
        $currentMonth = date('Y-m');
        $hasAppliedThisMonth = $this->payrollModel->getSlipByMonth($userId, $currentMonth) !== null;

        $pageTitle = 'My Payroll';
        ob_start();
        require_once APP_ROOT . '/views/payroll/staff_index.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Handle staff applying for pay slip
     */
    public function apply(): void
    {
        requireRole(['staff', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyToken()) {
            $userId = $_SESSION['user_id'];
            $staffDetails = $this->payrollModel->getStaffDetails($userId);
            $monthYear = date('Y-m');

            if ($this->payrollModel->getSlipByMonth($userId, $monthYear)) {
                setFlash('error', 'You have already applied for this month.');
            } else if ($staffDetails) {
                $base = $staffDetails['current_salary'];
                $this->payrollModel->applyForSlip($userId, $monthYear, $base);
                
                AuditLog::log($userId, 'CREATE', 'pay_slips', null, "Applied for payroll slip $monthYear");
                setFlash('success', 'Payroll slip application sent to administration successfully.');
            }
        }
        
        header('Location: ' . BASE_URL . '?url=payroll/index');
        exit;
    }

    /**
     * Admin view for reviewing pending payslips
     */
    public function review(): void
    {
        requireRole(['super_admin', 'admin']);

        $pendingSlips = $this->payrollModel->getPendingSlipsForAdmin();
        
        $pageTitle = 'Review Payroll Applications';
        ob_start();
        require_once APP_ROOT . '/views/payroll/review.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Admin process application
     */
    public function processApproval(): void
    {
        requireRole(['super_admin', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyToken()) {
            $id = sanitizeInt($_POST['slip_id'] ?? 0);
            
            $data = [
                'bonus' => sanitizeFloat($_POST['performance_bonus'] ?? 0),
                'deductions' => sanitizeFloat($_POST['deductions'] ?? 0),
                'office_days' => sanitizeInt($_POST['office_days'] ?? 0),
                'notes' => sanitize($_POST['admin_notes'] ?? '')
            ];

            if ($this->payrollModel->approveSlip($id, $_SESSION['user_id'], $data)) {
                AuditLog::log($_SESSION['user_id'], 'UPDATE', 'pay_slips', $id, "Approved payroll slip");
                setFlash('success', 'Pay slip approved and forwarded to Super Admin for distribution.');
            } else {
                setFlash('error', 'Failed to approve slip.');
            }
        }
        
        header('Location: ' . BASE_URL . '?url=payroll/review');
        exit;
    }

    /**
     * Super Admin view for paying approved slips
     */
    public function distribute(): void
    {
        requireRole(['super_admin']);

        $approvedSlips = $this->payrollModel->getApprovedSlipsForPayment();

        $pageTitle = 'Distribute Salary';
        ob_start();
        require_once APP_ROOT . '/views/payroll/distribute.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Super Admin actually processes payment
     */
    public function pay(): void
    {
        requireRole(['super_admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyToken()) {
            $id = sanitizeInt($_POST['slip_id'] ?? 0);
            
            $slip = $this->payrollModel->getSlipById($id);
            if ($slip && $slip['status'] === 'approved') {
                $db = Database::getInstance();
                $db->beginTransaction();

                try {
                    // Mark as paid
                    $this->payrollModel->markAsPaid($id, $_SESSION['user_id']);

                    // Insert as expense in transactions
                    $db->query("
                        INSERT INTO transactions (type, amount, reference_type, reference_id, description, transaction_date, recorded_by)
                        VALUES ('expense', :amount, 'payroll', :ref, :desc, CURDATE(), :uid)
                    ", [
                        ':amount' => $slip['net_salary'],
                        ':desc'   => "Salary disbursement for {$slip['full_name']} ({$slip['month_year']})",
                        ':ref'    => $id,
                        ':uid'    => $_SESSION['user_id']
                    ]);

                    $db->commit();
                    AuditLog::log($_SESSION['user_id'], 'UPDATE', 'pay_slips', $id, "Paid salary slip");
                    setFlash('success', 'Salary successfully distributed. Hostel funds deducted.');
                } catch (\Exception $e) {
                    $db->rollBack();
                    setFlash('error', 'Error recording transaction.');
                }
            }
        }
        
        header('Location: ' . BASE_URL . '?url=payroll/distribute');
        exit;
    }
}
