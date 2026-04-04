<?php
/**
 * In-app notifications for residents (billing alerts, etc.).
 */

require_once APP_ROOT . '/app/models/UserNotification.php';

class NotificationController
{
    public function __construct()
    {
        if (!isLoggedIn()) {
            header('Location: ' . BASE_URL . '?url=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        requireAuth();
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $items = UserNotification::forUser($userId, 80);
        $pageTitle = 'Notifications';

        ob_start();
        require_once APP_ROOT . '/views/notifications/index.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    public function markRead(int $id = 0): void
    {
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyToken()) {
            header('Location: ' . BASE_URL . '?url=notifications/index');
            exit;
        }
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        UserNotification::markRead($id, $userId);
        header('Location: ' . BASE_URL . '?url=notifications/index');
        exit;
    }

    public function markAllRead(): void
    {
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyToken()) {
            header('Location: ' . BASE_URL . '?url=notifications/index');
            exit;
        }
        UserNotification::markAllRead((int) ($_SESSION['user_id'] ?? 0));
        setFlash('success', 'All notifications marked as read.');
        header('Location: ' . BASE_URL . '?url=notifications/index');
        exit;
    }
}
