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
     * Insert one payment row, resolve billing slip, sync entitlement, ledger row.
     * Caller must already be inside a transaction (or call record() instead).
     *
     * Optional keys: billing_charge_id, skip_billing_resolve (bool).
     */
    private function insertPaymentWithLedger(array $data): int
    {
        $receiptNo = $this->generateReceiptNo();
        $billingChargeId = isset($data['billing_charge_id']) ? (int) $data['billing_charge_id'] : 0;
        if ($billingChargeId <= 0) {
            $billingChargeId = null;
        }

        $this->db->query(
            "INSERT INTO payments (student_id, fee_id, amount_paid, payment_date, receipt_no, payment_method, recorded_by, month_year, notes, billing_charge_id)
             VALUES (:student_id, :fee_id, :amount_paid, :payment_date, :receipt_no, :method, :recorded_by, :month_year, :notes, :billing_charge_id)",
            [
                'student_id'        => $data['student_id'],
                'fee_id'            => $data['fee_id'],
                'amount_paid'       => $data['amount_paid'],
                'payment_date'      => $data['payment_date'] ?? date('Y-m-d'),
                'receipt_no'        => $receiptNo,
                'method'            => $data['payment_method'] ?? 'cash',
                'recorded_by'       => $data['recorded_by'],
                'month_year'        => $data['month_year'] ?? date('Y-m'),
                'notes'             => $data['notes'] ?? null,
                'billing_charge_id' => $billingChargeId,
            ]
        );
        $paymentId = (int) $this->db->lastInsertId();

        if ($billingChargeId !== null) {
            $this->db->query(
                "UPDATE billing_charges SET status = 'paid', payment_id = :pid
                 WHERE id = :cid AND student_id = :sid AND status = 'pending'",
                ['pid' => $paymentId, 'cid' => $billingChargeId, 'sid' => $data['student_id']]
            );
        } elseif (empty($data['skip_billing_resolve'])) {
            $this->resolvePendingBillingCharge(
                $paymentId,
                (int) $data['student_id'],
                (int) $data['fee_id'],
                (string) ($data['month_year'] ?? date('Y-m'))
            );
        }

        $this->syncEntitlementFromFee((int) $data['student_id'], (int) $data['fee_id']);

        $this->db->query(
            "INSERT INTO transactions (type, amount, reference_type, reference_id, description, transaction_date, recorded_by)
             VALUES ('income', :amount, 'payment', :refId, :desc, CURDATE(), :uid)",
            [
                'amount'  => $data['amount_paid'],
                'refId'   => $paymentId,
                'desc'    => 'Fee Payment - ' . $receiptNo,
                'uid'     => $data['recorded_by'],
            ]
        );

        return $paymentId;
    }

    /**
     * Record a new payment.
     *
     * Optional keys: billing_charge_id (links warden-issued slip), skip_billing_resolve (bool).
     */
    public function record(array $data): int
    {
        try {
            $this->db->beginTransaction();
            $paymentId = $this->insertPaymentWithLedger($data);
            $this->db->commit();
            return $paymentId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Payment record error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Pay every pending warden slip for this student in one transaction (one receipt per slip).
     *
     * @return int[] New payment IDs
     */
    public function recordAllPendingBillingCharges(int $studentId, int $recordedBy): array
    {
        $stmt = $this->db->query(
            "SELECT id, student_id, fee_id, amount_due, period_month
             FROM billing_charges
             WHERE student_id = :sid AND status = 'pending'
             ORDER BY period_month ASC, id ASC",
            ['sid' => $studentId]
        );
        $charges = $stmt->fetchAll();
        if ($charges === []) {
            return [];
        }

        $this->db->beginTransaction();
        try {
            $ids = [];
            foreach ($charges as $c) {
                if ((int) $c['student_id'] !== $studentId) {
                    continue;
                }
                $amount = (float) $c['amount_due'];
                if ($amount <= 0) {
                    continue;
                }
                $ids[] = $this->insertPaymentWithLedger([
                    'student_id'         => $studentId,
                    'fee_id'             => (int) $c['fee_id'],
                    'amount_paid'        => $amount,
                    'payment_date'       => date('Y-m-d'),
                    'payment_method'     => 'online',
                    'recorded_by'        => $recordedBy,
                    'month_year'         => (string) $c['period_month'],
                    'notes'              => 'Online portal — pay all, slip #' . (int) $c['id'],
                    'billing_charge_id'  => (int) $c['id'],
                ]);
            }
            if ($ids === []) {
                $this->db->rollBack();
                return [];
            }
            $this->db->commit();
            return $ids;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Payment batch record error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Match an admin-recorded payment to a pending warden slip when not paying from the portal.
     */
    private function resolvePendingBillingCharge(
        int $paymentId,
        int $studentId,
        int $feeId,
        string $periodMonth
    ): void {
        $stmt = $this->db->query(
            "SELECT id FROM billing_charges
             WHERE student_id = :sid AND fee_id = :fid AND period_month = :pm AND status = 'pending'
             ORDER BY id ASC LIMIT 1",
            ['sid' => $studentId, 'fid' => $feeId, 'pm' => $periodMonth]
        );
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }
        $this->db->query(
            "UPDATE billing_charges SET status = 'paid', payment_id = :pid WHERE id = :id",
            ['pid' => $paymentId, 'id' => (int) $row['id']]
        );
        $this->db->query(
            "UPDATE payments SET billing_charge_id = :cid WHERE id = :pid",
            ['cid' => (int) $row['id'], 'pid' => $paymentId]
        );
    }

    /**
     * When a room-rent fee with maps_room_type is paid, lock the student's allocatable room tier.
     */
    public function syncEntitlementFromFee(int $studentId, int $feeId): void
    {
        $stmt = $this->db->query(
            "SELECT maps_room_type, fee_category FROM fee_structures WHERE id = :id LIMIT 1",
            ['id' => $feeId]
        );
        $fee = $stmt->fetch();
        if (!$fee || ($fee['fee_category'] ?? '') !== 'room_rent' || empty($fee['maps_room_type'])) {
            return;
        }
        $this->db->query(
            "UPDATE students SET entitled_room_type = :t WHERE id = :sid",
            ['t' => $fee['maps_room_type'], 'sid' => $studentId]
        );
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
     * Total ৳ pending from warden-issued billing slips (students pay only against these online).
     */
    public function calculateStudentDue(int $studentId): float
    {
        $stmt = $this->db->query(
            "SELECT COALESCE(SUM(amount_due), 0) AS s FROM billing_charges
             WHERE student_id = :sid AND status = 'pending'",
            ['sid' => $studentId]
        );
        return max(0, (float) ($stmt->fetch()['s'] ?? 0));
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
     * Total already paid toward a catalog fee for a calendar billing month (YYYY-MM).
     * Used to skip or reduce slips when students prepay before the warden issues bills.
     */
    public function totalPaidForFeeMonth(int $studentId, int $feeId, string $monthYear): float
    {
        if ($studentId <= 0 || $feeId <= 0 || !preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
            return 0.0;
        }
        $stmt = $this->db->query(
            'SELECT COALESCE(SUM(amount_paid), 0) AS s FROM payments
             WHERE student_id = :sid AND fee_id = :fid AND month_year = :my',
            ['sid' => $studentId, 'fid' => $feeId, 'my' => $monthYear]
        );

        return (float) ($stmt->fetch()['s'] ?? 0);
    }

    /**
     * Sum payments for a yearly fee (e.g. annual maintenance) within a calendar year.
     * Counts rows where month_year is YYYY-* for that year, or payment_date falls in that year
     * when month_year is missing or not in YYYY-MM form (e.g. transfer fee keys).
     */
    public function totalPaidForYearlyFeeInYear(int $studentId, int $feeId, int $year): float
    {
        if ($studentId <= 0 || $feeId <= 0 || $year < 2000 || $year > 2100) {
            return 0.0;
        }
        $likeYear = $year . '-%';
        $stmt = $this->db->query(
            "SELECT COALESCE(SUM(amount_paid), 0) AS s FROM payments
             WHERE student_id = :sid AND fee_id = :fid
             AND (
               month_year LIKE :likeYear
               OR (
                 (month_year IS NULL OR month_year = '' OR month_year NOT REGEXP '^[0-9]{{4}}-[0-9]{{2}}$')
                 AND YEAR(payment_date) = :y
               )
             )",
            ['sid' => $studentId, 'fid' => $feeId, 'likeYear' => $likeYear, 'y' => $year]
        );

        return (float) ($stmt->fetch()['s'] ?? 0);
    }

    /**
     * Get fee structures.
     */
    public function getFeeStructures(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM fee_structures WHERE is_active = TRUE ORDER BY fee_category, name"
        );
        return $stmt->fetchAll();
    }

    /**
     * Fees that can be bulk-issued for a billing month (excludes ad-hoc categories if needed).
     */
    public function getFeesForMonthlyIssue(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM fee_structures WHERE is_active = TRUE
             AND frequency IN ('monthly','yearly')
             AND fee_category IN ('room_rent','meal','utility','service','other')
             ORDER BY fee_category, name"
        );
        return $stmt->fetchAll();
    }

    /**
     * Enrollment-related fees (security deposit + room tier rent).
     */
    public function getFeesForEnrollmentIssue(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM fee_structures WHERE is_active = TRUE
             AND fee_category IN ('security_deposit','room_rent')
             ORDER BY fee_category, name"
        );
        return $stmt->fetchAll();
    }

    /**
     * Students with at least one unpaid warden-issued slip (optional filter by billing month).
     */
    public function outstandingCount(string $monthYear = ''): int
    {
        if ($monthYear !== '') {
            $stmt = $this->db->query(
                "SELECT COUNT(DISTINCT student_id) AS total FROM billing_charges
                 WHERE status = 'pending' AND period_month = :m",
                ['m' => $monthYear]
            );
        } else {
            $stmt = $this->db->query(
                "SELECT COUNT(DISTINCT student_id) AS total FROM billing_charges WHERE status = 'pending'"
            );
        }
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
