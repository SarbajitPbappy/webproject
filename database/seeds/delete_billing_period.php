<?php
/**
 * Remove billing slips for a given YYYY-MM so wardens can re-issue after rule changes.
 *
 * Default: deletes only status = pending (safe). Paid slips are left alone unless --force-paid.
 *
 *   php database/seeds/delete_billing_period.php 2026-04
 *   php database/seeds/delete_billing_period.php 2026-04 --force-paid   # also removes paid (clears payment link)
 */

define('APP_ROOT', dirname(__DIR__, 2));

require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';

$argv = $_SERVER['argv'] ?? [];
$period = $argv[1] ?? '';
if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
    fwrite(STDERR, "Usage: php database/seeds/delete_billing_period.php YYYY-MM [--force-paid]\n");
    exit(1);
}

$forcePaid = in_array('--force-paid', $argv, true);

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    if ($forcePaid) {
        $sel = $pdo->prepare(
            'SELECT id FROM billing_charges WHERE period_month = :p AND status = :st'
        );
        $sel->execute(['p' => $period, 'st' => 'paid']);
        $ids = $sel->fetchAll(PDO::FETCH_COLUMN);
        if ($ids !== []) {
            $in = implode(',', array_map('intval', $ids));
            $pdo->exec("UPDATE payments SET billing_charge_id = NULL WHERE billing_charge_id IN ($in)");
        }
        $del = $pdo->prepare('DELETE FROM billing_charges WHERE period_month = :p');
        $del->execute(['p' => $period]);
        $n = $del->rowCount();
        echo "Deleted {$n} billing row(s) for {$period} (including paid; payment rows kept, links cleared).\n";
    } else {
        $del = $pdo->prepare(
            "DELETE FROM billing_charges WHERE period_month = :p AND status = 'pending'"
        );
        $del->execute(['p' => $period]);
        $n = $del->rowCount();
        echo "Deleted {$n} pending billing slip(s) for {$period}.\n";
        echo "Paid or waived slips for that month were not changed. Re-issue from Billing → Issue monthly bills.\n";
    }

    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
