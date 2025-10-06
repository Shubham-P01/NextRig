<?php
// Set the default timezone for the entire application to India Standard Time
date_default_timezone_set('Asia/Kolkata');

// Database credentials
$host = 'localhost';
$dbname = 'nextrig'; // Your database name
$username = 'root';        // Your database username
$password = '';            // Your database password
$charset = 'utf8mb4';

// Data Source Name (DSN)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, $username, $password);
    
} catch (\PDOException $e) {
    // This will catch connection errors
    die("Connection failed: " . $e->getMessage());
}
?>