<?php
// File: update_booking_status.php
require_once 'auth_check.php';
require_once 'db_connect.php';
require_once 'csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'], $_POST['csrf_token'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        header("Location: admins_booking.php?error=Invalid+CSRF+token");
        exit();
    }

    $bookingId = (int)$_POST['id'];
    $status = $_POST['status'];
    $allowed = ['pending', 'confirmed', 'cancelled', 'completed'];

    if (!in_array($status, $allowed)) {
        header("Location: admins_booking.php?error=Invalid+status");
        exit();
    }

    $stmt = $conn->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
    if (!$stmt) {
        header("Location: admins_booking.php?error=Database+prepare+failed");
        exit();
    }
    $stmt->bind_param("si", $status, $bookingId);

    if ($stmt->execute()) {
        header("Location: admins_booking.php?success=Status+updated");
    } else {
        header("Location: admins_booking.php?error=Update+failed");
    }
    $stmt->close();
    exit();
}

header("Location: admins_booking.php?error=Invalid+request");
exit();
?>
