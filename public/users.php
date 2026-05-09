<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/functions.php';

require_role('admin');

require_once __DIR__ . '/includes/header.php';

$db = getAuthDB();
$success = '';
$error = '';

// Kullanıcı Ekleme / Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $username = clean($_POST['username']);
    $displayName = clean($_POST['display_name']);
    $role = clean($_POST['role']);
    $password = $_POST['password'] ?? '';
    $userId = $_POST['user_id'] ?? null;

    if ($action === 'add') {
        $auth = new Auth();
        $result = $auth->register($username, $password, $displayName, $role);
        if ($result['success'])
            $success = "Yeni kullanıcı başarıyla oluşturuldu.";
        else
            $error = $result['message'];
    } elseif ($action === 'edit' && $userId) {
        try {
            if (!empty($password)) {
                $hash = hash_password($password);
                $db->query("UPDATE auth_users SET username=?, display_name=?, role=?, password_hash=? WHERE id=?", [
                    $username,
                    $displayName,
                    $role,
                    $hash,
                    $userId
                ]);
            } else {
                $db->query("UPDATE auth_users SET username=?, display_name=?, role=? WHERE id=?", [
                    $username,
                    $displayName,
                    $role,
                    $userId
                ]);
            }
            $success = "Kullanıcı bilgileri güncellendi.";
        } catch (Exception $e) {
            $error = "Güncelleme hatası: " . $e->getMessage();
        }
    }
}

// Silme
if (isset($_GET['delete'])) {
    $id = clean($_GET['delete']);
    if ($id != $_SESSION['user']['id']) {
        $db->query("DELETE FROM auth_users WHERE id = ?", [$id]);
        $success = "Kullanıcı silindi.";
    } else {
        $error = "Kendi hesabınızı silemezsiniz!";
    }
}

// Durum Güncelleme
if (isset($_GET['toggle_status'])) {
    $id = clean($_GET['toggle_status']);
    if ($id != $_SESSION['user']['id']) {
        $current = $db->fetchColumn("SELECT is_active FROM auth_users WHERE id = ?", [$id]);
        $newStatus = $current ? 0 : 1;
        $db->query("UPDATE auth_users SET is_active = ? WHERE id = ?", [$newStatus, $id]);
        $success = "Kullanıcı durumu güncellendi.";
    }
}

