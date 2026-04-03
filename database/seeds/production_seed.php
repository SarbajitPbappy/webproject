<?php
/**
 * HostelEase — Production Seeding Script
 * 
 * Run via CLI: php database/seeds/production_seed.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

echo "Starting Production Seed...\n";

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // 1. Clear existing non-essential tables (optional, skip if fresh db)
    $db->query("SET FOREIGN_KEY_CHECKS = 0");
    $db->query("TRUNCATE TABLE audit_logs");
    $db->query("TRUNCATE TABLE notices");
    $db->query("TRUNCATE TABLE complaints");
    $db->query("TRUNCATE TABLE payments");
    $db->query("TRUNCATE TABLE fee_structures");
    $db->query("TRUNCATE TABLE allocations");
    $db->query("TRUNCATE TABLE waitlist");
    $db->query("TRUNCATE TABLE rooms");
    $db->query("TRUNCATE TABLE students");
    $db->query("TRUNCATE TABLE login_attempts");
    $db->query("TRUNCATE TABLE password_resets");
    $db->query("DELETE FROM users WHERE email != 'admin@hostelease.com'"); 
    $db->query("SET FOREIGN_KEY_CHECKS = 1");
    echo "Existing tables cleared (kept default admin).\n";

    // Hash defaults
    $hashStaff   = password_hash('Staff@123', PASSWORD_BCRYPT);
    $hashStudent = password_hash('Student@123', PASSWORD_BCRYPT);
    $hashAdmin   = password_hash('Admin@123', PASSWORD_BCRYPT);

    // 2. Create 1 extra Super Admin
    $db->query("INSERT INTO users (full_name, email, password_hash, role) VALUES ('System Owner', 'super@hostelease.com', :pw, 'super_admin')", [':pw' => $hashAdmin]);
    echo "Super admins created.\n";

    // 3. Create 10 Admins/Wardens
    for ($i = 1; $i <= 10; $i++) {
        $db->query("INSERT INTO users (full_name, email, password_hash, role) VALUES (:n, :e, :pw, 'admin')", [
            ':n' => "Warden $i",
            ':e' => "warden{$i}@hostelease.com",
            ':pw' => $hashAdmin,
        ]);
    }
    echo "Admins created.\n";

    // 4. Create 50 Staff Members
    $staffIds = [];
    for ($i = 1; $i <= 50; $i++) {
        $db->query("INSERT INTO users (full_name, email, password_hash, role) VALUES (:n, :e, :pw, 'staff')", [
            ':n' => "Maintenance Staff $i",
            ':e' => "staff{$i}@hostelease.com",
            ':pw' => $hashStaff,
        ]);
        $staffIds[] = $db->lastInsertId();
    }
    echo "Staff created.\n";

    // 5. Create 10 Floors * 5 Rooms = 50 Rooms
    $roomIds = [];
    for ($floor = 1; $floor <= 10; $floor++) {
        for ($r = 1; $r <= 5; $r++) {
            $roomNum = sprintf("%d%02d", $floor, $r);
            // Types: 1=single, 2=double, 3=triple, 4=dormitory(5)
            $typeRoll = rand(1, 10);
            if ($typeRoll <= 2) { $type = 'single'; $cap = 1; }
            elseif ($typeRoll <= 5) { $type = 'double'; $cap = 2; }
            elseif ($typeRoll <= 8) { $type = 'triple'; $cap = 3; }
            else { $type = 'dormitory'; $cap = 5; }

            $db->query("INSERT INTO rooms (room_number, floor, type, capacity, status) VALUES (:num, :fl, :ty, :cap, 'available')", [
                ':num' => $roomNum,
                ':fl'  => $floor,
                ':ty'  => $type,
                ':cap' => $cap
            ]);
            $roomIds[] = ['id' => $db->lastInsertId(), 'cap' => $cap, 'occ' => 0];
        }
    }
    echo "Rooms created.\n";

    // 6. Create Fee Structures
    $db->query("INSERT INTO fee_structures (name, amount, frequency, is_active) VALUES ('Monthly Rent (Single)', 5000.00, 'monthly', 1)");
    $db->query("INSERT INTO fee_structures (name, amount, frequency, is_active) VALUES ('Monthly Rent (Double)', 3500.00, 'monthly', 1)");
    $feeId1 = $db->lastInsertId();
    $db->query("INSERT INTO fee_structures (name, amount, frequency, is_active) VALUES ('Security Deposit', 10000.00, 'one_time', 1)");
    $db->query("INSERT INTO fee_structures (name, amount, frequency, is_active) VALUES ('Dining Fee', 4000.00, 'monthly', 1)");
    $db->query("INSERT INTO fee_structures (name, amount, frequency, is_active) VALUES ('Late Fine', 500.00, 'one_time', 1)");
    echo "Fee structures created.\n";

    // 7. Create 100 Students and allocate to rooms randomly
    $studentIds = [];
    for ($i = 1; $i <= 100; $i++) {
        // Create user
        $email = $i === 1 ? "student@hostelease.com" : "student{$i}@hostelease.com";
        $db->query("INSERT INTO users (full_name, email, password_hash, role) VALUES (:n, :e, :pw, 'student')", [
            ':n' => "Student Name $i",
            ':e' => $email,
            ':pw' => $hashStudent,
        ]);
        $uid = $db->lastInsertId();

        // Create student profile
        $db->query("INSERT INTO students (user_id, student_id_no, phone, enrolled_date) VALUES (:uid, :sid, :ph, :ed)", [
            ':uid' => $uid,
            ':sid' => "STU" . date('Y') . sprintf("%04d", $i),
            ':ph'  => "+8801700" . sprintf("%04d", $i),
            ':ed'  => date('Y-m-d', strtotime('-' . rand(10, 300) . ' days')),
        ]);
        $sid = $db->lastInsertId();
        $studentIds[] = $sid;

        // Try allocating to an available room
        $allocated = false;
        shuffle($roomIds);
        foreach ($roomIds as &$r) {
            if ($r['occ'] < $r['cap']) {
                $db->query("INSERT INTO allocations (student_id, room_id, start_date, status) VALUES (:sid, :rid, :sd, 'active')", [
                    ':sid' => $sid,
                    ':rid' => $r['id'],
                    ':sd'  => date('Y-m-d', strtotime('-' . rand(1, 100) . ' days'))
                ]);
                $r['occ']++;
                $db->query("UPDATE rooms SET status = :st WHERE id = :rid", [
                    ':st'  => ($r['occ'] == $r['cap']) ? 'full' : 'available',
                    ':rid' => $r['id']
                ]);
                $allocated = true;
                break;
            }
        }
    }
    echo "100 Students created and allocated.\n";

    // 8. Create Payments
    for ($i = 0; $i < 80; $i++) {
        $sid = $studentIds[array_rand($studentIds)];
        $methods = ['cash', 'bank', 'online'];
        $db->query("INSERT INTO payments (student_id, fee_id, amount_paid, payment_method, receipt_no, payment_date, month_year) VALUES (:sid, :fid, :amt, :meth, :ref, :pd, :my)", [
            ':sid'  => $sid,
            ':fid'  => $feeId1,
            ':amt'  => 3500.00,
            ':meth' => $methods[rand(0, 2)],
            ':ref'  => "REF" . strtoupper(uniqid()),
            ':pd'   => date('Y-m-d', strtotime('-' . rand(1, 30) . ' days')),
            ':my'   => date('Y-m')
        ]);
    }
    echo "Payments created.\n";

    // 9. Create Complaints
    $statuses = ['open', 'in_progress', 'resolved', 'closed'];
    $categories = ['Electrical', 'Plumbing', 'Cleaning', 'Internet', 'Other'];
    for ($i = 0; $i < 30; $i++) {
        $sid = $studentIds[array_rand($studentIds)];
        $staffId = (rand(1, 10) > 3) ? $staffIds[array_rand($staffIds)] : null; // 70% assigned
        $db->query("INSERT INTO complaints (student_id, category, description, status, priority, assigned_to) VALUES (:sid, :cat, :desc, :st, :pr, :ast)", [
            ':sid'  => $sid,
            ':cat'  => $categories[array_rand($categories)],
            ':desc' => "This is a sample complaint issue reported by student.",
            ':st'   => $statuses[array_rand($statuses)],
            ':pr'   => ['low', 'medium', 'high'][rand(0, 2)],
            ':ast'  => $staffId
        ]);
    }
    echo "Complaints created.\n";

    // 10. Notices
    for ($i = 1; $i <= 10; $i++) {
        $db->query("INSERT INTO notices (title, body, posted_by, is_pinned) VALUES (:t, :b, 1, 0)", [
            ':t' => "Important Notice #" . $i,
            ':b' => "This is an important system update or facility notice generated during seeding."
        ]);
    }
    echo "Notices created.\n";

    $db->commit();
    echo "\n✔ SUCCESS! Production database seeded completely.\n";

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}
