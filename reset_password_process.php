<?php
session_start();


$host = 'localhost';
$dbname = 'tasala'; 
$username = 'root';     
$password = '';        

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error'] = "Database connection failed: " . $e->getMessage();
    header("Location: reset_password_form.php");
    exit();
}

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Validate email
    if (empty($email)) {
        $_SESSION['error'] = "Please enter your email address";
        header("Location: reset_password_form.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please enter a valid email address";
        header("Location: reset_password_form.php");
        exit();
    }

    // Check if email exists in database
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['error'] = "No account found with that email address";
            header("Location: reset_password_form.php");
            exit();
        }

        // Generate reset token (32 characters)
        $token = bin2hex(random_bytes(16));
        $expires = date("Y-m-d H:i:s", time() + 3600); // 1 hour expiration

        // Store token in database
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
        $stmt->execute([$token, $expires, $email]);

        // Send email with reset link (in a real app, you would actually send an email)
        $resetLink = "http://yourdomain.com/reset_password_confirm.php?token=$token";

        $to = $email;
    $subject = "Password Reset Request";
    $headers = "From: yourname@gmail.com\r\n";
    $headers .= "Reply-To: yourname@gmail.com\r\n";
    $message = "Click here to reset your password: $resetLink";
        
    if (mail($to, $subject, $message, $headers)) {
    $_SESSION['status'] = "Password reset link has been sent to your email.";
} else {
    $_SESSION['status'] = "Failed to send email. Contact support.";
}
        // In a production environment, you would use a mailer library like PHPMailer
        // For this example, we'll just store the link in session to display it
        $_SESSION['email_sent'] = $resetLink;

        $_SESSION['success'] = "Password reset link has been sent to your email";
        header("Location: reset_password_form.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
        header("Location: reset_password_form.php");
        exit();
    }
} else {
    // If someone tries to access this page directly
    header("Location: reset_password_form.php");
    exit();
}