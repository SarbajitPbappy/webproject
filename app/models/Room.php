<?php
/**
 * HostelEase — Room Model
 * 
 * Handles all database operations for the rooms table.
 */

require_once APP_ROOT . '/config/database.php';

class Room
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all rooms with optional filters.
     */
    public function all(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "r.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['type'])) {
            $where[] = "r.type = :type";
            $params['type'] = $filters['type'];
        }
        if (!empty($filters['floor'])) {
            $where[] = "r.floor = :floor";
            $params['floor'] = $filters['floor'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT r.*,
                       (SELECT COUNT(*) FROM allocations a WHERE a.room_id = r.id AND a.status = 'active') as current_occupancy
                FROM rooms r
                $whereClause
                ORDER BY r.floor, r.room_number";

        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Find room by ID.
     */
    public function findById(int $id): array|false
    {
        $stmt = $this->db->query(
            "SELECT r.*,
                    (SELECT COUNT(*) FROM allocations a WHERE a.room_id = r.id AND a.status = 'active') as current_occupancy
             FROM rooms r
             WHERE r.id = :id LIMIT 1",
            ['id' => $id]
        );
        return $stmt->fetch();
    }

    /**
     * Create a new room.
     */
    public function create(array $data): int
    {
        $this->db->query(
            "INSERT INTO rooms (room_number, floor, type, capacity, facilities, status)
             VALUES (:room_number, :floor, :type, :capacity, :facilities, :status)",
            [
                'room_number' => $data['room_number'],
                'floor'       => $data['floor'] ?? null,
                'type'        => $data['type'] ?? 'single',
                'capacity'    => $data['capacity'] ?? 1,
                'facilities'  => $data['facilities'] ?? null,
                'status'      => $data['status'] ?? 'available',
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update room data.
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];
        $allowed = ['room_number', 'floor', 'type', 'capacity', 'facilities', 'status'];

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) return false;

        $this->db->query(
            "UPDATE rooms SET " . implode(', ', $fields) . " WHERE id = :id",
            $params
        );
        return true;
    }

    /**
     * Get available rooms (rooms with remaining capacity).
     */
    public function getAvailable(): array
    {
        $stmt = $this->db->query(
            "SELECT r.*,
                    (SELECT COUNT(*) FROM allocations a WHERE a.room_id = r.id AND a.status = 'active') as current_occupancy
             FROM rooms r
             WHERE r.status = 'available'
             HAVING current_occupancy < r.capacity
             ORDER BY r.floor, r.room_number"
        );
        return $stmt->fetchAll();
    }

    /**
     * Vacant beds for a given room tier (single / double / …).
     */
    public function getAvailableByRoomType(string $roomType): array
    {
        $stmt = $this->db->query(
            "SELECT r.*,
                    (SELECT COUNT(*) FROM allocations a WHERE a.room_id = r.id AND a.status = 'active') as current_occupancy
             FROM rooms r
             WHERE r.status = 'available' AND r.type = :rtype
             HAVING current_occupancy < r.capacity
             ORDER BY r.floor, r.room_number",
            ['rtype' => $roomType]
        );
        return $stmt->fetchAll();
    }

    /**
     * Room roster: every room with names of current occupants (for warden portal).
     */
    public function getOccupancyBoard(): array
    {
        $stmt = $this->db->query(
            "SELECT r.id, r.room_number, r.floor, r.type, r.capacity, r.status,
                    (SELECT COUNT(*) FROM allocations a WHERE a.room_id = r.id AND a.status = 'active') AS current_occupancy,
                    GROUP_CONCAT(
                        CONCAT(u.full_name, ' (', s.student_id_no, ')')
                        ORDER BY u.full_name SEPARATOR ' · '
                    ) AS occupant_labels
             FROM rooms r
             LEFT JOIN allocations al ON al.room_id = r.id AND al.status = 'active'
             LEFT JOIN students s ON al.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             GROUP BY r.id, r.room_number, r.floor, r.type, r.capacity, r.status
             ORDER BY r.floor, r.room_number"
        );
        return $stmt->fetchAll();
    }

    /**
     * Update room status.
     */
    public function updateStatus(int $id, string $status): bool
    {
        $this->db->query(
            "UPDATE rooms SET status = :status WHERE id = :id",
            ['status' => $status, 'id' => $id]
        );
        return true;
    }

    /**
     * Auto-update room status based on occupancy.
     */
    public function refreshStatus(int $roomId): void
    {
        $room = $this->findById($roomId);
        if (!$room) return;

        if ($room['status'] === 'maintenance') return; // Don't auto-change maintenance

        if ($room['current_occupancy'] >= $room['capacity']) {
            $this->updateStatus($roomId, 'full');
        } else {
            $this->updateStatus($roomId, 'available');
        }
    }

    /**
     * Count rooms, optionally by status.
     */
    public function count(?string $status = null): int
    {
        if ($status) {
            $stmt = $this->db->query(
                "SELECT COUNT(*) as total FROM rooms WHERE status = :status",
                ['status' => $status]
            );
        } else {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM rooms");
        }
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Count by status (for dashboard).
     */
    public function countByStatus(): array
    {
        $stmt = $this->db->query(
            "SELECT status, COUNT(*) as total FROM rooms GROUP BY status"
        );
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = (int) $row['total'];
        }
        return $result;
    }

    /**
     * Get occupancy statistics.
     */
    public function getOccupancyStats(): array
    {
        $stmt = $this->db->query(
            "SELECT
                SUM(r.capacity) as total_capacity,
                (SELECT COUNT(*) FROM allocations a WHERE a.status = 'active') as total_occupied
             FROM rooms r
             WHERE r.status != 'maintenance'"
        );
        $stats = $stmt->fetch();
        $stats['total_capacity'] = (int) ($stats['total_capacity'] ?? 0);
        $stats['total_occupied'] = (int) ($stats['total_occupied'] ?? 0);
        $stats['occupancy_percent'] = $stats['total_capacity'] > 0
            ? round(($stats['total_occupied'] / $stats['total_capacity']) * 100, 1)
            : 0;
        return $stats;
    }

    /**
     * Sum of free beds per room type (non-maintenance rooms; active allocations only).
     * Used on registration to show vacancy for single/double/triple/dormitory.
     *
     * @return array<string,int> keys: single, double, triple, dormitory
     */
    public function countOpenBedsByRoomType(): array
    {
        $types = ['single', 'double', 'triple', 'dormitory'];
        $out = array_fill_keys($types, 0);
        $stmt = $this->db->query(
            "SELECT r.type,
                    SUM(GREATEST(0, r.capacity - COALESCE(occ.cnt, 0))) AS open_beds
             FROM rooms r
             LEFT JOIN (
                 SELECT room_id, COUNT(*) AS cnt FROM allocations
                 WHERE status = 'active' GROUP BY room_id
             ) occ ON occ.room_id = r.id
             WHERE r.status != 'maintenance'
             GROUP BY r.type"
        );
        foreach ($stmt->fetchAll() as $row) {
            $t = (string) $row['type'];
            if (array_key_exists($t, $out)) {
                $out[$t] = (int) $row['open_beds'];
            }
        }
        return $out;
    }

    /**
     * Get distinct floors for filter dropdown.
     */
    public function getFloors(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT floor FROM rooms WHERE floor IS NOT NULL ORDER BY floor"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Delete a room.
     */
    public function delete(int $id): bool
    {
        $this->db->query("DELETE FROM rooms WHERE id = :id", ['id' => $id]);
        return true;
    }
}
