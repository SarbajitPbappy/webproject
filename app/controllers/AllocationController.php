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
        if (empty($student['entitled_room_type'])) {
            return 'Student has no paid room tier. Issue enrollment billing (security deposit + correct room rent) or record a matching room rent payment before allocating.';
        }
        if ($student['entitled_room_type'] !== $room['type']) {
            return 'Room type mismatch: this student paid for '
                . $student['entitled_room_type'] . ' but the selected room is ' . $room['type'] . '. Choose a matching room or add them to the waitlist.';
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

        $students = $this->studentModel->getForDropdown();
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
     * Show allocation form / process allocation.
     */
    public function allocate(): void
    {
        requireRole(['super_admin', 'admin']);

        $errors = [];
        $students = $this->studentModel->getForDropdown();
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken()) {
                $errors[] = 'Invalid security token.';
            } else {
                $studentId = sanitizeInt($_POST['student_id'] ?? 0);
                $newRoomId = sanitizeInt($_POST['room_id'] ?? 0);
                $notes = sanitize($_POST['notes'] ?? '');

                if (!$studentId) $errors[] = 'Please select a student.';
                if (!$newRoomId) $errors[] = 'Please select a new room.';

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

                if (empty($errors)) {
                    try {
                        $oldRoomId = $currentAlloc['room_id'];
                        $allocId = $this->allocationModel->transfer($studentId, $newRoomId, $_SESSION['user_id'], $notes);

                        // Refresh both room statuses
                        $this->roomModel->refreshStatus($oldRoomId);
                        $this->roomModel->refreshStatus($newRoomId);

                        AuditLog::log(
                            $_SESSION['user_id'], 'UPDATE', 'allocations', $allocId,
                            "Transferred student #$studentId from room #$oldRoomId to #$newRoomId"
                        );

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
                $this->allocationModel->vacate($studentId, $notes);
                $roomId = (int)$currentAlloc['room_id'];
                $this->roomModel->refreshStatus($roomId);

                // Automation: next waitlisted student for this room tier only.
                $availability = $this->allocationModel->checkAvailability($roomId);
                $roomMeta = $this->roomModel->findById($roomId);
                if (!empty($availability['available']) && $roomMeta) {
                    $nextWait = $this->allocationModel->waitlistNextForRoomType((string) $roomMeta['type']);
                    if ($nextWait && empty($this->allocationModel->findActiveByStudent((int) $nextWait['student_id']))) {
                        $allocId = $this->allocationModel->allocate([
                            'student_id'   => (int) $nextWait['student_id'],
                            'room_id'      => $roomId,
                            'allocated_by' => (int) $_SESSION['user_id'],
                            'start_date'   => date('Y-m-d'),
                            'notes'        => 'Auto-allocated from waitlist (matching room tier)',
                        ]);
                        $this->allocationModel->waitlistUpdateStatus((int) $nextWait['student_id'], 'allocated');
                        $this->roomModel->refreshStatus($roomId);

                        AuditLog::log(
                            (int) $_SESSION['user_id'],
                            'CREATE',
                            'allocations',
                            $allocId,
                            "Auto-allocated waitlisted student #{$nextWait['student_id']} to room #{$roomId} ({$roomMeta['type']})"
                        );
                    }
                }

                AuditLog::log(
                    $_SESSION['user_id'], 'UPDATE', 'allocations', $currentAlloc['id'],
                    "Vacated student #$studentId from room #{$currentAlloc['room_id']}"
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

        $students = $this->studentModel->getForDropdown();
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
