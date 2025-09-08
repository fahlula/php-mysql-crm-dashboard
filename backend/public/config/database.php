<?php
// Database configuration and connection for single-container runtime

// Read from getenv first to support Docker ENV/“-e”; fallback to $_ENV; then defaults
$host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1');
$dbname = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'crm_database');
$username = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'crm_user');
$password = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? 'crm_password');

// Force TCP if someone set localhost (avoids Unix socket permission issues)
if ($host === 'localhost') {
	$host = '127.0.0.1';
}

$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

try {
	$pdo = new PDO($dsn, $username, $password, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES => false,
	]);
	// Ensure connection uses utf8mb4 consistently
	$pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
	http_response_code(500);
	die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}