<?php
// src/Services/ProcessLogger.php

class ProcessLogger
{
    private $db;
    private $requestId;
    private $userId;

    public function __construct($db, $userId, $requestId = null)
    {
        $this->db = $db;
        $this->userId = $userId;
        $this->requestId = $requestId ?: uniqid('req_');
    }

    public function log($stepName, $content)
    {
        try {
            $this->db->query(
                "INSERT INTO ai_process_logs (request_id, user_id, step_name, content) VALUES (?, ?, ?, ?)",
                [$this->requestId, $this->userId, $stepName, is_string($content) ? $content : json_encode($content, JSON_UNESCAPED_UNICODE)]
            );
        } catch (Exception $e) {
            error_log("Logging Error: " . $e->getMessage());
        }
    }

    public function getRequestId()
    {
        return $this->requestId;
    }
}
?>