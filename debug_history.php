<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';

try {
    $db = getAuthDB();
    echo "Connected to: " . AUTH_DB_NAME . "\n";

    $res = $db->fetchAll("DESCRIBE chat_history");
    echo "Table 'chat_history' columns:\n";
    print_r($res);

    $count = $db->fetchColumn("SELECT COUNT(*) FROM chat_history");
    echo "Total rows: $count\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
