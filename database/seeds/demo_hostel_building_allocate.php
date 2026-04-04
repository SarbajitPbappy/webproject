<?php
/**
 * Demo: 10-floor building (DEMO-* rooms), allocate 100 hallportal students, optional sample bills + notifications.
 *
 * Prerequisites: import hostelease.sql, run bangladesh_demo_seed.php, run migrate_user_notifications.php if needed.
 *
 *   php database/seeds/demo_hostel_building_allocate.php
 *   php database/seeds/demo_hostel_building_allocate.php --force
 *   php database/seeds/demo_hostel_building_allocate.php --force --with-billing
 */

define('APP_ROOT', dirname(__DIR__, 2));

require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/app/models/Room.php';
require_once APP_ROOT . '/app/models/Allocation.php';
require_once APP_ROOT . '/app/models/BillingCharge.php';
require_once APP_ROOT . '/app/models/UserNotification.php';

$argv = $_SERVER['argv'] ?? [];
$force = in_array('--force', $argv, true);
$withBilling = in_array('--with-billing', $argv, true);

function tableExists(Database $db, string $name): bool
{
    $stmt = $db->query(
        "SELECT COUNT(*) AS c FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t",
        ['t' => $name]
    );

    return (int) $stmt->fetch()['c'] > 0;
}

try {
    $db = Database::getInstance();
    if (!tableExists($db, 'rooms') || !tableExists($db, 'students') || !tableExists($db, 'allocations')) {
        fwrite(STDERR, "Missing tables. Import database/hostelease.sql first.\n");
        exit(1);
    }

    $check = $db->query(
        "SELECT COUNT(*) AS c FROM rooms WHERE room_number LIKE 'DEMO-%' LIMIT 1"
    )->fetch();
    if ((int) ($check['c'] ?? 0) > 0 && !$force) {
        echo "DEMO rooms already exist. Use --force to rebuild allocations and rooms.\n";
        exit(0);
    }

    $demoStudentIds = $db->query(
        "SELECT s.id FROM students s
         JOIN users u ON s.user_id = u.id
         WHERE u.email LIKE 'stu%hall@hallportal.demo.bd'
         ORDER BY s.id ASC"
    )->fetchAll(PDO::FETCH_COLUMN);

    if (count($demoStudentIds) < 100) {
        fwrite(STDERR, 'Need 100 demo students (stu*.hall@hallportal.demo.bd). Run database/seeds/bangladesh_demo_seed.php first.' . "\n");
        exit(1);
    }

    $demoStudentIds = array_map('intval', array_slice($demoStudentIds, 0, 100));

    $allocator = $db->query(
        "SELECT id FROM users WHERE role IN ('admin','super_admin') AND status = 'active' ORDER BY id ASC LIMIT 1"
    )->fetch();
    $allocatedBy = (int) ($allocator['id'] ?? 0);
    if ($allocatedBy <= 0) {
        fwrite(STDERR, "No admin user found to set as allocated_by.\n");
        exit(1);
    }

    $roomModel = new Room();
    $allocModel = new Allocation();

    $db->beginTransaction();
    try {
        if ($force) {
            $db->query(
                "DELETE a FROM allocations a
                 JOIN students s ON a.student_id = s.id
                 JOIN users u ON s.user_id = u.id
                 WHERE u.email LIKE 'stu%hall@hallportal.demo.bd'"
            );
            $db->query("DELETE FROM rooms WHERE room_number LIKE 'DEMO-%'");
        }

        /** @var list<array{num:string,floor:int,type:string,capacity:int}> $blueprint */
        $blueprint = [];
        for ($floor = 1; $floor <= 10; $floor++) {
            $f = str_pad((string) $floor, 2, '0', STR_PAD_LEFT);
            $blueprint[] = ['num' => "DEMO-{$f}-S", 'floor' => $floor, 'type' => 'single', 'capacity' => 1];
            $blueprint[] = ['num' => "DEMO-{$f}-D1", 'floor' => $floor, 'type' => 'double', 'capacity' => 2];
            $blueprint[] = ['num' => "DEMO-{$f}-D2", 'floor' => $floor, 'type' => 'double', 'capacity' => 2];
            $blueprint[] = ['num' => "DEMO-{$f}-T1", 'floor' => $floor, 'type' => 'triple', 'capacity' => 3];
            $blueprint[] = ['num' => "DEMO-{$f}-T2", 'floor' => $floor, 'type' => 'triple', 'capacity' => 3];
            $blueprint[] = ['num' => "DEMO-{$f}-DO", 'floor' => $floor, 'type' => 'dormitory', 'capacity' => 6];
        }

        $roomSlots = [];
        foreach ($blueprint as $spec) {
            $rid = $roomModel->create([
                'room_number' => $spec['num'],
                'floor'       => $spec['floor'],
                'type'        => $spec['type'],
                'capacity'    => $spec['capacity'],
                'facilities'  => 'Demo tower — floor ' . $spec['floor'] . ' (' . $spec['type'] . ')',
                'status'      => 'available',
            ]);
            $roomSlots[] = [
                'id'       => $rid,
                'type'     => $spec['type'],
                'capacity' => $spec['capacity'],
                'filled'   => 0,
            ];
        }

        $si = 0;
        foreach ($roomSlots as &$slot) {
            while ($slot['filled'] < $slot['capacity'] && $si < count($demoStudentIds)) {
                $sid = $demoStudentIds[$si++];
                $db->query(
                    'UPDATE students SET entitled_room_type = :t WHERE id = :id',
                    ['t' => $slot['type'], 'id' => $sid]
                );
                $allocModel->allocate([
                    'student_id'   => $sid,
                    'room_id'      => $slot['id'],
                    'allocated_by' => $allocatedBy,
                    'start_date'   => date('Y-m-d'),
                    'notes'        => 'Demo seed — DEMO tower',
                ]);
                $slot['filled']++;
                $roomModel->refreshStatus($slot['id']);
            }
        }
        unset($slot);

        if ($si < count($demoStudentIds)) {
            throw new RuntimeException('Not enough DEMO bed capacity; logic error.');
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    echo "✅ Created 60 DEMO rooms (10 floors × 6) and allocated 100 students.\n";

    if ($withBilling) {
        $periodMonth = date('Y-m');
        $feeRows = $db->query(
            "SELECT id FROM fee_structures
             WHERE is_active = 1 AND fee_category IN ('meal','utility')
             ORDER BY id ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
        $feeIds = array_map(static fn ($r) => (int) $r['id'], $feeRows);
        if (empty($feeIds)) {
            echo "⚠️  No active meal/utility fees in fee_structures; skipped --with-billing.\n";
        } else {
            $billing = new BillingCharge();
            $result = $billing->issueBulk($demoStudentIds, $periodMonth, $feeIds, $allocatedBy);
            $n = $result['created'];
            if (!empty($result['notified_student_ids'])) {
                UserNotification::notifyBillingIssued($result['notified_student_ids'], $periodMonth);
            }
            echo "✅ Issued {$n} billing line(s) for {$periodMonth}" . (!empty($result['notified_student_ids']) ? ' and notified affected students.' : '.') . "\n";
        }
    }

    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, '❌ demo_hostel_building_allocate failed: ' . $e->getMessage() . "\n");
    exit(1);
}
