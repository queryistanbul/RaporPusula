<?php
// src/ai_engine.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

class AIEngine
{
    private $provider;
    private $apiKey;
    private $model;
    private $baseUrl;

    public function __construct()
    {
        // Ayarları yükle
        $db = getAuthDB();
        $this->provider = $this->getSetting($db, 'AI_PROVIDER') ?: 'google';

        if ($this->provider === 'google') {
            $this->apiKey = $this->getSetting($db, 'GOOGLE_API_KEY');
            $this->model = $this->getSetting($db, 'GOOGLE_MODEL') ?: 'gemini-1.5-flash';
        } elseif ($this->provider === 'openai') {
            $this->apiKey = $this->getSetting($db, 'OPENAI_API_KEY');
            $this->model = $this->getSetting($db, 'OPENAI_MODEL') ?: 'gpt-4o';
        } elseif ($this->provider === 'deepseek') {
            $this->apiKey = $this->getSetting($db, 'DEEPSEEK_API_KEY');
            $this->model = $this->getSetting($db, 'DEEPSEEK_MODEL') ?: 'deepseek-chat';
        } elseif ($this->provider === 'minimax') {
            $this->apiKey = $this->getSetting($db, 'MINIMAX_API_KEY');
            $this->model = $this->getSetting($db, 'MINIMAX_MODEL') ?: 'MiniMax-Text-01';
        }
        // Diğer sağlayıcılar eklenebilir...
    }

    private function getSetting($db, $key)
    {
        $val = $db->fetchColumn("SELECT setting_value FROM auth_app_settings WHERE setting_key = ?", [$key]);
        return $val ? decrypt_value($val) : null;
    }

    public function testConnection()
    {
        $prompt = "Merhaba, bu bir bağlantı testidir. Sadece 'OK' cevabı ver.";
        $result = $this->generateResponse($prompt);
        if ($result['success']) {
            return ['success' => true, 'message' => 'Bağlantı başarılı: ' . $result['text']];
        }
        return ['success' => false, 'error' => $result['error']];
    }

    public function generateResponse($prompt, $systemInstruction = null)
    {
        if ($this->provider === 'google') {
            return $this->callGemini($prompt, $systemInstruction);
        } elseif ($this->provider === 'openai') {
            return $this->callOpenAI($prompt, $systemInstruction);
        } elseif ($this->provider === 'deepseek') {
            return $this->callDeepseek($prompt, $systemInstruction);
        } elseif ($this->provider === 'minimax') {
            return $this->callMinimax($prompt, $systemInstruction);
        }
        return ['success' => false, 'error' => 'Desteklenmeyen sağlayıcı.'];
    }

