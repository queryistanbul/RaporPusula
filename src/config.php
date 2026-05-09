<?php
// src/config.php

// Veritabanı Ayarları
require_once __DIR__ . '/DotEnv.php';
try {
    DotEnv::load(__DIR__ . '/../.env');
} catch (Exception $e) {
    // .env dosyası yoksa veya hata varsa, varsayılanları kullanabilir veya hata verebiliriz
    // Şimdilik sessizce devam et veya logla
    error_log($e->getMessage());
}

// Veritabanı Ayarları
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'aitest');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// Auth Veritabanı (Kullanıcılar ve Ayarlar)
define('AUTH_DB_HOST', getenv('AUTH_DB_HOST') ?: '127.0.0.1');
define('AUTH_DB_NAME', getenv('AUTH_DB_NAME') ?: 'a_airapor_dev');
define('AUTH_DB_USER', getenv('AUTH_DB_USER') ?: 'root');
define('AUTH_DB_PASS', getenv('AUTH_DB_PASS') ?: '');

// Şifreleme Anahtarları
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: 'default-secret-key');
define('ENCRYPTION_IV', getenv('ENCRYPTION_IV') ?: 'default-iv-key');

// Uygulama Ayarları
define('APP_NAME', 'Rapor Pusula AI');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/a_airapor_dev/public');

// Hata Raporlama (Geliştirme ortamı için açık)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session Başlatma
if (session_status() === PHP_SESSION_NONE) {
    // Güvenli Session Ayarları
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax' // Strict bazen redirectlerde sorun olabilir, Lax daha güvenli bir varsayılan
    ]);
    session_name('AIRAPOR_SESSION');
    session_start();
}
?>