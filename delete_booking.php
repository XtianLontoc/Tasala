<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

// Check if booking ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No booking ID provided for deletion";
    header("Location: " . ($_SESSION['user_role'] == 1 ? "admins_booking.php" : "user_dashboard.php"));
    exit();
}

$bookingId = intval($_GET['id']); // Always sanitize

// Check if booking exists and belongs to the user (or admin is accessing)
$checkQuery = "SELECT b.id, b.user_id 
               FROM bookings b
               WHERE b.id = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Booking not found or already deleted.";
    header("Location: " . ($_SESSION['user_role'] == 1 ? "admins_booking.php" : "user_dashboard.php"));
    exit();
}

$bookingData = $result->fetch_assoc();

// Check if user is allowed to delete this booking
$isAdmin = ($_SESSION['user_role'] == 1);
$isOwner = ($bookingData['user_id'] == $_SESSION['user_id']);

if (!$isAdmin && !$isOwner) {
    $_SESSION['error'] = "You don't have permission to delete this booking.";
    header("Location: user_dashboard.php");
    exit();
}

// Proceed to delete the booking
try {
    $deleteQuery = "DELETE FROM bookings WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $bookingId);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Booking successfully deleted.";

        // Log the admin action if applicable
        if ($isAdmin) {
            $logQuery = "INSERT INTO admin_actions (admin_id, action_type, description) 
                         VALUES (?, 'delete', ?)";
            $logStmt = $conn->prepare($logQuery);
            $actionDesc = "Deleted booking ID: " . $bookingId;
            $logStmt->bind_param("is", $_SESSION['user_id'], $actionDesc);
            $logStmt->execute();
        }
    } else {
        $_SESSION['error'] = "Error deleting booking: " . $stmt->error;
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

// Redirect based on role
header("Location: " . ($isAdmin ? "admins_booking.php" : "user_dashboard.php"));
exit();
?>
