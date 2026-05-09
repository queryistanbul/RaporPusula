<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/functions.php';

require_role(['admin', 'analyst']);

require_once __DIR__ . '/includes/header.php';

$user = Auth::user();
$db = getAuthDB();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connection_id'])) {
    $connId = $_POST['connection_id'];
    $rules = $_POST['business_rules'];

    try {
        $db->query("UPDATE auth_user_connections SET business_rules = ? WHERE id = ? AND user_id = ?", [
            $rules,
            $connId,
            $user['id']
        ]);
        $success = "İş kuralları başarıyla kaydedildi.";
    } catch (Exception $e) {
        $error = "Hata: " . $e->getMessage();
    }
}

$connections = $db->fetchAll("SELECT id, connection_name, db_type, business_rules FROM auth_user_connections WHERE
user_id = ? ORDER BY is_default DESC, connection_name", [$user['id']]);
?>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header animate-in">
        <h1>📂 İş Kuralları (Metadata)</h1>
        <p>Her veritabanı için özel terimler, hesaplama kuralları ve şema detayları tanımlayın.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success animate-in"
            style="background: rgba(34, 197, 94, 0.1); color: #86EFAC; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid rgba(34, 197, 94, 0.2); display: flex; align-items: center; gap: 10px;">
            <span>✅</span> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 320px 1fr; gap: 1.5rem; align-items: start;">

        <!-- Sol Kolon: Veritabanı Listesi -->
        <div class="glass-card animate-in" style="padding: 0; overflow: hidden;">
            <div style="padding: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <h3 style="margin:0; font-size:1.1rem;">Veri Kaynakları</h3>
            </div>
            <div style="max-height: 600px; overflow-y: auto;">
                <?php foreach ($connections as $conn): ?>
                    <div class="conn-item"
                        onclick="selectConnection(<?php echo $conn['id']; ?>, <?php echo htmlspecialchars(json_encode($conn['business_rules'] ?: '')); ?>)"
                        id="conn-<?php echo $conn['id']; ?>"
                        style="padding: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.03); cursor: pointer; transition: all 0.2s;">
                        <div style="font-weight: 600; color: #F1F5F9;"><?php echo clean($conn['connection_name']); ?></div>
                        <div style="font-size: 0.75rem; color: #64748B; margin-top: 4px;">
                            <?php echo strtoupper($conn['db_type']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sağ Kolon: Editör -->
        <div class="glass-card animate-in" id="editor-card" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 id="selected-conn-name" style="margin:0; font-size:1.25rem;">📌 İş Kurallarını Düzenle</h3>
                <span id="save-status" style="font-size: 0.8rem; color: #4ADE80; opacity: 0;">Değişiklikler
                    kaydedildi</span>
            </div>

            <form method="POST" id="metadata-form">
                <input type="hidden" name="connection_id" id="form-conn-id">

                <div
                    style="background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #3B82F6;">
                    <h4 style="margin:0 0 0.5rem 0; font-size: 0.9rem; color: #94A3B8;">📝 İpucu ve Örnekler</h4>
                    <p style="margin:0; font-size: 0.85rem; color: #64748B; line-height: 1.5;">
                        - "Maliyet" = `purchase_price * tax_rate`<br>
                        - "Vip Üye" = Toplam harcaması 10.000 TL üzeri olanlar.<br>
                        - `tablo_xyz` aslında tedarikçi listesidir.
                    </p>
                </div>

                <textarea name="business_rules" id="rules-textarea" rows="18"
                    placeholder="Bu veritabanına özel kuralları buraya yazın..."
                    style="background: rgba(15, 23, 42, 0.4); border-color: rgba(255,255,255,0.1); line-height: 1.6; font-family: 'JetBrains Mono', 'Courier New', monospace;"></textarea>

                <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2.5rem;">Değişiklikleri
                        Kaydet</button>
                </div>
            </form>
        </div>

        <!-- Boş Durum -->
        <div class="glass-card animate-in" id="empty-state" style="padding: 4rem; text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1.5rem;">👈</div>
            <h3 style="color: #F1F5F9; margin-bottom: 0.5rem;">Bir Veritabanı Seçin</h3>
            <p style="color: #64748B; margin: 0;">İş kurallarını düzenlemek için sol listeden bir bağlantı seçin.</p>
        </div>

    </div>
</div>

<style>
    .conn-item:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .conn-item.active {
        background: rgba(37, 99, 235, 0.1);
        border-left: 4px solid #3B82F6;
        padding-left: calc(1.25rem - 4px);
    }

    textarea:focus {
        border-color: #3B82F6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }
</style>

<script>
    function selectConnection(id, rules) {
        document.getElementById('empty-state').style.display = 'none';
        document.getElementById('editor-card').style.display = 'block';

        document.getElementById('form-conn-id').value = id;
        document.getElementById('rules-textarea').value = rules;

        // UI updates
        document.querySelectorAll('.conn-item').forEach(el => el.classList.remove('active'));
        document.getElementById('conn-' + id).classList.add('active');

        const name = document.getElementById('conn-' + id).querySelector('div').innerText;
        document.getElementById('selected-conn-name').innerText = "📌 " + name + " - İş Kuralları";
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>