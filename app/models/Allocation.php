<?php
/**
 * HostelEase — Allocation Model
 * 
 * Handles room allocations, transfers, vacating, and waitlist.
 */

require_once APP_ROOT . '/config/database.php';

class Allocation
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Allocate a student to a room.
     */
    public function allocate(array $data): int
    {
        $this->db->query(
            "INSERT INTO allocations (student_id, room_id, allocated_by, start_date, status, notes)
             VALUES (:student_id, :room_id, :allocated_by, :start_date, 'active', :notes)",
            [
                'student_id'   => $data['student_id'],
                'room_id'      => $data['room_id'],
                'allocated_by' => $data['allocated_by'],
                'start_date'   => $data['start_date'] ?? date('Y-m-d'),
                'notes'        => $data['notes'] ?? null,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Transfer a student to a different room.
     */
    public function transfer(int $studentId, int $newRoomId, int $allocatedBy, ?string $notes = null): int
    {
        $this->db->beginTransaction();
        try {
            // End current allocation
            $this->db->query(
                "UPDATE allocations SET status = 'transferred', end_date = CURDATE()
                 WHERE student_id = :student_id AND status = 'active'",
                ['student_id' => $studentId]
            );

            // Create new allocation
            $this->db->query(
                "INSERT INTO allocations (student_id, room_id, allocated_by, start_date, status, notes)
                 VALUES (:student_id, :room_id, :allocated_by, CURDATE(), 'active', :notes)",
                [
                    'student_id'   => $studentId,
                    'room_id'      => $newRoomId,
                    'allocated_by' => $allocatedBy,
                    'notes'        => $notes ?? 'Room transfer',
                ]
            );
            $newAllocId = (int) $this->db->lastInsertId();

            $this->db->commit();
            return $newAllocId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Vacate a student from their current room.
     */
    public function vacate(int $studentId, ?string $notes = null): bool
    {
        $this->db->query(
            "UPDATE allocations SET status = 'vacated', end_date = CURDATE(), notes = CONCAT(IFNULL(notes,''), :notes)
             WHERE student_id = :student_id AND status = 'active'",
            [
                'student_id' => $studentId,
                'notes'      => $notes ? ' | Vacated: ' . $notes : ' | Vacated',
            ]
        );
        return true;
    }

    /**
     * Find active allocation for a student.
     */
    public function findActiveByStudent(int $studentId): array|false
    {
        $stmt = $this->db->query(
            "SELECT a.*, r.room_number, r.type as room_type, r.floor
             FROM allocations a
             JOIN rooms r ON a.room_id = r.id
             WHERE a.student_id = :student_id AND a.status = 'active'
             LIMIT 1",
            ['student_id' => $studentId]
        );
        return $stmt->fetch();
    }

    /**
     * Find all allocations for a room (active).
     */
    public function findByRoom(int $roomId): array
    {
        $stmt = $this->db->query(
            "SELECT a.*, s.student_id_no, u.full_name
             FROM allocations a
             JOIN students s ON a.student_id = s.id
             JOIN users u ON s.user_id = u.id
             WHERE a.room_id = :room_id AND a.status = 'active'
             ORDER BY a.start_date",
            ['room_id' => $roomId]
        );
        return $stmt->fetchAll();
    }

    /**
     * Get allocation history for a student.
     */
    public function history(int $studentId): array
    {
        $stmt = $this->db->query(
            "SELECT a.*, r.room_number, r.type as room_type,
                    u.full_name as allocated_by_name
             FROM allocations a
             JOIN rooms r ON a.room_id = r.id
             LEFT JOIN users u ON a.allocated_by = u.id
             WHERE a.student_id = :student_id
             ORDER BY a.created_at DESC",
            ['student_id' => $studentId]
        );
        return $stmt->fetchAll();
    }

    /**
     * Get all allocations (for admin view).
     */
    public function all(): array
    {
        $stmt = $this->db->query(
            "SELECT a.*, r.room_number, r.floor, r.type AS room_type, s.student_id_no, u.full_name,
                    alloc_user.full_name as allocated_by_name
             FROM allocations a
             JOIN rooms r ON a.room_id = r.id
             JOIN students s ON a.student_id = s.id
             JOIN users u ON s.user_id = u.id
             LEFT JOIN users alloc_user ON a.allocated_by = alloc_user.id
             ORDER BY a.created_at DESC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Check room availability (capacity vs current occupants).
     */
    public function checkAvailability(int $roomId): array
    {
        $stmt = $this->db->query(
            "SELECT r.capacity,
                    (SELECT COUNT(*) FROM allocations a WHERE a.room_id = r.id AND a.status = 'active') as current_occupancy
             FROM rooms r WHERE r.id = :id",
            ['id' => $roomId]
        );
        $result = $stmt->fetch();
        if (!$result) return ['available' => false, 'remaining' => 0];

        $remaining = $result['capacity'] - $result['current_occupancy'];
        return [
            'available'  => $remaining > 0 && true,
            'remaining'  => $remaining,
            'capacity'   => (int) $result['capacity'],
            'occupied'   => (int) $result['current_occupancy'],
        ];
    }

    // ─── Waitlist ────────────────────────────────────────────────────

    /**
     * Add student to waitlist.
     */
    public function waitlistAdd(int $studentId, ?string $preferredRoomType = null): int
    {
        $stmt = $this->db->query(
            'SELECT id FROM waitlist WHERE student_id = :sid AND status = :st LIMIT 1',
            ['sid' => $studentId, 'st' => 'waiting']
        );
        $existing = $stmt->fetch();
        if ($existing) {
            if ($preferredRoomType !== null && $preferredRoomType !== '') {
                $this->db->query(
                    'UPDATE waitlist SET preferred_room_type = :prt WHERE id = :id',
                    ['prt' => $preferredRoomType, 'id' => (int) $existing['id']]
                );
            }
            return (int) $existing['id'];
        }
        $this->db->query(
            "INSERT INTO waitlist (student_id, preferred_room_type, status) VALUES (:student_id, :prt, 'waiting')",
            ['student_id' => $studentId, 'prt' => $preferredRoomType]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Next waitlisted student who is eligible for a room of this type (paid tier or queue preference).
     */
    public function waitlistNextForRoomType(string $roomType): array|false
    {
        $stmt = $this->db->query(
            "SELECT w.*, u.full_name, s.student_id_no, s.entitled_room_type
             FROM waitlist w
             JOIN students s ON w.student_id = s.id
             JOIN users u ON s.user_id = u.id
             WHERE w.status = 'waiting'
               AND COALESCE(s.entitled_room_type, w.preferred_room_type) = :rt
             ORDER BY w.requested_at ASC
             LIMIT 1",
            ['rt' => $roomType]
        );
        return $stmt->fetch();
    }

    /**
     * Get next student from waitlist (any type — prefer waitlistNextForRoomType for auto-allocation).
     */
    public function waitlistNext(): array|false
    {
        $stmt = $this->db->query(
            "SELECT w.*, u.full_name, s.student_id_no
             FROM waitlist w
             JOIN students s ON w.student_id = s.id
             JOIN users u ON s.user_id = u.id
             WHERE w.status = 'waiting'
             ORDER BY w.requested_at ASC
             LIMIT 1"
        );
        return $stmt->fetch();
    }

    /**
     * Get all waitlist entries.
     */
    public function waitlistAll(): array
    {
        $stmt = $this->db->query(
            "SELECT w.*, u.full_name, s.student_id_no, s.entitled_room_type
             FROM waitlist w
             JOIN students s ON w.student_id = s.id
             JOIN users u ON s.user_id = u.id
             WHERE w.status = 'waiting'
             ORDER BY w.requested_at ASC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Update waitlist status for a student.
     */
    public function waitlistUpdateStatus(int $studentId, string $status): bool
    {
        $this->db->query(
            "UPDATE waitlist SET status = :status WHERE student_id = :sid AND status = 'waiting'",
            ['status' => $status, 'sid' => $studentId]
        );
        return true;
    }

    /**
     * Update or create a waiting waitlist row with the chosen room tier.
     * Not allowed when the student already has an active room allocation.
     *
     * @return string|null Error message, or null on success
     */
    public function updateStudentWaitlistPreference(int $studentId, string $roomType): ?string
    {
        $allowed = ['single', 'double', 'triple', 'dormitory'];
        if (!in_array($roomType, $allowed, true)) {
            return 'Please choose a valid room type.';
        }
        if ($this->findActiveByStudent($studentId)) {
            return 'You already have an allocated room. Contact the office if you need to change category.';
        }
        $this->waitlistAdd($studentId, $roomType);
        return null;
    }

    /**
     * Preferred room tier on the active waitlist row (for enrollment billing).
     */
    public function waitlistPreferredType(int $studentId): ?string
    {
        $stmt = $this->db->query(
            "SELECT preferred_room_type FROM waitlist
             WHERE student_id = :sid AND status = 'waiting'
             ORDER BY requested_at ASC LIMIT 1",
            ['sid' => $studentId]
        );
        $r = $stmt->fetch();
        if (!$r || empty($r['preferred_room_type'])) {
            return null;
        }
        return (string) $r['preferred_room_type'];
    }

    /**
     * Check if student is already on waitlist.
     */
    public function isOnWaitlist(int $studentId): bool
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM waitlist WHERE student_id = :sid AND status = 'waiting'",
            ['sid' => $studentId]
        );
        return (int) $stmt->fetch()['total'] > 0;
    }
}
