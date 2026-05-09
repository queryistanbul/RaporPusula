<?php
// public/debug_logs.php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';

header('Content-Type: application/json');

try {
    $db = getAuthDB();
    $tableExists = $db->fetchColumn("SHOW TABLES LIKE 'ai_process_logs'");

    if ($tableExists) {
        $count = $db->fetchColumn("SELECT COUNT(*) FROM ai_process_logs");
        echo json_encode([
            'success' => true,
            'message' => 'Tablo mevcut.',
            'record_count' => $count
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Hata: ai_process_logs tablosu bulunamadı. Lütfen setup_logs.php dosyasını çalıştırın veya manuel oluşturun.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Bağlantı Hatası: ' . $e->getMessage()
    ]);
}
?>