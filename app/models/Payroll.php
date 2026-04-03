<?php
/**
 * HostelEase — Payroll Model
 * 
 * Handles database operations for staff details and pay slips.
 */

class Payroll
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get staff details including 1.5% compounding arithmetic per year
     */
    public function getStaffDetails(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT sd.*, u.full_name, u.role 
            FROM staff_details sd
            JOIN users u ON sd.user_id = u.id
            WHERE sd.user_id = :uid
        ");
        $stmt->execute([':uid' => $userId]);
        $details = $stmt->fetch();

        if ($details) {
            $details['current_salary'] = $this->calculateCurrentSalary(
                (float)$details['basic_salary'], 
                $details['join_date']
            );
        }

        return $details ?: null;
    }

    /**
     * Helper: Compound base salary by 1.5% per full year worked
     */
    private function calculateCurrentSalary(float $base, string $joinDate): float
    {
        $join = new DateTime($joinDate);
        $now = new DateTime();
        $diff = $join->diff($now);
        $years = $diff->y;

        if ($years > 0) {
            // Compound interest formula: A = P(1+r)^t
            return $base * pow(1.015, $years);
        }
        return $base;
    }

    /**
     * Apply for a Pay Slip
     */
    public function applyForSlip(int $userId, string $monthYear, float $baseSalary): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO pay_slips (user_id, month_year, basic_salary, net_salary, status)
            VALUES (:uid, :my, :base, :net, 'applied')
        ");
        
        $stmt->execute([
            ':uid'  => $userId,
            ':my'   => $monthYear,
            ':base' => $baseSalary,
            ':net'  => $baseSalary // net may change after admin review
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    /**
     * Check if slip exists for month
     */
    public function getSlipByMonth(int $userId, string $monthYear): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM pay_slips WHERE user_id = :uid AND month_year = :my LIMIT 1");
        $stmt->execute([':uid' => $userId, ':my' => $monthYear]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Admin gets all pending slips
     */
    public function getPendingSlipsForAdmin(): array
    {
        return $this->db->query("
            SELECT p.*, u.full_name, u.role 
            FROM pay_slips p
            JOIN users u ON p.user_id = u.id
            WHERE p.status = 'applied'
            ORDER BY p.created_at DESC
        ")->fetchAll();
    }

    /**
     * Admin approves slip with bonuses/deductions
     */
    public function approveSlip(int $id, int $adminId, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE pay_slips 
            SET status = 'approved',
                performance_bonus = :bonus,
                deductions = :deduct,
                net_salary = basic_salary + :bonus_calc - :deduct_calc,
                office_days = :days,
                admin_notes = :notes,
                approved_by = :aid
            WHERE id = :id AND status = 'applied'
        ");

        return $stmt->execute([
            ':bonus'        => $data['bonus'],
            ':bonus_calc'   => $data['bonus'], // Required due to PDO placeholder unique constraints
            ':deduct'       => $data['deductions'],
            ':deduct_calc'  => $data['deductions'],
            ':days'         => $data['office_days'],
            ':notes'        => $data['notes'],
            ':aid'          => $adminId,
            ':id'           => $id
        ]);
    }

    /**
     * Super Admin gets approved slips to pay
     */
    public function getApprovedSlipsForPayment(): array
    {
        return $this->db->query("
            SELECT p.*, u.full_name, u.role, sd.bank_account, sd.bank_name
            FROM pay_slips p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN staff_details sd ON p.user_id = sd.user_id
            WHERE p.status = 'approved'
            ORDER BY p.created_at ASC
        ")->fetchAll();
    }

    /**
     * Pay slip (and subtract from funds logically handled in Controller via Transactions)
     */
    public function markAsPaid(int $id, int $superAdminId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE pay_slips 
            SET status = 'paid', paid_by = :sid, paid_at = CURRENT_TIMESTAMP
            WHERE id = :id AND status = 'approved'
        ");
        return $stmt->execute([':sid' => $superAdminId, ':id' => $id]);
    }
    
    /**
     * Get user's slip history
     */
    public function getUserSlipHistory(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM pay_slips WHERE user_id = :uid ORDER BY month_year DESC");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get slip by id
     */
    public function getSlipById(int $id): ?array 
    {
        $stmt = $this->db->prepare("
            SELECT p.*, u.full_name, u.role, sd.bank_account, sd.bank_name
            FROM pay_slips p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN staff_details sd ON p.user_id = sd.user_id
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
