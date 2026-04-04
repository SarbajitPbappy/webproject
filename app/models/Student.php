<?php
/**
 * HostelEase — Student Model
 */

class Student
{
    private Database $db;
    private $pdo; // For raw access if needed

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Count students with optional status filter
     */
    public function count(?string $status = null): int
    {
        $params = [];
        $sql = "SELECT COUNT(*) as total FROM students s JOIN users u ON s.user_id = u.id";
        if ($status) {
            $sql .= " WHERE u.status = :status";
            $params['status'] = $status;
        }
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Find student record by user ID
     */
    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->query("
            SELECT s.*, u.full_name, u.email, u.status, u.role, u.profile_photo
            FROM students s
            JOIN users u ON s.user_id = u.id
            WHERE s.user_id = :uid
        ", [':uid' => $userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all students with their active room allocation (optional filters)
     */
    public function all(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "u.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(u.full_name LIKE :search OR u.email LIKE :search OR s.student_id_no LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->query("
            SELECT s.*,
                   u.full_name,
                   u.email,
                   u.status,
                   u.profile_photo
                   ,a.room_id,
                   r.room_number
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN allocations a ON s.id = a.student_id AND a.status = 'active'
            LEFT JOIN rooms r ON a.room_id = r.id
            {$whereClause}
            ORDER BY u.full_name ASC
        ", $params);

        return $stmt->fetchAll();
    }

    /**
     * Get basic student info for dropdowns
     */
    public function getForDropdown(): array
    {
        $stmt = $this->db->query("
            SELECT s.id, s.student_id_no, s.entitled_room_type, u.full_name 
            FROM students s
            JOIN users u ON s.user_id = u.id
            WHERE u.status = 'active'
            ORDER BY u.full_name ASC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Get students currently holding an active allocation for dropdowns
     */
    public function getAllocatedForDropdown(): array
    {
        $stmt = $this->db->query("
            SELECT s.id, s.student_id_no, s.entitled_room_type, u.full_name, a.room_id, r.room_number, r.type AS current_room_type
            FROM students s
            JOIN users u ON s.user_id = u.id
            JOIN allocations a ON s.id = a.student_id AND a.status = 'active'
            JOIN rooms r ON a.room_id = r.id
            WHERE u.status = 'active'
            ORDER BY u.full_name ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Active students currently living in the hall (active allocation).
     *
     * @return int[]
     */
    public function activeResidentIds(): array
    {
        $stmt = $this->db->query("
            SELECT DISTINCT s.id FROM students s
            JOIN users u ON s.user_id = u.id
            JOIN allocations a ON a.student_id = s.id AND a.status = 'active'
            WHERE u.status = 'active'
        ");
        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }

    /**
     * All students with active user accounts.
     *
     * @return int[]
     */
    public function allActiveStudentIds(): array
    {
        $stmt = $this->db->query("
            SELECT s.id FROM students s
            JOIN users u ON s.user_id = u.id
            WHERE u.status = 'active'
        ");
        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }

    /**
     * Find a single student by ID
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->query("
            SELECT s.*, u.full_name, u.email, u.status, u.role, u.profile_photo
            FROM students s
            JOIN users u ON s.user_id = u.id
            WHERE s.id = :id
        ", [':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Compatibility alias used by controllers.
     */
    public function findById(int $id): ?array
    {
        return $this->find($id);
    }

    /**
     * Create a student (creates user + student profile).
     *
     * @return int New student id
     */
    public function create(array $data): int
    {
        require_once APP_ROOT . '/app/models/User.php';

        $this->db->beginTransaction();
        try {
            $userModel = new User();
            $profilePhoto = $data['profile_photo'] ?? null;

            $userId = $userModel->create([
                'full_name'      => $data['full_name'],
                'email'          => $data['email'],
                'password'       => $data['password'],
                'role'           => 'student',
                'status'         => $data['status'] ?? 'active',
                'profile_photo'  => $profilePhoto,
            ]);

            $this->db->query(
                "INSERT INTO students (user_id, student_id_no, phone, guardian_name, guardian_phone, nid_or_card, enrolled_date)
                 VALUES (:uid, :sid, :phone, :gname, :gphone, :nid, :edate)",
                [
                    ':uid'    => $userId,
                    ':sid'    => $data['student_id_no'],
                    ':phone'  => $data['phone'] ?? null,
                    ':gname'  => $data['guardian_name'] ?? null,
                    ':gphone' => $data['guardian_phone'] ?? null,
                    ':nid'    => $data['nid_or_card'] ?? null,
                    ':edate'  => $data['enrolled_date'] ?? null,
                ]
            );

            $studentId = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $studentId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Update a student (updates user + student profile).
     */
    public function update(int $id, array $data): bool
    {
        $student = $this->find($id);
        if (!$student) return false;

        $this->db->beginTransaction();
        try {
            // Update users table (name/email/status/profile photo)
            $fields = [
                'full_name'     => $data['full_name'] ?? $student['full_name'],
                'email'         => $data['email'] ?? $student['email'],
                'status'        => $data['status'] ?? $student['status'],
            ];

            $photo = array_key_exists('profile_photo', $data) ? ($data['profile_photo'] ?? null) : ($student['profile_photo'] ?? null);

            if ($photo !== $student['profile_photo']) {
                $fields['profile_photo'] = $photo;
            }

            $params = [':uid' => (int)$student['user_id']];
            $setParts = [];

            foreach ($fields as $k => $v) {
                $setParts[] = "{$k} = :{$k}";
                $params[":{$k}"] = $v;
            }

            if (!empty($setParts)) {
                $this->db->query(
                    "UPDATE users SET " . implode(', ', $setParts) . " WHERE id = :uid",
                    $params
                );
            }

            // Update students table (profile details)
            $studentSet = [];
            $studentParams = [':id' => $id];

            foreach ([
                'student_id_no',
                'phone',
                'guardian_name',
                'guardian_phone',
                'enrolled_date',
                'nid_or_card',
            ] as $field) {
                if (array_key_exists($field, $data)) {
                    $studentSet[] = "{$field} = :{$field}";
                    $studentParams[":{$field}"] = $data[$field];
                }
            }

            if (!empty($studentSet)) {
                $this->db->query(
                    "UPDATE students SET " . implode(', ', $studentSet) . " WHERE id = :id",
                    $studentParams
                );
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Delete user mapping and student data
     */
    public function delete(int $id): bool
    {
        $student = $this->find($id);
        if (!$student) return false;

        try {
            $this->db->beginTransaction();

            $this->db->query("DELETE FROM students WHERE id = :id", [':id' => $id]);
            $this->db->query("DELETE FROM users WHERE id = :user_id", [':user_id' => $student['user_id']]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Delete Student Error: ' . $e->getMessage());
            return false;
        }
    }
}
