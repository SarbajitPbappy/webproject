<?php
/**
 * HostelEase — Warden-issued billing slips (monthly / enrollment charges).
 */

require_once APP_ROOT . '/config/database.php';

class BillingCharge
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function pendingForStudent(int $studentId): array
    {
        $stmt = $this->db->query(
            "SELECT c.*, f.name AS fee_name, f.frequency, f.fee_category, f.amount AS fee_catalog_amount
             FROM billing_charges c
             JOIN fee_structures f ON c.fee_id = f.id
             WHERE c.student_id = :sid AND c.status = 'pending'
             ORDER BY c.period_month ASC, f.name ASC",
            ['sid' => $studentId]
        );
        return $stmt->fetchAll();
    }

    public function pendingTotalForStudent(int $studentId): float
    {
        $stmt = $this->db->query(
            "SELECT COALESCE(SUM(amount_due), 0) AS s FROM billing_charges
             WHERE student_id = :sid AND status = 'pending'",
            ['sid' => $studentId]
        );
        return (float) ($stmt->fetch()['s'] ?? 0);
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->query(
            "SELECT c.*, f.name AS fee_name, f.amount AS fee_default_amount, f.maps_room_type
             FROM billing_charges c
             JOIN fee_structures f ON c.fee_id = f.id
             WHERE c.id = :id LIMIT 1",
            ['id' => $id]
        );
        return $stmt->fetch();
    }

    /**
     * Create a charge if the same student/fee/month does not already exist.
     */
    public function createIfMissing(array $row): bool
    {
        $this->db->query(
            "INSERT IGNORE INTO billing_charges
                (student_id, fee_id, period_month, amount_due, status, issued_by, notes)
             VALUES (:student_id, :fee_id, :period_month, :amount_due, 'pending', :issued_by, :notes)",
            [
                'student_id'   => $row['student_id'],
                'fee_id'       => $row['fee_id'],
                'period_month' => $row['period_month'],
                'amount_due'   => $row['amount_due'],
                'issued_by'    => $row['issued_by'] ?? null,
                'notes'        => $row['notes'] ?? null,
            ]
        );
        return $this->db->lastInsertId() !== '' && $this->db->lastInsertId() !== '0';
    }

    /**
     * @param int[] $studentIds
     * @param int[] $feeIds
     */
    public function issueBulk(array $studentIds, string $periodMonth, array $feeIds, int $issuedBy): int
    {
        $created = 0;
        foreach ($studentIds as $sid) {
            $sid = (int) $sid;
            if ($sid <= 0) {
                continue;
            }
            foreach ($feeIds as $fid) {
                $fid = (int) $fid;
                if ($fid <= 0) {
                    continue;
                }
                $fee = $this->db->query(
                    "SELECT id, amount FROM fee_structures WHERE id = :id AND is_active = TRUE LIMIT 1",
                    ['id' => $fid]
                )->fetch();
                if (!$fee) {
                    continue;
                }
                $stmt = $this->db->query(
                    "INSERT IGNORE INTO billing_charges
                        (student_id, fee_id, period_month, amount_due, status, issued_by)
                     VALUES (:student_id, :fee_id, :period_month, :amount_due, 'pending', :issued_by)",
                    [
                        'student_id'   => $sid,
                        'fee_id'       => $fid,
                        'period_month' => $periodMonth,
                        'amount_due'   => (float) $fee['amount'],
                        'issued_by'    => $issuedBy,
                    ]
                );
                $created += (int) $stmt->rowCount();
            }
        }
        return $created;
    }

    public function markPaid(int $chargeId, int $paymentId, int $studentId): bool
    {
        $this->db->query(
            "UPDATE billing_charges SET status = 'paid', payment_id = :pid
             WHERE id = :cid AND student_id = :sid AND status = 'pending'",
            ['pid' => $paymentId, 'cid' => $chargeId, 'sid' => $studentId]
        );
        return true;
    }

    public function countPendingGlobally(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS c FROM billing_charges WHERE status = 'pending'"
        );
        return (int) ($stmt->fetch()['c'] ?? 0);
    }

    public function countPendingForMonth(string $monthYear): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS c FROM billing_charges WHERE status = 'pending' AND period_month = :m",
            ['m' => $monthYear]
        );
        return (int) ($stmt->fetch()['c'] ?? 0);
    }
}
