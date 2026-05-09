<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/functions.php';

require_role('admin');

require_once __DIR__ . '/includes/header.php';

$db = getAuthDB();
$success = '';
$error = '';

// Helper to get setting
function get_setting($key)
{
    global $db;
    $val = $db->fetchColumn("SELECT setting_value FROM auth_app_settings WHERE setting_key = ?", [$key]);
    return $val ? decrypt_value($val) : '';
}

// Helper to set setting
function set_setting($key, $val)
{
    global $db;
    $enc = encrypt_value($val);
    // Insert or Update
    $sql = "INSERT INTO auth_app_settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW())
ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
    $db->query($sql, [$key, $enc, $enc]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'set_active') {
        $provider = clean($_POST['ai_provider']);
        set_setting('AI_PROVIDER', $provider);
        $success = ucfirst($provider) . " aktif sağlayıcı olarak ayarlandı!";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'save_provider') {
        $provider = clean($_POST['provider_name']);
        
        if ($provider === 'google') {
            set_setting('GOOGLE_API_KEY', $_POST['google_api_key']);
            set_setting('GOOGLE_MODEL', $_POST['google_model']);
        } elseif ($provider === 'openai') {
            set_setting('OPENAI_API_KEY', $_POST['openai_api_key']);
            set_setting('OPENAI_MODEL', $_POST['openai_model']);
        } elseif ($provider === 'anthropic') {
            set_setting('ANTHROPIC_API_KEY', $_POST['anthropic_api_key']);
            set_setting('ANTHROPIC_MODEL', $_POST['anthropic_model']);
        } elseif ($provider === 'deepseek') {
            set_setting('DEEPSEEK_API_KEY', $_POST['deepseek_api_key']);
            set_setting('DEEPSEEK_MODEL', $_POST['deepseek_model']);
        } elseif ($provider === 'minimax') {
            set_setting('MINIMAX_API_KEY', $_POST['minimax_api_key']);
            set_setting('MINIMAX_MODEL', $_POST['minimax_model']);
        } elseif ($provider === 'custom') {
            set_setting('CUSTOM_API_KEY', $_POST['custom_api_key']);
            set_setting('CUSTOM_BASE_URL', $_POST['custom_base_url']);
            set_setting('CUSTOM_MODEL', $_POST['custom_model']);
        }
        $success = ucfirst($provider) . " ayarları kaydedildi!";
    }
}

