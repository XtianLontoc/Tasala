<?php
session_start();
require 'db_connect.php';

$error = '';
$username = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords don't match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "Email already registered";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $default_role_id = 2; // User role

                $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
                $insert_stmt->bind_param("sssi", $username, $email, $hashed_password, $default_role_id);
                
                if ($insert_stmt->execute()) {
                    $_SESSION['registration_success'] = true;
                    header("Location: registration_success.php");
                    exit();
                } else {
                    $error = "Registration failed. Please try again.";
                }
                $insert_stmt->close();
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Tasala</title>
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
        
        .registration-container {
            background: white;
            width: 100%;
            max-width: 500px;
            padding: 2.5rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .logo {
            width: 80px;
            margin-bottom: 1.5rem;
        }
        
        .registration-container h2 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .registration-subtitle {
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
        
        .password-strength {
            height: 5px;
            background: #eee;
            border-radius: 5px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .strength-meter {
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
        
        .register-button {
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
        
        .register-button:hover {
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

    <div class="registration-container">
        <img src="./images/Logo.png" alt="Tasala Logo" class="logo">
        <h2>Create Your Account</h2>
        <p class="registration-subtitle">Join the Tasala community</p>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" id="registrationForm">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}">
                <div class="password-strength">
                    <div class="strength-meter" id="strengthMeter"></div>
                </div>
                <small>Must contain at least 8 characters, one uppercase, one lowercase, and one number</small>
            </div>
            
            <div class="input-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="register-button">Register</button>
            
            <p class="login-link">Already have an account? <a href="login.php">Log in</a></p>
        </form>
    </div>

    <script>
        // Password strength meter
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const meter = document.getElementById('strengthMeter');
            let strength = 0;
            
            if (password.length > 7) strength += 1;
            if (/\d/.test(password)) strength += 1;
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
            
            const colors = ['#ff0000', '#ff5a00', '#ffb400', '#a0ff00', '#00ff00'];
            const width = (strength / 5) * 100;
            meter.style.width = width + '%';
            meter.style.backgroundColor = colors[strength - 1] || '#eee';
        });
        
        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>