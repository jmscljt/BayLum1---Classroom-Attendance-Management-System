<?php
$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
$port = $_ENV['DB_PORT'] ?? getenv('DB_PORT');
$db   = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
$user = $_ENV['DB_USER'] ?? getenv('DB_USER');
$pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS');

// Use XAMPP defaults only when no deployment database variables are configured.
if ($host === false || $host === '') {
    $host = '127.0.0.1';
}
if ($port === false || $port === '') {
    $port = '3306';
}
if ($db === false || $db === '') {
    $db = 'bl1es_db';
}
if ($user === false || $user === '') {
    $user = 'root';
}
if ($pass === false) {
    $pass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD');
}
$pass = $pass === false ? '' : $pass;
$sslCa = $_ENV['DB_SSL_CA'] ?? getenv('DB_SSL_CA');
$ifMissing = $sslCa === false || $sslCa === '';
$bundledSslCa = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'certs' . DIRECTORY_SEPARATOR . 'isrgrootx1.pem';
$isLocalHost = in_array(strtolower((string) $host), ['127.0.0.1', 'localhost', '::1'], true);
if ($ifMissing && !$isLocalHost && is_file($bundledSslCa)) {
    $sslCa = $bundledSslCa;
}
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 10,
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