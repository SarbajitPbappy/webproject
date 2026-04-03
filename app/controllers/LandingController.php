<?php
/**
 * HostelEase — Landing Page Controller
 * Public page — no auth required
 */

require_once APP_ROOT . '/app/models/Student.php';
require_once APP_ROOT . '/app/models/Room.php';
require_once APP_ROOT . '/app/models/Complaint.php';

class LandingController
{
    public function index(): void
    {
        // If already logged in, redirect to dashboard
        if (isLoggedIn()) {
            $role = $_SESSION['user_role'] ?? '';
            $redirectMap = [
                'super_admin' => 'dashboard/index',
                'admin'       => 'dashboard/index',
                'student'     => 'dashboard/student',
                'staff'       => 'dashboard/staff',
            ];
            $dest = $redirectMap[$role] ?? 'auth/login';
            header('Location: ' . BASE_URL . '?url=' . $dest);
            exit;
        }

        // Fetch live statistics
        try {
            $db = Database::getInstance();
            $stats = [
                'students' => (int)($db->query("SELECT COUNT(*) as c FROM users WHERE role='student' AND status='active'")->fetch()['c'] ?? 0),
                'staff'    => (int)($db->query("SELECT COUNT(*) as c FROM users WHERE role IN ('staff','admin','super_admin') AND status='active'")->fetch()['c'] ?? 0),
                'rooms'    => (int)($db->query("SELECT COUNT(*) as c FROM rooms")->fetch()['c'] ?? 0),
                'floors'   => (int)($db->query("SELECT COALESCE(MAX(floor),10) as c FROM rooms")->fetch()['c'] ?? 10),
            ];
        } catch (Exception $e) {
            $stats = ['students' => 100, 'staff' => 62, 'rooms' => 50, 'floors' => 10];
        }

        require_once APP_ROOT . '/views/landing.php';
    }
}
