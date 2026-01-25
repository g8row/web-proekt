<?php
// config.php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP/MAMP password often empty
define('DB_NAME', 'fmi_parking');

// Attempt connection to create DB if not exists (for setup convenience) or connect to it
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    // connect to the specific db
    $conn->select_db(DB_NAME);
} else {
    die("Error creating database: " . $conn->error);
}

session_start();
?>
