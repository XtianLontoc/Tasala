<?php
session_start();
if (isset($_SESSION['user_id']) || isset($_SESSION['admin_logged_in'])) {
    header("Location: " . (isset($_SESSION['admin_logged_in']) ? 'admin-dashboard.php' : 'user-dashboard.php'));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Tasala</title>
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
        
        .login-container {
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
        
        .login-container h2 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
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
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        
        .forgot-password {
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 14px;
        }
        
        .login-button {
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
        
        .login-button:hover {
            background-color: #1a252f;
        }
        
        .signup-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #7f8c8d;
        }
        
        .signup-link a {
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
    <div class="login-container">
        <img src="./images/Logo.png" alt="Tasala Logo" class="logo">
        <h2>Welcome Back</h2>
        <p class="login-subtitle">Sign in to your Tasala account</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form class="login-form" action="login_process.php" method="POST">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="email" name="username" required>
            </div>
            
            <div class="input-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" required>
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                </div>
            </div>
            
            <div class="remember-forgot">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    Remember me
                </label>
                <a href="reset_password_form.php" class="forgot-password">Forgot password?</a>
            </div>
            
            <button type="submit" class="login-button">Log In</button>
            
            <p class="signup-link">Don't have an account? <a href="register.php">Sign up</a></p>
        </form>
    </div>

    <script>
        // Password visibility toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>