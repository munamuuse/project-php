<?php
/**
 * Database Configuration
 */

class Database {
    private $host = "localhost";
    private $db_name = "citizen_system";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5
                ]
            );
        } catch(PDOException $exception) {
            // Don't echo directly - let calling code handle errors
            error_log("Database connection error: " . $exception->getMessage());
            throw $exception; // Re-throw so calling code can handle it
        }

        return $this->conn;
    }
}

