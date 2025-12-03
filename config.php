<?php
if (defined('CONFIG_LOADED')) {
    return;
}
define('CONFIG_LOADED', true);

$dbDriver = getenv('DB_DRIVER') ?: 'pgsql';
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'postgres';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'user_management';
$dbPort = getenv('DB_PORT') ?: 5432;

define('GMAIL_USER', 'sreezon51@gmail.com');
define('GMAIL_PASS', 'odoi lglz cvun gprs');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/user-management/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    if ($dbDriver === 'pgsql') {
        $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName";
    } else {
        $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    }
    
    $pdo = new PDO(
        $dsn,
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

if (!function_exists('isAuthenticated')) {
    function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('isUserBlocked')) {
    function isUserBlocked($pdo, $userId) {
        $stmt = $pdo->prepare('SELECT status FROM users WHERE id = ? AND status = ?');
        $stmt->execute([$userId, 'blocked']);
        return $stmt->rowCount() > 0;
    }
}

if (!function_exists('requireAuth')) {
    function requireAuth() {
        if (!isAuthenticated()) {
            header('Location: ' . SITE_URL . 'login.php');
            exit;
        }
    }
}
?>
