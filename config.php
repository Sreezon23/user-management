<?php
if (defined('CONFIG_LOADED')) {
    return;
}
define('CONFIG_LOADED', true);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'user_management');

define('GMAIL_USER', 'sreezon51@gmail.com');
define('GMAIL_PASS', 'odoi lglz cvun gprs');
define('SITE_URL', 'http://localhost/user-management/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
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
        $stmt = $pdo->prepare('SELECT status FROM users WHERE id = ? AND status = "blocked"');
        $stmt->execute([$userId]);
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
