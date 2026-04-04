<?php
/**
 * HostelEase — Student Controller
 * 
 * Handles student CRUD operations.
 * Access: super_admin, admin (full), student (own profile only)
 */

require_once APP_ROOT . '/app/models/Student.php';
require_once APP_ROOT . '/app/models/User.php';
require_once APP_ROOT . '/app/models/AuditLog.php';
require_once APP_ROOT . '/app/models/Room.php';
require_once APP_ROOT . '/app/models/Allocation.php';
require_once APP_ROOT . '/app/models/RoomServiceRequest.php';
require_once APP_ROOT . '/app/models/BillingCharge.php';
require_once APP_ROOT . '/app/helpers/upload.php';

class StudentController
{
    private Student $studentModel;
    private User $userModel;

    public function __construct()
    {
        $this->studentModel = new Student();
        $this->userModel = new User();
    }

    /**
     * List all students with search/filter.
     */
    public function index(): void
    {
        requireRole(['super_admin', 'admin']);

        $filters = [];
        if (!empty($_GET['status'])) {
            $filters['status'] = sanitize($_GET['status']);
        }
        if (!empty($_GET['search'])) {
            $filters['search'] = sanitize($_GET['search']);
        }

        $students = $this->studentModel->all($filters);
        $pageTitle = 'Student Management';

        ob_start();
        require_once APP_ROOT . '/views/students/index.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Show a single student's profile.
     */
    public function show(int $id = 0): void
    {
        requireAuth();
        $id = sanitizeInt($id ?: ($_GET['id'] ?? 0));

        // Students can only view their own profile
        if (hasRole(['student'])) {
            $student = $this->studentModel->findByUserId($_SESSION['user_id']);
            if (!$student || ($id !== 0 && $student['id'] !== $id)) {
                setFlash('error', 'You can only view your own profile.');
                header('Location: ' . BASE_URL . '?url=dashboard/student');
                exit;
            }
            $id = $student['id']; // Force id to default to their own profile
        } else {
            requireRole(['super_admin', 'admin']);
            $student = $this->studentModel->findById($id);
        }

        if (!$student) {
            setFlash('error', 'Student not found.');
            header('Location: ' . BASE_URL . '?url=students/index');
            exit;
        }

        $allocationModel = new Allocation();
        $studentActiveAllocation = $allocationModel->findActiveByStudent((int) $student['id']);
        $studentWaitlistRoomPref = $studentActiveAllocation
            ? null
            : $allocationModel->waitlistPreferredType((int) $student['id']);

        $pageTitle = 'Student Profile';
        ob_start();
        require_once APP_ROOT . '/views/students/show.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Show create student form.
     */
    public function create(): void
    {
        requireRole(['super_admin', 'admin']);

        $errors = [];
        $data = [];
        $vacancyByType = (new Room())->countOpenBedsByRoomType();
        $pageTitle = 'Register New Student';

        ob_start();
        require_once APP_ROOT . '/views/students/create.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Process student creation.
     */
    public function store(): void
    {
        requireRole(['super_admin', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?url=students/create');
            exit;
        }

        if (!verifyToken()) {
            setFlash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '?url=students/create');
            exit;
        }

        $password = trim((string)($_POST['password'] ?? ''));
        if ($password === '') {
            $password = 'Student@123'; // Keep behavior promised by the UI placeholder.
        }

        $data = [
            'full_name'             => sanitize($_POST['full_name'] ?? ''),
            'email'                 => sanitizeEmail($_POST['email'] ?? ''),
            'student_id_no'         => sanitize($_POST['student_id_no'] ?? ''),
            'phone'                 => sanitizePhone($_POST['phone'] ?? ''),
            'guardian_name'         => sanitize($_POST['guardian_name'] ?? ''),
            'guardian_phone'        => sanitizePhone($_POST['guardian_phone'] ?? ''),
            'enrolled_date'         => sanitizeDate($_POST['enrolled_date'] ?? ''),
            'password'              => $password,
            'preferred_room_type'   => sanitize($_POST['preferred_room_type'] ?? ''),
        ];

        $errors = $this->validateStudent($data);

        // Handle photo upload
        if (!empty($_FILES['profile_photo']['name'])) {
            $uploadResult = uploadFile($_FILES['profile_photo'], 'students');
            if ($uploadResult['success']) {
                $data['profile_photo'] = $uploadResult['filename'];
            } else {
                $errors[] = 'Photo: ' . $uploadResult['error'];
            }
        }

        // Handle NID/Card upload
        if (!empty($_FILES['nid_or_card']['name'])) {
            $uploadResult = uploadFile(
                $_FILES['nid_or_card'],
                'documents',
                ALLOWED_DOC_TYPES,
                ALLOWED_DOC_EXTENSIONS
            );
            if ($uploadResult['success']) {
                $data['nid_or_card'] = $uploadResult['filename'];
            } else {
                $errors[] = 'Document: ' . $uploadResult['error'];
            }
        }

        if (!empty($errors)) {
            $pageTitle = 'Register New Student';
            $vacancyByType = (new Room())->countOpenBedsByRoomType();
            ob_start();
            require_once APP_ROOT . '/views/students/create.php';
            $viewContent = ob_get_clean();
            require_once APP_ROOT . '/views/layouts/main.php';
            return;
        }

        try {
            $studentId = $this->studentModel->create($data);

            $tierPref = $data['preferred_room_type'] ?? '';
            $allowedTiers = ['single', 'double', 'triple', 'dormitory'];
            if (in_array($tierPref, $allowedTiers, true)) {
                (new Allocation())->waitlistAdd($studentId, $tierPref);
            }

            AuditLog::log(
                $_SESSION['user_id'],
                'CREATE',
                'students',
                $studentId,
                'Created student: ' . $data['full_name'] . ' (' . $data['student_id_no'] . ')'
            );

            setFlash('success', 'Student registered successfully! Default password: Student@123');
            header('Location: ' . BASE_URL . '?url=students/show/' . $studentId);
            exit;

        } catch (\Exception $e) {
            error_log('StudentController::store() error: ' . $e->getMessage());
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $errors[] = 'Email or Student ID already exists.';
            } else {
                $errors[] = 'An error occurred while creating the student.';
            }

            $pageTitle = 'Register New Student';
            $vacancyByType = (new Room())->countOpenBedsByRoomType();
            ob_start();
            require_once APP_ROOT . '/views/students/create.php';
            $viewContent = ob_get_clean();
            require_once APP_ROOT . '/views/layouts/main.php';
        }
    }

    /**
     * Show edit student form.
     */
    public function edit(int $id = 0): void
    {
        requireRole(['super_admin', 'admin']);
        $id = sanitizeInt($id ?: ($_GET['id'] ?? 0));

        $student = $this->studentModel->findById($id);
        if (!$student) {
            setFlash('error', 'Student not found.');
            header('Location: ' . BASE_URL . '?url=students/index');
            exit;
        }

        $data = $student;
        $errors = [];
        $pageTitle = 'Edit Student';

        ob_start();
        require_once APP_ROOT . '/views/students/edit.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Process student update.
     */
    public function update(int $id = 0): void
    {
        requireRole(['super_admin', 'admin']);
        $id = sanitizeInt($id ?: ($_POST['id'] ?? 0));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?url=students/edit/' . $id);
            exit;
        }

        if (!verifyToken()) {
            setFlash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '?url=students/edit/' . $id);
            exit;
        }

        $student = $this->studentModel->findById($id);
        if (!$student) {
            setFlash('error', 'Student not found.');
            header('Location: ' . BASE_URL . '?url=students/index');
            exit;
        }

        $data = [
            'full_name'      => sanitize($_POST['full_name'] ?? ''),
            'email'          => sanitizeEmail($_POST['email'] ?? ''),
            'student_id_no'  => sanitize($_POST['student_id_no'] ?? ''),
            'phone'          => sanitizePhone($_POST['phone'] ?? ''),
            'guardian_name'  => sanitize($_POST['guardian_name'] ?? ''),
            'guardian_phone' => sanitizePhone($_POST['guardian_phone'] ?? ''),
            'enrolled_date'  => sanitizeDate($_POST['enrolled_date'] ?? ''),
            'status'         => sanitize($_POST['status'] ?? 'active'),
        ];

        $errors = $this->validateStudent($data, $id);

        // Handle photo upload
        if (!empty($_FILES['profile_photo']['name'])) {
            $uploadResult = uploadFile($_FILES['profile_photo'], 'students');
            if ($uploadResult['success']) {
                // Delete old photo
                if (!empty($student['profile_photo'])) {
                    deleteUploadedFile($student['profile_photo'], 'students');
                }
                $data['profile_photo'] = $uploadResult['filename'];
            } else {
                $errors[] = 'Photo: ' . $uploadResult['error'];
            }
        }

        if (!empty($errors)) {
            $data = array_merge($student, $data);
            $pageTitle = 'Edit Student';
            ob_start();
            require_once APP_ROOT . '/views/students/edit.php';
            $viewContent = ob_get_clean();
            require_once APP_ROOT . '/views/layouts/main.php';
            return;
        }

        try {
            $this->studentModel->update($id, $data);

            AuditLog::log(
                $_SESSION['user_id'],
                'UPDATE',
                'students',
                $id,
                'Updated student: ' . $data['full_name']
            );

            setFlash('success', 'Student updated successfully.');
            header('Location: ' . BASE_URL . '?url=students/show/' . $id);
            exit;

        } catch (\Exception $e) {
            error_log('StudentController::update() error: ' . $e->getMessage());
            $errors[] = 'An error occurred while updating the student.';
            $data = array_merge($student, $data);
            $pageTitle = 'Edit Student';
            ob_start();
            require_once APP_ROOT . '/views/students/edit.php';
            $viewContent = ob_get_clean();
            require_once APP_ROOT . '/views/layouts/main.php';
        }
    }

    /**
     * Delete a student.
     */
    public function delete(int $id = 0): void
    {
        requireRole(['super_admin', 'admin']);
        $id = sanitizeInt($id ?: ($_POST['id'] ?? 0));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?url=students/index');
            exit;
        }

        if (!verifyToken()) {
            setFlash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '?url=students/index');
            exit;
        }

