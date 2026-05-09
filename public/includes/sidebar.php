<!-- theme inline script added to document body via DOMContentLoaded below -->
<?php
// public/includes/sidebar.php

$user = Auth::user();
$role = $user['role'];
$page = basename($_SERVER['PHP_SELF'], '.php');
$theme = 'dark'; // Varsayılan tema (Session'dan alınmalı)

// Menü öğeleri
$menuItems = [
    // Herkes için
    ['url' => 'chat.php', 'icon' => '💬', 'label' => 'Sohbet', 'roles' => ['admin', 'analyst', 'viewer']],
    ['url' => 'history.php', 'icon' => '🕒', 'label' => 'Geçmiş', 'roles' => ['admin', 'analyst', 'viewer']],
    ['url' => 'logs.php', 'icon' => '📜', 'label' => 'Log Kayıtları', 'roles' => ['admin', 'analyst', 'viewer']],

    // Admin ve Analist
    ['url' => 'connections.php', 'icon' => '🗄️', 'label' => 'Veritabanı', 'roles' => ['admin', 'analyst']],
    ['url' => 'metadata.php', 'icon' => '📂', 'label' => 'İş Kuralları', 'roles' => ['admin', 'analyst']],
    ['url' => 'viewer_assign.php', 'icon' => '👥', 'label' => 'Viewer Atama', 'roles' => ['admin', 'analyst']],

    // Sadece Admin
    ['url' => 'settings.php', 'icon' => '🧠', 'label' => 'AI Ayarları', 'roles' => ['admin']],
    ['url' => 'users.php', 'icon' => '⚙️', 'label' => 'Kullanıcılar', 'roles' => ['admin']],
];

?>
<script>
    // Initial theme check to prevent flash of wrong theme before DOMContentLoaded
    if(localStorage.getItem('theme') === 'light') {
        document.body.classList.add('light-mode');
    }
</script>
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <!-- Logo SVG (Basitleştirilmiş) -->
            <svg width="40" height="40" viewBox="0 0 42 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="42" height="42" rx="10" fill="#FF6B00" />
                <circle cx="21" cy="21" r="12" stroke="#FFF" stroke-width="2" />
                <path d="M21 9 L23 18 L21 16 L19 18 Z" fill="#FFFFFF" />
                <text x="21" y="26" text-anchor="middle" fill="#FFF" font-size="6" font-weight="bold">AI</text>
            </svg>
            <div>
                <div class="logo-text" style="font-size: 1rem;">Rapor Pusula</div>
                <div class="logo-subtext">AI EDITION</div>
            </div>
        </div>
    </div>

    <div class="sidebar-menu">
        <?php foreach ($menuItems as $item): ?>
            <?php if (in_array(strtolower($role), $item['roles'])): ?>
                <a href="<?php echo $item['url']; ?>"
                    class="nav-btn <?php echo ($page == str_replace('.php', '', $item['url'])) ? 'active' : ''; ?>">
                    <span style="margin-right: 10px;">
                        <?php echo $item['icon']; ?>
                    </span>
                    <?php echo $item['label']; ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="sidebar-footer">
        <div class="d-flex align-center gap-2 mb-4">
            <div class="user-avatar"
                style="width:32px; height:32px; background:#2563EB; border-radius:8px; display:flex; align-items:center; justify-content:center; color:white; font-weight:bold;">
                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
            </div>
            <div>
                <div style="font-weight:600; font-size:0.9rem;">
                    <?php echo clean($user['display_name']); ?>
                </div>
                <div style="font-size:0.75rem; color:#94A3B8;">
                    <?php echo ucfirst($role); ?>
                </div>
            </div>
        </div>
        <a href="logout.php" class="btn btn-secondary" style="text-decoration:none; display:block;">🚪 Çıkış Yap</a>
        <button id="themeToggleBtn" class="btn btn-secondary mt-4" style="text-decoration:none; display:block;">
            <span class="theme-icon">☀️</span> <span class="theme-text">Açık Tema</span>
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const themeIcon = themeToggleBtn.querySelector('.theme-icon');
        const themeText = themeToggleBtn.querySelector('.theme-text');
        
        // Check localStorage for theme
        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'light') {
            document.body.classList.add('light-mode');
            themeIcon.textContent = '🌙';
            themeText.textContent = 'Koyu Tema';
        }

        themeToggleBtn.addEventListener('click', () => {
            document.body.classList.toggle('light-mode');
            const isLight = document.body.classList.contains('light-mode');
            
            if (isLight) {
                localStorage.setItem('theme', 'light');
                themeIcon.textContent = '🌙';
                themeText.textContent = 'Koyu Tema';
            } else {
                localStorage.setItem('theme', 'dark');
                themeIcon.textContent = '☀️';
                themeText.textContent = 'Açık Tema';
            }
        });
    });
</script>