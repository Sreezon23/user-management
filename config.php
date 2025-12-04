<?php
if (defined('CONFIG_LOADED')) {
    return;
}
define('CONFIG_LOADED', true);

$dbDriver = getenv('DB_DRIVER') ?: 'pgsql';
$dbHost = getenv('DB_HOST') ?: 'dpg-d4o6hp15pdvs73atto40-a';
$dbUser = getenv('DB_USER') ?: 'user_management_s814_user';
$dbPass = getenv('DB_PASS') ?: 'fj94DtddPlJsJuo0TbT7J74SgTITUfyn';
$dbName = getenv('DB_NAME') ?: 'user_management_s814';
$dbPort = getenv('DB_PORT') ?: 5432;

define('GMAIL_USER', getenv('GMAIL_USER') ?: 'sreezon51@gmail.com');
define('GMAIL_PASS', getenv('GMAIL_PASS') ?: 'odoi lglz cvun gprs');
define('SITE_URL', getenv('SITE_URL') ?: 'https://user-management-z4r0.onrender.com/');

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
