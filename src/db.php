<?php
// src/db.php

class DB
{
    private $pdo;
    private $error;

    public function __construct($host = DB_HOST, $dbname = DB_NAME, $user = DB_USER, $pass = DB_PASS, $type = 'mysql', $port = 3306)
    {
        if ($type === 'mysql') {
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        } elseif ($type === 'mssql') {
            // MSSQL için dsn (sqlsrv veya dblib kullanılabilir)
            $dsn = "sqlsrv:Server=$host,$port;Database=$dbname";
        } else {
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new Exception("Veritabanı Bağlantı Hatası: " . $e->getMessage());
        }
    }

    // Tekil sorgu çalıştırma
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Sorgu Hatası: " . $e->getMessage());
        }
    }

    // Tek satır getir
    public function fetch($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    // Tüm satırları getir
    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }

    // Tek değer getir
    public function fetchColumn($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    // Son eklenen ID
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
}

// Global Auth DB Bağlantısı (Kullanıcı işlemleri için)
function getAuthDB()
{
    static $authDB = null;
    if ($authDB === null) {
        $authDB = new DB(AUTH_DB_HOST, AUTH_DB_NAME, AUTH_DB_USER, AUTH_DB_PASS);
    }
    return $authDB;
}
?>