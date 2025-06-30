<?php
require_once 'auth_check.php';
require_once 'db_connect.php';
require_once 'csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'], $_POST['booking_id'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['booking_error'] = "Invalid request.";
        header("Location: user_booking.php");
        exit();
    }
    $userId = $_SESSION['user_id'];
    $bookingId = (int)$_POST['booking_id'];

    // Only allow cancelling own upcoming bookings (pending or confirmed)
    $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')");
    $stmt->bind_param("ii", $bookingId, $userId);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['booking_success'] = "Booking cancelled successfully.";
    } else {
        $_SESSION['booking_error'] = "Unable to cancel booking.";
    }
    header("Location: user_booking.php");
    exit();
}
header("Location: user_booking.php");
exit();
?>