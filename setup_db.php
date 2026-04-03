<?php
require_once __DIR__ . '/config/database.php';
$db = Database::getInstance();
$sql = file_get_contents(__DIR__ . '/database/hostelease.sql');
try {
    $db->getConnection()->exec($sql);
    echo "Schema imported successfully.\n";
} catch (Exception $e) {
    echo "Error importing schema: " . $e->getMessage() . "\n";
}
