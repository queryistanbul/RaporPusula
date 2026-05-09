<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/functions.php';

require_role(['admin', 'analyst']);

require_once __DIR__ . '/includes/header.php';

$db = getAuthDB();
$success = '';
$error = '';

$user = Auth::user();

// Atama Kaydetme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['viewer_id'])) {
    $viewerId = (int) $_POST['viewer_id'];
    $selectedConns = $_POST['connections'] ?? [];

    try {
        // Önce mevcudu sil
        $db->query("DELETE FROM viewer_assignments WHERE viewer_user_id = ?", [$viewerId]);

        // Yeni atamaları yap
        foreach ($selectedConns as $connId) {
            $db->query(
                "INSERT INTO viewer_assignments (viewer_user_id, connection_id, assigned_by) VALUES (?, ?, ?)",
                [$viewerId, (int) $connId, $user['id']]
            );
        }
        $success = "Erişim yetkileri başarıyla güncellendi.";
    } catch (Exception $e) {
        $error = "Hata oluştu: " . $e->getMessage();
    }
}

// Veriler
$viewers = $db->fetchAll("SELECT id, username, display_name FROM auth_users WHERE role = 'viewer' ORDER BY
display_name");
$connections = $db->fetchAll("SELECT id, connection_name, db_type FROM auth_user_connections ORDER BY connection_name");
$assignments = $db->fetchAll("SELECT viewer_user_id, connection_id FROM viewer_assignments");

$assignedMap = [];
foreach ($assignments as $a) {
    if (!isset($assignedMap[$a['viewer_user_id']])) {
        $assignedMap[$a['viewer_user_id']] = [];
    }
    $assignedMap[$a['viewer_user_id']][] = $a['connection_id'];
}
?>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header animate-in">
        <h1>👥 Viewer Veritabanı Atamaları</h1>
        <p>Görüntüleyici kullanıcıların hangi veritabanlarına erişebileceğini tek bir panelden yönetin.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success animate-in"
            style="background: rgba(34, 197, 94, 0.1); color: #4ADE80; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid rgba(34, 197, 94, 0.2); display: flex; align-items: center; gap: 10px;">
            <span>✅</span> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error animate-in"
            style="background: rgba(239, 68, 68, 0.1); color: #FCA5A5; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2); display: flex; align-items: center; gap: 10px;">
            <span>❌</span> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 320px 1fr; gap: 1.5rem; align-items: start;">

        <!-- Sol Kolon: Viewer Listesi -->
        <div class="glass-card animate-in" style="padding: 0; overflow: hidden; position: sticky; top: 20px;">
            <div style="padding: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <h3 style="margin:0; font-size:1.1rem;">Viewer Kullanıcılar</h3>
            </div>
            <div style="max-height: 700px; overflow-y: auto;">
                <?php if (empty($viewers)): ?>
                    <div style="padding: 1.5rem; text-align: center; color: #64748B; font-size: 0.9rem;">Viewer bulunamadı.
                    </div>
                <?php else: ?>
                    <?php foreach ($viewers as $v): ?>
                        <div class="viewer-item"
                            onclick='selectViewer(<?php echo $v['id']; ?>, "<?php echo clean($v['display_name']); ?>", <?php echo json_encode($assignedMap[$v['id']] ?? []); ?>)'
                            id="viewer-<?php echo $v['id']; ?>"
                            style="padding: 1.25rem; border-bottom: 1px solid rgba(148,163,184,0.1); cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 12px;">
                            <div
                                style="width: 36px; height: 36px; background: rgba(59, 130, 246, 0.2); color: #2563EB; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem;">
                                <?php echo strtoupper(substr($v['display_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="viewer-name-text" style="font-weight: 600; font-size: 0.9rem;">
                                    <?php echo clean($v['display_name']); ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">@<?php echo clean($v['username']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sağ Kolon: Erişim Yetkileri Editörü -->
        <div id="editor-section">
            <div class="glass-card animate-in" id="editor-card" style="display: none;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 1rem;">
                    <div>
                        <h3 id="selected-viewer-name" style="margin:0; font-size:1.4rem;">🔑 Erişim Yetkilerini Yönet
                        </h3>
                        <p style="margin:4px 0 0 0; color:#64748B; font-size:0.9rem;">Bu kullanıcının hangi
                            veritabanlarında sorgu yapabileceğini seçin.</p>
                    </div>
                </div>

                <form method="POST" id="assignment-form">
                    <input type="hidden" name="viewer_id" id="form-viewer-id">

                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                        <?php if (empty($connections)): ?>
                            <div class="empty-dbs-msg"
                                style="grid-column: 1/-1; padding: 2rem; text-align: center; border-radius: 12px;">
                                Tanımlı veritabanı bulunamadı. Önce bağlantı ekleyin.
                            </div>
                        <?php else: ?>
                            <?php foreach ($connections as $c): ?>
                                <label class="db-card" id="db-label-<?php echo $c['id']; ?>">
                                    <div style="display: flex; align-items: center; gap: 12px; width: 100%;">
                                        <input type="checkbox" name="connections[]" value="<?php echo $c['id']; ?>"
                                            class="db-checkbox" id="db-check-<?php echo $c['id']; ?>">
                                        <div style="flex-grow: 1;">
                                            <div class="db-name-text" style="font-weight: 600; font-size: 1rem;">
                                                <?php echo clean($c['connection_name']); ?>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.8rem; margin-top: 2px;">
                                                <?php echo strtoupper($c['db_type']); ?>
                                            </div>
                                        </div>
                                        <div class="check-icon">✓</div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div
                        style="display: flex; justify-content: flex-end; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.05);">
                        <button type="submit" class="btn btn-primary"
                            style="padding: 0.85rem 3rem; font-weight: 700; font-size: 1rem; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);">Degişiklikleri
                            Kaydet</button>
                    </div>
                </form>
            </div>

            <!-- Boş Durum -->
            <div class="glass-card animate-in" id="empty-state" style="padding: 8rem 4rem; text-align: center;">
                <div style="font-size: 4rem; margin-bottom: 1.5rem; opacity: 0.5;">🔒</div>
                <h2 class="empty-state-title" style="margin-bottom: 1rem;">Bir Viewer Seçin</h2>
                <p class="text-muted" style="margin: 0; max-width: 400px; margin: 0 auto;">Yetkilerini düzenlemek veya yeni
                    veritabanı atamak için sol listeden bir kullanıcı seçin.</p>
            </div>
        </div>

    </div>
</div>

<style>
    .viewer-item:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .viewer-item.active {
        background: rgba(37, 99, 235, 0.1);
        border-left: 4px solid #3B82F6;
        padding-left: calc(1.25rem - 4px);
    }

    .db-card {
        background: rgba(15, 23, 42, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        position: relative;
        overflow: hidden;
    }

    .db-card:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.1);
        transform: translateY(-2px);
    }

    .db-card.selected {
        background: rgba(37, 99, 235, 0.1);
        border-color: rgba(59, 130, 246, 0.4);
        box-shadow: 0 0 20px rgba(37, 99, 235, 0.1);
    }

    .db-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #3B82F6;
    }

    .check-icon {
        width: 24px;
        height: 24px;
        background: #3B82F6;
        border-radius: 50%;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: bold;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.2s;
    }

    .db-card.selected .check-icon {
        opacity: 1;
        transform: scale(1);
    }
</style>

<script>
    function selectViewer(id, name, assignedDbIds) {
        document.getElementById('empty-state').style.display = 'none';
        document.getElementById('editor-card').style.display = 'block';

        document.getElementById('form-viewer-id').value = id;
        document.getElementById('selected-viewer-name').innerHTML = `🔑 <strong>${name}</strong> - Erişim Yetkileri`;

        // UI reset
        document.querySelectorAll('.viewer-item').forEach(el => el.classList.remove('active'));
        document.getElementById('viewer-' + id).classList.add('active');

        // Reset checkboxes
        document.querySelectorAll('.db-checkbox').forEach(cb => {
            cb.checked = false;
            cb.closest('.db-card').classList.remove('selected');
        });

        // Set assigned
        assignedDbIds.forEach(dbId => {
            const cb = document.getElementById('db-check-' + dbId);
            if (cb) {
                cb.checked = true;
                cb.closest('.db-card').classList.add('selected');
            }
        });

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Checkbox change visual feedback
    document.querySelectorAll('.db-checkbox').forEach(cb => {
        cb.addEventListener('change', function () {
            if (this.checked) {
                this.closest('.db-card').classList.add('selected');
            } else {
                this.closest('.db-card').classList.remove('selected');
            }
        });
    });

    // Make whole card clickable
    document.querySelectorAll('.db-card').forEach(card => {
        card.addEventListener('click', function (e) {
            if (e.target.type !== 'checkbox') {
                const cb = this.querySelector('.db-checkbox');
                cb.checked = !cb.checked;
                cb.dispatchEvent(new Event('change'));
            }
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>