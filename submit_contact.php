<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['NAME']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    if (empty($name) || empty($email) || empty($message)) {
        echo "All fields are required!";
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format!";
        exit;
    }

  
    $stmt = $conn->prepare("INSERT INTO contacts (NAME, email, message) VALUES (?, ?, ?)");
    if (!$stmt) {
        echo "Prepare failed: " . $conn->error;
        exit;
    }

    $stmt->bind_param("sss", $name, $email, $message);

    if ($stmt->execute()) {
        echo "success"; 
    } else {
        echo "Database error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
