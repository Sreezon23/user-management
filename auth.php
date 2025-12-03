<?php
require 'config.php';
require 'EmailSender.php';

class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function register($name, $email, $password) {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $token = bin2hex(random_bytes(32));

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (name, email, password, verification_token, status) 
                 VALUES (?, ?, ?, ?, "unverified")'
            );
            $stmt->execute([$name, $email, $hashedPassword, $token]);

            try {
                $emailSender = new EmailSender();
                $emailSender->sendVerificationEmail($email, $token);
            } catch (Exception $e) {

                error_log($e->getMessage());
            }

            return ['success' => true, 'message' => 'Registration successful. Check your email to verify.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }

    public function login($email, $password) {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, password, status FROM users WHERE email = ?'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        if ($user['status'] === 'blocked') {
            return ['success' => false, 'message' => 'Your account is blocked'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        $updateStmt = $this->pdo->prepare(
            'UPDATE users SET last_login = NOW() WHERE id = ?'
        );
        $updateStmt->execute([$user['id']]);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];

        return ['success' => true, 'message' => 'Login successful'];
    }

    public function verifyEmail($token) {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET status = "active", is_verified = 1, verification_token = NULL 
             WHERE verification_token = ?'
        );
        $result = $stmt->execute([$token]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Invalid or expired token'];
        }

        return ['success' => true, 'message' => 'Email verified successfully'];
    }

    public function logout() {
        session_destroy();
        return true;
    }
}
?>
