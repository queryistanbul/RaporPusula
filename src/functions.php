<?php
// src/functions.php

require_once __DIR__ . '/config.php';

// Debug helpers
function dd($data)
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    die();
}

// Redirect helper
function redirect($url)
{
    header("Location: " . APP_URL . "/$url");
    exit();
}

// Input cleaning
function clean($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

// Şifreleme Anahtarları config.php içinde tanımlandı
// define('ENCRYPTION_KEY', ...);
// define('ENCRYPTION_IV', ...);

// Şifreleme (AES-256-CBC)
function encrypt_value($plaintext)
{
    if (empty($plaintext))
        return "";
    return openssl_encrypt($plaintext, "AES-256-CBC", ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}

// Şifre Çözme
function decrypt_value($ciphertext)
{
    if (empty($ciphertext))
        return "";
    return openssl_decrypt($ciphertext, "AES-256-CBC", ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}

// Parola Hash (Bcrypt - Python ile uyumlu)
function hash_password($password)
{
    return password_hash($password, PASSWORD_BCRYPT);
}

// Parola Doğrulama
function verify_password($password, $hash)
{
    return password_verify($password, $hash);
}

// JSON Yanıt Döndür
function json_response($data, $status = 200)
{
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit();
}

// Rol Kontrolü Middleware
function require_role($roles = [])
{
    if (!isset($_SESSION['user'])) {
        redirect('login.php');
    }
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    if (!empty($roles) && !in_array(strtolower($_SESSION['user']['role']), $roles)) {
        // Yetkisiz erişim
        die("Bu sayfaya erişim yetkiniz yok.");
    }
}
?>