        $student = $this->studentModel->findById($id);
        if ($student) {
            // Delete photo
            if (!empty($student['profile_photo'])) {
                deleteUploadedFile($student['profile_photo'], 'students');
            }

            $this->studentModel->delete($id);

            AuditLog::log(
                $_SESSION['user_id'],
                'DELETE',
                'students',
                $id,
                'Deleted student: ' . $student['full_name']
            );

            setFlash('success', 'Student deleted successfully.');
        } else {
            setFlash('error', 'Student not found.');
        }

        header('Location: ' . BASE_URL . '?url=students/index');
        exit;
    }

    /**
     * Self-service student profile edit (name/phone/guardian + profile photo).
     */
    public function editSelf(): void
    {
        requireRole(['student']);

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $student = $this->studentModel->findByUserId($userId);
        if (!$student) {
            setFlash('error', 'Student profile not found.');
            header('Location: ' . BASE_URL . '?url=profile/index');
            exit;
        }

        $errors = [];

        $allocationModel = new Allocation();
        $sid = (int) $student['id'];
        $canChangeRoomPref = !$allocationModel->findActiveByStudent($sid);
        $waitlistPreferredType = $allocationModel->waitlistPreferredType($sid);
        $vacancyByType = (new Room())->countOpenBedsByRoomType();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken()) {
                $errors[] = 'Invalid security token.';
            } else {
                $fullName = sanitize($_POST['full_name'] ?? '');
                $phone = sanitizePhone($_POST['phone'] ?? '');
                $guardianName = sanitize($_POST['guardian_name'] ?? '');
                $guardianPhone = sanitizePhone($_POST['guardian_phone'] ?? '');
                $roomPrefPosted = sanitize($_POST['preferred_room_type'] ?? '');
                $allowedTiers = ['single', 'double', 'triple', 'dormitory'];

                if (empty($fullName)) {
                    $errors[] = 'Full name is required.';
                }
                if (empty($phone)) {
                    $errors[] = 'Phone number is required.';
                }
                if ($canChangeRoomPref && !in_array($roomPrefPosted, $allowedTiers, true)) {
                    $errors[] = 'Please select your preferred room type.';
                }

                $newProfilePhoto = null;
                if (empty($_FILES['profile_photo']['name']) === false) {
                    $uploadResult = uploadFile($_FILES['profile_photo'], 'students');
                    if ($uploadResult['success']) {
                        $newProfilePhoto = $uploadResult['filename'];
                    } else {
                        $errors[] = 'Photo: ' . $uploadResult['error'];
                    }
                }

                if (empty($errors)) {
                    try {
                        $db = Database::getInstance();
                        $db->beginTransaction();

                        // Update users + student profile
                        $params = [
                            ':name' => $fullName,
                            ':phone' => $phone,
                            ':uid' => $userId,
                        ];

                        if ($newProfilePhoto !== null) {
                            // Delete old photo best-effort
                            if (!empty($student['profile_photo'])) {
                                deleteUploadedFile($student['profile_photo'], 'students');
                            }
                            $params[':photo'] = $newProfilePhoto;
                            $db->query(
                                "UPDATE users SET full_name = :name, phone = :phone, profile_photo = :photo WHERE id = :uid",
                                $params
                            );
                        } else {
                            $db->query(
                                "UPDATE users SET full_name = :name, phone = :phone WHERE id = :uid",
                                $params
                            );
                        }

                        $db->query(
                            "UPDATE students
                             SET phone = :phone, guardian_name = :gname, guardian_phone = :gphone
                             WHERE user_id = :uid",
                            [
                                ':phone' => $phone,
                                ':gname' => $guardianName,
                                ':gphone' => $guardianPhone,
                                ':uid' => $userId,
                            ]
                        );

                        if ($canChangeRoomPref) {
                            $wlErr = $allocationModel->updateStudentWaitlistPreference($sid, $roomPrefPosted);
                            if ($wlErr !== null) {
                                throw new \RuntimeException($wlErr);
                            }
                            $waitlistPreferredType = $roomPrefPosted;
                        }

                        $db->commit();

                        $_SESSION['user_name'] = $fullName;
                        if ($newProfilePhoto !== null) {
                            $_SESSION['user_photo'] = $newProfilePhoto;
                        }

                        $logMsg = 'Student updated self profile';
                        if ($canChangeRoomPref) {
                            $logMsg .= '; waitlist preference ' . $roomPrefPosted;
                        }
                        AuditLog::log($userId, 'UPDATE', 'users', $userId, $logMsg);
                        setFlash('success', 'Profile updated successfully!');

                        header('Location: ' . BASE_URL . '?url=students/show');
                        exit;
                    } catch (\Exception $e) {
                        if (isset($db) && $db->getConnection()->inTransaction()) {
                            $db->rollBack();
                        }
                        error_log('StudentController::editSelf() error: ' . $e->getMessage());
                        $errors[] = $e instanceof \RuntimeException
                            ? $e->getMessage()
                            : 'An error occurred while updating your profile.';
                    }
                }
            }
        }

        $pageTitle = 'Edit My Profile';
        ob_start();
        require_once APP_ROOT . '/views/profile/edit_student.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Student / dual-profile staff: request room change or cancellation.
     */
    public function roomRequests(): void
    {
        requireAuth();
        $student = $this->studentModel->findByUserId((int) $_SESSION['user_id']);
        if (!$student) {
            setFlash('error', 'This page is only for accounts with a student profile.');
            header('Location: ' . BASE_URL . '?url=dashboard/index');
            exit;
        }

        $studentId = (int) $student['id'];
        $rsr = new RoomServiceRequest();
        if (!$rsr->tableReady()) {
            setFlash('error', 'Room requests are not set up yet. Ask the administrator to run the database migration.');
            header('Location: ' . BASE_URL . '?url=dashboard/index');
            exit;
        }

        $billing = new BillingCharge();
        $pendingDue = $billing->pendingTotalForStudent($studentId);
        $allocationModel = new Allocation();
        $hasRoom = (bool) $allocationModel->findActiveByStudent($studentId);
        $hasPending = $rsr->hasPending($studentId);
        $existing = $rsr->forStudent($studentId);

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken()) {
                $errors[] = 'Invalid security token.';
            } elseif ($hasPending) {
                $errors[] = 'You already have a pending room request.';
            } else {
                $type = sanitize($_POST['request_type'] ?? '');
                $pref = sanitize($_POST['preferred_room_type'] ?? '');
                $message = sanitize($_POST['message'] ?? '');

                if (!in_array($type, ['room_change', 'room_cancellation'], true)) {
                    $errors[] = 'Please choose a valid request type.';
                }
                if ($type === 'room_change' && $pref !== '' && !in_array($pref, ['single', 'double', 'triple', 'dormitory'], true)) {
                    $errors[] = 'Invalid preferred room type.';
                }
                if (!$hasRoom) {
                    $errors[] = 'You do not have an active room allocation.';
                }
                if ($type === 'room_cancellation' && $pendingDue > 0.009) {
                    $errors[] = 'You must clear all pending hall fees (৳' . number_format($pendingDue, 2)
                        . ' outstanding) before you can request cancellation.';
                }
                if (empty($errors)) {
                    $rsr->create([
                        'student_id'            => $studentId,
                        'request_type'          => $type,
                        'preferred_room_type'   => ($type === 'room_change' && $pref !== '') ? $pref : null,
                        'message'               => $message !== '' ? $message : null,
                    ]);
                    setFlash('success', 'Your request has been submitted.');
                    header('Location: ' . BASE_URL . '?url=students/roomRequests');
                    exit;
                }
            }
        }

        $pageTitle = 'Room change / cancellation';
        ob_start();
        require_once APP_ROOT . '/views/students/room_requests.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Validate student data.
     */
    private function validateStudent(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        if (empty($data['full_name'])) {
            $errors[] = 'Full name is required.';
        }
        if (empty($data['email'])) {
            $errors[] = 'Valid email address is required.';
        }
        if (empty($data['student_id_no'])) {
            $errors[] = 'Student ID number is required.';
        }

        return $errors;
    }
}
