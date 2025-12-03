<?php
require 'config.php';

echo "<h2>✅ Testing Your Setup</h2>";

// Test 1: Database Connection
try {
    $pdo->query("SELECT 1");
    echo "✅ Database connection OK<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 2: Users Table
try {
    $result = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $result->fetch()['count'];
    echo "✅ Users table OK (Total users: {$count})<br>";
} catch (Exception $e) {
    echo "❌ Table error: " . $e->getMessage() . "<br>";
}

// Test 3: PHPMailer
if (file_exists('vendor/autoload.php')) {
    echo "✅ Composer dependencies OK<br>";
} else {
    echo "❌ Run: composer install<br>";
}

echo "<h3>Now test:</h3>";
echo "<a href='register.php'>Go to Register Page</a>";
?>
