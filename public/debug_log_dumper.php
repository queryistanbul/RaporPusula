<?php
// public/debug_log_dumper.php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';

try {
    $db = getAuthDB();
    $logs = $db->fetchAll("SELECT * FROM ai_process_logs ORDER BY created_at DESC LIMIT 20");

    $output = "Total logs found: " . count($logs) . "\n\n";
    foreach ($logs as $log) {
        $output .= "ID: " . $log['id'] . "\n";
        $output .= "Request ID: " . $log['request_id'] . "\n";
        $output .= "User ID: " . $log['user_id'] . "\n";
        $output .= "Step: " . $log['step_name'] . "\n";
        $output .= "Time: " . $log['created_at'] . "\n";
        $output .= "Content Length: " . strlen($log['content']) . "\n";
        $output .= "Content Sample: " . substr($log['content'], 0, 100) . "...\n";
        $output .= "Full Content (No HTML):\n" . $log['content'] . "\n";
        $output .= "----------------------------------------\n";
    }

    file_put_contents(__DIR__ . '/debug_log_dump.txt', $output);
    echo "Logs dumped to debug_log_dump.txt";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>