$users = $db->fetchAll("SELECT * FROM auth_users ORDER BY id DESC");
?>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header animate-in">
        <h1>⚙️ Kullanıcı Yönetimi</h1>
        <p>Sistem kullanıcılarını, rollerini ve erişim durumlarını tek bir ekrandan yönetin.</p>
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

    <div style="display: grid; grid-template-columns: 350px 1fr; gap: 1.5rem; align-items: start;">

        <!-- Sol Kolon: Form -->
        <div class="glass-card animate-in" style="position: sticky; top: 20px;">
            <h3 id="form-title" style="margin-top:0; margin-bottom:1.5rem; font-size:1.25rem;">➕ Yeni Kullanıcı</h3>
            <form method="POST" id="user-form">
                <input type="hidden" name="action" id="form-action" value="add">
                <input type="hidden" name="user_id" id="form-id" value="">

                <div class="mb-4">
                    <label class="form-label">KULLANICI ADI</label>
                    <input type="text" name="username" id="form-username" placeholder="örn: gursoy" required class="input-dark">
                </div>

                <div class="mb-4">
                    <label class="form-label">GÖRÜNEN İSİM</label>
                    <input type="text" name="display_name" id="form-display-name" placeholder="örn: Erhan Gürsoy"
                        required class="input-dark">
                </div>

                <div class="mb-4">
                    <label class="form-label">ROL</label>
                    <select name="role" id="form-role" class="input-dark">
                        <option value="viewer">Viewer (Görüntüleyici)</option>
                        <option value="analyst">Analyst (Analist)</option>
                        <option value="admin">Admin (Yönetici)</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label">ŞİFRE</label>
                    <input type="password" name="password" id="form-pass" placeholder="••••••••" class="input-dark">
                    <small id="pass-hint" class="pass-hint">Boş bırakılırsa şifre değişmez (Düzenleme için).</small>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 0.85rem;">Kaydet</button>
                    <button type="button" id="cancel-btn" class="btn btn-secondary"
                        style="display: none;">İptal</button>
                </div>
            </form>
        </div>

        <!-- Sağ Kolon: Liste -->
        <div class="glass-card animate-in" style="padding: 0; overflow: hidden;">
            <div class="card-header-users">
                <h3 style="margin:0; font-size:1.25rem;">Sistem Kullanıcıları</h3>
                <span class="users-count"><?php echo count($users); ?> Kullanıcı</span>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr class="tr-header">
                            <th class="table-th">Kullanıcı</th>
                            <th class="table-th">Rol</th>
                            <th class="table-th">Durum</th>
                            <th class="table-th">Son Giriş</th>
                            <th class="table-th" style="text-align: right;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr class="user-row tr-body">
                                <td style="padding: 1.25rem;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div class="user-avatar"
                                            style="width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem;">
                                            <?php echo strtoupper(substr($u['display_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="user-name">
                                                <?php echo clean($u['display_name']); ?>
                                            </div>
                                            <div class="user-username">
                                                @<?php echo clean($u['username']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 1.25rem;">
                                    <?php
                                    $roleClass = '';
                                    $roleLabel = ucfirst($u['role']);
                                    if ($u['role'] === 'admin')
                                        $roleClass = 'background: rgba(239, 68, 68, 0.15); color: #FCA5A5; border-color: rgba(239, 68, 68, 0.3);';
                                    elseif ($u['role'] === 'analyst')
                                        $roleClass = 'background: rgba(245, 158, 11, 0.15); color: #FCD34D; border-color: rgba(245, 158, 11, 0.3);';
                                    else
                                        $roleClass = 'background: rgba(59, 130, 246, 0.15); color: #93C5FD; border-color: rgba(59, 130, 246, 0.3);';
                                    ?>
                                    <span
                                        style="font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; border: 1px solid; <?php echo $roleClass; ?> uppercase; letter-spacing: 0.5px;">
                                        <?php echo $roleLabel; ?>
                                    </span>
                                </td>
                                <td style="padding: 1.25rem;">
                                    <a href="?toggle_status=<?php echo $u['id']; ?>"
                                        style="text-decoration: none; display: flex; align-items: center; gap: 6px;">
                                        <div class="<?php echo $u['is_active'] ? 'status-dot-active' : 'status-dot-passive'; ?>"
                                            style="width: 8px; height: 8px; border-radius: 50%;">
                                        </div>
                                        <span class="<?php echo $u['is_active'] ? 'status-text-active' : 'status-text-passive'; ?>">
                                            <?php echo $u['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                        </span>
                                    </a>
                                </td>
                                <td class="user-last-login">
                                    <?php echo $u['last_login'] ? date('d.m.Y H:i', strtotime($u['last_login'])) : 'Hiç girmedi'; ?>
                                </td>
                                <td style="padding: 1.25rem; text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <button type="button" class="btn-icon edit-btn" data-id="<?php echo $u['id']; ?>"
                                            data-username="<?php echo clean($u['username']); ?>"
                                            data-display="<?php echo clean($u['display_name']); ?>"
                                            data-role="<?php echo $u['role']; ?>" title="Düzenle">✏️</button>

                                        <?php if ($u['id'] != $_SESSION['user']['id'] && $u['username'] !== 'admin'): ?>
                                            <a href="?delete=<?php echo $u['id']; ?>" class="btn-icon" style="color: #F87171;"
                                                onclick="return confirm('Kullanıcıyı silmek istediğinize emin misiniz?')"
                                                title="Sil">🗑️</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.9rem;
        text-decoration: none;
    }

    .btn-icon:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.2);
        color: white;
        transform: translateY(-2px);
    }

    .user-row:hover {
        background: rgba(255, 255, 255, 0.02);
    }

    /* Users Page Light Mode Overrides */
    .user-name { color: #F1F5F9; font-weight: 600; font-size: 0.95rem; }
    .user-username { color: #64748B; font-size: 0.75rem; }
    .table-th { padding: 1.25rem; font-size: 0.75rem; text-transform: uppercase; color: #94A3B8; text-align: left; }
    .form-label { font-size:0.75rem; color:#94A3B8; margin-bottom:0.5rem; display:block; font-weight:600; text-transform: uppercase; }
    .pass-hint { font-size: 0.7rem; color: #64748B; margin-top: 4px; display: block; }
    .user-last-login { padding: 1.25rem; font-size: 0.8rem; color: #94A3B8; }
    .tr-header { background: rgba(255,255,255,0.02); border-bottom: 1px solid rgba(255,255,255,0.05); }
    .tr-body { border-bottom: 1px solid rgba(255,255,255,0.03); transition: background 0.2s; }
    .card-header-users { padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; }
    .users-count { font-size: 0.85rem; color: #94A3B8; }
    .input-dark { background: rgba(0,0,0,0.2) !important; color: white; border: 1px solid rgba(255,255,255,0.1); }
    .user-avatar { background: rgba(59, 130, 246, 0.2); color: #60A5FA; }
    .status-text-active { color: #4ADE80; font-size: 0.85rem; }
    .status-text-passive { color: #64748B; font-size: 0.85rem; }
    .status-dot-active { background: #22C55E; box-shadow: 0 0 8px rgba(34, 197, 14, 0.4); }
    .status-dot-passive { background: #94A3B8; box-shadow: none; }

    body.light-mode .user-name { color: #0F172A; }
    body.light-mode .user-username { color: #475569; }
    body.light-mode .table-th { color: #475569; }
    body.light-mode .form-label { color: #475569; }
    body.light-mode .pass-hint { color: #64748B; }
    body.light-mode .user-last-login { color: #475569; }
    body.light-mode .tr-header { background: rgba(15, 23, 42, 0.03); border-bottom-color: rgba(148, 163, 184, 0.2); }
    body.light-mode .tr-body { border-bottom-color: rgba(148, 163, 184, 0.1); }
    body.light-mode .card-header-users { border-bottom-color: rgba(148, 163, 184, 0.2); }
    body.light-mode .users-count { color: #475569; }
    body.light-mode .input-dark { background: #FFFFFF !important; border: 1px solid rgba(148, 163, 184, 0.5); color: #0F172A; }
    body.light-mode .user-row:hover { background: rgba(15, 23, 42, 0.04); }
    body.light-mode .user-avatar { background: rgba(37, 99, 235, 0.15); color: #2563EB; }
    body.light-mode .status-text-active { color: #16A34A; }
    body.light-mode .status-text-passive { color: #475569; }
    body.light-mode .status-dot-passive { background: #64748B; }
    
    body.light-mode .btn-icon { background: transparent; border-color: rgba(148, 163, 184, 0.4); color: #475569; }
    body.light-mode .btn-icon:hover { background: rgba(148, 163, 184, 0.15); border-color: rgba(148, 163, 184, 0.6); color: #0F172A; }
</style>

<script>
    // Düzenleme Fonksiyonu
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const data = this.dataset;
            document.getElementById('form-title').innerText = "✏️ Kullanıcıyı Düzenle";
            document.getElementById('form-action').value = "edit";
            document.getElementById('form-id').value = data.id;
            document.getElementById('form-username').value = data.username;
            document.getElementById('form-display-name').value = data.display;
            document.getElementById('form-role').value = data.role;
            document.getElementById('form-pass').placeholder = "Şifreyi değiştirmek için yazın...";
            document.getElementById('pass-hint').innerText = "Boş bırakırsanız mevcut şifre korunur.";
            document.getElementById('cancel-btn').style.display = "block";

            // Highlight the row
            document.querySelectorAll('.user-row').forEach(r => r.style.background = "");
            this.closest('tr').style.background = "rgba(37, 99, 235, 0.1)";

            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // İptal Butonu
    document.getElementById('cancel-btn').addEventListener('click', function () {
        document.getElementById('user-form').reset();
        document.getElementById('form-title').innerText = "➕ Yeni Kullanıcı";
        document.getElementById('form-action').value = "add";
        document.getElementById('form-id').value = "";
        document.getElementById('form-pass').placeholder = "••••••••";
        document.getElementById('pass-hint').innerText = "Yeni kullanıcı için şifre zorunludur.";
        this.style.display = "none";
        document.querySelectorAll('.user-row').forEach(r => r.style.background = "");
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>