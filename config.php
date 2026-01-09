<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bvrank');

// Create connection
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'message' => 'Kết nối thất bại: ' . $conn->connect_error]));
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Create table if not exists
function initDatabase() {
    $conn = getDbConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS calendar_days_off (
        id INT AUTO_INCREMENT PRIMARY KEY,
        day_date DATE NOT NULL UNIQUE,
        note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_date (day_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        return false;
    }
}

// Initialize database on first load
initDatabase();
?>
