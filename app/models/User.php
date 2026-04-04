<?php
/**
 * HostelEase — User Model
 * 
 * Handles all database operations related to the users table.
 * Includes login attempt tracking for throttling.
 */

require_once APP_ROOT . '/config/database.php';

class User
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find a user by email address.
     *
     * @param string $email
     * @return array|false User record or false
     */
    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->query(
            "SELECT * FROM users WHERE email = :email LIMIT 1",
            ['email' => $email]
        );
        return $stmt->fetch();
    }

    /**
     * Find a user by ID.
     *
     * @param int $id
     * @return array|false User record or false
     */
    public function findById(int $id): array|false
    {
        $stmt = $this->db->query(
            "SELECT * FROM users WHERE id = :id LIMIT 1",
            ['id' => $id]
        );
        return $stmt->fetch();
    }

    /**
     * Get all users, optionally filtered by role.
     *
     * @param string|null $role Filter by role
     * @return array
     */
    public function all(?string $role = null): array
    {
        if ($role) {
            $stmt = $this->db->query(
                "SELECT * FROM users WHERE role = :role ORDER BY created_at DESC",
                ['role' => $role]
            );
        } else {
            $stmt = $this->db->query("SELECT * FROM users ORDER BY created_at DESC");
        }
        return $stmt->fetchAll();
    }

    /**
     * Create a new user.
     *
     * @param array $data User data
     * @return int The new user's ID
     */
    public function create(array $data): int
    {
        $rolePrefixes = [
            'super_admin' => 'SUP',
            'admin'       => 'WRD', // Warden
            'student'     => 'STU',
            'staff'       => 'STF'
        ];
        
        $prefix = $rolePrefixes[$data['role']] ?? 'USR';
        $year = date('Y');
        
        // Find next sequence for this role and year
        $stmt = $this->db->query(
            "SELECT COUNT(*) as count FROM users WHERE role = :role AND system_id_no LIKE :pattern",
            ['role' => $data['role'], 'pattern' => "$prefix-$year-%"]
        );
        $count = $stmt->fetch()['count'] + 1;
        $systemId = sprintf("%s-%s-%04d", $prefix, $year, $count);

        $this->db->query(
            "INSERT INTO users (full_name, email, password_hash, role, status, profile_photo, system_id_no)
             VALUES (:full_name, :email, :password_hash, :role, :status, :profile_photo, :system_id_no)",
            [
                'full_name'     => $data['full_name'],
                'email'         => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                'role'          => $data['role'],
                'status'        => $data['status'] ?? 'active',
                'profile_photo' => $data['profile_photo'] ?? null,
                'system_id_no'  => $systemId
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update user data.
     *
     * @param int   $id   User ID
     * @param array $data Fields to update
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['full_name', 'email', 'role', 'status', 'profile_photo'])) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->query($sql, $params);
        return true;
    }

    /**
     * Update a user's password.
     *
     * @param int    $id          User ID
     * @param string $newPassword Plain-text password (will be hashed)
     * @return bool
     */
    public function updatePassword(int $id, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->db->query(
            "UPDATE users SET password_hash = :hash WHERE id = :id",
            ['hash' => $hash, 'id' => $id]
        );
        return true;
    }

    /**
     * Delete a user by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $this->db->query("DELETE FROM users WHERE id = :id", ['id' => $id]);
        return true;
    }

    /**
     * Count total users, optionally filtered by role.
     *
     * @param string|null $role
     * @return int
     */
    public function count(?string $role = null): int
    {
        if ($role) {
            $stmt = $this->db->query(
                "SELECT COUNT(*) as total FROM users WHERE role = :role",
                ['role' => $role]
            );
        } else {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM users");
        }
        $result = $stmt->fetch();
        return (int) $result['total'];
    }

    // ─── Login Attempt Tracking ─────────────────────────────────────

    private function loginAttemptsTableMissing(\PDOException $e): bool
    {
        return str_contains($e->getMessage(), 'login_attempts')
            && (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'Base table or view not found'));
    }

    /**
     * Record a failed login attempt.
     *
     * @param string $email
     * @param string $ip
     */
    public function recordLoginAttempt(string $email, string $ip): void
    {
        try {
            $this->db->query(
                "INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)",
                ['email' => $email, 'ip' => $ip]
            );
        } catch (\PDOException $e) {
            if ($this->loginAttemptsTableMissing($e)) {
                error_log('login_attempts table missing; run database/migrations/migrate_login_attempts.php — ' . $e->getMessage());
                return;
            }
            throw $e;
        }
    }

    /**
     * Count recent failed login attempts within the lockout window.
     *
     * @param string $email
     * @return int Number of recent attempts
     */
    public function getLoginAttempts(string $email): int
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) as attempts FROM login_attempts
                 WHERE email = :email
                 AND attempted_at > DATE_SUB(NOW(), INTERVAL :seconds SECOND)",
                ['email' => $email, 'seconds' => LOGIN_LOCKOUT_TIME]
            );
            $result = $stmt->fetch();
            return (int) $result['attempts'];
        } catch (\PDOException $e) {
            if ($this->loginAttemptsTableMissing($e)) {
                return 0;
            }
            throw $e;
        }
    }

    /**
     * Clear login attempts for an email (after successful login).
     *
     * @param string $email
     */
    public function clearLoginAttempts(string $email): void
    {
        try {
            $this->db->query(
                "DELETE FROM login_attempts WHERE email = :email",
                ['email' => $email]
            );
        } catch (\PDOException $e) {
            if ($this->loginAttemptsTableMissing($e)) {
                return;
            }
            throw $e;
        }
    }

    /**
     * Check if an email is currently locked out.
     *
     * @param string $email
     * @return bool True if locked out
     */
    public function isLockedOut(string $email): bool
    {
        return $this->getLoginAttempts($email) >= MAX_LOGIN_ATTEMPTS;
    }

    // ─── Password Reset ─────────────────────────────────────────────

    /**
     * Create a password reset token.
     *
     * @param int $userId
     * @return string The generated token
     */
    public function createPasswordResetToken(int $userId): string
    {
        // Invalidate existing tokens
        $this->db->query(
            "UPDATE password_resets SET used = TRUE WHERE user_id = :user_id AND used = FALSE",
            ['user_id' => $userId]
        );

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + PASSWORD_RESET_EXPIRY);

        $this->db->query(
            "INSERT INTO password_resets (user_id, token, expires_at)
             VALUES (:user_id, :token, :expires_at)",
            [
                'user_id'    => $userId,
                'token'      => $token,
                'expires_at' => $expiresAt,
            ]
        );

        return $token;
    }

    /**
     * Validate a password reset token.
     *
     * @param string $token
     * @return array|false Token record with user_id, or false
     */
    public function validateResetToken(string $token): array|false
    {
        $stmt = $this->db->query(
            "SELECT * FROM password_resets
             WHERE token = :token
             AND used = FALSE
             AND expires_at > NOW()
             LIMIT 1",
            ['token' => $token]
        );
        return $stmt->fetch();
    }

    /**
     * Mark a reset token as used.
     *
     * @param int $tokenId
     */
    public function markTokenUsed(int $tokenId): void
    {
        $this->db->query(
            "UPDATE password_resets SET used = TRUE WHERE id = :id",
            ['id' => $tokenId]
        );
    }

    /**
     * Get staff members (for complaint assignment).
     *
     * @return array
     */
    public function getStaff(): array
    {
        $stmt = $this->db->query(
            "SELECT id, full_name, email FROM users WHERE role = 'staff' AND status = 'active' ORDER BY full_name"
        );
        return $stmt->fetchAll();
    }
}
