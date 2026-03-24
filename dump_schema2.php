<?php
$env = parse_ini_file(__DIR__ . '/../fleetriteAPI/.env');
$db = new PDO('mysql:host='.$env['DB_HOST'].';dbname='.$env['DB_DATABASE'], $env['DB_USERNAME'], $env['DB_PASSWORD']);

echo "=== gs_user_objects ===\n";
$stmt = $db->query("SHOW COLUMNS FROM gs_user_objects");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== gs_object_groups ===\n";
$stmt = $db->query("SHOW COLUMNS FROM gs_object_groups");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== gs_user_object_groups ===\n";
try {
    $stmt = $db->query("SHOW COLUMNS FROM gs_user_object_groups");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Table does not exist\n";
}
