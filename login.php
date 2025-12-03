<?php
require 'config.php';
require 'auth.php';

if (isAuthenticated()) {
    header('Location: ' . SITE_URL . 'admin.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $message = 'Email and password are required';
        $messageType = 'danger';
    } else {
        $auth = new Auth($pdo);
        $result = $auth->login($email, $password);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';

        if ($result['success']) {
            header('Location: ' . SITE_URL . 'admin.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - The App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 800px;
            width: 100%;
        }
        .form-section {
            padding: 60px 40px;
        }
        .geometric-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .form-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 30px;
            color: #333;
        }
        .btn-primary {
            width: 100%;
            background: #667eea;
            border: none;
        }
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
            }
            .geometric-bg {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="form-section">
            <h2 class="form-title">Sign In To The App</h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="test@example.com" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>

                <button type="submit" class="btn btn-primary">Sign In</button>
            </form>

            <div class="mt-3 text-center">
                <small>Don't have an account? <a href="register.php">Sign up</a></small>
                <br>
                <small><a href="forgot-password.php">Forgot password?</a></small>
            </div>
        </div>

        <div class="geometric-bg"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
