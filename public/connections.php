<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/functions.php';

require_role(['admin', 'analyst']);

require_once __DIR__ . '/includes/header.php';

$user = Auth::user();
$error = '';
$success = '';

$db = getAuthDB();

// Bağlantı Ekleme / Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $name = clean($_POST['connection_name']);
    $type = clean($_POST['db_type']);
    $host = clean($_POST['host']);
    $port = clean($_POST['port']);
    $dbUser = clean($_POST['db_user']);
    $dbPass = $_POST['db_password'];
    $dbName = clean($_POST['db_name']);
    $isDefault = isset($_POST['is_default']) ? 1 : 0;
    $connId = $_POST['connection_id'] ?? null;

    // Varsayılanı sıfırla
    if ($isDefault) {
        $db->query("UPDATE auth_user_connections SET is_default = 0 WHERE user_id = ?", [$user['id']]);
    }

    if ($action === 'add') {
        $passEnc = encrypt_value($dbPass);
        $sql = "INSERT INTO auth_user_connections (user_id, connection_name, db_type, host, port, db_user, db_password_enc,
db_name, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        try {
            $db->query($sql, [$user['id'], $name, $type, $host, $port, $dbUser, $passEnc, $dbName, $isDefault]);
            $success = "Yeni bağlantı başarıyla eklendi.";
        } catch (Exception $e) {
            $error = "Hata: " . $e->getMessage();
        }
    } elseif ($action === 'edit' && $connId) {
        if (!empty($dbPass)) {
            $passEnc = encrypt_value($dbPass);
            $sql = "UPDATE auth_user_connections SET connection_name=?, db_type=?, host=?, port=?, db_user=?, db_password_enc=?,
db_name=?, is_default=? WHERE id=? AND user_id=?";
            $params = [$name, $type, $host, $port, $dbUser, $passEnc, $dbName, $isDefault, $connId, $user['id']];
        } else {
            $sql = "UPDATE auth_user_connections SET connection_name=?, db_type=?, host=?, port=?, db_user=?, db_name=?,
is_default=? WHERE id=? AND user_id=?";
            $params = [$name, $type, $host, $port, $dbUser, $dbName, $isDefault, $connId, $user['id']];
        }
        try {
            $db->query($sql, $params);
            $success = "Bağlantı güncellendi.";
        } catch (Exception $e) {
            $error = "Güncelleme hatası: " . $e->getMessage();
        }
    }
}

// Bağlantı Silme
if (isset($_GET['delete'])) {
    $id = clean($_GET['delete']);
    $db->query("DELETE FROM auth_user_connections WHERE id = ? AND user_id = ?", [$id, $user['id']]);
    $success = "Bağlantı silindi.";
}

