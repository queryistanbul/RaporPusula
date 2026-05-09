<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/functions.php';

require_role(['admin', 'analyst', 'viewer']);

require_once __DIR__ . '/includes/header.php';

$user = Auth::user();
$db = getAuthDB();

// Admin ise hepsini, değilse sadece kendi geçmişini gör
if ($user['role'] === 'admin') {
    $sql = "SELECT h.*, u.display_name FROM chat_history h
JOIN auth_users u ON h.user_id = u.id
ORDER BY h.created_at DESC";
    $params = [];
} else {
    $sql = "SELECT h.*, 'Siz' as display_name FROM chat_history h
WHERE h.user_id = ?
ORDER BY h.created_at DESC";
    $params = [$user['id']];
}

$history = $db->fetchAll($sql, $params);
?>
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header animate-in">
        <h1>🕒 Sohbet Geçmişi</h1>
        <p>Geçmiş sorgularınızı ve AI yanıtlarını inceleyin.</p>
    </div>

    <div class="glass-card">
        <?php if (empty($history)): ?>
            <p class="text-muted">Henüz bir sohbet geçmişiniz bulunmuyor.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th style="width: 150px;">Tarih</th>
                            <?php if ($user['role'] === 'admin'): ?>
                                <th style="width: 150px;">Kullanıcı</th>
                            <?php endif; ?>
                            <th>Soru</th>
                            <th>Yanıt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $row): ?>
                            <tr>
                                <td class="text-muted" style="font-size: 0.85rem;">
                                    <?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?>
                                </td>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <td style="font-weight: 600;">
                                        <?php echo clean($row['display_name']); ?>
                                    </td>
                                <?php endif; ?>
                                <td style="max-width: 300px;">
                                    <div style="font-weight: 500; font-size: 0.95rem;">
                                        <?php echo clean($row['prompt']); ?>
                                    </div>
                                    <?php if ($row['sql_query']): ?>
                                        <details style="margin-top: 5px;">
                                            <summary class="sql-summary">SQL Sorgusunu Gör</summary>
                                            <pre class="sql-pre"><?php echo clean($row['sql_query']); ?></pre>
                                        </details>
                                    <?php endif; ?>
                                </td>
                                <td class="response-text" style="font-size: 0.9rem;">
                                    <?php echo nl2br(clean($row['response'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>