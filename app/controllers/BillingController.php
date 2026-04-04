<?php
/**
 * HostelEase — Warden-issued billing slips (monthly hall charges & enrollment).
 */

require_once APP_ROOT . '/app/models/BillingCharge.php';
require_once APP_ROOT . '/app/models/Payment.php';
require_once APP_ROOT . '/app/models/Student.php';
require_once APP_ROOT . '/app/models/Allocation.php';
require_once APP_ROOT . '/app/models/AuditLog.php';
require_once APP_ROOT . '/app/models/UserNotification.php';

class BillingController
{
    private BillingCharge $billingModel;
    private Payment $paymentModel;
    private Student $studentModel;
    private Allocation $allocationModel;

    public function __construct()
    {
        if (!isLoggedIn()) {
            header('Location: ' . BASE_URL . '?url=auth/login');
            exit;
        }
        $this->billingModel = new BillingCharge();
        $this->paymentModel = new Payment();
        $this->studentModel = new Student();
        $this->allocationModel = new Allocation();
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
                    $result = $this->billingModel->issueBulk($studentIds, $periodMonth, $feeIds, (int) $_SESSION['user_id']);
                    $n = $result['created'];
                    $skippedPre = (int) ($result['skipped_prepaid'] ?? 0);
                    $skippedYearly = (int) ($result['skipped_yearly_paid'] ?? 0);
                    if (!empty($result['notified_student_ids'])) {
                        UserNotification::notifyBillingIssued($result['notified_student_ids'], $periodMonth);
                    }
                    AuditLog::log(
                        (int) $_SESSION['user_id'],
                        'CREATE',
                        'billing_charges',
                        null,
                        "Issued {$n} billing line(s) for {$periodMonth} (room rent matched to allocation tier)"
                    );
                    $msg = $n > 0
                        ? "Issued {$n} new billing line(s) for {$periodMonth}. Room rent was applied by allocated room type only. Affected students were notified."
                        : 'No new slips were created (everyone may already have these charges for that month, or no students matched tiered rent rules).';
                    if ($skippedPre > 0) {
                        $msg .= " {$skippedPre} fee line(s) skipped — students already prepaid that fee for {$periodMonth}.";
                    }
                    if ($skippedYearly > 0) {
                        $calY = substr($periodMonth, 0, 4);
                        $msg .= " {$skippedYearly} yearly fee line(s) skipped — already paid for calendar year {$calY} (e.g. annual maintenance).";
                    }
                    setFlash($n > 0 || $skippedPre > 0 || $skippedYearly > 0 ? 'success' : 'warning', $msg);
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
                    $needsTieredRent = false;
                    foreach ($fees as $f) {
                        if (!in_array((int) $f['id'], $feeIds, true)) {
                            continue;
                        }
                        if (($f['fee_category'] ?? '') === 'room_rent' && !empty($f['maps_room_type'])) {
                            $needsTieredRent = true;
                            break;
                        }
                    }
                    if ($needsTieredRent) {
                        $hasRoom = (bool) $this->allocationModel->findActiveByStudent($studentId);
                        $pref = $this->allocationModel->waitlistPreferredType($studentId);
                        if (!$hasRoom && $pref === null) {
                            $errors[] = 'This student has no active room allocation and no waitlist room preference. Add them to the waitlist with a preferred tier (student self-registration or admin “Register student” with a tier), then issue enrollment billing again.';
                        }
                    }
                }

                if (empty($errors)) {
                    $result = $this->billingModel->issueBulk(
                        [$studentId],
                        $periodMonth,
                        $feeIds,
                        (int) $_SESSION['user_id'],
                        true
                    );
                    $n = $result['created'];
                    $skippedPre = (int) ($result['skipped_prepaid'] ?? 0);
                    $skippedYearly = (int) ($result['skipped_yearly_paid'] ?? 0);
                    if (!empty($result['notified_student_ids'])) {
                        UserNotification::notifyBillingIssued($result['notified_student_ids'], $periodMonth);
                    }
                    AuditLog::log(
                        (int) $_SESSION['user_id'],
                        'CREATE',
                        'billing_charges',
                        $studentId,
                        "Enrollment billing issued for period {$periodMonth}"
                    );
                    $msg = $n > 0
                        ? "Issued {$n} enrollment billing line(s). Room rent used the student's current room type if allocated, otherwise their waitlist preference from registration. The student was notified when new slips were added."
                        : 'No new enrollment slips were created (duplicates for that month, missing waitlist room preference for unallocated students, or room rent tier did not match).';
                    if ($skippedPre > 0) {
                        $msg .= " {$skippedPre} line(s) skipped (already prepaid for that period).";
                    }
                    if ($skippedYearly > 0) {
                        $calY = substr($periodMonth, 0, 4);
                        $msg .= " {$skippedYearly} yearly fee line(s) skipped (already paid for {$calY}).";
                    }
                    setFlash($n > 0 || $skippedPre > 0 || $skippedYearly > 0 ? 'success' : 'warning', $msg);
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
