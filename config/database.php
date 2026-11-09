<?php
// config/database.php

// Load file .env secara native (tanpa perlu library tambahan)
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
$dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'gym_membership';
$username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
$appDebug = filter_var($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set PDO error mode to exception for better debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Prevent emulated prepares for better security
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Create vip_requests table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS vip_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        regular_member_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        status ENUM('pending', 'approved') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (regular_member_id) REFERENCES regular_members(id) ON DELETE CASCADE
    )");
} catch (PDOException $e) {
    if ($appDebug) {
        die("Koneksi Database Gagal: " . $e->getMessage());
    } else {
        error_log("Database Connection Error: " . $e->getMessage());
        die("Maaf, sedang terjadi gangguan koneksi pada server.");
    }
}
?>
