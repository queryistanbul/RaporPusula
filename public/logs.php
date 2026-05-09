<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/functions.php';

require_role(['admin', 'analyst', 'viewer']);

require_once __DIR__ . '/includes/header.php';

$user = Auth::user();
$db = getAuthDB();

$logs = $db->fetchAll("
SELECT h.*, u.display_name
FROM chat_history h
JOIN auth_users u ON h.user_id = u.id
ORDER BY h.created_at DESC
LIMIT 50
");
?>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header animate-in">
        <h1>📜 Log Kayıtları</h1>
        <p>AI ve SQL süreçlerinin tüm detaylarını ve aşamalarını inceleyin.</p>
    </div>

    <div class="glass-card animate-in">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Kullanıcı</th>
                        <th>Soru</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 2rem; color: #64748B;">Henüz kayıt
                                bulunmuyor.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td style="white-space: nowrap; font-size: 0.85rem; color: #94A3B8;">
                                <?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>
                            </td>
                            <td>
                                <div style="font-weight: 600;">
                                    <?php echo clean($log['display_name']); ?>
                                </div>
                            </td>
                            <td>
                                <div
                                    style="max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo clean($log['prompt']); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($log['request_id']): ?>
                                    <a href="log_detail.php?request_id=<?php echo $log['request_id']; ?>"
                                        class="btn btn-sm btn-primary">
                                        🔍 Detaylı Rapor
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 0.75rem; color: #64748B;">Detay yok (Eski kayıt)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>