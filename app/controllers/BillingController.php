<?php
/**
 * HostelEase — Warden-issued billing slips (monthly hall charges & enrollment).
 */

require_once APP_ROOT . '/app/models/BillingCharge.php';
require_once APP_ROOT . '/app/models/Payment.php';
require_once APP_ROOT . '/app/models/Student.php';
require_once APP_ROOT . '/app/models/AuditLog.php';

class BillingController
{
    private BillingCharge $billingModel;
    private Payment $paymentModel;
    private Student $studentModel;

    public function __construct()
    {
        if (!isLoggedIn()) {
            header('Location: ' . BASE_URL . '?url=auth/login');
            exit;
        }
        $this->billingModel = new BillingCharge();
        $this->paymentModel = new Payment();
        $this->studentModel = new Student();
    }

    /**
     * Issue recurring monthly / yearly-type slips (meal, utilities, rent, etc.).
     */
    public function issueMonthly(): void
    {
        requireRole(['super_admin', 'admin']);

        $errors = [];
        $fees = $this->paymentModel->getFeesForMonthlyIssue();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken()) {
                $errors[] = 'Invalid security token.';
            } else {
                $periodMonth = sanitize($_POST['period_month'] ?? '');
                if (!preg_match('/^\d{4}-\d{2}$/', $periodMonth)) {
                    $errors[] = 'Select a valid billing month (YYYY-MM).';
                }

                $feeIds = array_map('intval', $_POST['fee_ids'] ?? []);
                $feeIds = array_filter($feeIds, fn ($id) => $id > 0);
                if (empty($feeIds)) {
                    $errors[] = 'Select at least one fee to issue.';
                }

                $scope = sanitize($_POST['scope'] ?? 'residents');
                $studentIds = $scope === 'all_active'
                    ? $this->studentModel->allActiveStudentIds()
                    : $this->studentModel->activeResidentIds();

                if (empty($studentIds)) {
                    $errors[] = 'No students match the selected scope.';
                }

                if (empty($errors)) {
                    $n = $this->billingModel->issueBulk($studentIds, $periodMonth, $feeIds, (int) $_SESSION['user_id']);
                    AuditLog::log(
                        (int) $_SESSION['user_id'],
                        'CREATE',
                        'billing_charges',
                        null,
                        "Issued {$n} billing line(s) for {$periodMonth}"
                    );
                    setFlash('success', "Issued {$n} new billing line(s) for {$periodMonth}. Duplicates for the same student/fee/month were skipped.");
                    header('Location: ' . BASE_URL . '?url=billing/issueMonthly');
                    exit;
                }
            }
        }

        $pageTitle = 'Issue Monthly Bills';
        ob_start();
        require_once APP_ROOT . '/views/billing/issue_monthly.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Issue enrollment slips for one student (security deposit + room rent tier, etc.).
     */
    public function issueEnrollment(): void
    {
        requireRole(['super_admin', 'admin']);

        $errors = [];
        $fees = $this->paymentModel->getFeesForEnrollmentIssue();
        $students = $this->studentModel->getForDropdown();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken()) {
                $errors[] = 'Invalid security token.';
            } else {
                $studentId = sanitizeInt($_POST['student_id'] ?? 0);
                $periodMonth = sanitize($_POST['period_month'] ?? '');
                if ($studentId <= 0) {
                    $errors[] = 'Select a student.';
                }
                if (!preg_match('/^\d{4}-\d{2}$/', $periodMonth)) {
                    $errors[] = 'Select a valid period month for these charges.';
                }

                $feeIds = array_map('intval', $_POST['fee_ids'] ?? []);
                $feeIds = array_filter($feeIds, fn ($id) => $id > 0);
                if (empty($feeIds)) {
                    $errors[] = 'Select at least one fee (e.g. security deposit and the correct room rent tier).';
                }

                if (empty($errors)) {
                    $n = $this->billingModel->issueBulk([$studentId], $periodMonth, $feeIds, (int) $_SESSION['user_id']);
                    AuditLog::log(
                        (int) $_SESSION['user_id'],
                        'CREATE',
                        'billing_charges',
                        $studentId,
                        "Enrollment billing issued for period {$periodMonth}"
                    );
                    setFlash('success', "Issued {$n} enrollment billing line(s). Student can pay online or at the office.");
                    header('Location: ' . BASE_URL . '?url=billing/issueEnrollment');
                    exit;
                }
            }
        }

        $pageTitle = 'Issue Enrollment Billing';
        ob_start();
        require_once APP_ROOT . '/views/billing/issue_enrollment.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }
}
