<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';

try {
    $db = getAuthDB();

    // Drop the problematic table if it exists
    $db->query("DROP TABLE IF EXISTS chat_history");
    echo "Old table dropped.\n";

    // Create new table with correct schema
    $sql = "CREATE TABLE chat_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        prompt TEXT NOT NULL,
        sql_query TEXT,
        response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->query($sql);
    echo "New 'chat_history' table created successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
