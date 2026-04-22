<?php
class Database {
    private $host = "localhost";
    private $db_name = "stud";
    private $username = "stud"; // 請修改為您的資料庫用戶名
    private $password = "12345678"; // 請修改為您的資料庫密碼
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES utf8mb4");
        } catch(PDOException $exception) {
            echo "資料庫連接錯誤: " . $exception->getMessage();
            error_log("資料庫連接錯誤: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
?>