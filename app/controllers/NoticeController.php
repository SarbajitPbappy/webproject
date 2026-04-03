<?php
/**
 * HostelEase — Notice Controller
 */

require_once APP_ROOT . '/app/models/Notice.php';
require_once APP_ROOT . '/app/models/AuditLog.php';

class NoticeController
{
    private Notice $noticeModel;

    public function __construct()
    {
        $this->noticeModel = new Notice();
    }

    public function index(): void
    {
        requireAuth();
        $notices = $this->noticeModel->all();
        $pageTitle = 'Notice Board';

        ob_start();
        require_once APP_ROOT . '/views/notices/index.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    public function create(): void
    {
        requireRole(['super_admin', 'admin']);
        $errors = [];
        $data = [];
        $pageTitle = 'Create Notice';

        ob_start();
        require_once APP_ROOT . '/views/notices/create.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    public function store(): void
    {
        requireRole(['super_admin', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyToken()) {
            setFlash('error', 'Invalid request.');
            header('Location: ' . BASE_URL . '?url=notices/create');
            exit;
        }

        $data = [
            'title'     => sanitize($_POST['title'] ?? ''),
            'body'      => sanitize($_POST['body'] ?? ''),
            'posted_by' => $_SESSION['user_id'],
            'is_pinned' => isset($_POST['is_pinned']) ? 1 : 0,
        ];

        $errors = [];
        if (empty($data['title'])) $errors[] = 'Title is required.';
        if (empty($data['body'])) $errors[] = 'Body is required.';

        if (!empty($errors)) {
            $pageTitle = 'Create Notice';
            ob_start();
            require_once APP_ROOT . '/views/notices/create.php';
            $viewContent = ob_get_clean();
            require_once APP_ROOT . '/views/layouts/main.php';
            return;
        }

        $noticeId = $this->noticeModel->create($data);
        AuditLog::log($_SESSION['user_id'], 'CREATE', 'notices', $noticeId, 'Posted notice: ' . $data['title']);
        setFlash('success', 'Notice posted successfully!');
        header('Location: ' . BASE_URL . '?url=notices/index');
        exit;
    }

    public function edit(int $id = 0): void
    {
        requireRole(['super_admin', 'admin']);
        $id = sanitizeInt($id ?: ($_GET['id'] ?? 0));

        $notice = $this->noticeModel->findById($id);
        if (!$notice) {
            setFlash('error', 'Notice not found.');
            header('Location: ' . BASE_URL . '?url=notices/index');
            exit;
        }

        $data = $notice;
        $errors = [];
        $pageTitle = 'Edit Notice';

        ob_start();
        require_once APP_ROOT . '/views/notices/create.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    public function update(int $id = 0): void
    {
        requireRole(['super_admin', 'admin']);
        $id = sanitizeInt($id ?: ($_POST['id'] ?? 0));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyToken()) {
            setFlash('error', 'Invalid request.');
            header('Location: ' . BASE_URL . '?url=notices/index');
            exit;
        }

        $data = [
            'title'     => sanitize($_POST['title'] ?? ''),
            'body'      => sanitize($_POST['body'] ?? ''),
            'is_pinned' => isset($_POST['is_pinned']) ? 1 : 0,
        ];

        $this->noticeModel->update($id, $data);
        AuditLog::log($_SESSION['user_id'], 'UPDATE', 'notices', $id, 'Updated notice: ' . $data['title']);
        setFlash('success', 'Notice updated.');
        header('Location: ' . BASE_URL . '?url=notices/index');
        exit;
    }

    public function delete(int $id = 0): void
    {
        requireRole(['super_admin', 'admin']);
        $id = sanitizeInt($id ?: ($_POST['id'] ?? 0));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyToken()) {
            setFlash('error', 'Invalid request.');
            header('Location: ' . BASE_URL . '?url=notices/index');
            exit;
        }

        $this->noticeModel->delete($id);
        AuditLog::log($_SESSION['user_id'], 'DELETE', 'notices', $id, 'Deleted notice');
        setFlash('success', 'Notice deleted.');
        header('Location: ' . BASE_URL . '?url=notices/index');
        exit;
    }
}
