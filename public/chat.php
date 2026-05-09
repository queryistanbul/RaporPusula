<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/functions.php';

require_role(['admin', 'analyst', 'viewer']);

require_once __DIR__ . '/includes/header.php';

$user = Auth::user();
$db = getAuthDB();

// Kullanıcının erişebileceği veritabanlarını getir
if ($user['role'] === 'admin' || $user['role'] === 'analyst') {
    $connections = $db->fetchAll("SELECT id, connection_name, db_type, is_default FROM auth_user_connections WHERE user_id =
?", [$user['id']]);
} else {
    $connections = $db->fetchAll("SELECT c.id, c.connection_name, c.db_type, c.is_default
FROM auth_user_connections c
JOIN viewer_assignments v ON c.id = v.connection_id
WHERE v.viewer_user_id = ?", [$user['id']]);
}

$defaultConnId = null;
foreach ($connections as $c) {
    if ($c['is_default'])
        $defaultConnId = $c['id'];
}
if (!$defaultConnId && !empty($connections)) {
    $defaultConnId = $connections[0]['id'];
}
?>

<style>
    :root {
        --chat-bg: rgba(15, 23, 42, 0.6);
        --user-bubble: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
        --user-text: white;
        --assistant-bubble: rgba(30, 41, 59, 0.8);
        --assistant-text: #E2E8F0;
        --accent-glow: rgba(255, 107, 0, 0.4);
        --input-wrapper-bg: rgba(30, 41, 59, 0.6);
        --input-wrapper-focus: rgba(30, 41, 59, 0.8);
        --input-text: white;
        --sql-bg: rgba(0, 0, 0, 0.3);
        --sql-text: #86EFAC;
        --chart-bg: rgba(0, 0, 0, 0.2);
        --chart-border: rgba(255, 255, 255, 0.05);
        --chat-footer-bg: linear-gradient(to top, #0F172A, transparent);
    }

    body.light-mode {
        --assistant-bubble: rgba(255, 255, 255, 0.95);
        --assistant-text: #1E293B;
        --input-wrapper-bg: rgba(255, 255, 255, 0.9);
        --input-wrapper-focus: #FFFFFF;
        --input-text: #0F172A;
        --sql-bg: rgba(241, 245, 249, 0.9);
        --sql-text: #059669;
        --chart-bg: rgba(255, 255, 255, 0.85);
        --chart-border: rgba(148, 163, 184, 0.3);
        --chat-footer-bg: linear-gradient(to top, rgba(241, 245, 249, 0.95), transparent);
    }

    .chat-wrapper {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 40px);
        max-width: 1200px;
        margin: 0 auto;
    }

    .chat-header-section {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        margin-bottom: 1rem;
    }

    /* Message Bubbles */
    #chat-container {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
    }

    .message-row {
        display: flex;
        width: 100%;
        animation: messageIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .message-row.user {
        justify-content: flex-end;
    }

    .message-row.assistant {
        justify-content: flex-start;
    }

    .bubble {
        max-width: 80%;
        padding: 1rem 1.25rem;
        border-radius: 18px;
        position: relative;
        font-size: 0.95rem;
        line-height: 1.6;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .user .bubble {
        background: var(--user-bubble);
        color: var(--user-text);
        border-bottom-right-radius: 4px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .assistant .bubble {
        background: var(--assistant-bubble);
        color: var(--assistant-text);
        border-bottom-left-radius: 4px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        backdrop-filter: blur(10px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .assistant .bubble::before {
        content: '🤖';
        position: absolute;
        left: -30px;
        top: 0;
        font-size: 1.2rem;
    }

    /* Thinking Animation */
    .typing-dots {
        display: flex;
        gap: 4px;
        padding: 4px 0;
    }

    .dot {
        width: 6px;
        height: 6px;
        background: #94A3B8;
        border-radius: 50%;
        animation: dotPulse 1.4s infinite;
    }

    .dot:nth-child(2) {
        animation-delay: 0.2s;
    }

    .dot:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes dotPulse {

        0%,
        100% {
            opacity: 0.3;
            transform: scale(1);
        }

        50% {
            opacity: 1;
            transform: scale(1.2);
        }
    }

    @keyframes messageIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Input Bar */
    .chat-footer {
        padding: 1.5rem;
        background: var(--chat-footer-bg);
    }

    .input-wrapper {
        background: var(--input-wrapper-bg);
        border: 1px solid rgba(59, 130, 246, 0.3);
        border-radius: 16px;
        padding: 6px;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s;
        backdrop-filter: blur(15px);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }

    .input-wrapper:focus-within {
        border-color: rgba(59, 130, 246, 0.6);
        box-shadow: 0 0 20px rgba(37, 99, 235, 0.2);
        background: var(--input-wrapper-focus);
    }

    .input-wrapper input {
        flex: 1;
        background: transparent !important;
        border: none !important;
        color: var(--input-text) !important;
        padding: 10px 15px;
        font-size: 1rem;
        outline: none !important;
        box-shadow: none !important;
        margin-bottom: 0 !important;
    }

    .send-btn {
        background: #2563EB;
        color: white;
        border: none;
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .send-btn:hover {
        background: #1D4ED8;
        transform: scale(1.05);
    }

    /* Chart Result Adjustment */
    .chart-container-bubble {
        background: var(--chart-bg);
        border-radius: 16px;
        padding: 1.5rem;
        margin-top: 1rem;
        border: 1px solid var(--chart-border);
    }

    /* SQL Display Box */
    .sql-box {
        margin-top: 10px;
        font-family: monospace;
        background: var(--sql-bg);
        padding: 10px;
        border-radius: 8px;
        font-size: 0.85rem;
        color: var(--sql-text);
        border-left: 3px solid #10B981;
    }

    .sql-box-title {
        color: #64748B;
        font-size: 0.7rem;
        margin-bottom: 5px;
        font-weight: bold;
    }
</style>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="chat-wrapper animate-in">

        <!-- Header -->
        <div class="chat-header-section">
            <div>
                <h1 style="margin:0; font-size:1.75rem; display:flex; align-items:center; gap:12px;">
                    <span
                        style="background: linear-gradient(45deg, #FF6B00, #FF9500); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Rapor
                        Pusula</span> AI
                </h1>
                <p style="margin:4px 0 0 0; color:#64748B; font-size:0.9rem;">Veritabanınızı doğal dilde sorgulayın.</p>
            </div>

            <div style="width: 280px;">
                <label
                    style="font-size: 0.7rem; color: #94A3B8; margin-bottom: 0.4rem; display: block; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Aktif
                    Kaynak</label>
                <select id="db-selector"
                    style="padding: 0.75rem; border-radius: 10px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">
                    <?php if (empty($connections)): ?>
                        <option value="">-- Atanmış Veritabanı Yok --</option>
                    <?php else: ?>
                        <?php foreach ($connections as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $defaultConnId ? 'selected' : ''; ?>>
                                🔌 <?php echo clean($c['connection_name']); ?> (<?php echo strtoupper($c['db_type']); ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- Scrollable Chat Area -->
        <div id="chat-container">
            <div class="message-row assistant">
                <div class="bubble">
                    Hoş geldiniz! Ben <strong>Rapor Pusula</strong> yapay zeka asistanıyım. <br>
                    Hangi verileri analiz etmek istersiniz? Örneğin: "<em>Geçen aya ait toplam satışları getir</em>" veya "<em>En çok sipariş veren 5 müşteriyi listele</em>" diyebilirsiniz.
                </div>
            </div>
        </div>

        <!-- Fixed Footer Input -->
        <div class="chat-footer">
            <form id="chat-form">
                <div class="input-wrapper">
                    <input type="text" id="user-input" placeholder="Bir soru sorun..." required autocomplete="off">
                    <button type="submit" class="send-btn" title="Gönder">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
            </form>
            <div style="text-align: center; margin-top: 10px; font-size: 0.75rem; color: #475569;">
                Rapor Pusula AI verilerinizi güvenli bir şekilde analiz eder.
            </div>
        </div>

    </div>
</div>

<script>
    const chatContainer = document.getElementById('chat-container');
    const chatForm = document.getElementById('chat-form');
    const userInput = document.getElementById('user-input');

    function setInput(text) {
        userInput.value = text;
        userInput.focus();
    }

    chatForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const message = userInput.value.trim();
        if (!message) return;

        addMessage(message, 'user');
        userInput.value = '';

        const dbId = document.getElementById('db-selector').value;
        if (!dbId) {
            addMessage('Lütfen önce bir veritabanı seçin.', 'assistant');
            return;
        }

        const loadingId = addLoading();

        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'chat', message: message, db_id: dbId })
            });

            const data = await response.json();
            removeLoading(loadingId);

            if (data.success) {
                let reply = data.reply;

                // 1. SQL Gösterimi
                if (data.sql) {
                    reply += `<div class="sql-box">
                                <div class="sql-box-title">ÇALIŞTIRILAN SQL SORGUSU</div>
                                ${data.sql}
                              </div>`;
                }

                // 2. Açıklama Gösterimi
                if (data.explanation) {
                    reply += `<div class="explanation-box">
                                <div class="explanation-title">💡 NASIL YAPTIM?</div>
                                ${data.explanation}
                              </div>`;
                }

                // 3. Öneri Gösterimi
                if (data.suggestions && data.suggestions.length > 0) {
                    reply += `<div class="suggestion-chips">`;
                    data.suggestions.forEach(q => {
                        reply += `<button class="chip" onclick="setInput('${q}')">${q}</button>`;
                    });
                    reply += `</div>`;
                }

                addMessage(reply, 'assistant');
                if (data.chart) renderChart(data.chart);
            } else {
                addMessage('**Hata:** ' + data.error, 'assistant');
            }

        } catch (err) {
            removeLoading(loadingId);
            addMessage('Bir bağlantı hatası oluştu. Lütfen tekrar deneyin.', 'assistant');
        }
    });

    function addMessage(html, role) {
        const row = document.createElement('div');
        row.className = `message-row ${role}`;

        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        // Simple MD-like bolding support
        bubble.innerHTML = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');

        row.appendChild(bubble);
        chatContainer.appendChild(row);
        scrollToBottom();
    }

    function addLoading() {
        const id = 'loading-' + Date.now();
        const row = document.createElement('div');
        row.className = 'message-row assistant';
        row.id = id;

        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.innerHTML = `
            <div class="typing-dots">
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
            </div>
            <span style="font-size:0.8rem; color:#64748B;">Analiz ediliyor...</span>
        `;

        row.appendChild(bubble);
        chatContainer.appendChild(row);
        scrollToBottom();
        return id;
    }

    function removeLoading(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    function scrollToBottom() {
        chatContainer.scrollTo({ top: chatContainer.scrollHeight, behavior: 'smooth' });
    }

    function renderChart(config) {
        const wrapper = document.createElement('div');
        wrapper.className = 'message-row assistant';

        const card = document.createElement('div');
        card.className = 'bubble chart-container-bubble';
        card.style.maxWidth = '90%';

        const canvas = document.createElement('canvas');
        card.appendChild(canvas);
        wrapper.appendChild(card);
        chatContainer.appendChild(wrapper);

        new Chart(canvas, {
            type: config.type,
            data: config.data,
            options: config.options
        });
        scrollToBottom();
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>