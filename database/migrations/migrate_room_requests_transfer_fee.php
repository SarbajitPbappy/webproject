<?php
/**
 * Room service requests (change / cancellation), flexible billing period keys for transfer fees.
 *
 *   php database/migrations/migrate_room_requests_transfer_fee.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

function columnInfo(Database $db, string $table, string $column): ?array
{
    $stmt = $db->query(
        "SELECT COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c",
        ['t' => $table, 'c' => $column]
    );
    $r = $stmt->fetch();
    return $r ?: null;
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

    $info = columnInfo($db, 'billing_charges', 'period_month');
    if ($info && (int) ($info['CHARACTER_MAXIMUM_LENGTH'] ?? 0) < 40) {
        $db->query('ALTER TABLE billing_charges MODIFY period_month VARCHAR(40) NOT NULL COMMENT \'YYYY-MM or TRF-* unique key\'');
        echo "billing_charges.period_month: widened to VARCHAR(40).\n";
    } else {
        echo "billing_charges.period_month: already wide enough or missing.\n";
    }

    $pInfo = columnInfo($db, 'payments', 'month_year');
    if ($pInfo && (int) ($pInfo['CHARACTER_MAXIMUM_LENGTH'] ?? 0) < 40) {
        $db->query('ALTER TABLE payments MODIFY month_year VARCHAR(40) DEFAULT NULL');
        echo "payments.month_year: widened to VARCHAR(40).\n";
    } else {
        echo "payments.month_year: already wide enough or missing.\n";
    }

    if (!tableExists($db, 'room_service_requests')) {
        $db->query("
            CREATE TABLE `room_service_requests` (
              `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              `student_id` INT UNSIGNED NOT NULL,
              `request_type` ENUM('room_change','room_cancellation') NOT NULL,
              `status` ENUM('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
              `preferred_room_type` ENUM('single','double','triple','dormitory') DEFAULT NULL,
              `message` TEXT DEFAULT NULL,
              `admin_notes` TEXT DEFAULT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `processed_at` TIMESTAMP NULL DEFAULT NULL,
              `processed_by` INT UNSIGNED DEFAULT NULL,
              INDEX `idx_rsr_student` (`student_id`),
              INDEX `idx_rsr_status` (`status`),
              CONSTRAINT `fk_rsr_student` FOREIGN KEY (`student_id`)
                REFERENCES `students`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk_rsr_processor` FOREIGN KEY (`processed_by`)
                REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "room_service_requests: created.\n";
    } else {
        echo "room_service_requests: already exists.\n";
    }

    $stmt = $db->query(
        "SELECT COUNT(*) AS c FROM fee_structures WHERE name = 'Room transfer fee' LIMIT 1"
    );
    if ((int) $stmt->fetch()['c'] === 0) {
        $db->query("
            INSERT INTO fee_structures (name, amount, frequency, is_active, fee_category, maps_room_type)
            VALUES ('Room transfer fee', 1500.00, 'one_time', TRUE, 'service', NULL)
        ");
        echo "fee_structures: added Room transfer fee.\n";
    } else {
        echo "fee_structures: Room transfer fee already present.\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'migrate_room_requests_transfer_fee failed: ' . $e->getMessage() . "\n");
    exit(1);
}
