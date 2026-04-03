<?php
/**
 * HostelEase — Payment Controller (Student & Warden)
 */

require_once APP_ROOT . '/app/models/Payment.php';
require_once APP_ROOT . '/app/models/AuditLog.php';
require_once APP_ROOT . '/app/models/Student.php';

class PaymentController
{
    private Payment $paymentModel;
    private Student $studentModel;

    public function __construct()
    {
        // Ensure user is logged in
        if (!isLoggedIn()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'] ?? '';
            setFlash('error', 'Please log in to manage payments.');
            header('Location: ' . BASE_URL . '?url=auth/login');
            exit;
        }
        $this->paymentModel = new Payment();
        $this->studentModel = new Student();
    }

    /**
     * Show payments dashboard (Student + Admin)
     */
    public function index(): void
    {
        // Pass 'success' messages from external redirects
        if (isset($_GET['success']) && $_GET['success'] == 1) {
             setFlash('success', 'Payment successful!');
        }
        requireRole(['super_admin', 'admin', 'student', 'staff']);

        $role = $_SESSION['user_role'] ?? '';
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $filters = [];
        $students = [];

        if (in_array($role, ['student', 'staff'], true)) {
            $student = $this->studentModel->findByUserId($userId);
            if (!$student) {
                setFlash('error', 'Student profile not found.');
                header('Location: ' . BASE_URL . '?url=dashboard/' . ($role === 'staff' ? 'staff' : 'student'));
                exit;
            }

            $studentId = (int)$student['id'];
            $filters['student_id'] = $studentId;

            $students = [[
                'id' => $studentId,
                'student_id_no' => $student['student_id_no'],
                'full_name' => $student['full_name'],
            ]];

            $monthYear = sanitize($_GET['month_year'] ?? '');
            if (!empty($monthYear)) {
                $filters['month_year'] = $monthYear;
            }

            $pageTitle = 'My Payments';
        } else {
            $students = $this->studentModel->getForDropdown();

            if (!empty($_GET['student_id'])) {
                $sid = sanitizeInt($_GET['student_id']);
                if ($sid > 0) {
                    $filters['student_id'] = $sid;
                }
            }

            $monthYear = sanitize($_GET['month_year'] ?? '');
            if (!empty($monthYear)) {
                $filters['month_year'] = $monthYear;
            }

            $pageTitle = 'Payments';
        }

        $payments = $this->paymentModel->all($filters);
        ob_start();
        require_once APP_ROOT . '/views/payments/index.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Show online payment portal
     */
    public function makePayment(): void
    {
        // Online payment: only occupants (student/staff/admin), not super_admin.
        requireRole(['student', 'admin', 'staff']);

        $userId = (int)$_SESSION['user_id'];
        $role = $_SESSION['user_role'] ?? 'student';
        $student = $this->studentModel->findByUserId($userId);

        if (!$student) {
            setFlash('error', 'Student profile not found.');
            header('Location: ' . BASE_URL . '?url=dashboard/' . ($role === 'staff' ? 'staff' : 'student'));
            exit;
        }

        $studentId = (int)$student['id'];
        $dueAmount = $this->paymentModel->calculateStudentDue($studentId);
        
        if ($dueAmount <= 0) {
            setFlash('info', 'You have no pending dues to pay.');
            header('Location: ' . BASE_URL . '?url=payments/index');
            exit;
        }

        $pageTitle = 'Make Payment';
        
        // Fetch active fee structures for the dropdown
        $feeStructures = $this->paymentModel->getFeeStructures();
        
        ob_start();
        require_once APP_ROOT . '/views/payments/portal.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Process simulated online payment
     */
    public function processPortal(): void
    {
        try {
            requireRole(['student', 'admin', 'staff']);

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            if (!verifyToken()) {
                setFlash('error', 'Invalid security token.');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            $userId = (int)$_SESSION['user_id'];
            $role = $_SESSION['user_role'] ?? 'student';
            $student = $this->studentModel->findByUserId($userId);
            if (!$student) {
                setFlash('error', 'Student profile not found.');
                header('Location: ' . BASE_URL . '?url=dashboard/' . ($role === 'staff' ? 'staff' : 'student'));
                exit;
            }

            $feeId = sanitizeInt($_POST['fee_id'] ?? 0);
            if ($feeId <= 0) {
                setFlash('error', 'Please select a fee type.');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            $cardNumber = trim((string)($_POST['card_number'] ?? ''));
            $expiry = trim((string)($_POST['expiry'] ?? ''));
            $cvv = trim((string)($_POST['cvv'] ?? ''));
            $cardName = trim((string)($_POST['card_name'] ?? ''));

            if (empty($cardNumber) || empty($expiry) || empty($cvv) || empty($cardName)) {
                setFlash('error', 'Please fill in all payment details correctly.');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            // Do not trust the posted amount; use the fee_structures table value.
            $db = Database::getInstance();
            $fee = $db->query(
                "SELECT id, amount FROM fee_structures WHERE id = :fid AND is_active = TRUE LIMIT 1",
                [':fid' => $feeId]
            )->fetch();

            if (!$fee) {
                setFlash('error', 'Selected fee type is not available.');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            $amount = (float)$fee['amount'];
            if ($amount <= 0) {
                setFlash('error', 'Invalid fee amount.');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            $paymentId = $this->paymentModel->record([
                'student_id' => (int)$student['id'],
                'fee_id' => (int)$feeId,
                'amount_paid' => $amount,
                'payment_date' => date('Y-m-d'),
                'payment_method' => 'online',
                'recorded_by' => $userId,
                'month_year' => date('Y-m'),
                'notes' => 'Online portal payment',
            ]);

            AuditLog::log($userId, 'PAYMENT', 'payments', $paymentId, "Made online payment of ₹{$amount}");
            header('Location: ' . BASE_URL . '?url=payments/index&success=1');
            exit;

        } catch (Exception $e) {
            error_log("Payment Error: " . $e->getMessage());
            setFlash('error', 'Payment processing failed. Please try again or contact administration.');
            header('Location: ' . BASE_URL . '?url=payments/makePayment');
            exit;
        }
    }

    /**
     * Admin: show cash/bank payment entry form
     */
    public function record(): void
    {
        // Cash/bank recording is warden-only (admin). Super Admin can view but shouldn't create payments.
        requireRole(['admin']);

        $errors = [];
        $data = [];
        $students = $this->studentModel->getForDropdown();
        $feeStructures = $this->paymentModel->getFeeStructures();

        $pageTitle = 'Record Payment';
        ob_start();
        require_once APP_ROOT . '/views/payments/record.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Admin: store a recorded payment
     */
    public function store(): void
    {
        // Cash/bank recording is warden-only (admin). Super Admin can view but shouldn't create payments.
        requireRole(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?url=payments/record');
            exit;
        }

        if (!verifyToken()) {
            setFlash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '?url=payments/record');
            exit;
        }

        $errors = [];
        $userId = (int)$_SESSION['user_id'];

        $paymentDate = sanitizeDate($_POST['payment_date'] ?? '');
        $monthYear = sanitize($_POST['month_year'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');

        $paymentMethod = sanitize($_POST['payment_method'] ?? 'cash');
        $allowedMethods = ['cash', 'bank', 'online'];
        if (!in_array($paymentMethod, $allowedMethods, true)) {
            $errors[] = 'Invalid payment method.';
        }

        $data = [
            'student_id' => sanitizeInt($_POST['student_id'] ?? 0),
            'fee_id' => sanitizeInt($_POST['fee_id'] ?? 0),
            'amount_paid' => (float)($_POST['amount_paid'] ?? 0),
            'payment_date' => $paymentDate ?? date('Y-m-d'),
            'month_year' => !empty($monthYear) ? $monthYear : date('Y-m'),
            'payment_method' => $paymentMethod,
            'notes' => !empty($notes) ? $notes : null,
        ];

        if ($data['student_id'] <= 0) $errors[] = 'Student is required.';
        if ($data['fee_id'] <= 0) $errors[] = 'Fee type is required.';
        if ($data['amount_paid'] <= 0) $errors[] = 'Amount paid must be greater than 0.';

        if (!empty($errors)) {
            $pageTitle = 'Record Payment';
            $students = $this->studentModel->getForDropdown();
            $feeStructures = $this->paymentModel->getFeeStructures();

            // Re-render with entered values
            ob_start();
            require_once APP_ROOT . '/views/payments/record.php';
            $viewContent = ob_get_clean();
            require_once APP_ROOT . '/views/layouts/main.php';
            return;
        }

        try {
            $paymentId = $this->paymentModel->record([
                'student_id' => $data['student_id'],
                'fee_id' => $data['fee_id'],
                'amount_paid' => $data['amount_paid'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'recorded_by' => $userId,
                'month_year' => $data['month_year'],
                'notes' => $data['notes'],
            ]);

            AuditLog::log(
                $userId,
                'CREATE',
                'payments',
                $paymentId,
                'Recorded payment of ₹' . $data['amount_paid']
            );

            setFlash('success', 'Payment recorded successfully!');
            header('Location: ' . BASE_URL . '?url=payments/receiptView/' . $paymentId);
            exit;
        } catch (Exception $e) {
            error_log("Payment store error: " . $e->getMessage());
            $errors[] = 'Payment recording failed. Please try again.';

            $pageTitle = 'Record Payment';
            $students = $this->studentModel->getForDropdown();
            $feeStructures = $this->paymentModel->getFeeStructures();
            ob_start();
            require_once APP_ROOT . '/views/payments/record.php';
            $viewContent = ob_get_clean();
            require_once APP_ROOT . '/views/layouts/main.php';
        }
    }

    /**
     * View a payment receipt
     */
    public function receiptView(int $id = 0): void
    {
        requireRole(['super_admin', 'admin', 'student', 'staff']);

        $payment = $this->paymentModel->findById($id);
        if (!$payment) {
            setFlash('error', 'Payment not found.');
            header('Location: ' . BASE_URL . '?url=payments/index');
            exit;
        }

        if (in_array(($_SESSION['user_role'] ?? ''), ['student', 'staff'], true)) {
            $student = $this->studentModel->findByUserId((int)$_SESSION['user_id']);
            if (!$student || (int)$payment['student_id'] !== (int)$student['id']) {
                setFlash('error', 'You can only view your own receipts.');
                header('Location: ' . BASE_URL . '?url=payments/index');
                exit;
            }
        }

        $pageTitle = 'Payment Receipt';
        ob_start();
        require_once APP_ROOT . '/views/payments/receipt.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }
}
