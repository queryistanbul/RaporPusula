<?php
// src/setup_logs.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

try {
    $db = getAuthDB();

    // Create ai_process_logs table
    $sql = "CREATE TABLE IF NOT EXISTS ai_process_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id VARCHAR(50) NOT NULL,
        user_id INT NOT NULL,
        step_name VARCHAR(100) NOT NULL,
        content LONGTEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_request (request_id),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->query($sql);

    echo "ai_process_logs tablosu başarıyla oluşturuldu.\n";
} catch (Exception $e) {
    die("Hata: " . $e->getMessage() . "\n");
}
?>