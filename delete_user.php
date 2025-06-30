<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "User ID not provided.";
    exit();
}

$userId = intval($_GET['id']);

// Delete user
$deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$deleteStmt->bind_param("i", $userId);
$deleteStmt->execute();

header("Location: manage_users.php");
exit();
?>
