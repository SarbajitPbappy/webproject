<?php
/**
 * HostelEase — Billing charges, fee categories, room entitlement, waitlist room type.
 *
 * Run once from CLI:
 *   php database/migrations/migrate_billing_entitlement.php
 *
 * Idempotent: skips columns/tables that already exist.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

function columnExists(Database $db, string $table, string $column): bool
{
    $stmt = $db->query(
        "SELECT COUNT(*) AS c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c",
        ['t' => $table, 'c' => $column]
    );
    return (int) $stmt->fetch()['c'] > 0;
}

function tableExists(Database $db, string $table): bool
{
    $stmt = $db->query(
        "SELECT COUNT(*) AS c FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t",
        ['t' => $table]
    );
    return (int) $stmt->fetch()['c'] > 0;
}

try {
    $db = Database::getInstance();
    echo "migrate_billing_entitlement: starting...\n";

    if (!columnExists($db, 'fee_structures', 'fee_category')) {
        $db->query("
            ALTER TABLE fee_structures
            ADD COLUMN fee_category ENUM(
                'room_rent','security_deposit','meal','utility','service','other'
            ) NOT NULL DEFAULT 'other' AFTER is_active
        ");
        echo "  + fee_structures.fee_category\n";
    }
    if (!columnExists($db, 'fee_structures', 'maps_room_type')) {
        $db->query("
            ALTER TABLE fee_structures
            ADD COLUMN maps_room_type ENUM('single','double','triple','dormitory') NULL DEFAULT NULL
                COMMENT 'For room_rent fees: which room tier this payment unlocks' AFTER fee_category
        ");
        echo "  + fee_structures.maps_room_type\n";
    }

    if (!columnExists($db, 'students', 'entitled_room_type')) {
        $db->query("
            ALTER TABLE students
            ADD COLUMN entitled_room_type ENUM('single','double','triple','dormitory') NULL DEFAULT NULL
                COMMENT 'Room tier paid for; allocation must match' AFTER checkout_date
        ");
        echo "  + students.entitled_room_type\n";
    }

    if (!columnExists($db, 'waitlist', 'preferred_room_type')) {
        $db->query("
            ALTER TABLE waitlist
            ADD COLUMN preferred_room_type ENUM('single','double','triple','dormitory') NULL DEFAULT NULL
                AFTER student_id
        ");
        echo "  + waitlist.preferred_room_type\n";
    }

    if (!columnExists($db, 'payments', 'billing_charge_id')) {
        $db->query("
            ALTER TABLE payments
            ADD COLUMN billing_charge_id INT UNSIGNED NULL DEFAULT NULL AFTER notes,
            ADD INDEX idx_payments_billing_charge (billing_charge_id)
        ");
        echo "  + payments.billing_charge_id\n";
    }

    if (!tableExists($db, 'billing_charges')) {
        $db->query("
            CREATE TABLE billing_charges (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id INT UNSIGNED NOT NULL,
                fee_id INT UNSIGNED NOT NULL,
                period_month VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
                amount_due DECIMAL(10,2) NOT NULL,
                status ENUM('pending','paid','waived') NOT NULL DEFAULT 'pending',
                issued_by INT UNSIGNED NULL,
                issued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                payment_id INT UNSIGNED NULL COMMENT 'Set after payment row exists (no FK to avoid insert cycles)',
                notes TEXT NULL,
                UNIQUE KEY uk_bill_student_fee_period (student_id, fee_id, period_month),
                INDEX idx_bill_student_status (student_id, status),
                INDEX idx_bill_period (period_month),
                INDEX idx_bill_payment (payment_id),
                CONSTRAINT fk_bill_student FOREIGN KEY (student_id)
                    REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_bill_fee FOREIGN KEY (fee_id)
                    REFERENCES fee_structures(id) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT fk_bill_issuer FOREIGN KEY (issued_by)
                    REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "  + billing_charges table\n";
    }

    // Back-fill categories from legacy names (best-effort)
    $db->query("
        UPDATE fee_structures SET fee_category = 'security_deposit', maps_room_type = NULL
        WHERE LOWER(name) LIKE '%security%' OR LOWER(name) LIKE '%deposit%'
    ");
    $db->query("
        UPDATE fee_structures SET fee_category = 'meal', maps_room_type = NULL
        WHERE LOWER(name) LIKE '%dining%' OR LOWER(name) LIKE '%meal%'
    ");
    $db->query("
        UPDATE fee_structures SET fee_category = 'utility', maps_room_type = NULL
        WHERE LOWER(name) LIKE '%utility%'
    ");
    $db->query("
        UPDATE fee_structures SET fee_category = 'service', maps_room_type = NULL
        WHERE LOWER(name) LIKE '%laundry%'
    ");
    $db->query("
        UPDATE fee_structures SET fee_category = 'room_rent', maps_room_type = 'single'
        WHERE LOWER(name) LIKE '%single%' AND (fee_category = 'other' OR fee_category IS NULL)
    ");
    $db->query("
        UPDATE fee_structures SET fee_category = 'room_rent', maps_room_type = 'double'
        WHERE LOWER(name) LIKE '%double%' AND fee_category = 'other'
    ");
    $db->query("
        UPDATE fee_structures SET fee_category = 'room_rent', maps_room_type = 'triple'
        WHERE LOWER(name) LIKE '%triple%' AND fee_category = 'other'
    ");
    $db->query("
        UPDATE fee_structures SET fee_category = 'room_rent', maps_room_type = 'dormitory'
        WHERE LOWER(name) LIKE '%dorm%' AND fee_category = 'other'
    ");
    $db->query("
        UPDATE fee_structures SET fee_category = 'room_rent', maps_room_type = 'single'
        WHERE LOWER(name) LIKE '%rent%' AND LOWER(name) NOT LIKE '%double%'
          AND LOWER(name) NOT LIKE '%triple%' AND LOWER(name) NOT LIKE '%dorm%'
          AND fee_category = 'other'
    ");
    $db->query("
        UPDATE fee_structures SET fee_category = 'other', maps_room_type = NULL
        WHERE LOWER(name) LIKE '%annual%' OR LOWER(name) LIKE '%maintenance%'
    ");

    echo "migrate_billing_entitlement: done.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "migrate_billing_entitlement failed: " . $e->getMessage() . "\n");
    exit(1);
}