// Bağlantı Listesi
$connections = $db->fetchAll("SELECT * FROM auth_user_connections WHERE user_id = ? ORDER BY is_default DESC,
connection_name", [$user['id']]);
?>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header animate-in">
        <h1>🗄️ Veritabanı Yönetimi</h1>
        <p>Tüm veri kaynaklarınızı tek bir ekrandan yönetin, düzenleyin ve test edin.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success animate-in"
            style="background: rgba(34, 197, 94, 0.1); color: #86EFAC; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid rgba(34, 197, 94, 0.2); display: flex; align-items: center; gap: 10px;">
            <span>✅</span> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error animate-in"
            style="background: rgba(239, 68, 68, 0.1); color: #FCA5A5; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2); display: flex; align-items: center; gap: 10px;">
            <span>❌</span> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 380px 1fr; gap: 1.5rem; align-items: start;">

        <!-- Sol Kolon: Form -->
        <div class="glass-card animate-in" style="position: sticky; top: 20px;">
            <h3 id="form-title" style="margin-top:0; margin-bottom:1.5rem; font-size:1.25rem;">➕ Yeni Bağlantı</h3>
            <form method="POST" id="conn-form">
                <input type="hidden" name="action" id="form-action" value="add">
                <input type="hidden" name="connection_id" id="form-id" value="">

                <div class="mb-4">
                    <label style="font-size:0.75rem; color:#94A3B8; margin-bottom:0.5rem; display:block;">BAĞLANTI
                        ADI</label>
                    <input type="text" name="connection_name" id="form-name" placeholder="Örn: Satış DB" required
                        style="background: rgba(0,0,0,0.2);">
                </div>

                <div class="mb-4">
                    <label style="font-size:0.75rem; color:#94A3B8; margin-bottom:0.5rem; display:block;">VERİTABANI
                        TÜRÜ</label>
                    <select name="db_type" id="form-type" onchange="updatePort()" style="background: rgba(0,0,0,0.2);">
                        <option value="mysql">MySQL</option>
                        <option value="mssql">MSSQL (SQL Server)</option>
                        <option value="postgresql">PostgreSQL</option>
                        <option value="oracle">Oracle</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                    <div class="mb-4">
                        <label style="font-size:0.75rem; color:#94A3B8; margin-bottom:0.5rem; display:block;">HOST /
                            SERVER</label>
                        <input type="text" name="host" id="form-host" value="localhost" required
                            style="background: rgba(0,0,0,0.2);">
                    </div>
                    <div class="mb-4">
                        <label
                            style="font-size:0.75rem; color:#94A3B8; margin-bottom:0.5rem; display:block;">PORT</label>
                        <input type="number" name="port" id="form-port" value="3306" required
                            style="background: rgba(0,0,0,0.2);">
                    </div>
                </div>

                <div class="mb-4">
                    <label style="font-size:0.75rem; color:#94A3B8; margin-bottom:0.5rem; display:block;">VERİTABANI ADI
                        / SID</label>
                    <input type="text" name="db_name" id="form-dbname" required style="background: rgba(0,0,0,0.2);">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="mb-4">
                        <label
                            style="font-size:0.75rem; color:#94A3B8; margin-bottom:0.5rem; display:block;">KULLANICI</label>
                        <input type="text" name="db_user" id="form-user" required style="background: rgba(0,0,0,0.2);">
                    </div>
                    <div class="mb-4">
                        <label
                            style="font-size:0.75rem; color:#94A3B8; margin-bottom:0.5rem; display:block;">ŞİFRE</label>
                        <input type="password" name="db_password" id="form-pass" placeholder="••••••••"
                            style="background: rgba(0,0,0,0.2);">
                    </div>
                </div>

                <div class="mb-4"
                    style="display: flex; align-items: center; gap: 0.75rem; background: rgba(0,0,0,0.1); padding: 0.75rem; border-radius: 8px;">
                    <input type="checkbox" name="is_default" id="form-default" style="width: auto; margin: 0;">
                    <label for="form-default"
                        style="margin: 0; cursor: pointer; font-size: 0.85rem; color: #CBD5E1;">Varsayılan bağlantı
                        yap</label>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 0.85rem;">Kaydet</button>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" id="test-btn" class="btn btn-secondary"
                            style="flex: 1; border-color: rgba(59, 130, 246, 0.4); color: #60A5FA;">🧪 Test Et</button>
                        <button type="button" id="cancel-btn" class="btn btn-secondary"
                            style="flex: 1; display: none;">İptal</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sağ Kolon: Liste -->
        <div class="glass-card animate-in" style="padding: 0; overflow: hidden;">
            <div
                style="padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin:0; font-size:1.25rem;">Mevcut Bağlantılar</h3>
                <span style="font-size: 0.85rem; color: #94A3B8;"><?php echo count($connections); ?> Bağlantı</span>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr
                            style="text-align: left; background: rgba(255,255,255,0.02); border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <th
                                style="padding: 1.25rem; font-size: 0.75rem; text-transform: uppercase; color: #94A3B8;">
                                Durum</th>
                            <th
                                style="padding: 1.25rem; font-size: 0.75rem; text-transform: uppercase; color: #94A3B8;">
                                Bağlantı Detayları</th>
                            <th
                                style="padding: 1.25rem; font-size: 0.75rem; text-transform: uppercase; color: #94A3B8;">
                                Sunucu</th>
                            <th
                                style="padding: 1.25rem; font-size: 0.75rem; text-transform: uppercase; color: #94A3B8; text-align: right;">
                                İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($connections)): ?>
                            <tr>
                                <td colspan="4" style="padding: 3rem; text-align: center; color: #94A3B8;">Henüz bir
                                    bağlantı tanımlanmamış.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($connections as $conn): ?>
                                <tr class="conn-row"
                                    style="border-bottom: 1px solid rgba(255,255,255,0.03); transition: background 0.2s;">
                                    <td style="padding: 1.25rem;">
                                        <?php if ($conn['is_default']): ?>
                                            <span
                                                style="background: rgba(34, 197, 94, 0.15); color: #4ADE80; font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(34, 197, 94, 0.3);">VARSAYILAN</span>
                                        <?php else: ?>
                                            <span
                                                style="display:inline-block; width: 8px; height: 8px; background: #475569; border-radius: 50%; margin-left: 10px;"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 1.25rem;">
                                        <div style="font-weight: 600; color: #F1F5F9; font-size: 1rem;">
                                            <?php echo clean($conn['connection_name']); ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: #64748B; margin-top: 2px;">
                                            <?php echo strtoupper($conn['db_type']); ?> · <?php echo clean($conn['db_name']); ?>
                                        </div>
                                    </td>
                                    <td style="padding: 1.25rem;">
                                        <div style="font-size: 0.9rem; color: #CBD5E1;"><?php echo $conn['host']; ?></div>
                                        <div style="font-size: 0.75rem; color: #64748B;">Port: <?php echo $conn['port']; ?>
                                        </div>
                                    </td>
                                    <td style="padding: 1.25rem; text-align: right;">
                                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                            <button type="button" class="btn-icon test-db-btn"
                                                data-id="<?php echo $conn['id']; ?>" title="Test Et">⚡</button>
                                            <button type="button" class="btn-icon edit-btn" data-id="<?php echo $conn['id']; ?>"
                                                data-name="<?php echo clean($conn['connection_name']); ?>"
                                                data-type="<?php echo $conn['db_type']; ?>"
                                                data-host="<?php echo $conn['host']; ?>"
                                                data-port="<?php echo $conn['port']; ?>"
                                                data-user="<?php echo clean($conn['db_user']); ?>"
                                                data-dbname="<?php echo clean($conn['db_name']); ?>"
                                                data-default="<?php echo $conn['is_default']; ?>" title="Düzenle">✏️</button>
                                            <a href="?delete=<?php echo $conn['id']; ?>" class="btn-icon"
                                                style="color: #F87171;"
                                                onclick="return confirm('Silmek istediğinize emin misiniz?')"
                                                title="Sil">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-icon {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #94A3B8;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 1rem;
        text-decoration: none;
    }

    .btn-icon:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.2);
        color: white;
        transform: translateY(-2px);
    }

    .conn-row:hover {
        background: rgba(255, 255, 255, 0.02);
    }
