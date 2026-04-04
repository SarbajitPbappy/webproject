<?php
/**
 * Student-initiated room change / cancellation requests.
 */

require_once APP_ROOT . '/config/database.php';

class RoomServiceRequest
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function tableReady(): bool
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) AS c FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'room_service_requests'"
            );
            return (int) $stmt->fetch()['c'] > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function create(array $row): int
    {
        $this->db->query(
            "INSERT INTO room_service_requests
                (student_id, request_type, status, preferred_room_type, message)
             VALUES (:sid, :rtype, 'pending', :prt, :msg)",
            [
                'sid'   => $row['student_id'],
                'rtype' => $row['request_type'],
                'prt'   => $row['preferred_room_type'] ?? null,
                'msg'   => $row['message'] ?? null,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->query(
            "SELECT r.*, u.full_name, u.id AS user_id, s.student_id_no
             FROM room_service_requests r
             JOIN students s ON s.id = r.student_id
             JOIN users u ON u.id = s.user_id
             WHERE r.id = :id LIMIT 1",
            ['id' => $id]
        );
        $r = $stmt->fetch();
        return $r ?: null;
    }

    /** Most recent request for dashboard (any status). */
    public function latestForStudent(int $studentId): ?array
    {
        if (!$this->tableReady()) {
            return null;
        }
        $stmt = $this->db->query(
            "SELECT * FROM room_service_requests WHERE student_id = :sid ORDER BY created_at DESC LIMIT 1",
            ['sid' => $studentId]
        );
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public function forStudent(int $studentId): array
    {
        if (!$this->tableReady()) {
            return [];
        }
        $stmt = $this->db->query(
            "SELECT * FROM room_service_requests WHERE student_id = :sid ORDER BY created_at DESC",
            ['sid' => $studentId]
        );
        return $stmt->fetchAll();
    }

    public function hasPending(int $studentId): bool
    {
        if (!$this->tableReady()) {
            return false;
        }
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS c FROM room_service_requests
             WHERE student_id = :sid AND status = 'pending'",
            ['sid' => $studentId]
        );
        return (int) $stmt->fetch()['c'] > 0;
    }

    public function pendingForAdmin(): array
    {
        if (!$this->tableReady()) {
            return [];
        }
        $stmt = $this->db->query(
            "SELECT r.*, u.full_name, s.student_id_no, rm.room_number AS current_room
             FROM room_service_requests r
             JOIN students s ON s.id = r.student_id
             JOIN users u ON u.id = s.user_id
             LEFT JOIN allocations a ON a.student_id = s.id AND a.status = 'active'
             LEFT JOIN rooms rm ON rm.id = a.room_id
             WHERE r.status = 'pending'
             ORDER BY r.created_at ASC"
        );
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status, ?int $processedBy, ?string $adminNotes): void
    {
        $this->db->query(
            "UPDATE room_service_requests
             SET status = :st, processed_at = NOW(), processed_by = :pb, admin_notes = :an
             WHERE id = :id",
            ['st' => $status, 'pb' => $processedBy, 'an' => $adminNotes, 'id' => $id]
        );
    }
}
