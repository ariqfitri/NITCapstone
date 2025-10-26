<?php
$servername = getenv('DB_HOST') ?: 'database';
$username = getenv('DB_USER') ?: 'kidssmart_user';
$password = getenv('DB_PASSWORD') ?: 'SecurePass123!';
$dbname = getenv('DB_NAME') ?: 'kidssmart_app';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
