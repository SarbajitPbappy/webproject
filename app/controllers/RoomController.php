<?php
/**
 * HostelEase — Room Controller
 * 
 * Handles room CRUD and occupancy reports.
 * Access: super_admin, admin
 */

require_once APP_ROOT . '/app/models/Room.php';
require_once APP_ROOT . '/app/models/AuditLog.php';

class RoomController
{
    private Room $roomModel;

    public function __construct()
    {
        $this->roomModel = new Room();
    }

    /**
     * List all rooms with filters.
     */
    public function index(): void
    {
        requireRole(['super_admin', 'admin']);

        $filters = [];
        if (!empty($_GET['status'])) $filters['status'] = sanitize($_GET['status']);
        if (!empty($_GET['type'])) $filters['type'] = sanitize($_GET['type']);
        if (!empty($_GET['floor'])) $filters['floor'] = sanitizeInt($_GET['floor']);

        $rooms = $this->roomModel->all($filters);
        $floors = $this->roomModel->getFloors();
        $stats = $this->roomModel->getOccupancyStats();
        $pageTitle = 'Room Management';

        ob_start();
        require_once APP_ROOT . '/views/rooms/index.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Show create room form.
     */
    public function create(): void
    {
        requireRole(['super_admin', 'admin']);

        $errors = [];
        $data = [];
        $pageTitle = 'Add New Room';

        ob_start();
        require_once APP_ROOT . '/views/rooms/create.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Process room creation.
     */
    public function store(): void
    {
        requireRole(['super_admin', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?url=rooms/create');
            exit;
        }
        if (!verifyToken()) {
            setFlash('error', 'Invalid security token.');
            header('Location: ' . BASE_URL . '?url=rooms/create');
            exit;
        }

        $data = [
            'room_number' => sanitize($_POST['room_number'] ?? ''),
            'floor'       => sanitizeInt($_POST['floor'] ?? 0),
            'type'        => sanitize($_POST['type'] ?? 'single'),
            'capacity'    => sanitizeInt($_POST['capacity'] ?? 1),
            'facilities'  => sanitize($_POST['facilities'] ?? ''),
            'status'      => sanitize($_POST['status'] ?? 'available'),
        ];

        $errors = [];
        if (empty($data['room_number'])) $errors[] = 'Room number is required.';
        if ($data['capacity'] < 1) $errors[] = 'Capacity must be at least 1.';

        if (!empty($errors)) {
            $pageTitle = 'Add New Room';
            ob_start();
            require_once APP_ROOT . '/views/rooms/create.php';
            $viewContent = ob_get_clean();
            require_once APP_ROOT . '/views/layouts/main.php';
            return;
        }

        try {
            $roomId = $this->roomModel->create($data);

            AuditLog::log(
                $_SESSION['user_id'], 'CREATE', 'rooms', $roomId,
                'Created room: ' . $data['room_number']
            );

            setFlash('success', 'Room created successfully.');
            header('Location: ' . BASE_URL . '?url=rooms/index');
            exit;
        } catch (\Exception $e) {
            error_log('RoomController::store() error: ' . $e->getMessage());
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $errors[] = 'Room number already exists.';
            } else {
                $errors[] = 'An error occurred while creating the room.';
            }
            $pageTitle = 'Add New Room';
            ob_start();
            require_once APP_ROOT . '/views/rooms/create.php';
            $viewContent = ob_get_clean();
            require_once APP_ROOT . '/views/layouts/main.php';
        }
    }

    /**
     * Show edit room form.
     */
    public function edit(int $id = 0): void
    {
        requireRole(['super_admin', 'admin']);
        $id = sanitizeInt($id ?: ($_GET['id'] ?? 0));

        $room = $this->roomModel->findById($id);
        if (!$room) {
            setFlash('error', 'Room not found.');
            header('Location: ' . BASE_URL . '?url=rooms/index');
            exit;
        }

        $data = $room;
        $errors = [];
        $pageTitle = 'Edit Room';

        ob_start();
        require_once APP_ROOT . '/views/rooms/edit.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Process room update.
     */
    public function update(int $id = 0): void
    {
        requireRole(['super_admin', 'admin']);
        $id = sanitizeInt($id ?: ($_POST['id'] ?? 0));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyToken()) {
            setFlash('error', 'Invalid request.');
            header('Location: ' . BASE_URL . '?url=rooms/index');
            exit;
        }

        $data = [
            'room_number' => sanitize($_POST['room_number'] ?? ''),
            'floor'       => sanitizeInt($_POST['floor'] ?? 0),
            'type'        => sanitize($_POST['type'] ?? 'single'),
            'capacity'    => sanitizeInt($_POST['capacity'] ?? 1),
            'facilities'  => sanitize($_POST['facilities'] ?? ''),
            'status'      => sanitize($_POST['status'] ?? 'available'),
        ];

        $errors = [];
        if (empty($data['room_number'])) $errors[] = 'Room number is required.';
        if ($data['capacity'] < 1) $errors[] = 'Capacity must be at least 1.';

        if (!empty($errors)) {
            $room = $this->roomModel->findById($id);
            $data = array_merge($room, $data);
            $pageTitle = 'Edit Room';
            ob_start();
            require_once APP_ROOT . '/views/rooms/edit.php';
            $viewContent = ob_get_clean();
            require_once APP_ROOT . '/views/layouts/main.php';
            return;
        }

        try {
            $this->roomModel->update($id, $data);
            AuditLog::log($_SESSION['user_id'], 'UPDATE', 'rooms', $id, 'Updated room: ' . $data['room_number']);
            setFlash('success', 'Room updated successfully.');
            header('Location: ' . BASE_URL . '?url=rooms/index');
            exit;
        } catch (\Exception $e) {
            error_log('RoomController::update() error: ' . $e->getMessage());
            setFlash('error', 'An error occurred.');
            header('Location: ' . BASE_URL . '?url=rooms/edit/' . $id);
            exit;
        }
    }

    /**
     * Occupancy report.
     */
    public function occupancyReport(): void
    {
        requireRole(['super_admin', 'admin']);

        $rooms = $this->roomModel->all();
        $stats = $this->roomModel->getOccupancyStats();
        $pageTitle = 'Occupancy Report';

        ob_start();
        require_once APP_ROOT . '/views/rooms/index.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }
}
