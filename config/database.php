<?php
/**
 * Database Configuration for ClassTrack
 * Eastern Visayas State University - Ormoc Campus
 */

class Database {
    private $host = "localhost";
    private $dbname = "classtrack_db";
    private $username = "root";
    private $password = "";
    private $charset = "utf8mb4";
    
    public $conn;
    
    public function __construct() {
        $this->connect();
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $e) {
            // Log error instead of displaying in production
            error_log("Database Connection Error: " . $e->getMessage());
            
            // For development - show error
            die("Database connection failed. Please check your database configuration.");
        }
    }
    
    /**
     * Get database connection
     * @return PDO
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Test database connection
     * @return bool
     */
    public function testConnection() {
        try {
            $this->conn->query("SELECT 1");
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    /**
     * Close database connection
     */
    public function close() {
        $this->conn = null;
    }
}

// Create global database instance
$database = new Database();
$db = $database->getConnection();
?>
