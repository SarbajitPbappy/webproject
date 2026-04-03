<?php
/**
 * HostelEase — Complaint Model
 * 
 * Handles complaint tickets with SLA tracking.
 */

require_once APP_ROOT . '/config/database.php';

class Complaint
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all complaints with filters.
     */
    public function all(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "c.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $where[] = "c.priority = :priority";
            $params['priority'] = $filters['priority'];
        }
        if (!empty($filters['student_id'])) {
            $where[] = "c.student_id = :student_id";
            $params['student_id'] = $filters['student_id'];
        }
        if (!empty($filters['assigned_to'])) {
            $where[] = "c.assigned_to = :assigned_to";
            $params['assigned_to'] = $filters['assigned_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->query(
            "SELECT c.*, u.full_name as student_name, s.student_id_no,
                    staff.full_name as assigned_to_name
             FROM complaints c
             JOIN students s ON c.student_id = s.id
             JOIN users u ON s.user_id = u.id
             LEFT JOIN users staff ON c.assigned_to = staff.id
             $whereClause
             ORDER BY c.created_at DESC",
            $params
        );
        return $stmt->fetchAll();
    }

    /**
     * Create a new complaint.
     */
    public function create(array $data): int
    {
        $this->db->query(
            "INSERT INTO complaints (student_id, category, description, priority, status)
             VALUES (:student_id, :category, :description, :priority, 'open')",
            [
                'student_id'  => $data['student_id'],
                'category'    => $data['category'],
                'description' => $data['description'],
                'priority'    => $data['priority'] ?? 'medium',
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Find complaint by ID.
     */
    public function findById(int $id): array|false
    {
        $stmt = $this->db->query(
            "SELECT c.*, u.full_name as student_name, s.student_id_no, u.email as student_email,
                    staff.full_name as assigned_to_name
             FROM complaints c
             JOIN students s ON c.student_id = s.id
             JOIN users u ON s.user_id = u.id
             LEFT JOIN users staff ON c.assigned_to = staff.id
             WHERE c.id = :id LIMIT 1",
            ['id' => $id]
        );
        return $stmt->fetch();
    }

    /**
     * Find complaints by student.
     */
    public function findByStudent(int $studentId): array
    {
        return $this->all(['student_id' => $studentId]);
    }

    /**
     * Update complaint status.
     */
    public function updateStatus(int $id, string $status): bool
    {
        $params = ['status' => $status, 'id' => $id];
        $resolvedAt = '';

        if ($status === 'resolved' || $status === 'closed') {
            $resolvedAt = ", resolved_at = NOW()";
        }

        $this->db->query(
            "UPDATE complaints SET status = :status $resolvedAt WHERE id = :id",
            $params
        );
        return true;
    }

    /**
     * Assign complaint to staff member.
     */
    public function assignTo(int $complaintId, int $staffId): bool
    {
        $this->db->query(
            "UPDATE complaints SET assigned_to = :staff_id, status = 'in_progress' WHERE id = :id",
            ['staff_id' => $staffId, 'id' => $complaintId]
        );
        return true;
    }

    /**
     * Get SLA-overdue complaints.
     * High priority: overdue after 24h
     * Medium priority: overdue after 72h
     */
    public function getSLAOverdue(): array
    {
        $stmt = $this->db->query(
            "SELECT c.*, u.full_name as student_name, s.student_id_no,
                    staff.full_name as assigned_to_name,
                    TIMESTAMPDIFF(HOUR, c.created_at, NOW()) as hours_open
             FROM complaints c
             JOIN students s ON c.student_id = s.id
             JOIN users u ON s.user_id = u.id
             LEFT JOIN users staff ON c.assigned_to = staff.id
             WHERE c.status IN ('open', 'in_progress')
             AND (
                 (c.priority = 'high' AND TIMESTAMPDIFF(HOUR, c.created_at, NOW()) > :sla_high)
                 OR
                 (c.priority = 'medium' AND TIMESTAMPDIFF(HOUR, c.created_at, NOW()) > :sla_medium)
             )
             ORDER BY c.priority DESC, c.created_at ASC",
            [
                'sla_high'   => SLA_HIGH_PRIORITY_HOURS,
                'sla_medium' => SLA_MEDIUM_PRIORITY_HOURS,
            ]
        );
        return $stmt->fetchAll();
    }

    /**
     * Count complaints by status.
     */
    public function count(?string $status = null): int
    {
        if ($status) {
            $stmt = $this->db->query(
                "SELECT COUNT(*) as total FROM complaints WHERE status = :status",
                ['status' => $status]
            );
        } else {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM complaints");
        }
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Count open complaints (for dashboard).
     */
    public function countOpen(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM complaints WHERE status IN ('open', 'in_progress')"
        );
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Get complaints assigned to a specific staff member.
     */
    public function findByStaff(int $userId): array
    {
        return $this->all(['assigned_to' => $userId]);
    }
}
