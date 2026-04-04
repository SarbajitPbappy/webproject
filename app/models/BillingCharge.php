<?php
/**
 * HostelEase — Warden-issued billing slips (monthly / enrollment charges).
 */

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/app/models/Student.php';
require_once APP_ROOT . '/app/models/Payment.php';

class BillingCharge
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function paidChargesForStudent(int $studentId, int $limit = 80): array
    {
        $lim = max(1, min(200, $limit));
        $stmt = $this->db->query(
            "SELECT c.*, f.name AS fee_name, f.fee_category
             FROM billing_charges c
             JOIN fee_structures f ON f.id = c.fee_id
             WHERE c.student_id = :sid AND c.status = 'paid'
             ORDER BY c.period_month DESC, c.id DESC
             LIMIT {$lim}",
            ['sid' => $studentId]
        );

        return $stmt->fetchAll();
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

    /** Active catalog row for room transfers (seed / migration). */
    public function getTransferFee(): ?array
    {
        $stmt = $this->db->query(
            "SELECT * FROM fee_structures WHERE name = 'Room transfer fee' AND is_active = TRUE LIMIT 1"
        );
        $r = $stmt->fetch();
        return $r ?: null;
    }

    /**
     * One-off slip with unique period key (e.g. TRF-…) for transfer fees.
     *
     * @return string|null Period key if a new row was inserted
     */
    public function issueUniqueSlip(
        int $studentId,
        int $feeId,
        float $amount,
        int $issuedBy,
        ?string $notes = null
    ): ?string {
        for ($i = 0; $i < 6; $i++) {
            $periodKey = 'TRF-' . bin2hex(random_bytes(5));
            if ($this->createIfMissing([
                'student_id'   => $studentId,
                'fee_id'       => $feeId,
                'period_month' => $periodKey,
                'amount_due'   => $amount,
                'issued_by'    => $issuedBy,
                'notes'        => $notes,
            ])) {
                return $periodKey;
            }
        }
        return null;
    }

    public function findPendingChargeId(int $studentId, int $feeId, string $periodMonth): ?int
    {
        $stmt = $this->db->query(
            "SELECT id FROM billing_charges
             WHERE student_id = :sid AND fee_id = :fid AND period_month = :pm AND status = 'pending'
             LIMIT 1",
            ['sid' => $studentId, 'fid' => $feeId, 'pm' => $periodMonth]
        );
        $r = $stmt->fetch();
        return $r ? (int) $r['id'] : null;
    }

    /**
     * Issue slips for many students. Room-rent fees that have maps_room_type are matched to:
     * - **Residents:** active allocation room type (monthly billing).
     * - **Enrollment ($useWaitlistTierWhenUnallocated):** if the student has no active room yet,
     *   use their **waitlist.preferred_room_type** (from self-registration or admin intake) so they
     *   can pay deposit + correct tier **before** allocation.
     * Meal, utility, and other fees apply to every selected student as before.
     *
     * @param int[] $studentIds
     * @param int[] $feeIds
     * @param bool  $useWaitlistTierWhenUnallocated set true for enrollment billing only
     * @return array{created:int, notified_student_ids:int[], skipped_prepaid:int, skipped_yearly_paid:int}
     */
    public function issueBulk(
        array $studentIds,
        string $periodMonth,
        array $feeIds,
        int $issuedBy,
        bool $useWaitlistTierWhenUnallocated = false
    ): array {
        $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));
        $feeIds = array_values(array_unique(array_filter(array_map('intval', $feeIds))));

        if ($studentIds === [] || $feeIds === []) {
            return [
                'created'               => 0,
                'notified_student_ids'  => [],
                'skipped_prepaid'       => 0,
                'skipped_yearly_paid'   => 0,
            ];
        }

        $pdo = $this->db->getConnection();

        $issueYear = null;
        if (preg_match('/^(\d{4})-\d{2}$/', $periodMonth, $pm)) {
            $issueYear = (int) $pm[1];
        }

        $feePlaceholders = implode(',', array_fill(0, count($feeIds), '?'));
        $feeStmt = $pdo->prepare(
            "SELECT id, amount, fee_category, maps_room_type, frequency FROM fee_structures
             WHERE id IN ($feePlaceholders) AND is_active = TRUE"
        );
        $feeStmt->execute($feeIds);
        $feesById = [];
        foreach ($feeStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $feesById[(int) $row['id']] = $row;
        }

        $stuPlaceholders = implode(',', array_fill(0, count($studentIds), '?'));
        $mapStmt = $pdo->prepare(
            "SELECT a.student_id, r.type AS room_type
             FROM allocations a
             INNER JOIN rooms r ON r.id = a.room_id
             WHERE a.status = 'active' AND a.student_id IN ($stuPlaceholders)"
        );
        $mapStmt->execute($studentIds);
        $roomTypeByStudent = [];
        foreach ($mapStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $roomTypeByStudent[(int) $row['student_id']] = (string) $row['room_type'];
        }

        $waitlistTierByStudent = [];
        if ($useWaitlistTierWhenUnallocated) {
            $wlStmt = $pdo->prepare(
                "SELECT student_id, preferred_room_type FROM waitlist
                 WHERE status = 'waiting' AND student_id IN ($stuPlaceholders)
                 ORDER BY requested_at ASC"
            );
            $wlStmt->execute($studentIds);
            foreach ($wlStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $sid = (int) $row['student_id'];
                $prt = $row['preferred_room_type'] ?? null;
                if (!isset($waitlistTierByStudent[$sid]) && $prt !== null && $prt !== '') {
                    $waitlistTierByStudent[$sid] = (string) $prt;
                }
            }
        }

        $created = 0;
        $notified = [];
        $skippedPrepaid = 0;
        $skippedYearlyPaid = 0;
        $studentModel = new Student();
        $paymentModel = new Payment();

        $ins = $pdo->prepare(
            "INSERT IGNORE INTO billing_charges
                (student_id, fee_id, period_month, amount_due, status, issued_by)
             VALUES (:student_id, :fee_id, :period_month, :amount_due, 'pending', :issued_by)"
        );

        foreach ($studentIds as $sid) {
            if ($sid <= 0) {
                continue;
            }
            foreach ($feeIds as $fid) {
                if ($fid <= 0) {
                    continue;
                }
                $fee = $feesById[$fid] ?? null;
                if (!$fee) {
                    continue;
                }

                $category = (string) ($fee['fee_category'] ?? '');
                $mapsType = $fee['maps_room_type'] ?? null;
                if ($mapsType !== null && $mapsType !== '') {
                    $mapsType = (string) $mapsType;
                } else {
                    $mapsType = null;
                }

                if ($category === 'room_rent' && $mapsType !== null) {
                    $effectiveTier = $roomTypeByStudent[$sid] ?? null;
                    if (
                        $effectiveTier === null
                        && $useWaitlistTierWhenUnallocated
                    ) {
                        $effectiveTier = $waitlistTierByStudent[$sid] ?? null;
                    }
                    if ($effectiveTier === null || $effectiveTier !== $mapsType) {
                        continue;
                    }
                }

                $rawCatalog = (float) $fee['amount'];
                $frequency = (string) ($fee['frequency'] ?? '');
                if ($frequency === 'yearly' && $issueYear !== null) {
                    $paidYear = $paymentModel->totalPaidForYearlyFeeInYear($sid, $fid, $issueYear);
                    if ($paidYear >= $rawCatalog - 0.009) {
                        $skippedYearlyPaid++;
                        continue;
                    }
                    $rawCatalog = max(0.0, $rawCatalog - $paidYear);
                }

                $rawAmount = $rawCatalog;
                if (preg_match('/^\d{4}-\d{2}$/', $periodMonth)) {
                    $prepaid = $paymentModel->totalPaidForFeeMonth($sid, $fid, $periodMonth);
                    $rawAmount = max(0.0, $rawCatalog - $prepaid);
                    if ($rawAmount <= 0.005) {
                        $skippedPrepaid++;
                        continue;
                    }
                }

                $amountDue = $rawAmount;
                $creditToApply = 0.0;
                if ($category === 'room_rent' && $mapsType !== null) {
                    $bal = $studentModel->getBillingCreditBalance($sid);
                    $creditToApply = min($bal, $rawAmount);
                    $amountDue = $rawAmount - $creditToApply;
                }

                if ($amountDue <= 0.005) {
                    if ($creditToApply > 0.005) {
                        $studentModel->deductBillingCredit($sid, $creditToApply);
                        $notified[$sid] = true;
                    }
                    continue;
                }

                $ins->execute([
                    'student_id'   => $sid,
                    'fee_id'       => $fid,
                    'period_month' => $periodMonth,
                    'amount_due'   => $amountDue,
                    'issued_by'    => $issuedBy,
                ]);
                if ($ins->rowCount() > 0) {
                    $created++;
                    $notified[$sid] = true;
                    if ($creditToApply > 0.005) {
                        $studentModel->deductBillingCredit($sid, $creditToApply);
                    }
                }
            }
        }

        return [
            'created'                => $created,
            'notified_student_ids'   => array_map('intval', array_keys($notified)),
            'skipped_prepaid'        => $skippedPrepaid,
            'skipped_yearly_paid'    => $skippedYearlyPaid,
        ];
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
