<?php
// src/Controllers/ChatController.php

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../ai_engine.php';
require_once __DIR__ . '/../Services/ProcessLogger.php';

class ChatController
{
    public function handle($input)
    {
        $user = Auth::user();
        if (!$user) {
            json_response(['success' => false, 'error' => 'Oturum gerekli'], 401);
        }

        $db = getAuthDB();
        $logger = new ProcessLogger($db, $user['id']);
        $requestId = $logger->getRequestId();

        $message = $input['message'] ?? '';
        $logger->log('Kullanıcı Sorusu', $message);

        // 1. Veritabanı Bağlantısını Bul
        $selectedDbId = $input['db_id'] ?? null;
        if (!$selectedDbId) {
            $logger->log('Hata', 'Veritabanı ID belirtilmedi.');
            return ['success' => false, 'error' => 'Veritabanı ID belirtilmedi.'];
        }

        // Yetki Kontrolü
        if ($user['role'] === 'viewer') {
            $hasAccess = $db->fetchColumn("SELECT COUNT(*) FROM viewer_assignments WHERE viewer_user_id = ? AND connection_id = ?", [$user['id'], $selectedDbId]);
            if (!$hasAccess) {
                $logger->log('Hata', 'Bu veritabanına erişim yetkiniz yok.');
                return ['success' => false, 'error' => 'Bu veritabanına erişim yetkiniz yok.'];
            }
        }

        // Bağlantı Bilgilerini Getir
        $connInfo = $db->fetch("SELECT * FROM auth_user_connections WHERE id = ?", [$selectedDbId]);
        if (!$connInfo) {
            $logger->log('Hata', 'Veritabanı bağlantısı bulunamadı.');
            return ['success' => false, 'error' => 'Veritabanı bağlantısı bulunamadı.'];
        }

        $dbPass = decrypt_value($connInfo['db_password_enc']);

        // 2. Hedef Veritabanına Bağlan
        try {
            $targetDB = new DB($connInfo['host'], $connInfo['db_name'], $connInfo['db_user'], $dbPass, $connInfo['db_type'], $connInfo['port']);
        } catch (Exception $e) {
            $logger->log('Bağlantı Hatası', $e->getMessage());
            return ['success' => false, 'error' => 'Hedef veritabanına bağlanılamadı: ' . $e->getMessage()];
        }

        // 3. Şema Bilgisini Çek (Detaylı - Kolonlar ve Tipler)
        try {
            $tables = $targetDB->fetchAll("SHOW TABLES");
            $schemaText = "Aşağıdaki veritabanı şemasını kullan:\n\n";

            foreach ($tables as $t) {
                $tableName = array_values($t)[0];
                $schemaText .= "TABLO: $tableName\n";
                $schemaText .= "KOLONLAR:\n";

                // Kolonları çek
                $columns = $targetDB->fetchAll("SHOW COLUMNS FROM $tableName");
                foreach ($columns as $col) {
                    $colName = $col['Field'];
                    $colType = $col['Type'];
                    $colKey = $col['Key']; // PRI, MUL vs.

                    $keyInfo = "";
                    if ($colKey === 'PRI')
                        $keyInfo = " (PRIMARY KEY)";
                    if ($colKey === 'MUL')
                        $keyInfo = " (FOREIGN KEY olabilir)";

                    $schemaText .= "- $colName ($colType)$keyInfo\n";
                }
                $schemaText .= "\n";
            }
        } catch (Exception $e) {
            $schemaText = "Şema alınamadı: " . $e->getMessage();
        }
        $logger->log('Veritabanı Şeması', $schemaText);

        // 4. AI Engine ile SQL Üret (Gelişmiş - Chain of Thought)
        $ai = new AIEngine();
        $rules = $connInfo['business_rules'] ?: '';

        $systemPrompt = "Sen kıdemli bir veritabanı uzmanısın. Görevin, verilen doğal dil sorusunu işletme kurallarına ve veritabanı şemasına uygun, optimize edilmiş bir MySQL sorgusuna çevirmektir.

ŞEMA BİLGİSİ:
$schemaText

İŞ KURALLARI:
$rules

TALİMATLAR:
1. Adım adım düşün. Önce hangi tabloların gerektiğini belirle, sonra bu tabloları nasıl birleştireceğini (JOIN) planla.
2. Karmaşık sorular için CTE (Common Table Expressions) veya Subquery kullanmaktan çekinme.
3. Tarih/Saat işlemlerinde dikkatli ol ve fonksiyonları doğru kullan.
4. Sadece SQL kodu üret. Markdown yok, açıklama yok.
5. Kullanıcı 'satışlar' dediğinde 'orders', 'sales' gibi tabloları; 'müşteriler' dediğinde 'users', 'customers' gibi tabloları kontrol et (Semantic Matching yap).

SORGU:";

        $logger->log('AI SQL Üretim Promptu', ["system" => $systemPrompt, "user" => $message]);

        $sqlResult = $ai->generateResponse($message, $systemPrompt);

        if (!$sqlResult['success']) {
            $logger->log('AI SQL Üretim Hatası', $sqlResult['error']);
            return ['success' => false, 'error' => 'AI Hatası: ' . $sqlResult['error']];
        }

        // Helper to extract SQL
        $cleanSQL = function ($text) {
            // 1. Try to find markdown block
            if (preg_match('/```sql\s*(.*?)\s*```/is', $text, $matches)) {
                return trim($matches[1]);
            }
            if (preg_match('/```\s*(.*?)\s*```/is', $text, $matches)) {
                return trim($matches[1]);
            }

            // 2. Find first SELECT or WITH
            $pSelect = stripos($text, 'SELECT');
            $pWith = stripos($text, 'WITH');

            if ($pSelect !== false && ($pWith === false || $pSelect < $pWith)) {
                return trim(substr($text, $pSelect));
            }
            if ($pWith !== false && ($pSelect === false || $pWith < $pSelect)) {
                return trim(substr($text, $pWith));
            }

            return trim($text);
        };

        $sql = $cleanSQL($sqlResult['text']);
        $logger->log('Üretilen SQL (Temizlenmiş)', $sql);

        // 5. Sorguyu Çalıştır (Hata Düzeltme Döngüsü ile)
        $maxRetries = 3;
        $attempt = 0;
        $success = false;
        $errorMsg = '';
        $data = [];

        while ($attempt < $maxRetries && !$success) {
            try {
                // Güvenlik Kontrolü: Sadece SELECT veya WITH ile başlamalı
                $upperSQL = strtoupper($sql);
                if (strpos($upperSQL, 'SELECT') !== 0 && strpos($upperSQL, 'WITH') !== 0) {
                    throw new Exception("Sadece SELECT veya WITH ile başlayan sorgular çalıştırılabilir. Üretilen: " . substr($sql, 0, 50) . "...");
                }
                $data = $targetDB->fetchAll($sql);
                $success = true;
                $logger->log('SQL Sorgu Sonucu', ["count" => count($data), "sample" => array_slice($data, 0, 5)]);
            } catch (Exception $e) {
                $errorMsg = $e->getMessage();
                $logger->log("SQL Hatası (Deneme " . ($attempt + 1) . ")", $errorMsg);

                $attempt++;
                if ($attempt < $maxRetries) {
                    $logger->log('AI Hata Düzeltme İsteği', "Hatalı SQL: $sql, Hata: $errorMsg");
                    $repairResult = $ai->repairSQL($errorMsg, $sql, $schemaText);

                    if ($repairResult['success']) {
                        $sql = $cleanSQL($repairResult['text']);
                        $logger->log('Düzeltilmiş SQL', $sql);
                    } else {
                        $logger->log('Hata Düzeltme Başarısız', $repairResult['error']);
                        break; // AI düzeltemedi, çık
                    }
                }
            }
        }

        if (!$success) {
            return ['success' => false, 'error' => "Sorgu çalıştırılamadı ($attempt deneme sonrası): " . $errorMsg, 'sql' => $sql];
        }

        // 6. Sonucu Yorumla ve Açıkla
        $dataStr = json_encode(array_slice($data, 0, 10));

        // Paralel çalıştırılabilecek işlemler (PHP'de senkron ama mantıksal olarak ayrı)
        $interpretPrompt = "Kullanıcı sorusu: '$message'.\nSQL: $sql\nSonuçlar: $dataStr\n\nBu sonuçları kullanıcıya profesyonelce özetle.";
        $interpretResult = $ai->generateResponse($interpretPrompt);
        $reply = $interpretResult['success'] ? $interpretResult['text'] : "Sonuçlar: " . count($data) . " satır bulundu.";

        // Açıklama İste
        $explanationResult = $ai->explainSQL($sql);
        $explanation = $explanationResult['success'] ? $explanationResult['text'] : null;

        // Öneri İste
        $suggestionsResult = $ai->suggestQuestions($schemaText, $message, $data);
        $suggestions = $suggestionsResult['success'] ? $suggestionsResult['suggestions'] : [];

        $logger->log('Final AI Cevabı', ['reply' => $reply, 'explanation' => $explanation, 'suggestions' => $suggestions]);

        // 7. Log to Chat History
        try {
            $db->query(
                "INSERT INTO chat_history (user_id, prompt, sql_query, response, request_id) VALUES (?, ?, ?, ?, ?)",
                [$user['id'], $message, $sql, $reply, $requestId]
            );
        } catch (Exception $e) {
            error_log("Chat History Log Error: " . $e->getMessage());
        }

        return [
            'success' => true,
            'reply' => $reply,
            'sql' => $sql,
            'explanation' => $explanation,
            'suggestions' => $suggestions,
            'request_id' => $requestId,
            'chart' => null
        ];
    }
}
?>