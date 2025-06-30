<?php
session_start();
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if (empty($email)) {
        $_SESSION['error'] = "Please enter your email address";
    } else {
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + 3600);
            
            
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
            if ($stmt->execute([$token, $expiry, $email])) {

                $resetLink = "http://yourdomain.com/reset_password_form.php?token=$token";
                $_SESSION['success'] = "Password reset link has been sent to your email";
            } else {
                $_SESSION['error'] = "Failed to generate reset token";
            }
        } else {
            $_SESSION['error'] = "Email not found in our system";
        }
    }
    header("Location: forgot_password.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password | Tasala</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main class="login-main">
        <div class="login-wrapper">
            <div class="login-container">
                <h2>Forgot Password</h2>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($_SESSION['error']); ?>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($_SESSION['success']); ?>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="input-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <button type="submit" class="login-button">Reset Password</button>
                </form>
                
                <p class="signup-link">
                    Remember your password? <a href="login.php">Log in</a>
                </p>
            </div>
        </div>
    </main>
    
    <?php include 'footer.php'; ?>
</body>
</html>