<?php
require_once __DIR__ . '/config.php';

class Database {
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            $this->conn->exec("SET time_zone = '+07:00'");
        } catch (PDOException $exception) {
            app_log('Database connection failed: ' . $exception->getMessage());
            throw new RuntimeException('Không thể kết nối cơ sở dữ liệu.', 0, $exception);
        }
        return $this->conn;
    }
}
?>