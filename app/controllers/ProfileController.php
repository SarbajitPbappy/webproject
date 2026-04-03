<?php
/**
 * HostelEase — Profile Controller
 * 
 * Handles user profile viewing, updating, and password changes for all roles.
 */

require_once APP_ROOT . '/app/models/User.php';
require_once APP_ROOT . '/app/models/Student.php';
require_once APP_ROOT . '/app/models/AuditLog.php';
require_once APP_ROOT . '/app/helpers/upload.php';

class ProfileController
{
    private User $userModel;
    private Student $studentModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->studentModel = new Student();
    }

    /**
     * Show profile dashboard.
     */
    public function index(): void
    {
        requireAuth();

        $userId = sanitizeInt($_SESSION['user_id']);
        $user = $this->userModel->findById($userId);

        if (!$user) {
            setFlash('error', 'User not found.');
            header('Location: ' . BASE_URL . '?url=auth/login');
            exit;
        }

        // We no longer redirect students; let everyone use this dashboard
        $isStudent = ($user['role'] === 'student');

        $pageTitle = 'My Profile';
        ob_start();
        require_once APP_ROOT . '/views/profile/index.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Show edit profile form.
     */
    public function edit(): void
    {
        requireAuth();

        $userId = sanitizeInt($_SESSION['user_id']);
        $user = $this->userModel->findById($userId);

        if (!$user) {
            setFlash('error', 'User not found.');
            header('Location: ' . BASE_URL . '?url=auth/login');
            exit;
        }

        // We only allow editing name via profile for non-students for now because students may have more data
        // Students can't edit via profile/edit easily if they redirect, but we can manage later.
        
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken()) {
                 $errors[] = 'Invalid security token.';
            } else {
                  $fullName = sanitize($_POST['full_name'] ?? '');
                  $phone = sanitizePhone($_POST['phone'] ?? '');
                  $newProfilePhoto = null;

                  if (empty($fullName)) {
                      $errors[] = "Full Name is required.";
                  }

                  if (empty($errors)) {
                      try {
                          $db = Database::getInstance();
                          $db->beginTransaction();

                          // Optional profile photo upload
                          if (!empty($_FILES['profile_photo']['name'])) {
                              $uploadResult = uploadFile($_FILES['profile_photo'], 'students');
                              if ($uploadResult['success']) {
                                  // Delete old photo (best-effort)
                                  if (!empty($user['profile_photo'])) {
                                      deleteUploadedFile($user['profile_photo'], 'students');
                                  }
                                  $newProfilePhoto = $uploadResult['filename'];
                                  $db->query("UPDATE users SET profile_photo = :photo WHERE id = :id", [
                                      ':photo' => $newProfilePhoto,
                                      ':id' => $userId
                                  ]);
                              } else {
                                  throw new \RuntimeException($uploadResult['error'] ?? 'Photo upload failed.');
                              }
                          }

                          $db->query(
                              "UPDATE users SET full_name = :name, phone = :phone WHERE id = :id",
                              [
                                  ':name' => $fullName,
                                  ':phone' => $phone,
                                  ':id' => $userId
                              ]
                          );

                          // If a students-profile exists for this user (student or hostel-occupant),
                          // sync phone there as well.
                          $db->query("UPDATE students SET phone = :phone WHERE user_id = :uid", [
                              ':phone' => $phone,
                              ':uid' => $userId
                          ]);

                          $db->commit();
                          
                          $_SESSION['user_name'] = $fullName;
                          if ($newProfilePhoto !== null) {
                              $_SESSION['user_photo'] = $newProfilePhoto;
                          }

                          AuditLog::log($userId, 'UPDATE', 'users', $userId, 'User updated their profile details');

                          setFlash('success', 'Profile updated successfully!');
                          header('Location: ' . BASE_URL . '?url=profile/index');
                          exit;

                      } catch (Exception $e) {
                          $db->rollBack();
                          error_log("Profile Update Error: " . $e->getMessage());
                          $errors[] = "An error occurred while updating the profile.";
                      }
                  }
            }
        }

        $pageTitle = 'Edit Profile';
        ob_start();
        require_once APP_ROOT . '/views/profile/edit.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Show change password form.
     */
    public function changePassword(): void
    {
        requireAuth();
        
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken()) {
                $errors[] = 'Invalid security token.';
            } else {
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                $user = $this->userModel->findById($_SESSION['user_id']);

                if (!password_verify($currentPassword, $user['password_hash'])) {
                    $errors[] = 'Current password is incorrect.';
                }
                if (strlen($newPassword) < 6) {
                    $errors[] = 'New password must be at least 6 characters.';
                }
                if ($newPassword !== $confirmPassword) {
                    $errors[] = 'New passwords do not match.';
                }

                if (empty($errors)) {
                    $this->userModel->updatePassword($user['id'], $newPassword);
                    setFlash('success', 'Password updated successfully!');
                    
                    if ($user['role'] === 'student') {
                        header('Location: ' . BASE_URL . '?url=students/show');
                    } else {
                        header('Location: ' . BASE_URL . '?url=profile/index');
                    }
                    exit;
                }
            }
        }

        $pageTitle = 'Change Password';
        ob_start();
        require_once APP_ROOT . '/views/profile/change_password.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }
}
