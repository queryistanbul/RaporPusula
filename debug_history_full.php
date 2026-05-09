<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/auth.php';

try {
    $db = getAuthDB();
    echo "Auth Database: " . AUTH_DB_NAME . "\n";

    // 1. Check table existence
    $check = $db->fetchAll("SHOW TABLES LIKE 'chat_history'");
    if (empty($check)) {
        die("Error: Table 'chat_history' DOES NOT EXIST.\n");
    }
    echo "Table 'chat_history' exists.\n";

    // 2. Try simple insert
    echo "Testing manual insertion...\n";
    $db->query(
        "INSERT INTO chat_history (user_id, prompt, sql_query, response) VALUES (?, ?, ?, ?)",
        [1, "Test Prompt", "SELECT 1", "Test Response"]
    );
    echo "Manual insertion successful.\n";

    // 3. Current count
    $count = $db->fetchColumn("SELECT COUNT(*) FROM chat_history");
    echo "Total rows: $count\n";

} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