$currentProvider = get_setting('AI_PROVIDER') ?: 'google';
$googleKey = get_setting('GOOGLE_API_KEY');
$openaiKey = get_setting('OPENAI_API_KEY');
$openaiModel = get_setting('OPENAI_MODEL') ?: 'gpt-4o';
$deepseekKey = get_setting('DEEPSEEK_API_KEY');
$deepseekModel = get_setting('DEEPSEEK_MODEL') ?: 'deepseek-chat';
$minimaxKey = get_setting('MINIMAX_API_KEY');
$minimaxModel = get_setting('MINIMAX_MODEL') ?: 'MiniMax-Text-01';
?>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header animate-in" style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1>🧠 Yapay Zeka Ayarları</h1>
            <p>Aktif AI modelini ve API anahtarlarını yapılandırın.</p>
        </div>
        <button type="button" id="test-ai-btn" class="btn btn-secondary" style="border-color: #3B82F6; color: #3B82F6; width: auto; padding: 0.6rem 1.2rem; font-size: 0.9rem;">
            🧪 Aktif Sağlayıcı Bağlantısını Test Et
        </button>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"
            style="background: rgba(34, 197, 94, 0.15); color: #86EFAC; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid rgba(34, 197, 94, 0.3);">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 1.5rem; align-items: start;">
        
        <!-- Google Settings -->
        <div class="glass-card provider-card <?php echo $currentProvider === 'google' ? 'active-provider-card' : ''; ?>">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.5rem;">G</span> Google Gemini
                </h3>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="set_active">
                    <input type="hidden" name="ai_provider" value="google">
                    <?php if ($currentProvider === 'google'): ?>
                        <span class="badge-active">Hizmette</span>
                    <?php else: ?>
                        <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; width: auto; margin: 0;">Aktif Yap</button>
                    <?php endif; ?>
                </form>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="save_provider">
                <input type="hidden" name="provider_name" value="google">
                <div class="mb-4">
                    <label>GOOGLE API KEY</label>
                    <input type="password" name="google_api_key" value="<?php echo $googleKey; ?>" placeholder="AIzaSy..." class="input-dark">
                </div>
                <div class="mb-4">
                    <label>MODEL ADI</label>
                    <input type="text" name="google_model" value="<?php echo get_setting('GOOGLE_MODEL') ?: 'gemini-1.5-flash'; ?>" class="input-dark">
                </div>
                <button type="submit" class="btn btn-primary">💾 Kaydet</button>
            </form>
        </div>

        <!-- OpenAI Settings -->
        <div class="glass-card provider-card <?php echo $currentProvider === 'openai' ? 'active-provider-card' : ''; ?>">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.5rem;">O</span> OpenAI
                </h3>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="set_active">
                    <input type="hidden" name="ai_provider" value="openai">
                    <?php if ($currentProvider === 'openai'): ?>
                        <span class="badge-active">Hizmette</span>
                    <?php else: ?>
                        <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; width: auto; margin: 0;">Aktif Yap</button>
                    <?php endif; ?>
                </form>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="save_provider">
                <input type="hidden" name="provider_name" value="openai">
                <div class="mb-4">
                    <label>OPENAI API KEY</label>
                    <input type="password" name="openai_api_key" value="<?php echo $openaiKey; ?>" placeholder="sk-..." class="input-dark">
                </div>
                <div class="mb-4">
                    <label>MODEL ADI</label>
                    <input type="text" name="openai_model" value="<?php echo $openaiModel; ?>" class="input-dark">
                </div>
                <button type="submit" class="btn btn-primary">💾 Kaydet</button>
            </form>
        </div>

        <!-- Anthropic Settings -->
        <div class="glass-card provider-card <?php echo $currentProvider === 'anthropic' ? 'active-provider-card' : ''; ?>">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.5rem;">A</span> Anthropic
                </h3>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="set_active">
                    <input type="hidden" name="ai_provider" value="anthropic">
                    <?php if ($currentProvider === 'anthropic'): ?>
                        <span class="badge-active">Hizmette</span>
                    <?php else: ?>
                        <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; width: auto; margin: 0;">Aktif Yap</button>
                    <?php endif; ?>
                </form>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="save_provider">
                <input type="hidden" name="provider_name" value="anthropic">
                <div class="mb-4">
                    <label>ANTHROPIC API KEY</label>
                    <input type="password" name="anthropic_api_key" value="<?php echo get_setting('ANTHROPIC_API_KEY'); ?>" placeholder="sk-ant-..." class="input-dark">
                </div>
                <div class="mb-4">
                    <label>MODEL ADI</label>
                    <input type="text" name="anthropic_model" value="<?php echo get_setting('ANTHROPIC_MODEL') ?: 'claude-3-opus-20240229'; ?>" class="input-dark">
                </div>
                <button type="submit" class="btn btn-primary">💾 Kaydet</button>
            </form>
        </div>

        <!-- Deepseek Settings -->
        <div class="glass-card provider-card <?php echo $currentProvider === 'deepseek' ? 'active-provider-card' : ''; ?>">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.5rem;">D</span> Deepseek
                </h3>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="set_active">
                    <input type="hidden" name="ai_provider" value="deepseek">
                    <?php if ($currentProvider === 'deepseek'): ?>
                        <span class="badge-active">Hizmette</span>
                    <?php else: ?>
                        <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; width: auto; margin: 0;">Aktif Yap</button>
                    <?php endif; ?>
                </form>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="save_provider">
                <input type="hidden" name="provider_name" value="deepseek">
                <div class="mb-4">
                    <label>DEEPSEEK API KEY</label>
                    <input type="password" name="deepseek_api_key" value="<?php echo $deepseekKey; ?>" placeholder="sk-..." class="input-dark">
                </div>
                <div class="mb-4">
                    <label>MODEL ADI</label>
                    <input type="text" name="deepseek_model" value="<?php echo $deepseekModel; ?>" class="input-dark">
                </div>
                <button type="submit" class="btn btn-primary">💾 Kaydet</button>
            </form>
        </div>

        <!-- Minimax Settings -->
        <div class="glass-card provider-card <?php echo $currentProvider === 'minimax' ? 'active-provider-card' : ''; ?>">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.5rem;">M</span> Minimax
                </h3>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="set_active">
                    <input type="hidden" name="ai_provider" value="minimax">
                    <?php if ($currentProvider === 'minimax'): ?>
                        <span class="badge-active">Hizmette</span>
                    <?php else: ?>
                        <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; width: auto; margin: 0;">Aktif Yap</button>
                    <?php endif; ?>
                </form>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="save_provider">
                <input type="hidden" name="provider_name" value="minimax">
                <div class="mb-4">
                    <label>MINIMAX API KEY</label>
                    <input type="password" name="minimax_api_key" value="<?php echo $minimaxKey; ?>" placeholder="sk-..." class="input-dark">
                </div>
                <div class="mb-4">
                    <label>MODEL ADI</label>
                    <input type="text" name="minimax_model" value="<?php echo $minimaxModel; ?>" class="input-dark">
                </div>
                <button type="submit" class="btn btn-primary">💾 Kaydet</button>
            </form>
        </div>

        <!-- Custom LLM Settings -->
        <div class="glass-card provider-card <?php echo $currentProvider === 'custom' ? 'active-provider-card' : ''; ?>">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.5rem;">L</span> Özel / Yerel LLM
                </h3>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="set_active">
                    <input type="hidden" name="ai_provider" value="custom">
                    <?php if ($currentProvider === 'custom'): ?>
                        <span class="badge-active">Hizmette</span>
                    <?php else: ?>
                        <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; width: auto; margin: 0;">Aktif Yap</button>
                    <?php endif; ?>
                </form>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="save_provider">
                <input type="hidden" name="provider_name" value="custom">
                <div class="mb-4">
                    <label>BASE URL</label>
                    <input type="text" name="custom_base_url" value="<?php echo get_setting('CUSTOM_BASE_URL'); ?>" placeholder="http://localhost:1234/v1" class="input-dark">
                </div>
                <div class="mb-4">
                    <label>API KEY (Opsiyonel)</label>
                    <input type="password" name="custom_api_key" value="<?php echo get_setting('CUSTOM_API_KEY'); ?>" class="input-dark">
                </div>
                <div class="mb-4">
                    <label>MODEL ADI</label>
                    <input type="text" name="custom_model" value="<?php echo get_setting('CUSTOM_MODEL'); ?>" class="input-dark">
                </div>
                <button type="submit" class="btn btn-primary">💾 Kaydet</button>
            </form>
        </div>

    </div>

