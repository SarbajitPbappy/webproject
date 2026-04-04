<?php
/**
 * HostelEase — Allocation Controller
 * 
 * Handles room allocation, transfers, vacating, and waitlist.
 * Access: super_admin, admin
 */

require_once APP_ROOT . '/app/models/Allocation.php';
require_once APP_ROOT . '/app/models/Student.php';
require_once APP_ROOT . '/app/models/Room.php';
require_once APP_ROOT . '/app/models/BillingCharge.php';
require_once APP_ROOT . '/app/models/Payment.php';
require_once APP_ROOT . '/app/models/RoomServiceRequest.php';
require_once APP_ROOT . '/app/models/UserNotification.php';
require_once APP_ROOT . '/app/models/AuditLog.php';

class AllocationController
{
    private Allocation $allocationModel;
    private Student $studentModel;
    private Room $roomModel;

    public function __construct()
    {
        $this->allocationModel = new Allocation();
        $this->studentModel = new Student();
        $this->roomModel = new Room();
    }

    /**
     * Ensure paid room tier matches physical room type.
     */
    private function allocationTierError(int $studentId, array $room): ?string
    {
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            return 'Student not found.';
        }
        $tier = trim((string) ($student['entitled_room_type'] ?? ''));
        if ($tier === '') {
            $tier = trim((string) ($this->allocationModel->waitlistPreferredType($studentId) ?? ''));
        }
        if ($tier === '') {
            return 'Student has no paid room tier and no waitlist category. Complete enrollment billing for the correct room rent, or ensure their waitlist preference is set.';
        }
        if ($tier !== $room['type']) {
            return 'Room type mismatch: this student is eligible for '
                . $tier . ' but the selected room is ' . $room['type'] . '. Choose a matching room or update billing / waitlist preference.';
        }
        return null;
    }

    /**
     * Room roster for wardens / super admin.
     */
    public function occupancy(): void
    {
        requireRole(['super_admin', 'admin']);

        $board = $this->roomModel->getOccupancyBoard();
        $pageTitle = 'Room roster';

        ob_start();
        require_once APP_ROOT . '/views/rooms/occupancy.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * List all allocations.
     */
    public function index(): void
    {
        requireRole(['super_admin', 'admin']);

        $students = $this->studentModel->getWaitlistedForDropdown();
        $rooms = $this->roomModel->getAvailable();
        $allocations = $this->allocationModel->all();
        $waitlist = $this->allocationModel->waitlistAll();
        $pageTitle = 'Room Allocations';

        ob_start();
        require_once APP_ROOT . '/views/rooms/allocate.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Admin: pending room change / cancellation requests from students.
     */
    public function roomRequests(): void
    {
        requireRole(['super_admin', 'admin']);

        $rsr = new RoomServiceRequest();
        if (!$rsr->tableReady()) {
            setFlash('error', 'Room requests table is missing. Run: php database/migrations/migrate_room_requests_transfer_fee.php');
            header('Location: ' . BASE_URL . '?url=allocations/index');
            exit;
        }

        $requests = $rsr->pendingForAdmin();
        $billingModel = new BillingCharge();
        $transferFee = $billingModel->getTransferFee();

        $pageTitle = 'Room requests';
        ob_start();
        require_once APP_ROOT . '/views/rooms/room_requests_admin.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Admin: approve / reject a room service request (POST).
     */
    public function roomRequestProcess(): void
    {
        requireRole(['super_admin', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyToken()) {
            setFlash('error', 'Invalid request.');
            header('Location: ' . BASE_URL . '?url=allocations/roomRequests');
            exit;
        }

        $rsr = new RoomServiceRequest();
        if (!$rsr->tableReady()) {
            setFlash('error', 'Room requests are not available.');
            header('Location: ' . BASE_URL . '?url=allocations/index');
            exit;
        }

        $id = sanitizeInt($_POST['request_id'] ?? 0);
        $decision = sanitize($_POST['decision'] ?? '');
        $adminNotes = sanitize($_POST['admin_notes'] ?? '');
        $autoBill = !empty($_POST['auto_issue_transfer_fee']);

        $req = $id > 0 ? $rsr->find($id) : null;
        if (!$req || $req['status'] !== 'pending') {
            setFlash('error', 'Request not found or already processed.');
            header('Location: ' . BASE_URL . '?url=allocations/roomRequests');
            exit;
        }

        $studentId = (int) $req['student_id'];
        $actorId = (int) $_SESSION['user_id'];

        if ($decision === 'reject') {
            $rsr->updateStatus($id, 'rejected', $actorId, $adminNotes !== '' ? $adminNotes : null);
            AuditLog::log($actorId, 'UPDATE', 'room_service_requests', $id, 'Rejected room request');
            UserNotification::notifyStudentAccount(
                $studentId,
                'Room request update',
                'Your room request was rejected.'
                    . ($adminNotes !== '' ? ' Note: ' . $adminNotes : '')
                    . ' Open My Dashboard or Room change / cancel for details.',
                'room_request'
            );
            setFlash('success', 'Request rejected.');
            header('Location: ' . BASE_URL . '?url=allocations/roomRequests');
            exit;
        }

        if ($decision !== 'approve') {
            setFlash('error', 'Invalid decision.');
            header('Location: ' . BASE_URL . '?url=allocations/roomRequests');
            exit;
        }

        $billingModel = new BillingCharge();

        if ($req['request_type'] === 'room_cancellation') {
            $due = $billingModel->pendingTotalForStudent($studentId);
            if ($due > 0.009) {
                setFlash('error', 'This student still has pending hall bills (৳' . number_format($due, 2) . '). They must clear all fees before cancellation can be approved.');
                header('Location: ' . BASE_URL . '?url=allocations/roomRequests');
                exit;
            }
            $this->performVacateWithAutomation($studentId, 'Approved room cancellation request #' . $id, $actorId);
            $rsr->updateStatus($id, 'completed', $actorId, $adminNotes !== '' ? $adminNotes : 'Vacated from room.');
            AuditLog::log($actorId, 'UPDATE', 'room_service_requests', $id, 'Approved cancellation; student vacated');
            UserNotification::notifyStudentAccount(
                $studentId,
                'Room cancellation approved',
                'Your cancellation request was approved and you have been vacated from your room.'
                    . ($adminNotes !== '' ? ' ' . $adminNotes : ''),
                'room_request'
            );
            setFlash('success', 'Cancellation approved and the student has been vacated.');
            header('Location: ' . BASE_URL . '?url=allocations/roomRequests');
            exit;
        }

        if ($req['request_type'] === 'room_change') {
            if ($autoBill) {
                $fee = $billingModel->getTransferFee();
                if ($fee) {
                    $periodKey = $billingModel->issueUniqueSlip(
                        $studentId,
                        (int) $fee['id'],
                        (float) $fee['amount'],
                        $actorId,
                        'Room change request #' . $id
                    );
                    if ($periodKey) {
                        UserNotification::notifyBillingIssued([$studentId], $periodKey);
                    }
                }
            }

            $preferred = $req['preferred_room_type'] ?? null;
            $allowedTypes = ['single', 'double', 'triple', 'dormitory'];
            $alloc = $this->allocationModel->findActiveByStudent($studentId);
            $moveDone = false;
            $statusNote = '';
            $finalStatus = 'approved';

            if (
                $alloc
                && is_string($preferred)
                && in_array($preferred, $allowedTypes, true)
            ) {
                $currentType = (string) ($alloc['room_type'] ?? '');
                if ($currentType === $preferred) {
                    $this->studentModel->setEntitledRoomType($studentId, $preferred);
                    $moveDone = true;
                    $finalStatus = 'completed';
                    $statusNote = $adminNotes !== '' ? $adminNotes : 'Room tier already matched; entitlement updated.';
                } else {
                    $candidates = $this->roomModel->getAvailableByRoomType($preferred);
                    $targetRoomId = null;
                    foreach ($candidates as $cand) {
                        $rid = (int) $cand['id'];
                        $av = $this->allocationModel->checkAvailability($rid);
                        if (empty($av['available'])) {
                            continue;
                        }
                        if ($rid === (int) $alloc['room_id']) {
                            continue;
                        }
                        $targetRoomId = $rid;
                        break;
                    }
                    if ($targetRoomId === null) {
                        foreach ($candidates as $cand) {
                            $rid = (int) $cand['id'];
                            $av = $this->allocationModel->checkAvailability($rid);
                            if (!empty($av['available'])) {
                                $targetRoomId = $rid;
                                break;
                            }
                        }
                    }

                    if ($targetRoomId !== null) {
                        $oldRoomId = (int) $alloc['room_id'];
                        $oldType = $currentType;
                        $this->allocationModel->transfer($studentId, $targetRoomId, $actorId, 'Approved room change request #' . $id);
                        $this->roomModel->refreshStatus($oldRoomId);
                        $this->roomModel->refreshStatus($targetRoomId);
                        $newRoomRow = $this->roomModel->findById($targetRoomId);
                        $newType = (string) ($newRoomRow['type'] ?? $preferred);
                        $this->studentModel->setEntitledRoomType($studentId, $preferred);
                        $this->studentModel->applyTierChangeBillingCredit($studentId, $oldType, $newType);
                        $moveDone = true;
                        $finalStatus = 'completed';
                        $statusNote = $adminNotes !== ''
                            ? $adminNotes
                            : ('Moved to room ' . ($newRoomRow['room_number'] ?? ('#' . $targetRoomId)) . ' (' . $preferred . ').');
                        AuditLog::log(
                            $actorId,
                            'UPDATE',
                            'room_service_requests',
                            $id,
                            "Room change completed; student #{$studentId} → room #{$targetRoomId}"
                        );
                    } else {
                        $statusNote = $adminNotes !== ''
                            ? $adminNotes
                            : ('No vacant ' . $preferred . ' room available. Complete transfer manually when a bed is free.');
                    }
                }
            } else {
                $statusNote = $adminNotes !== ''
                    ? $adminNotes
                    : ($alloc
                        ? 'Approved — add a preferred room category on the student request or use Transfer manually.'
                        : 'Approved — student has no active allocation.');
            }

            $rsr->updateStatus($id, $finalStatus, $actorId, $statusNote !== '' ? $statusNote : null);
            AuditLog::log($actorId, 'UPDATE', 'room_service_requests', $id, 'Processed room change request');

            $body = $moveDone
                ? 'Your room change was approved and your room assignment has been updated. Check My Dashboard.'
                : 'Your room change was approved. ' . ($statusNote !== '' ? $statusNote : 'The office will finalize your move when a room is available.');
            UserNotification::notifyStudentAccount(
                $studentId,
                $moveDone ? 'Room change completed' : 'Room change approved',
                $body . ($adminNotes !== '' && $moveDone === false ? ' ' . $adminNotes : ''),
                'room_request'
            );

            $flash = $moveDone
                ? 'Room change completed: student moved and entitlement updated.'
                : 'Room change approved (no auto-move: no vacant matching room or missing preference).';
            if ($autoBill) {
                $flash .= ' Transfer fee slip added when configured.';
            }
            setFlash($moveDone ? 'success' : 'warning', $flash);
            header('Location: ' . BASE_URL . '?url=allocations/roomRequests');
            exit;
        }

        setFlash('error', 'Unknown request type.');
        header('Location: ' . BASE_URL . '?url=allocations/roomRequests');
        exit;
    }

    /**
     * Vacate and run waitlist auto-fill (same as vacate action).
     */
    private function performVacateWithAutomation(int $studentId, string $notes, int $actorId): void
    {
        $currentAlloc = $this->allocationModel->findActiveByStudent($studentId);
        if (!$currentAlloc) {
            return;
        }
        $this->allocationModel->vacate($studentId, $notes);
        $roomId = (int) $currentAlloc['room_id'];
        $this->roomModel->refreshStatus($roomId);

        $availability = $this->allocationModel->checkAvailability($roomId);
        $roomMeta = $this->roomModel->findById($roomId);
        if (!empty($availability['available']) && $roomMeta) {
            $nextWait = $this->allocationModel->waitlistNextForRoomType((string) $roomMeta['type']);
            if ($nextWait && empty($this->allocationModel->findActiveByStudent((int) $nextWait['student_id']))) {
                $allocId = $this->allocationModel->allocate([
                    'student_id'   => (int) $nextWait['student_id'],
                    'room_id'      => $roomId,
                    'allocated_by' => $actorId,
                    'start_date'   => date('Y-m-d'),
                    'notes'        => 'Auto-allocated from waitlist (matching room tier)',
                ]);
                $this->allocationModel->waitlistUpdateStatus((int) $nextWait['student_id'], 'allocated');
                $this->roomModel->refreshStatus($roomId);
                AuditLog::log(
                    $actorId,
                    'CREATE',
                    'allocations',
                    $allocId,
                    "Auto-allocated waitlisted student #{$nextWait['student_id']} to room #{$roomId}"
                );
            }
        }

        AuditLog::log(
            $actorId,
            'UPDATE',
            'allocations',
            (int) $currentAlloc['id'],
            "Vacated student #$studentId from room #{$roomId}"
        );
    }

    /**
     * Show allocation form / process allocation.
     */
    public function allocate(): void
    {
        requireRole(['super_admin', 'admin']);

        $errors = [];
        $students = $this->studentModel->getWaitlistedForDropdown();
        $rooms = $this->roomModel->getAvailable();
        $allocations = $this->allocationModel->all();
        $waitlist = $this->allocationModel->waitlistAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken()) {
                $errors[] = 'Invalid security token.';
            } else {
                $studentId = sanitizeInt($_POST['student_id'] ?? 0);
                $roomId = sanitizeInt($_POST['room_id'] ?? 0);
                $startDate = sanitizeDate($_POST['start_date'] ?? '') ?? date('Y-m-d');
                $notes = sanitize($_POST['notes'] ?? '');

                if (!$studentId) $errors[] = 'Please select a student.';
                if (!$roomId) $errors[] = 'Please select a room.';

                if ($studentId && !$this->studentModel->isWaitlistedForNewAllocation($studentId)) {
                    $errors[] = 'Only waitlisted students without an active room can be allocated here. Use Transfer for residents.';
                }

                $roomRow = $roomId ? $this->roomModel->findById($roomId) : null;
                if ($roomId && !$roomRow) {
                    $errors[] = 'Invalid room selected.';
                }

                // Check if student already has active allocation
                if ($studentId && $this->allocationModel->findActiveByStudent($studentId)) {
                    $errors[] = 'This student already has an active room allocation. Transfer or vacate first.';
                }

                if ($studentId && $roomRow && empty($errors)) {
                    $tierErr = $this->allocationTierError($studentId, $roomRow);
                    if ($tierErr !== null) {
                        $errors[] = $tierErr;
                    }
                }

                // Check room availability
                if ($roomId && $roomRow && empty($errors)) {
                    $availability = $this->allocationModel->checkAvailability($roomId);
                    if (!$availability['available']) {
                        $waitType = (string) $roomRow['type'];
                        if (!$this->allocationModel->isOnWaitlist($studentId)) {
                            $this->allocationModel->waitlistAdd($studentId, $waitType);
                            AuditLog::log($_SESSION['user_id'], 'CREATE', 'waitlist', $studentId, "Waitlist ({$waitType}) — room full");
                            setFlash('warning', 'Room is full. Student queued for the next available ' . $waitType . ' room.');
                        } else {
                            setFlash('info', 'Student is already on the waitlist.');
                        }
                        header('Location: ' . BASE_URL . '?url=allocations/allocate');
                        exit;
                    }
                }

                if (empty($errors)) {
                    try {
                        $allocId = $this->allocationModel->allocate([
                            'student_id'   => $studentId,
                            'room_id'      => $roomId,
                            'allocated_by' => $_SESSION['user_id'],
                            'start_date'   => $startDate,
                            'notes'        => $notes,
                        ]);

                        // Refresh room status
                        $this->roomModel->refreshStatus($roomId);

                        // Remove from waitlist if applicable
                        $this->allocationModel->waitlistUpdateStatus($studentId, 'allocated');

                        AuditLog::log(
                            $_SESSION['user_id'], 'CREATE', 'allocations', $allocId,
                            "Allocated student #$studentId to room #$roomId"
                        );

                        setFlash('success', 'Room allocated successfully!');
                        header('Location: ' . BASE_URL . '?url=allocations/allocate');
                        exit;
                    } catch (\Exception $e) {
                        error_log('AllocationController::allocate() error: ' . $e->getMessage());
                        $errors[] = 'An error occurred during allocation.';
                    }
                }
            }
        }

        $pageTitle = 'Room Allocations';
        ob_start();
        require_once APP_ROOT . '/views/rooms/allocate.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Transfer student to a different room.
     */
    public function transfer(): void
    {
        requireRole(['super_admin', 'admin']);

        $errors = [];
        $preSelectedStudentId = sanitizeInt($_GET['student_id'] ?? 0);
        $students = $this->studentModel->getAllocatedForDropdown();
        $rooms = $this->roomModel->getAvailable();
        $billingModel = new BillingCharge();
        $transferFee = $billingModel->getTransferFee();
        $isSuperAdmin = (($_SESSION['user_role'] ?? '') === 'super_admin');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken()) {
                $errors[] = 'Invalid security token.';
            } else {
                $studentId = sanitizeInt($_POST['student_id'] ?? 0);
                $newRoomId = sanitizeInt($_POST['room_id'] ?? 0);
                $notes = sanitize($_POST['notes'] ?? '');
                $feeAction = sanitize($_POST['transfer_fee_action'] ?? 'slip');
                $paymentMethod = sanitize($_POST['payment_method'] ?? 'cash');
                $paymentRef = sanitize($_POST['payment_reference'] ?? '');

                if (!$studentId) $errors[] = 'Please select a student.';
                if (!$newRoomId) $errors[] = 'Please select a new room.';
                if (!in_array($paymentMethod, ['cash', 'bank'], true)) {
                    $paymentMethod = 'cash';
                }

                $currentAlloc = $this->allocationModel->findActiveByStudent($studentId);
                if (!$currentAlloc) {
                    $errors[] = 'Student has no active allocation to transfer from.';
                }

                $targetRoom = $newRoomId ? $this->roomModel->findById($newRoomId) : null;
                if ($newRoomId && !$targetRoom) {
                    $errors[] = 'Invalid room selected.';
                }

                if ($studentId && $targetRoom && empty($errors)) {
                    $tierErr = $this->allocationTierError($studentId, $targetRoom);
                    if ($tierErr !== null) {
                        $errors[] = $tierErr;
                    }
                }

                if ($newRoomId && empty($errors)) {
                    $availability = $this->allocationModel->checkAvailability($newRoomId);
                    if (!$availability['available']) {
                        $errors[] = 'Target room is full.';
                    }
                }

                $actorId = (int) $_SESSION['user_id'];
                $paymentModel = new Payment();
                $periodKey = null;
                $billingChargeId = null;

                if ($transferFee && empty($errors)) {
                    $feeId = (int) $transferFee['id'];
                    $feeAmt = (float) $transferFee['amount'];
                    $allowed = ['slip', 'slip_pay', 'pay_only', 'waive'];
                    if (!in_array($feeAction, $allowed, true)) {
                        $feeAction = 'slip';
                    }
                    if ($feeAction === 'waive' && !$isSuperAdmin) {
                        $errors[] = 'Only a super admin can waive the transfer fee.';
                    }
                    if (empty($errors) && $feeAction !== 'waive') {
                        if ($feeAction === 'slip' || $feeAction === 'slip_pay') {
                            $periodKey = $billingModel->issueUniqueSlip(
                                $studentId,
                                $feeId,
                                $feeAmt,
                                $actorId,
                                trim($notes) !== '' ? 'Transfer: ' . $notes : 'Room transfer fee'
                            );
                            if (!$periodKey) {
                                $errors[] = 'Could not create transfer fee slip. Try again or use offline payment only.';
                            } else {
                                UserNotification::notifyBillingIssued([$studentId], $periodKey);
                                if ($feeAction === 'slip_pay') {
                                    $billingChargeId = $billingModel->findPendingChargeId($studentId, $feeId, $periodKey);
                                    if (!$billingChargeId) {
                                        $errors[] = 'Slip was created but could not be linked for payment recording.';
                                    }
                                }
                            }
                        } elseif ($feeAction === 'pay_only') {
                            // Offline payment without a portal slip
                            try {
                                $paymentModel->record([
                                    'student_id'            => $studentId,
                                    'fee_id'                => $feeId,
                                    'amount_paid'           => $feeAmt,
                                    'payment_date'          => date('Y-m-d'),
                                    'payment_method'        => $paymentMethod,
                                    'recorded_by'           => $actorId,
                                    'month_year'            => date('Y-m'),
                                    'notes'                 => 'Room transfer fee (offline, no slip)'
                                        . ($paymentRef !== '' ? ' — Ref: ' . $paymentRef : ''),
                                    'skip_billing_resolve'  => true,
                                ]);
                            } catch (\Exception $e) {
                                error_log('Transfer fee pay_only: ' . $e->getMessage());
                                $errors[] = 'Could not record offline transfer payment.';
                            }
                        }
                    }
                    if (empty($errors) && $feeAction === 'slip_pay' && $billingChargeId) {
                        try {
                            $paymentModel->record([
                                'student_id'         => $studentId,
                                'fee_id'             => $feeId,
                                'amount_paid'        => $feeAmt,
                                'payment_date'       => date('Y-m-d'),
                                'payment_method'     => $paymentMethod,
                                'recorded_by'        => $actorId,
                                'month_year'         => (string) $periodKey,
                                'notes'              => 'Room transfer fee (offline)'
                                    . ($paymentRef !== '' ? ' — Ref: ' . $paymentRef : ''),
                                'billing_charge_id'  => $billingChargeId,
                            ]);
                        } catch (\Exception $e) {
                            error_log('Transfer fee slip_pay: ' . $e->getMessage());
                            $errors[] = 'Transfer fee slip was issued but payment could not be recorded. Record payment from Payments.';
                        }
                    }
                }

                if (empty($errors)) {
                    try {
                        $oldRoomId = $currentAlloc['room_id'];
                        $allocId = $this->allocationModel->transfer($studentId, $newRoomId, $actorId, $notes);

                        // Refresh both room statuses
                        $this->roomModel->refreshStatus($oldRoomId);
                        $this->roomModel->refreshStatus($newRoomId);

                        AuditLog::log(
                            $actorId, 'UPDATE', 'allocations', $allocId,
                            "Transferred student #$studentId from room #$oldRoomId to #$newRoomId"
                        );

                        $oldRoomRow = $this->roomModel->findById((int) $oldRoomId);
                        $oldType = (string) ($oldRoomRow['type'] ?? '');
                        $newType = (string) ($targetRoom['type'] ?? '');
                        if ($oldType !== '' && $newType !== '' && $oldType !== $newType) {
                            $this->studentModel->applyTierChangeBillingCredit($studentId, $oldType, $newType);
                            AuditLog::log(
                                $actorId,
                                'UPDATE',
                                'students',
                                $studentId,
                                "Billing credit applied for room tier change {$oldType} → {$newType}"
                            );
                        }

                        setFlash('success', 'Student transferred successfully!');
                        header('Location: ' . BASE_URL . '?url=allocations/allocate');
                        exit;
                    } catch (\Exception $e) {
                        error_log('Transfer error: ' . $e->getMessage());
                        $errors[] = 'An error occurred during transfer.';
                    }
                }
            }
        }

        $pageTitle = 'Transfer Student';
        ob_start();
        require_once APP_ROOT . '/views/rooms/transfer.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Vacate a student from their room.
     */
    public function vacate(): void
    {
        requireRole(['super_admin', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken()) {
                setFlash('error', 'Invalid security token.');
                header('Location: ' . BASE_URL . '?url=allocations/allocate');
                exit;
            }

            $studentId = sanitizeInt($_POST['student_id'] ?? 0);
            $notes = sanitize($_POST['notes'] ?? '');

            $currentAlloc = $this->allocationModel->findActiveByStudent($studentId);
            if ($currentAlloc) {
                $this->performVacateWithAutomation(
                    $studentId,
                    $notes,
                    (int) $_SESSION['user_id']
                );
                setFlash('success', 'Student vacated successfully.');
            } else {
                setFlash('error', 'No active allocation found for this student.');
            }
        }

        header('Location: ' . BASE_URL . '?url=allocations/allocate');
        exit;
    }

    /**
     * Waitlist management.
     */
    public function waitlist(): void
    {
        requireRole(['super_admin', 'admin']);

        $students = $this->studentModel->getWaitlistedForDropdown();
        $rooms = $this->roomModel->getAvailable();
        $waitlist = $this->allocationModel->waitlistAll();
        $allocations = $this->allocationModel->all();
        $pageTitle = 'Waitlist';

        ob_start();
        require_once APP_ROOT . '/views/rooms/allocate.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }
}
