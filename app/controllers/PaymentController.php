<?php
/**
 * HostelEase — Payment Controller (Student & Warden)
 */

require_once APP_ROOT . '/app/models/Payment.php';
require_once APP_ROOT . '/app/models/AuditLog.php';
require_once APP_ROOT . '/app/models/Student.php';
require_once APP_ROOT . '/app/models/BillingCharge.php';
require_once APP_ROOT . '/app/models/Allocation.php';

class PaymentController
{
    private Payment $paymentModel;
    private Student $studentModel;
    private BillingCharge $billingModel;

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
        $this->billingModel = new BillingCharge();
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

        $billingPending = [];
        if (in_array($role, ['student', 'staff'], true)) {
            $stuRow = $this->studentModel->findByUserId($userId);
            if ($stuRow) {
                $billingPending = $this->billingModel->pendingForStudent((int) $stuRow['id']);
            }
        }

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

        $studentId = (int) $student['id'];
        $pendingCharges = $this->billingModel->pendingForStudent($studentId);
        $prepayFees = $this->feesAvailableForPrepay($studentId);

        if ($pendingCharges === [] && $prepayFees === []) {
            setFlash(
                'info',
                'You have no outstanding bills and no fees available for advance payment right now. The warden posts slips for rent, meals, and utilities—you can also prepay eligible fees here when they appear.'
            );
            header('Location: ' . BASE_URL . '?url=payments/index');
            exit;
        }

        $pageTitle = 'Make Payment';

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

