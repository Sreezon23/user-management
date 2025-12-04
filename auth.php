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
            $verificationToken = bin2hex(random_bytes(16));
            $status = 'active';
            $isVerified = 0;

            $stmt = $this->pdo->prepare(
                'INSERT INTO users (name, email, password, status, verification_token, is_verified, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            
            $stmt->execute([$name, $email, $hashedPassword, $status, $verificationToken, $isVerified]);

            require_once __DIR__ . '/EmailSender.php';
            $sender = new EmailSender();

            try {
                $sender->sendVerificationEmail($email, $verificationToken);
            } catch (Exception $e) {
                error_log('Register: verification email failed: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Email error: ' . $e->getMessage()
                ];
            }

            return [
                'success' => true,
                'message' => 'Registration successful! Please check your email to verify your account.'
            ];
            
        } catch (Exception $e) {
            error_log('Register error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
public function login($email, $password) {
    try {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, password, status, is_verified FROM users WHERE email = ?'
        );
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
        
        $this->pdo
            ->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')
            ->execute([$user['id']]);
        
        return ['success' => true, 'message' => 'Login successful'];
        
    } catch (Exception $e) {
        error_log('Login error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}
    
    public function verifyEmail($token) {
        try {
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE verification_token = ?');
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid verification token'];
            }
            
            $this->pdo->prepare(
                'UPDATE users SET is_verified = 1, verification_token = NULL, updated_at = NOW() WHERE id = ?'
            )->execute([$user['id']]);
            
            return ['success' => true, 'message' => 'Email verified successfully!'];
            
        } catch (Exception $e) {
            error_log('Verify email error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}

$auth = new Auth($pdo);
?>
