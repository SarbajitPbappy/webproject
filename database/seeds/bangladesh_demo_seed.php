<?php
/**
 * HostelEase — Bangladesh demo data: 2 wardens, 10 staff, 100 students.
 *
 * Run from the hostelease directory:
 *   php database/seeds/bangladesh_demo_seed.php
 *   php database/seeds/bangladesh_demo_seed.php --force   # run again (may duplicate emails if not cleaned)
 *
 * Passwords (change after use):
 *   Wardens: Warden@123
 *   Staff:   Staff@123
 *   Students: Student@123
 *
 * All demo emails use @hallportal.demo.bd so you can find or delete them easily.
 */

define('APP_ROOT', dirname(__DIR__, 2));

require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/app/models/User.php';
require_once APP_ROOT . '/app/models/Student.php';

$force = in_array('--force', $_SERVER['argv'] ?? [], true);

function tableExists(Database $db, string $name): bool
{
    $stmt = $db->query(
        "SELECT COUNT(*) AS c FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t",
        ['t' => $name]
    );
    return (int) $stmt->fetch()['c'] > 0;
}

function bdMobile(): string
{
    $second = [3, 4, 5, 6, 7, 8, 9][random_int(0, 6)];
    $rest = '';
    for ($i = 0; $i < 8; $i++) {
        $rest .= (string) random_int(0, 9);
    }
    return '+8801' . $second . $rest;
}

/** @return array{0:string,1:string} [fullName, gender m|f] */
function randomStudentName(): array
{
    static $maleGiven = [
        'Md. Rafi Ahmed', 'Md. Tanvir Hossain', 'Md. Abrar Islam', 'Sadman Sakib', 'Nafis Chowdhury',
        'Imran Hassan', 'Yasir Arafat', 'Mehedi Hasan', 'Fahim Rahman', 'Arif Khan',
        'Md. Shakib Hassan', 'Liton Kumar Das', 'Mahmudul Hasan', 'Rifat Bin Karim', 'Siam Ahmed',
        'Ahnaf Tahmid', 'Zubair Ibn Rashid', 'Nayeem Siddiqui', 'Partha Sarathi Das', 'Rubel Mia',
        'Kamal Hossain', 'Jamal Uddin', 'Farhan Shakil', 'Ashikur Rahman', 'Tawsif Islam',
    ];
    static $femaleGiven = [
        'Nadia Rahman', 'Samia Akter', 'Tasnia Chowdhury', 'Farhana Begum', 'Jannatul Ferdous',
        'Sumaiya Islam', 'Ayesha Siddiqua', 'Ritu Moni Das', 'Mithila Farzana', 'Priya Saha',
        'Nabila Tasnim', 'Fariha Hossain', 'Tanisha Ahmed', 'Lamia Karim', 'Sadia Afrin',
        'Umme Habiba', 'Nusrat Jahan', 'Sharmin Akter', 'Raisa Islam', 'Maliha Chowdhury',
        'Afroza Khatun', 'Shirin Sultana', 'Rokeya Begum', 'Tahmina Akter', 'Joya Das',
    ];
    if (random_int(0, 1) === 0) {
        return [$maleGiven[array_rand($maleGiven)], 'm'];
    }
    return [$femaleGiven[array_rand($femaleGiven)], 'f'];
}

function guardianName(string $studentGender): string
{
    $fathers = [
        'Md. Abdul Karim', 'Md. Delwar Hossain', 'Md. Shahidul Islam', 'Md. Nurul Amin',
        'Md. Anwar Hossain', 'Md. Mizanur Rahman', 'Md. Kamruzzaman', 'Md. Harun-or-Rashid',
    ];
    $mothers = [
        'Mrs. Rokeya Begum', 'Mrs. Salma Khatun', 'Mrs. Fatema Begum', 'Mrs. Nasima Akter',
        'Mrs. Shahnaz Parvin', 'Mrs. Kohinoor Begum',
    ];
    return random_int(0, 1) === 0
        ? $fathers[array_rand($fathers)]
        : $mothers[array_rand($mothers)];
}

$districts = [
    'Dhaka', 'Gazipur', 'Narayanganj', 'Chattogram', 'Cumilla', 'Sylhet', 'Rajshahi',
    'Khulna', 'Barishal', 'Rangpur', 'Mymensingh', 'Jessore', 'Bogura', 'Noakhali',
];

$banks = [
    ['Sonali Bank', 'Dhanmondi Branch, Dhaka'],
    ['BRAC Bank', 'Gulshan Branch, Dhaka'],
    ['Dutch-Bangla Bank', 'Uttara Branch, Dhaka'],
    ['Islami Bank Bangladesh', 'Agrabad, Chattogram'],
    ['Pubali Bank', 'Zindabazar, Sylhet'],
];

