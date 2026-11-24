<?php
$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
$port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '4000';
$db   = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'bl1es_database';
$user = $_ENV['DB_USER'] ?? getenv('DB_USER');
$pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS');
if ($pass === false) {
    $pass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD');
}
$pass = $pass === false ? '' : $pass;
$sslCa = $_ENV['DB_SSL_CA'] ?? getenv('DB_SSL_CA');
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
if ($sslCa !== false && $sslCa !== '') {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Check the deployment database settings.');
}
?>