<?php
session_start();

require_once 'db_connect.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get current user's role
$stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$_SESSION['user_role'] = $user['role_id'];

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    // Update bookings for this user
    $conn->query("UPDATE bookings SET status = 'activated' WHERE status = 'confirmed' AND NOW() >= time_in AND NOW() < time_out AND user_id = $userId");
    $conn->query("UPDATE bookings SET status = 'completed' WHERE status = 'activated' AND NOW() >= time_out AND user_id = $userId");
    $conn->query("UPDATE bookings SET status = 'cancelled' WHERE status = 'pending' AND NOW() > time_in AND user_id = $userId");
}
?>
