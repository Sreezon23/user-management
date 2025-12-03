<?php
require 'config.php';

echo "<h2> Testing Your Setup....</h2>";

try {
    $pdo->query("SELECT 1");
    echo "Database connection is OK!<br>";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

try {
    $result = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $result->fetch()['count'];
    echo "Users table OK (Total users: {$count})<br>";
} catch (Exception $e) {
    echo "Table error: " . $e->getMessage() . "<br>";
}

if (file_exists('vendor/autoload.php')) {
    echo "Composer dependencies OK<br>";
} else {
    echo "Run: composer install<br>";
}

echo "<h3>Now test:</h3>";
echo "<a href='register.php'>Go to Register Page</a>";
?>
