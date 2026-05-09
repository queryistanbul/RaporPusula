<?php
// src/auth.php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

class Auth
{
    private $db;

    public function __construct()
    {
        $this->db = getAuthDB();
    }

    // Giriş Yap
    public function login($username, $password)
    {
        $sql = "SELECT * FROM auth_users WHERE username = ?";
        $user = $this->db->fetch($sql, [$username]);

        if (!$user) {
            return ['success' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }

        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Hesap devre dışı.'];
        }

        if (verify_password($password, $user['password_hash'])) {
            // Başarılı giriş
            unset($user['password_hash']); // Hash'i session'a koyma
            $_SESSION['user'] = $user;
            $_SESSION['last_activity'] = time();

            // Son giriş zamanını güncelle
            $this->db->query("UPDATE auth_users SET last_login = NOW() WHERE id = ?", [$user['id']]);

            return ['success' => true, 'message' => 'Giriş başarılı.'];
        }

        return ['success' => false, 'message' => 'Hatalı şifre.'];
    }

    // Kayıt Ol
    public function register($username, $password, $displayName, $role = 'viewer')
    {
        // Kullanıcı var mı kontrol et
        $exists = $this->db->fetch("SELECT id FROM auth_users WHERE username = ?", [$username]);
        if ($exists) {
            return ['success' => false, 'message' => 'Bu kullanıcı adı zaten alınmış.'];
        }

        $hash = hash_password($password);

        try {
            $this->db->query(
                "INSERT INTO auth_users (username, password_hash, display_name, role) VALUES (?, ?, ?, ?)",
                [$username, $hash, $displayName, $role]
            );
            return ['success' => true, 'message' => 'Kullanıcı oluşturuldu.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Kayıt hatası: ' . $e->getMessage()];
        }
    }

    // Şifre Değiştir
    public function changePassword($userId, $newPassword)
    {
        $hash = hash_password($newPassword);
        $this->db->query(
            "UPDATE auth_users SET password_hash = ?, must_change_password = 0 WHERE id = ?",
            [$hash, $userId]
        );
        return ['success' => true, 'message' => 'Şifre güncellendi.'];
    }

    // Çıkış Yap
    public static function logout()
    {
        session_destroy();
        redirect('login.php');
    }

    // Giriş yapmış mı?
    public static function check()
    {
        return isset($_SESSION['user']);
    }

    // Mevcut kullanıcı
    public static function user()
    {
        return $_SESSION['user'] ?? null;
    }
}
?>