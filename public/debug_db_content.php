<?php
// public/debug_db_content.php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';

header('Content-Type: application/json');

try {
    $db = getAuthDB();
    $logs = $db->fetchAll("SELECT * FROM ai_process_logs ORDER BY created_at DESC LIMIT 5");

    echo json_encode([
        'success' => true,
        'count' => count($logs),
        'logs' => $logs
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>