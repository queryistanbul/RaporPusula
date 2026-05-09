<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';

try {
    $db = getAuthDB();
    $sql = "CREATE TABLE IF NOT EXISTS chat_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        prompt TEXT NOT NULL,
        sql_query TEXT,
        response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->query($sql);
    echo "Chat history table created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
