<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'database';
        $this->db_name = getenv('DB_NAME') ?: 'kidssmart_users'; 
        $this->username = getenv('DB_USER') ?: 'app_user';
        $this->password = getenv('DB_PASSWORD') ?: 'AppPass123!';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            // Don't display errors to users in production
            echo "Database connection failed. Please try again later.";
        }
        return $this->conn;
    }

    // Method to get connection to activities database
    public function getAppConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=kidssmart_app;charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            echo "Database connection failed. Please try again later.";
        }
        return $this->conn;
    }
}
?>