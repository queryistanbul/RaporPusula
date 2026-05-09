<?php
require_once __DIR__ . '/../src/auth.php';

if (Auth::check()) {
    redirect('chat.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username']);
    $password = clean($_POST['password']);

    $auth = new Auth();
    $result = $auth->login($username, $password);

    if ($result['success']) {
        if ($_SESSION['user']['must_change_password']) {
            redirect('change_password.php');
        }
        redirect('chat.php');
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Giriş Yap -
        <?php echo APP_NAME; ?>
    </title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-page {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #0F172A 0%, #1a1f3a 50%, #0F172A 100%);
        }

        .login-box {
            width: 100%;
            max-width: 440px;
            padding: 2rem;
        }
    </style>
</head>

<body>
    <div class="login-page">
        <div class="login-box">
            <!-- Logo Area -->
            <div style="text-align: center; margin-bottom: 2.5rem;">
                <svg width="64" height="64" viewBox="0 0 42 42" fill="none" xmlns="http://www.w3.org/2000/svg"
                    style="margin-bottom: 1rem;">
                    <rect width="42" height="42" rx="10" fill="url(#logo_bg_login)" />
                    <circle cx="21" cy="21" r="12" stroke="#FFF" stroke-width="2" stroke-opacity="0.9" />
                    <circle cx="21" cy="21" r="7" stroke="#FFF" stroke-width="1.5" stroke-opacity="0.7" />
                    <path d="M21 9 L23 18 L21 16 L19 18 Z" fill="#FFFFFF" />
                    <path d="M21 33 L23 24 L21 26 L19 24 Z" fill="rgba(255,255,255,0.9)" />
                    <path d="M9 21 L18 19 L16 21 L18 23 Z" fill="rgba(255,255,255,0.9)" />
                    <path d="M33 21 L24 19 L26 21 L24 23 Z" fill="rgba(255,255,255,0.9)" />
                    <circle cx="21" cy="21" r="2.5" fill="#FFFFFF" />
                    <defs>
                        <linearGradient id="logo_bg_login" x1="0" y1="0" x2="42" y2="42">
                            <stop offset="0%" stop-color="#FF6B00" />
                            <stop offset="100%" stop-color="#FF9500" />
                        </linearGradient>
                    </defs>
                </svg>
                <div style="font-size:1.75rem; font-weight:800; color:#E2E8F0; letter-spacing:-0.5px;">Rapor Pusula AI
                </div>
                <div style="font-size:1rem; color:#94A3B8; font-weight:400; margin-top:0.25rem;">Kurumsal Veri Analitiği
                    Platformu</div>
            </div>

            <div class="glass-card">
                <div style="text-align:center; margin-bottom:1.5rem;">
                    <h3 style="margin:0; font-size:1.5rem; font-weight:700; color:white;">Hoş Geldiniz</h3>
                    <p style="margin:0.5rem 0 0 0; color:#94A3B8; font-size:0.9rem;">Devam etmek için lütfen giriş yapın
                    </p>
                </div>

                <?php if ($error): ?>
                    <div
                        style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.5); color: #FCA5A5; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-4">
                        <label>KULLANICI ADI</label>
                        <input type="text" name="username" placeholder="E-posta veya kullanıcı adı" required>
                    </div>
                    <div class="mb-4">
                        <label>ŞİFRE</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Giriş Yap</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>