    private function callGemini($prompt, $systemInstruction)
    {
        if (!$this->apiKey)
            return ['success' => false, 'error' => 'Google API Key eksik.'];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $contents = [
            ['role' => 'user', 'parts' => [['text' => $prompt]]]
        ];

        // System instruction Gemini API'de ayrı bir alan ama basitlik için prompta gömebiliriz veya uygun API yapısını kullanırız.
        // v1beta'da systemInstruction parametresi var.
        $payload = [
            'contents' => $contents
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]]
            ];
        }

        $response = $this->curlRequest($url, $payload);

        if (isset($response['error'])) {
            return ['success' => false, 'error' => $response['error']['message']];
        }

        try {
            $text = $response['candidates'][0]['content']['parts'][0]['text'];
            return ['success' => true, 'text' => $text];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Yanıt İşleme Hatası: ' . json_encode($response)];
        }
    }

    private function callOpenAI($prompt, $systemInstruction)
    {
        if (!$this->apiKey)
            return ['success' => false, 'error' => 'OpenAI API Key eksik.'];

        $url = "https://api.openai.com/v1/chat/completions";

        $messages = [];
        if ($systemInstruction) {
            $messages[] = ['role' => 'system', 'content' => $systemInstruction];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.1
        ];

        $headers = [
            "Authorization: Bearer {$this->apiKey}"
        ];

        $response = $this->curlRequest($url, $payload, $headers);

        if (isset($response['error'])) {
            return ['success' => false, 'error' => $response['error']['message']];
        }

        try {
            $text = $response['choices'][0]['message']['content'];
            return ['success' => true, 'text' => $text];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Yanıt İşleme Hatası'];
        }
    }

    private function callDeepseek($prompt, $systemInstruction)
    {
        if (!$this->apiKey)
            return ['success' => false, 'error' => 'Deepseek API Key eksik.'];

        $url = "https://api.deepseek.com/chat/completions";

        $messages = [];
        if ($systemInstruction) {
            $messages[] = ['role' => 'system', 'content' => $systemInstruction];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.1
        ];

        $headers = [
            "Authorization: Bearer {$this->apiKey}"
        ];

        $response = $this->curlRequest($url, $payload, $headers);

        if (isset($response['error'])) {
            return ['success' => false, 'error' => $response['error']['message']];
        }

        try {
            $text = $response['choices'][0]['message']['content'];
            return ['success' => true, 'text' => $text];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Yanıt İşleme Hatası'];
        }
    }

    private function callMinimax($prompt, $systemInstruction)
    {
        if (!$this->apiKey)
            return ['success' => false, 'error' => 'Minimax API Key eksik.'];

        $url = "https://api.minimax.chat/v1/text/chatcompletion_v2";

        $messages = [];
        if ($systemInstruction) {
            $messages[] = ['role' => 'system', 'content' => $systemInstruction];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.1
        ];

        $headers = [
            "Authorization: Bearer {$this->apiKey}"
        ];

        $response = $this->curlRequest($url, $payload, $headers);

        if (isset($response['error'])) {
            return ['success' => false, 'error' => $response['error']['message']];
        }

        try {
            $text = $response['choices'][0]['message']['content'];
            return ['success' => true, 'text' => $text];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Yanıt İşleme Hatası'];
        }
    }

    public function repairSQL($error, $wrongSQL, $schema)
    {
        $prompt = "Aşağıdaki SQL sorgusu bir hata verdi. Lütfen şemaya ve hata mesajına bakarak sorguyu düzelt. Sadece düzeltilmiş SQL kodunu döndür. Markdown yok.\n\nŞema:\n$schema\n\nHatalı SQL:\n$wrongSQL\n\nHata Mesajı:\n$error";
        return $this->generateResponse($prompt);
    }

    public function explainSQL($sql)
    {
        $prompt = "Aşağıdaki SQL sorgusunu teknik olmayan bir kullanıcıya basitçe açıkla. Hangi tablodan neye göre veri çektiğini anlat. Çok kısa ve net ol.\n\nSQL:\n$sql";
        return $this->generateResponse($prompt);
    }

    public function suggestQuestions($schema, $userQuery, $dataSample)
    {
        $dataStr = is_array($dataSample) ? json_encode(array_slice($dataSample, 0, 3)) : '';
        $prompt = "Sen bir veri analistisin. Kullanıcı şu soruyu sordu: '$userQuery'.\nŞema:\n$schema\n\nBu analizin devamı niteliğinde, kullanıcının sorabileceği 3 kısa ve mantıklı takip sorusu öner. Sadece soruları json formatında bir liste olarak döndür. Örnek: [\"Soru 1\", \"Soru 2\", \"Soru 3\"]";

        $result = $this->generateResponse($prompt);
        if ($result['success']) {
            // JSON temizleme
            $jsonStr = str_replace(['```json', '```'], '', $result['text']);
            $suggestions = json_decode($jsonStr, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($suggestions)) {
                return ['success' => true, 'suggestions' => $suggestions];
            }
            return ['success' => false, 'error' => 'JSON parse hatası', 'raw' => $result['text']];
        }
        return $result;
    }

    private function curlRequest($url, $data, $extraHeaders = [])
    {
        $ch = curl_init($url);
        $headers = array_merge(['Content-Type: application/json'], $extraHeaders);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Geliştirme ortamı için

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            return ['error' => ['message' => 'Curl Hatası: ' . curl_error($ch)]];
        }

        curl_close($ch);
        return json_decode($result, true);
    }
}
?>