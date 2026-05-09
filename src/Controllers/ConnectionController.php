<?php
// src/Controllers/ConnectionController.php

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../ai_engine.php';

class ConnectionController
{
    public function testAI()
    {
        $ai = new AIEngine();
        return $ai->testConnection();
    }

    public function testDB($input)
    {
        $dbId = $input['db_id'] ?? null;
        $host = '';
        $dbName = '';
        $dbUser = '';
        $dbPass = '';

        if ($dbId) {
            $user = Auth::user();
            if (!$user) {
                return ['success' => false, 'error' => 'Oturum gerekli'];
            }
            $authDB = getAuthDB();
            $connInfo = $authDB->fetch("SELECT * FROM auth_user_connections WHERE id = ? AND user_id = ?", [$dbId, $user['id']]);

            if (!$connInfo) {
                return ['success' => false, 'error' => 'Bağlantı bulunamadı.'];
            }

            $host = $connInfo['host'];
            $dbName = $connInfo['db_name'];
            $dbUser = $connInfo['db_user'];
            $dbPass = decrypt_value($connInfo['db_password_enc']);
        } else {
            $host = $input['host'] ?? '';
            $dbName = $input['db_name'] ?? '';
            $dbUser = $input['db_user'] ?? '';
            $dbPass = $input['db_password'] ?? '';
        }

        try {
            $type = $input['db_type'] ?? 'mysql';
            $port = $input['port'] ?? 3306;
            $test = new DB($host, $dbName, $dbUser, $dbPass, $type, $port);
            return ['success' => true, 'message' => 'Bağlantı başarılı!'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>