<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $roomType = $_POST['room_type'];
    $duration = (int)$_POST['duration'];
    
    // Calculate amount based on room type
    $rate = ($roomType === 'premium') ? 150 : 20;
    $amount = $rate * $duration;
    
    // Set time in to now and calculate time out
    $timeIn = date('Y-m-d H:i:s');
    $timeOut = date('Y-m-d H:i:s', strtotime("+$duration hours"));
    
    // Insert booking
    $stmt = $conn->prepare("INSERT INTO bookings 
                          (user_id, room_type, time_in, time_out, duration_hours, amount_paid, status) 
                          VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("isssds", $userId, $roomType, $timeIn, $timeOut, $duration, $amount);
    
    if ($stmt->execute()) {
        $_SESSION['booking_success'] = "Booking request submitted! Waiting for confirmation.";
    } else {
        $_SESSION['booking_error'] = "Error creating booking: " . $conn->error;
    }
    
    header("Location: user_dashboard.php");
    exit();
}

header("Location: user_dashboard.php");
exit();
?>