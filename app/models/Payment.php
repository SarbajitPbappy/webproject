<?php
/**
 * HostelEase — Payment Model
 * 
 * Handles payment recording, receipt generation, and reconciliation.
 */

require_once APP_ROOT . '/config/database.php';

class Payment
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Record a new payment.
     */
    public function record(array $data): int
    {
        try {
            $this->db->beginTransaction();

            $receiptNo = $this->generateReceiptNo();

            $this->db->query(
                "INSERT INTO payments (student_id, fee_id, amount_paid, payment_date, receipt_no, payment_method, recorded_by, month_year, notes)
                 VALUES (:student_id, :fee_id, :amount_paid, :payment_date, :receipt_no, :method, :recorded_by, :month_year, :notes)",
                [
                    'student_id'   => $data['student_id'],
                    'fee_id'       => $data['fee_id'],
                    'amount_paid'  => $data['amount_paid'],
                    'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                    'receipt_no'   => $receiptNo,
                    'method'       => $data['payment_method'] ?? 'cash',
                    'recorded_by'  => $data['recorded_by'],
                    'month_year'   => $data['month_year'] ?? date('Y-m'),
                    'notes'        => $data['notes'] ?? null,
                ]
            );
            $paymentId = (int) $this->db->lastInsertId();

            // Link to the transactions table for finance calculation
            $this->db->query(
                "INSERT INTO transactions (type, amount, reference_type, reference_id, description, transaction_date, recorded_by)
                 VALUES ('income', :amount, 'payment', :refId, :desc, CURDATE(), :uid)",
                [
                    'amount'  => $data['amount_paid'],
                    'refId'   => $paymentId,
                    'desc'    => 'Fee Payment - ' . $receiptNo,
                    'uid'     => $data['recorded_by']
                ]
            );

            $this->db->commit();
            return $paymentId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Payment record error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find all payments (with student and fee details).
     */
    public function all(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['student_id'])) {
            $where[] = "p.student_id = :student_id";
            $params['student_id'] = $filters['student_id'];
        }
        if (!empty($filters['month_year'])) {
            $where[] = "p.month_year = :month_year";
            $params['month_year'] = $filters['month_year'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = "p.payment_date >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "p.payment_date <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->query(
            "SELECT p.*, u.full_name, s.student_id_no, f.name as fee_name, f.frequency,
                    rec.full_name as recorded_by_name
             FROM payments p
             JOIN students s ON p.student_id = s.id
             JOIN users u ON s.user_id = u.id
             JOIN fee_structures f ON p.fee_id = f.id
             LEFT JOIN users rec ON p.recorded_by = rec.id
             $whereClause
             ORDER BY p.created_at DESC",
            $params
        );
        return $stmt->fetchAll();
    }

    /**
     * Find payments by student.
     */
    public function findByStudent(int $studentId): array
    {
        $stmt = $this->db->query(
            "SELECT p.*, f.name as fee_name 
             FROM payments p 
             JOIN fee_structures f ON p.fee_id = f.id 
             WHERE p.student_id = :sid 
             ORDER BY p.payment_date DESC",
            ['sid' => $studentId]
        );
        return $stmt->fetchAll();
    }

    /**
     * Calculate total due for a student.
     * Simple logic: Sum of all active monthly fees - total paid this month.
     */
    public function calculateStudentDue(int $studentId): float
    {
        $monthYear = date('Y-m');
        
        // Get total expected monthly fees
        $stmt = $this->db->query("SELECT SUM(amount) as total FROM fee_structures WHERE is_active = TRUE AND frequency = 'monthly'");
        $expected = (float) ($stmt->fetch()['total'] ?? 0);
        
        // Get total paid for this month
        $stmt = $this->db->query(
            "SELECT SUM(amount_paid) as paid FROM payments WHERE student_id = :sid AND month_year = :my",
            ['sid' => $studentId, 'my' => $monthYear]
        );
        $paid = (float) ($stmt->fetch()['paid'] ?? 0);
        
        return max(0, $expected - $paid);
    }

    /**
     * Find payment by ID.
     */
    public function findById(int $id): array|false
    {
        $stmt = $this->db->query(
            "SELECT p.*, u.full_name, s.student_id_no, s.phone,
                    f.name as fee_name, f.amount as fee_amount, f.frequency,
                    rec.full_name as recorded_by_name
             FROM payments p
             JOIN students s ON p.student_id = s.id
             JOIN users u ON s.user_id = u.id
             JOIN fee_structures f ON p.fee_id = f.id
             LEFT JOIN users rec ON p.recorded_by = rec.id
             WHERE p.id = :id LIMIT 1",
            ['id' => $id]
        );
        return $stmt->fetch();
    }

    /**
     * Find payment by receipt number.
     */
    public function findByReceiptNo(string $receiptNo): array|false
    {
        $stmt = $this->db->query(
            "SELECT p.*, u.full_name, s.student_id_no
             FROM payments p
             JOIN students s ON p.student_id = s.id
             JOIN users u ON s.user_id = u.id
             WHERE p.receipt_no = :receipt_no LIMIT 1",
            ['receipt_no' => $receiptNo]
        );
        return $stmt->fetch();
    }

    /**
     * Generate unique receipt number: RCP-YYYYMM-XXXX
     */
    public function generateReceiptNo(): string
    {
        $prefix = RECEIPT_PREFIX . '-' . date('Ym') . '-';

        $stmt = $this->db->query(
            "SELECT receipt_no FROM payments
             WHERE receipt_no LIKE :prefix
             ORDER BY id DESC LIMIT 1",
            ['prefix' => $prefix . '%']
        );
        $last = $stmt->fetch();

        if ($last) {
            $parts = explode('-', $last['receipt_no']);
            $seq = (int) end($parts) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Monthly reconciliation report.
     */
    public function monthlyReconciliation(string $monthYear): array
    {
        $stmt = $this->db->query(
            "SELECT f.name as fee_name, f.amount as fee_amount,
                    COUNT(p.id) as payment_count,
                    SUM(p.amount_paid) as total_collected
             FROM fee_structures f
             LEFT JOIN payments p ON f.id = p.fee_id AND p.month_year = :month_year
             WHERE f.is_active = TRUE
             GROUP BY f.id, f.name, f.amount
             ORDER BY f.name",
            ['month_year' => $monthYear]
        );
        return $stmt->fetchAll();
    }

    /**
     * Get fee structures.
     */
    public function getFeeStructures(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM fee_structures WHERE is_active = TRUE ORDER BY name"
        );
        return $stmt->fetchAll();
    }

    /**
     * Get total outstanding (students who haven't paid for current month).
     */
    public function outstandingCount(string $monthYear = ''): int
    {
        if (empty($monthYear)) $monthYear = date('Y-m');

        $stmt = $this->db->query(
            "SELECT COUNT(DISTINCT s.id) as total
             FROM students s
             JOIN users u ON s.user_id = u.id
             WHERE u.status = 'active'
             AND s.id NOT IN (
                 SELECT DISTINCT student_id FROM payments WHERE month_year = :my
             )",
            ['my' => $monthYear]
        );
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Get total revenue for a period.
     */
    public function totalRevenue(?string $from = null, ?string $to = null): float
    {
        $where = '';
        $params = [];

        if ($from) {
            $where .= " AND payment_date >= :from_date";
            $params['from_date'] = $from;
        }
        if ($to) {
            $where .= " AND payment_date <= :to_date";
            $params['to_date'] = $to;
        }

        $stmt = $this->db->query(
            "SELECT COALESCE(SUM(amount_paid), 0) as total FROM payments WHERE 1=1 $where",
            $params
        );
        return (float) $stmt->fetch()['total'];
    }
}