            $billingChargeId = sanitizeInt($_POST['billing_charge_id'] ?? 0);
            if ($billingChargeId <= 0) {
                setFlash('error', 'Please select a bill to pay.');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            $charge = $this->billingModel->findById($billingChargeId);
            if (
                !$charge
                || $charge['status'] !== 'pending'
                || (int) $charge['student_id'] !== (int) $student['id']
            ) {
                setFlash('error', 'That bill is not available for payment.');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            $feeId = (int) $charge['fee_id'];
            $amount = (float) $charge['amount_due'];
            $periodMonth = (string) $charge['period_month'];

            $cardNumber = trim((string)($_POST['card_number'] ?? ''));
            $expiry = trim((string)($_POST['expiry'] ?? ''));
            $cvv = trim((string)($_POST['cvv'] ?? ''));
            $cardName = trim((string)($_POST['card_name'] ?? ''));

            if (empty($cardNumber) || empty($expiry) || empty($cvv) || empty($cardName)) {
                setFlash('error', 'Please fill in all payment details correctly.');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            if ($amount <= 0) {
                setFlash('error', 'Invalid bill amount.');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            $paymentId = $this->paymentModel->record([
                'student_id'        => (int) $student['id'],
                'fee_id'            => $feeId,
                'amount_paid'       => $amount,
                'payment_date'      => date('Y-m-d'),
                'payment_method'    => 'online',
                'recorded_by'       => $userId,
                'month_year'        => $periodMonth,
                'notes'             => 'Online portal — billing slip #' . $billingChargeId,
                'billing_charge_id' => $billingChargeId,
            ]);

            AuditLog::log($userId, 'PAYMENT', 'payments', $paymentId, "Made online payment of ৳{$amount}");
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
     * Advance payment for a billing month before the warden issues a slip (same fee + YYYY-MM as monthly issue).
     */
    public function processPrepay(): void
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

            $userId = (int) $_SESSION['user_id'];
            $role = $_SESSION['user_role'] ?? 'student';
            $student = $this->studentModel->findByUserId($userId);
            if (!$student) {
                setFlash('error', 'Student profile not found.');
                header('Location: ' . BASE_URL . '?url=dashboard/' . ($role === 'staff' ? 'staff' : 'student'));
                exit;
            }

            $studentId = (int) $student['id'];
            $feeId = sanitizeInt($_POST['fee_id'] ?? 0);
            $periodMonth = sanitize($_POST['period_month'] ?? '');

            if ($feeId <= 0 || !preg_match('/^\d{4}-\d{2}$/', $periodMonth)) {
                setFlash('error', 'Choose a valid fee and billing month (YYYY-MM).');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            $allowed = $this->feesAvailableForPrepay($studentId);
            $feeRow = null;
            foreach ($allowed as $f) {
                if ((int) $f['id'] === $feeId) {
                    $feeRow = $f;
                    break;
                }
            }
            if (!$feeRow) {
                setFlash('error', 'That fee is not available for advance payment for your account.');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            $catalog = (float) $feeRow['amount'];
            $already = $this->paymentModel->totalPaidForFeeMonth($studentId, $feeId, $periodMonth);
            if ($already >= $catalog - 0.009) {
                setFlash('warning', 'You have already paid this fee for ' . $periodMonth . '. No duplicate prepayment needed.');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            $cardNumber = trim((string) ($_POST['card_number'] ?? ''));
            $expiry = trim((string) ($_POST['expiry'] ?? ''));
            $cvv = trim((string) ($_POST['cvv'] ?? ''));
            $cardName = trim((string) ($_POST['card_name'] ?? ''));
            if ($cardNumber === '' || $expiry === '' || $cvv === '' || $cardName === '') {
                setFlash('error', 'Please fill in all payment details correctly.');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            $payAmount = max(0.0, $catalog - $already);
            if ($payAmount <= 0.009) {
                setFlash('info', 'Nothing left to pay for that fee and month.');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            $paymentId = $this->paymentModel->record([
                'student_id'           => $studentId,
                'fee_id'               => $feeId,
                'amount_paid'          => $payAmount,
                'payment_date'         => date('Y-m-d'),
                'payment_method'       => 'online',
                'recorded_by'          => $userId,
                'month_year'           => $periodMonth,
                'notes'                => 'Advance prepay (before slip) — ' . ($feeRow['name'] ?? '') . ' · ' . $periodMonth,
                'skip_billing_resolve' => true,
            ]);

            AuditLog::log($userId, 'PAYMENT', 'payments', $paymentId, "Prepaid ৳{$payAmount} for fee #{$feeId} {$periodMonth}");
            setFlash(
                'success',
                'Advance payment recorded for '
                    . ($feeRow['name'] ?? 'fee')
                    . ' (' . $periodMonth . '). When the office issues monthly bills for that month, you will not get a duplicate charge for this amount.'
            );
            header('Location: ' . BASE_URL . '?url=payments/balanceSheet');
            exit;
        } catch (Exception $e) {
            error_log('Prepay error: ' . $e->getMessage());
            setFlash('error', 'Payment failed. Please try again.');
            header('Location: ' . BASE_URL . '?url=payments/makePayment');
            exit;
        }
    }

    /**
     * Student-facing ledger: credits, slips, prepayments, paid history.
     */
    public function balanceSheet(): void
    {
        requireRole(['student', 'admin', 'staff']);

        $userId = (int) $_SESSION['user_id'];
        $role = $_SESSION['user_role'] ?? 'student';
        $student = $this->studentModel->findByUserId($userId);
        if (!$student) {
            setFlash('error', 'Student profile not found.');
            header('Location: ' . BASE_URL . '?url=dashboard/' . ($role === 'staff' ? 'staff' : 'student'));
            exit;
        }

        $studentId = (int) $student['id'];
        $creditBalance = $this->studentModel->getBillingCreditBalance($studentId);
        $pendingCharges = $this->billingModel->pendingForStudent($studentId);
        $paidCharges = $this->billingModel->paidChargesForStudent($studentId);
        $payments = $this->paymentModel->findByStudent($studentId);
        $allocationModel = new Allocation();
        $allocation = $allocationModel->findActiveByStudent($studentId);

        $pendingSlipTotal = 0.0;
        foreach ($pendingCharges as $row) {
            $pendingSlipTotal += (float) $row['amount_due'];
        }

        $pageTitle = 'Fee balance sheet';
        ob_start();
        require_once APP_ROOT . '/views/payments/balance_sheet.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Monthly fees this student may prepay (room rent matches current room tier or entitlement).
     *
     * @return list<array<string,mixed>>
     */
    private function feesAvailableForPrepay(int $studentId): array
    {
        $fees = $this->paymentModel->getFeesForMonthlyIssue();
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            return [];
        }

        $allocationModel = new Allocation();
        $alloc = $allocationModel->findActiveByStudent($studentId);
        $tier = null;
        if ($alloc && !empty($alloc['room_type'])) {
            $tier = (string) $alloc['room_type'];
        } elseif (!empty($student['entitled_room_type'])) {
            $tier = (string) $student['entitled_room_type'];
        }

        $out = [];
        foreach ($fees as $f) {
            $cat = (string) ($f['fee_category'] ?? '');
            if ($cat === 'room_rent' && !empty($f['maps_room_type'])) {
                if ($tier !== null && (string) $f['maps_room_type'] === $tier) {
                    $out[] = $f;
                }
                continue;
            }
            $out[] = $f;
        }

        return $out;
    }

    /**
     * Pay all pending warden slips in one checkout (simulated card).
     */
    public function processPortalAll(): void
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

            $userId = (int) $_SESSION['user_id'];
            $role = $_SESSION['user_role'] ?? 'student';
            $student = $this->studentModel->findByUserId($userId);
            if (!$student) {
                setFlash('error', 'Student profile not found.');
                header('Location: ' . BASE_URL . '?url=dashboard/' . ($role === 'staff' ? 'staff' : 'student'));
                exit;
            }

            $studentId = (int) $student['id'];
            $pending = $this->billingModel->pendingForStudent($studentId);
            if ($pending === []) {
                setFlash('error', 'You have no outstanding bills to pay.');
                header('Location: ' . BASE_URL . '?url=payments/index');
                exit;
            }

            $grandTotal = 0.0;
            foreach ($pending as $row) {
                $grandTotal += (float) $row['amount_due'];
            }
            if ($grandTotal <= 0) {
                setFlash('error', 'Invalid bill total.');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            $cardNumber = trim((string) ($_POST['card_number'] ?? ''));
            $expiry = trim((string) ($_POST['expiry'] ?? ''));
            $cvv = trim((string) ($_POST['cvv'] ?? ''));
            $cardName = trim((string) ($_POST['card_name'] ?? ''));

            if ($cardNumber === '' || $expiry === '' || $cvv === '' || $cardName === '') {
                setFlash('error', 'Please fill in all payment details correctly.');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            $paymentIds = $this->paymentModel->recordAllPendingBillingCharges($studentId, $userId);
            if ($paymentIds === []) {
                setFlash('error', 'No bills could be paid. Refresh and try again.');
                header('Location: ' . BASE_URL . '?url=payments/makePayment');
                exit;
            }

            $n = count($paymentIds);
            $lastId = (int) $paymentIds[array_key_last($paymentIds)];
            AuditLog::log(
                $userId,
                'PAYMENT',
                'payments',
                $lastId,
                "Batch online payment: {$n} slip(s), total ৳{$grandTotal}"
            );

            setFlash(
                'success',
                "Paid all outstanding bills in one step ({$n} slip" . ($n === 1 ? '' : 's') . ', ৳' . number_format($grandTotal, 2) . ' total). Each fee appears as its own receipt in your history.'
            );
            header('Location: ' . BASE_URL . '?url=payments/index&success=1');
            exit;
        } catch (Exception $e) {
            error_log('Payment batch error: ' . $e->getMessage());
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
                'Recorded payment of ৳' . $data['amount_paid']
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