try {
    $db = Database::getInstance();
    if (!tableExists($db, 'students') || !tableExists($db, 'users')) {
        fwrite(STDERR, "Missing core tables. Import database/hostelease.sql first.\n");
        exit(1);
    }

    if (!$force) {
        $stmt = $db->query(
            "SELECT COUNT(*) AS c FROM users WHERE email LIKE '%@hallportal.demo.bd'"
        );
        if ((int) $stmt->fetch()['c'] > 0) {
            echo "Demo data already present (emails @hallportal.demo.bd). Use --force to run anyway.\n";
            exit(0);
        }
    }

    $userModel = new User();
    $studentModel = new Student();
    $year = (int) date('Y');

    // ─── 2 Wardens (admin) ─────────────────────────────────────────
    $wardens = [
        ['full_name' => 'Md. Kamrul Hasan', 'email' => 'kamrul.hasan.warden@hallportal.demo.bd', 'phone' => bdMobile()],
        ['full_name' => 'Farzana Ahmed Chowdhury', 'email' => 'farzana.chowdhury.warden@hallportal.demo.bd', 'phone' => bdMobile()],
    ];
    foreach ($wardens as $w) {
        $uid = $userModel->create([
            'full_name' => $w['full_name'],
            'email'     => $w['email'],
            'password'  => 'Warden@123',
            'role'      => 'admin',
            'status'    => 'active',
        ]);
        $db->query(
            'UPDATE users SET phone = :p WHERE id = :id',
            ['p' => $w['phone'], 'id' => $uid]
        );
        echo "Warden: {$w['full_name']} <{$w['email']}>\n";
    }

    // ─── 10 Staff ─────────────────────────────────────────────────
    $staffNames = [
        'Md. Rashedul Islam', 'Shahana Akter', 'Md. Biplob Hossain', 'Nusrat Jahan Mim',
        'Md. Sohel Rana', 'Tania Rahman', 'Md. Jewel Ahmed', 'Kazi Omar Faruque',
        'Rumana Islam', 'Md. Asif Mahmud',
    ];
    foreach ($staffNames as $idx => $name) {
        $slug = sprintf('staff%02d', $idx + 1);
        $email = $slug . '@hallportal.demo.bd';
        $uid = $userModel->create([
            'full_name' => $name,
            'email'     => $email,
            'password'  => 'Staff@123',
            'role'      => 'staff',
            'status'    => 'active',
        ]);
        $phone = bdMobile();
        $db->query('UPDATE users SET phone = :p WHERE id = :id', ['p' => $phone, 'id' => $uid]);

        if (tableExists($db, 'staff_details')) {
            $bk = $banks[$idx % count($banks)];
            $salary = (float) random_int(14000, 22000);
            $join = date('Y-m-d', strtotime('-' . random_int(120, 900) . ' days'));
            $acct = '0' . random_int(1000000000000, 9999999999999);
            try {
                $db->query(
                    'INSERT INTO staff_details (user_id, basic_salary, join_date, bank_account, bank_name)
                     VALUES (:uid, :sal, :jd, :ac, :bn)',
                    [
                        'uid' => $uid,
                        'sal' => $salary,
                        'jd'  => $join,
                        'ac'  => $acct,
                        'bn'  => $bk[0] . ', ' . $bk[1],
                    ]
                );
            } catch (Throwable $e) {
                $db->query(
                    'INSERT INTO staff_details (user_id, basic_salary, join_date) VALUES (:uid, :sal, :jd)',
                    ['uid' => $uid, 'sal' => $salary, 'jd' => $join]
                );
            }
        }
        echo "Staff: {$name} <{$email}>\n";
    }

    // ─── 100 Students ───────────────────────────────────────────
    for ($i = 1; $i <= 100; $i++) {
        [$fullName, $g] = randomStudentName();
        $email = sprintf('stu%03d.hall@hallportal.demo.bd', $i);

        $studentIdNo = 'STU' . $year . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
        $phone = bdMobile();
        $gphone = bdMobile();
        $district = $districts[array_rand($districts)];
        $enrolled = date('Y-m-d', strtotime('-' . random_int(30, 400) . ' days'));
        $guardian = guardianName($g) . ' (' . $district . ')';

        $studentModel->create([
            'full_name'       => $fullName,
            'email'           => $email,
            'password'        => 'Student@123',
            'student_id_no'   => $studentIdNo,
            'phone'           => $phone,
            'guardian_name'   => $guardian,
            'guardian_phone'  => $gphone,
            'nid_or_card'     => null,
            'enrolled_date'   => $enrolled,
            'status'          => 'active',
        ]);

        if ($i % 25 === 0) {
            echo "Students created: {$i}/100\n";
        }
    }

    echo "\n✅ Done. 2 wardens, 10 staff, 100 students (@hallportal.demo.bd).\n";
    echo "   Warden login: Warden@123 | Staff: Staff@123 | Students: Student@123\n";
} catch (Throwable $e) {
    fwrite(STDERR, '❌ Seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}