</style>

<script>
    // Düzenleme Fonksiyonu
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const data = this.dataset;
            document.getElementById('form-title').innerText = "✏️ Bağlantıyı Düzenle";
            document.getElementById('form-action').value = "edit";
            document.getElementById('form-id').value = data.id;
            document.getElementById('form-name').value = data.name;
            document.getElementById('form-type').value = data.type;
            document.getElementById('form-host').value = data.host;
            document.getElementById('form-port').value = data.port;
            document.getElementById('form-user').value = data.user;
            document.getElementById('form-dbname').value = data.dbname;
            document.getElementById('form-pass').placeholder = "Değiştirmek için yazın...";
            document.getElementById('form-default').checked = data.default == "1";
            document.getElementById('cancel-btn').style.display = "block";

            // Highlight the row
            document.querySelectorAll('.conn-row').forEach(r => r.style.background = "");
            this.closest('tr').style.background = "rgba(37, 99, 235, 0.1)";

            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // İptal Butonu
    document.getElementById('cancel-btn').addEventListener('click', function () {
        document.getElementById('conn-form').reset();
        document.getElementById('form-title').innerText = "➕ Yeni Bağlantı";
        document.getElementById('form-action').value = "add";
        document.getElementById('form-id').value = "";
        document.getElementById('form-pass').placeholder = "••••••••";
        this.style.display = "none";
        document.querySelectorAll('.conn-row').forEach(r => r.style.background = "");
    });

    // Mevcut bağlantıları test et
    document.querySelectorAll('.test-db-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.getAttribute('data-id');
            const originalText = this.innerHTML;
            this.innerHTML = '⌛';
            this.disabled = true;

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'test_db', db_id: id })
                });
                const data = await response.json();
                if (data.success) alert('✅ ' + data.message);
                else alert('❌ Hata: ' + data.error);
            } catch (err) { alert('❌ Bağlantı hatası: ' + err.message); }
            finally { this.innerHTML = originalText; this.disabled = false; }
        });
    });

    // Formu test et
    document.getElementById('test-btn').addEventListener('click', async function () {
        const btn = this;
        const form = document.getElementById('conn-form');
        const originalText = btn.innerHTML;
        btn.innerHTML = '⌛ Test...';
        btn.disabled = true;

        const formData = {
            action: 'test_db',
            db_type: document.getElementById('form-type').value,
            host: document.getElementById('form-host').value,
            port: document.getElementById('form-port').value,
            db_name: document.getElementById('form-dbname').value,
            db_user: document.getElementById('form-user').value,
            db_password: document.getElementById('form-pass').value
        };

        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const data = await response.json();
            if (data.success) alert('✅ ' + data.message);
            else alert('❌ Hata: ' + data.error);
        } catch (err) { alert('❌ Bağlantı hatası: ' + err.message); }
        finally { btn.innerHTML = originalText; btn.disabled = false; }
    });

    function updatePort() {
        const type = document.getElementById('form-type').value;
        const portInput = document.getElementById('form-port');
        if (type === 'mysql') portInput.value = 3306;
        if (type === 'mssql') portInput.value = 1433;
        if (type === 'postgresql') portInput.value = 5432;
        if (type === 'oracle') portInput.value = 1521;
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>