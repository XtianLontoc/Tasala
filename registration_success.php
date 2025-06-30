<?php
// Check if user came from registration form
if (!isset($_SERVER['HTTP_REFERER']) || !strpos($_SERVER['HTTP_REFERER'], 'register.php')) {
    header("Location: register.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful | Tasala</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Success Page Specific Styles */
        .success-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .success-icon {
            font-size: 4rem;
            color: #2ecc71;
            margin-bottom: 1.5rem;
        }
        
        .success-title {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .success-message {
            color: #7f8c8d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .success-button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 12px 30px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .success-button:hover {
            background-color: #2980b9;
        }
        
        /* Main Container */
        .login-main {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 120px); /* Adjust based on header/footer height */
            padding: 2rem;
            background-color: #f5f7fa;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main class="login-main">
        <div class="success-container">
            <i class="fas fa-check-circle success-icon"></i>
            <h1 class="success-title">Registration Successful!</h1>
            <div class="success-message">
                <p>Your Tasala account has been created successfully.</p>
                <p>You can now log in and start enjoying our services.</p>
            </div>
            <a href="login.php" class="success-button">
                Continue to Login <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </main>
    
    <?php include 'footer.php'; ?>
</body>
</html>