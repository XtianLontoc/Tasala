<?php
session_start();

// Database connection (same as above)
$host = 'localhost';
$dbname = 'tasala'; 
$username = 'root';     // ← Default XAMPP/WAMP username
$password = '';         // ← Default password (empty)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error'] = "Database connection failed: " . $e->getMessage();
    header("Location: reset_password_form.php");
    exit();
}

// Check if token is provided
if (!isset($_GET['token'])) {
    $_SESSION['error'] = "Invalid password reset link";
    header("Location: reset_password_form.php");
    exit();
}

$token = $_GET['token'];

// Verify token
try {
    $stmt = $pdo->prepare("SELECT id, email, reset_token_expires FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error'] = "Invalid password reset link";
        header("Location: reset_password_form.php");
        exit();
    }

    // Check if token is expired
    if (strtotime($user['reset_token_expires']) < time()) {
        $_SESSION['error'] = "Password reset link has expired";
        header("Location: reset_password_form.php");
        exit();
    }

    // Process form submission if POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newPassword = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];

        // Validate passwords
        if (empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['error'] = "Please fill in all fields";
            header("Location: reset_password_confirm.php?token=$token");
            exit();
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = "Passwords do not match";
            header("Location: reset_password_confirm.php?token=$token");
            exit();
        }

        if (strlen($newPassword) < 8) {
            $_SESSION['error'] = "Password must be at least 8 characters";
            header("Location: reset_password_confirm.php?token=$token");
            exit();
        }

        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password and clear reset token
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $stmt->execute([$hashedPassword, $user['id']]);

        $_SESSION['success'] = "Your password has been updated successfully";
        header("Location: login.php");
        exit();
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "An error occurred: " . $e->getMessage();
    header("Location: reset_password_form.php");
    exit();
}

// Display the password reset form if GET request
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Tasala</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #2ecc71;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .reset-container {
            background: white;
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .logo {
            width: 80px;
            margin-bottom: 1.5rem;
        }
        
        .reset-container h2 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .reset-subtitle {
            color: #7f8c8d;
            margin-bottom: 2rem;
        }
        
        .input-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
            font-weight: 500;
        }
        
        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .input-group input:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .reset-button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .reset-button:hover {
            background-color: #1a252f;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #7f8c8d;
        }
        
        .login-link a {
            color: var(--secondary-color);
            text-decoration: none;
        }
        
        .error-message {
            color: var(--accent-color);
            background: #fde8e8;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            color: var(--success-color);
            background: #e8f8f0;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            border: 1px solid #c3e6cb;
        }
        
        .back-to-home {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 100;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            background: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>

<div class="back-to-home">
    <a href="index.html" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>
</div>

    <div class="reset-container">
        <img src="./images/Logo.png" alt="Tasala Logo" class="logo">
        <h2>Set a New Password</h2>
        <p class="reset-subtitle">Create a new password for your account</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form class="reset-form" method="POST">
            <div class="input-group">
                <label for="password">New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                </div>
            </div>
            
            <div class="input-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                </div>
            </div>
            
            <button type="submit" class="reset-button">Reset Password</button>
            
            <p class="login-link">Remember your password? <a href="login.php">Log in</a></p>
        </form>
    </div>

    <script>
        function togglePassword(id) {
            const passwordInput = document.getElementById(id);
            const toggleIcon = passwordInput.nextElementSibling;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>