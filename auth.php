<?php
require_once 'config.php';

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function register($name, $email, $password) {
        try {
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email already registered'];
            }
            
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (name, email, password, status, is_verified, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())'
            );
            
            $stmt->execute([$name, $email, $hashedPassword, 'active', 1]);
            
            return ['success' => true, 'message' => 'Registration successful!'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare('SELECT id, name, email, password, status FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Email not found'];
            }
            
            if ($user['status'] === 'blocked') {
                return ['success' => false, 'message' => 'Account blocked'];
            }
            
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid password'];
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            $this->pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
            
            return ['success' => true, 'message' => 'Login successful'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}

$auth = new Auth($pdo);
?>
