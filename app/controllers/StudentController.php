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
            'full_name'      => sanitize($_POST['full_name'] ?? ''),
            'email'          => sanitizeEmail($_POST['email'] ?? ''),
            'student_id_no'  => sanitize($_POST['student_id_no'] ?? ''),
            'phone'          => sanitizePhone($_POST['phone'] ?? ''),
            'guardian_name'  => sanitize($_POST['guardian_name'] ?? ''),
            'guardian_phone' => sanitizePhone($_POST['guardian_phone'] ?? ''),
            'enrolled_date'  => sanitizeDate($_POST['enrolled_date'] ?? ''),
            'password'       => $password,
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
            ob_start();
            require_once APP_ROOT . '/views/students/create.php';
            $viewContent = ob_get_clean();
            require_once APP_ROOT . '/views/layouts/main.php';
            return;
        }

        try {
            $studentId = $this->studentModel->create($data);

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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken()) {
                $errors[] = 'Invalid security token.';
            } else {
                $fullName = sanitize($_POST['full_name'] ?? '');
                $phone = sanitizePhone($_POST['phone'] ?? '');
                $guardianName = sanitize($_POST['guardian_name'] ?? '');
                $guardianPhone = sanitizePhone($_POST['guardian_phone'] ?? '');

                if (empty($fullName)) {
                    $errors[] = 'Full name is required.';
                }
                if (empty($phone)) {
                    $errors[] = 'Phone number is required.';
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

                        $db->commit();

                        $_SESSION['user_name'] = $fullName;
                        if ($newProfilePhoto !== null) {
                            $_SESSION['user_photo'] = $newProfilePhoto;
                        }

                        AuditLog::log($userId, 'UPDATE', 'users', $userId, 'Student updated self profile');
                        setFlash('success', 'Profile updated successfully!');

                        header('Location: ' . BASE_URL . '?url=students/show');
                        exit;
                    } catch (\Exception $e) {
                        error_log('StudentController::editSelf() error: ' . $e->getMessage());
                        $errors[] = 'An error occurred while updating your profile.';
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