</div>

<script>
    document.getElementById('test-ai-btn').addEventListener('click', async function () {
        const btn = this;
        const originalText = btn.innerHTML;
        btn.innerHTML = '⌛ Test Ediliyor...';
        btn.disabled = true;

        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'test_ai' })
            });
            const data = await response.json();

            if (data.success) {
                alert('✅ ' + data.message);
            } else {
                alert('❌ Hata: ' + data.error);
            }
        } catch (err) {
            alert('❌ Bağlantı hatası oluştu.');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
</script>

<style>
    .provider-card {
        padding: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.05);
        transition: all 0.3s ease;
    }

    .active-provider-card {
        border-color: #22C55E;
        box-shadow: 0 0 15px rgba(34, 197, 94, 0.2);
    }

    .badge-active {
        background: rgba(34, 197, 94, 0.15);
        color: #4ADE80;
        border: 1px solid rgba(34, 197, 94, 0.3);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
    }

    .input-dark {
        background: rgba(0,0,0,0.2) !important;
        color: white;
        border: 1px solid rgba(255,255,255,0.1);
    }

    /* Light Mode Overrides */
    body.light-mode .provider-card {
        border-color: rgba(148, 163, 184, 0.3);
    }

    body.light-mode .active-provider-card {
        border-color: #16A34A;
        box-shadow: 0 0 15px rgba(22, 163, 74, 0.2);
    }

    body.light-mode .badge-active {
        background: rgba(34, 197, 94, 0.15);
        color: #16A34A;
        border-color: rgba(34, 197, 94, 0.4);
    }

    body.light-mode .input-dark {
        background: #FFFFFF !important;
        border: 1px solid rgba(148, 163, 184, 0.5);
        color: #0F172A;
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>