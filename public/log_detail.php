<?php
// public/log_detail.php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/functions.php';

require_role(['admin', 'analyst', 'viewer']);

require_once __DIR__ . '/includes/header.php';

$requestId = $_GET['request_id'] ?? '';
$db = getAuthDB();

$steps = $db->fetchAll("
    SELECT * FROM ai_process_logs 
    WHERE request_id = ? 
    ORDER BY created_at ASC
", [$requestId]);

$chatInfo = $db->fetch("SELECT prompt FROM chat_history WHERE request_id = ?", [$requestId]);
?>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header animate-in">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="logs.php" class="btn btn-secondary" style="padding: 0.5rem 1rem;">← Geri</a>
            <div>
                <h1>🔍 Log Detay Raporu</h1>
                <p>
                    <?php echo $chatInfo ? '"' . clean($chatInfo['prompt']) . '"' : $requestId; ?> - İşlem Aşamaları
                </p>
            </div>
        </div>
    </div>

    <div class="timeline animate-in">
        <?php if (empty($steps)): ?>
            <div class="glass-card" style="text-align: center; padding: 4rem;">
                <p>Bu istek için detaylı log kaydı bulunamadı.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($steps as $index => $step): ?>
            <div class="timeline-item" style="margin-bottom: 2rem; position: relative; padding-left: 40px;">
                <!-- Timeline Line -->
                <?php if ($index < count($steps) - 1): ?>
                    <div
                        style="position: absolute; left: 19px; top: 30px; bottom: -30px; width: 2px; background: rgba(255,107,0,0.2);">
                    </div>
                <?php endif; ?>

                <!-- Timeline Dot -->
                <div
                    style="position: absolute; left: 10px; top: 5px; width: 20px; height: 20px; border-radius: 50%; background: #FF6B00; border: 4px solid rgba(255,107,0,0.2); z-index: 1;">
                </div>

                <div class="glass-card" style="padding: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <h3 style="margin:0; color: #FF6B00; font-size: 1.1rem;">
                            <?php echo clean($step['step_name']); ?>
                        </h3>
                        <span style="font-size: 0.8rem; color: #64748B;">
                            <?php echo date('H:i:s.u', strtotime($step['created_at'])); ?>
                        </span>
                    </div>

                    <div class="log-content">
                        <?php
                        $content = $step['content'];
                        $json = json_decode($content, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $output = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            echo '<pre style="margin:0;">' . htmlspecialchars($output) . '</pre>';
                        } else {
                            // JSON değilse ham metni güvenli şekilde bas
                            echo nl2br(htmlspecialchars($content));
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .timeline-item {
        transition: all 0.3s ease;
    }

    .timeline-item:hover {
        transform: translateX(5px);
    }

    pre {
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    .log-content {
        background: rgba(0, 0, 0, 0.3);
        padding: 1rem;
        border-radius: 8px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.9rem;
        color: #CBD5E1;
        overflow-x: auto;
    }

    body.light-mode .log-content {
        background: rgba(15, 23, 42, 0.05); /* Light gray background */
        color: #1E293B; /* Dark text */
        border: 1px solid rgba(148, 163, 184, 0.3);
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>