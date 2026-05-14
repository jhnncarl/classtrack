<?php

require_once 'env.php';

class Database {
    private $host;
    private $port;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    
    public $conn;
    
    public function __construct() {
        $this->host = envValue('DB_HOST', 'localhost');
        $this->port = envValue('DB_PORT', '3306');
        $this->dbname = envValue('DB_DATABASE', 'classtrack_db');
        $this->username = envValue('DB_USERNAME', 'root');
        $this->password = envValue('DB_PASSWORD', '');
        $this->charset = envValue('DB_CHARSET', 'utf8mb4');
        $this->connect();
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";
            
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
