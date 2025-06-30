<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Log received POST data
file_put_contents('debug.log', print_r($_POST, true), FILE_APPEND);

$required = ['username', 'first_name', 'last_name', 'email', 'role_id', 'password'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $error = "Missing field: $field";
        file_put_contents('debug.log', $error.PHP_EOL, FILE_APPEND);
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
}

$username   = $_POST['username'];
$first_name = $_POST['first_name'];
$last_name  = $_POST['last_name'];
$email      = $_POST['email'];
$role_id    = (int)$_POST['role_id'];
$password   = password_hash($_POST['password'], PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, first_name, last_name, email, role_id, password, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
if (!$stmt) {
    $error = "Prepare failed: " . $conn->error;
    file_put_contents('debug.log', $error.PHP_EOL, FILE_APPEND);
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
}

$stmt->bind_param("ssssss", $username, $first_name, $last_name, $email, $role_id, $password);

if ($stmt->execute()) {
    file_put_contents('debug.log', "User added successfully".PHP_EOL, FILE_APPEND);
    echo json_encode(['success' => true]);
} else {
    $error = "Execute failed: " . $stmt->error;
    file_put_contents('debug.log', $error.PHP_EOL, FILE_APPEND);
    echo json_encode(['success' => false, 'error' => $error]);
}
$stmt->close();